<?php

namespace Modules\Warehouse\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseBatchCodeRepository
{
    /**
     * Search and return batch codes for warehouses.
     */
    public function search(array $filters = [])
    {
        Log::debug('WarehouseBatchCodeRepository::search', $filters);

        // ── 1. Parent batches in warehouse_inventory ──────────────────────
        $whQuery = DB::table('warehouse_inventory as wi')
            ->join('products',  'wi.product_id',  '=', 'products.id')
            ->join('locations', 'wi.warehouse_id', '=', 'locations.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->where('locations.type', 'warehouse')
            ->select(
                'wi.batch        as batch_code',
                'wi.product_id',
                'wi.warehouse_id as location_id',
                'wi.grade',
                'locations.name  as location',
                'products.name   as product',
                'units.abbreviation as unit'
            );

        // ── 2. Batches/grades that arrived via transfer to warehouses ───────
        $transQuery = DB::table('stock_transfers as st')
            ->join('stock_transfer_items as sti', 'st.id',             '=', 'sti.stock_transfer_id')
            ->join('products',                     'sti.product_id',    '=', 'products.id')
            ->join('locations',                    'st.to_location_id', '=', 'locations.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->where('locations.type', 'warehouse')
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
        foreach ([$whQuery, $transQuery] as $q) {
            if (!empty($filters['product_listing'])) {
                $q->where('products.id', $filters['product_listing']);
            }
            if (!empty($filters['location'])) {
                $q->where('locations.id', $filters['location']);
            }
        }

        // ── Merge & deduplicate on composite key ──────────────────────────
        $allRows = [];
        foreach ([$whQuery->get(), $transQuery->get()] as $rows) {
            foreach ($rows as $row) {
                if (!$row->batch_code || !$row->product_id || !$row->location_id) {
                    continue;
                }

                $grade = $row->grade ?? '';
                $key = "{$row->location_id}_{$row->product_id}_{$row->batch_code}_{$grade}";
                $allRows[$key] = $row;
            }
        }

        // ── Compute real-time available qty ────────────────────────────────
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
}
