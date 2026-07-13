<?php

namespace Modules\StockManagement\Repositories\BatchCode;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchCodeRepository
{
    /**
     * Search and return batch codes based on filters.
     *
     * Returns a deduplicated set of (location, product, batch, grade) combinations
     * sourced from all ledger tables:
     *   - warehouse_inventory      (parent/base batches at warehouses)
     *   - shop_inventory           (batches at shops)
     *   - stock_transfer_items     (grades moved to destination locations)
     *
     * Each row is augmented with a real-time `available_qty` from StockLedgerService.
     * Rows with zero or negative availability are filtered out.
     *
     * @param array $filters {
     *     @type int|null $product_listing  Product ID
     *     @type int|null $location         Location ID
     * }
     *
     * @return \Illuminate\Support\Collection
     */
    public function search(array $filters = [])
    {
        Log::debug('BatchCodeRepository::search', $filters);

        // ── 1. Parent batches in warehouse_inventory ──────────────────────
        $whQuery = DB::table('warehouse_inventory as wi')
            ->join('products',  'wi.product_id',  '=', 'products.id')
            ->join('locations', 'wi.warehouse_id', '=', 'locations.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->select(
                'wi.batch        as batch_code',
                'wi.product_id',
                'wi.warehouse_id as location_id',
                'wi.grade',
                'locations.name  as location',
                'products.name   as product',
                'units.abbreviation as unit'
            );

        // ── 2. Parent batches in shop_inventory ───────────────────────────
        $shQuery = DB::table('shop_inventory as si')
            ->join('products',  'si.product_id', '=', 'products.id')
            ->join('locations', 'si.shop_id',    '=', 'locations.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->select(
                'si.batch_id     as batch_code',
                'si.product_id',
                'si.shop_id      as location_id',
                'si.grade',
                'locations.name  as location',
                'products.name   as product',
                'units.abbreviation as unit'
            );

        // ── 3. Batches/grades that arrived via transfer ───────────────────
        $transQuery = DB::table('stock_transfers as st')
            ->join('stock_transfer_items as sti', 'st.id',             '=', 'sti.stock_transfer_id')
            ->join('products',                     'sti.product_id',    '=', 'products.id')
            ->join('locations',                    'st.to_location_id', '=', 'locations.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->select(
                'sti.batch_code',
                'sti.product_id',
                'st.to_location_id  as location_id',
                'sti.grade',
                'locations.name     as location',
                'products.name      as product',
                'units.abbreviation as unit'
            );

        // ── Apply optional filters ────────────────────────────────────────
        foreach ([$whQuery, $shQuery, $transQuery] as $q) {
            if (!empty($filters['product_listing'])) {
                $q->where('products.id', $filters['product_listing']);
            }
            if (!empty($filters['location'])) {
                $q->where('locations.id', $filters['location']);
            }
        }

        // ── Merge & deduplicate on composite key ──────────────────────────
        $allRows = [];
        foreach ([$whQuery->get(), $shQuery->get(), $transQuery->get()] as $rows) {
            foreach ($rows as $row) {
                if (!$row->batch_code || !$row->product_id || !$row->location_id || !$row->grade) {
                    continue;
                }

                $key = "{$row->location_id}_{$row->product_id}_{$row->batch_code}_{$row->grade}";
                $allRows[$key] = $row;
            }
        }

        // ── Compute real-time available qty via the ledger service ─────────
        $service = app(\Modules\StockLedger\Services\StockLedgerService::class);

        return collect(array_values($allRows))
            ->map(function ($item) use ($service) {
                $item->available_qty = $service->getAvailableStock(
                    (int) $item->location_id,
                    (int) $item->product_id,
                    $item->batch_code,
                    $item->grade
                );
                return $item;
            })
            ->filter(fn($item) => $item->available_qty > 0)
            ->values();
    }

    /**
     * Search ONLY unsegregated/parent batches.
     */
    public function searchUnsegregated(array $filters = [])
    {
        return $this->search($filters);
    }

    /**
     * Search all physical stock (parent + child grades) at a location.
     */
    public function searchPhysicalStock(array $filters = [])
    {
        return $this->search($filters);
    }
}
