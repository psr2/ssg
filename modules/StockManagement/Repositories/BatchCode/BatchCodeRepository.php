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
     *   - stock_segregation_items  (child grades created during segregation)
     *   - stock_transfer_items     (grades moved to destination locations)
     *
     * Each row is augmented with a real-time `available_qty` from StockSegregationService.
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
            ->select(
                'wi.batch        as batch_code',
                'wi.product_id',
                'wi.warehouse_id as location_id',
                'wi.grade',
                'locations.name  as location',
                'products.name   as product'
            );

        // ── 2. Parent batches in shop_inventory ───────────────────────────
        $shQuery = DB::table('shop_inventory as si')
            ->join('products',  'si.product_id', '=', 'products.id')
            ->join('locations', 'si.shop_id',    '=', 'locations.id')
            ->select(
                'si.batch_id     as batch_code',
                'si.product_id',
                'si.shop_id      as location_id',
                'si.grade',
                'locations.name  as location',
                'products.name   as product'
            );

        // ── 3. Segregated child grades ────────────────────────────────────
        $segQuery = DB::table('stock_segregations as ss')
            ->join('stock_segregation_items as ssi', 'ss.id',          '=', 'ssi.stock_segregation_id')
            ->join('products',                        'ss.product_id',  '=', 'products.id')
            ->join('locations',                       'ss.location_id', '=', 'locations.id')
            ->select(
                'ss.parent_batch_code as batch_code',
                'ss.product_id',
                'ss.location_id',
                'ssi.grade',
                'locations.name       as location',
                'products.name        as product'
            );

        // ── 4. Batches/grades that arrived via transfer ───────────────────
        $transQuery = DB::table('stock_transfers as st')
            ->join('stock_transfer_items as sti', 'st.id',             '=', 'sti.stock_transfer_id')
            ->join('products',                     'sti.product_id',    '=', 'products.id')
            ->join('locations',                    'st.to_location_id', '=', 'locations.id')
            ->select(
                'sti.batch_code',
                'sti.product_id',
                'st.to_location_id  as location_id',
                'sti.grade',
                'locations.name     as location',
                'products.name      as product'
            );

        // ── Apply optional filters ────────────────────────────────────────
        foreach ([$whQuery, $shQuery, $segQuery, $transQuery] as $q) {
            if (!empty($filters['product_listing'])) {
                $q->where('products.id', $filters['product_listing']);
            }
            if (!empty($filters['location'])) {
                $q->where('locations.id', $filters['location']);
            }
        }

        // ── Merge & deduplicate on composite key ──────────────────────────
        $allRows = [];
        foreach ([$whQuery->get(), $shQuery->get(), $segQuery->get(), $transQuery->get()] as $rows) {
            foreach ($rows as $row) {
                if (!$row->batch_code || !$row->product_id || !$row->location_id || !$row->grade) {
                    continue;
                }

                // If unsegregated_only is requested, filter out rows that represent segregated child grades
                if (!empty($filters['unsegregated_only'])) {
                    // Check if this row's grade matches the parent batch's grade in warehouse or shop inventory
                    $parentGrade = DB::table('warehouse_inventory')
                        ->where('warehouse_id', $row->location_id)
                        ->where('product_id', $row->product_id)
                        ->where('batch', $row->batch_code)
                        ->value('grade');

                    if (!$parentGrade) {
                        $parentGrade = DB::table('shop_inventory')
                            ->where('shop_id', $row->location_id)
                            ->where('product_id', $row->product_id)
                            ->where('batch_id', $row->batch_code)
                            ->value('grade');
                    }

                    $isParent = false;
                    if ($parentGrade) {
                        $isParent = ($row->grade === $parentGrade);
                    } else {
                        // Fallback: check standard unsegregated grade names
                        $isParent = in_array(strtoupper($row->grade), ['N/A', 'UNSORTED']);
                    }

                    if (!$isParent) {
                        continue;
                    }
                }

                $key = "{$row->location_id}_{$row->product_id}_{$row->batch_code}_{$row->grade}";
                $allRows[$key] = $row;
            }
        }

        // ── Compute real-time available qty via the ledger service ─────────
        $service = app(\Modules\StockManagement\Services\StockSegregation\StockSegregationService::class);

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
        $filters['unsegregated_only'] = true;
        return $this->search($filters);
    }

    /**
     * Search all physical stock (parent + child grades) at a location.
     */
    public function searchPhysicalStock(array $filters = [])
    {
        unset($filters['unsegregated_only']);
        return $this->search($filters);
    }
}
