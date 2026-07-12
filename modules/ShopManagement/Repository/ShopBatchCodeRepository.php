<?php

namespace Modules\ShopManagement\Repository;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopBatchCodeRepository
{
    /**
     * Search and return batch codes for shops.
     */
    public function search(array $filters = [])
    {
        Log::debug('ShopBatchCodeRepository::search', $filters);

        // ── 1. Parent batches in shop_inventory ───────────────────────────
        $shQuery = DB::table('shop_inventory as si')
            ->join('products',  'si.product_id', '=', 'products.id')
            ->join('locations', 'si.shop_id',    '=', 'locations.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->where('locations.type', 'shop')
            ->select(
                'si.batch_id     as batch_code',
                'si.product_id',
                'si.shop_id      as location_id',
                'si.grade',
                'locations.name  as location',
                'products.name   as product',
                'units.abbreviation as unit'
            );

        // ── 2. Segregated child grades at shops ────────────────────────────
        $segQuery = DB::table('stock_segregations as ss')
            ->join('stock_segregation_items as ssi', 'ss.id',          '=', 'ssi.stock_segregation_id')
            ->join('products',                        'ss.product_id',  '=', 'products.id')
            ->join('locations',                       'ss.location_id', '=', 'locations.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->where('locations.type', 'shop')
            ->select(
                'ss.parent_batch_code as batch_code',
                'ss.product_id',
                'ss.location_id',
                'ssi.grade',
                'locations.name       as location',
                'products.name        as product',
                'units.abbreviation as unit'
            );

        // ── 3. Batches/grades that arrived via transfer to shops ───────────
        $transQuery = DB::table('stock_transfers as st')
            ->join('stock_transfer_items as sti', 'st.id',             '=', 'sti.stock_transfer_id')
            ->join('products',                     'sti.product_id',    '=', 'products.id')
            ->join('locations',                    'st.to_location_id', '=', 'locations.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->where('locations.type', 'shop')
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
        foreach ([$shQuery, $segQuery, $transQuery] as $q) {
            if (!empty($filters['product_listing'])) {
                $q->where('products.id', $filters['product_listing']);
            }
            if (!empty($filters['location'])) {
                $q->where('locations.id', $filters['location']);
            }
        }

        // ── Merge & deduplicate on composite key ──────────────────────────
        $allRows = [];
        foreach ([$shQuery->get(), $segQuery->get(), $transQuery->get()] as $rows) {
            foreach ($rows as $row) {
                if (!$row->batch_code || !$row->product_id || !$row->location_id || !$row->grade) {
                    continue;
                }

                $key = "{$row->location_id}_{$row->product_id}_{$row->batch_code}_{$row->grade}";
                $allRows[$key] = $row;
            }
        }

        // ── Compute real-time available qty ────────────────────────────────
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
}
