<?php

namespace Modules\Dashboard\Services\Dashboard;

use Illuminate\Support\Facades\DB;
use Modules\StockManagement\Services\StockSegregation\StockSegregationService;

class StockLedgerSummary
{
    protected StockSegregationService $segregationService;

    public function __construct(StockSegregationService $segregationService)
    {
        $this->segregationService = $segregationService;
    }

    /**
     * Calculate dynamically computed ledger stocks for all locations.
     *
     * @param \Illuminate\Support\Collection $warehouses
     * @param \Illuminate\Support\Collection $shops
     * @param \Illuminate\Support\Collection $locations
     * @return array
     */
    public function calculateLedgerStocks($warehouses, $shops, $locations): array
    {
        // 1. Get unique combinations from all database sources of stock
        $whCombos = DB::table('warehouse_inventory')
            ->select('warehouse_id as location_id', 'product_id', 'batch as batch_code', 'grade')
            ->distinct()->get();

        $shCombos = DB::table('shop_inventory')
            ->select('shop_id as location_id', 'product_id', 'batch_id as batch_code', 'grade')
            ->distinct()->get();

        $segCombos = DB::table('stock_segregations')
            ->join('stock_segregation_items', 'stock_segregations.id', '=', 'stock_segregation_items.stock_segregation_id')
            ->select('stock_segregations.location_id', 'stock_segregations.product_id', 'stock_segregations.parent_batch_code as batch_code', 'stock_segregation_items.grade')
            ->distinct()->get();

        $transSourceCombos = DB::table('stock_transfers')
            ->join('stock_transfer_items', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->select('stock_transfers.from_location_id as location_id', 'stock_transfer_items.product_id', 'stock_transfer_items.batch_code', 'stock_transfer_items.grade')
            ->distinct()->get();

        $transDestCombos = DB::table('stock_transfers')
            ->join('stock_transfer_items', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->select('stock_transfers.to_location_id as location_id', 'stock_transfer_items.product_id', 'stock_transfer_items.batch_code', 'stock_transfer_items.grade')
            ->distinct()->get();

        $allCombos = [];
        foreach ([$whCombos, $shCombos, $segCombos, $transSourceCombos, $transDestCombos] as $combos) {
            foreach ($combos as $c) {
                if (!$c->location_id || !$c->product_id || !$c->batch_code || !$c->grade) {
                    continue;
                }
                $key = "{$c->location_id}_{$c->product_id}_{$c->batch_code}_{$c->grade}";
                $allCombos[$key] = [
                    'location_id' => (int)$c->location_id,
                    'product_id' => (int)$c->product_id,
                    'batch_code' => $c->batch_code,
                    'grade' => $c->grade,
                ];
            }
        }

        $warehouseTotal = 0.00;
        $shopTotal = 0.00;

        $warehouseStocks = [];
        foreach ($warehouses as $wh) {
            $warehouseStocks[$wh->id] = 0.00;
        }

        $shopStocks = [];
        foreach ($shops as $sh) {
            $shopStocks[$sh->id] = 0.00;
        }

        $warehouseStockMap = [];
        $shopStockMap = [];

        // Summed stock per product per location (for stock alert calculation)
        $locProductStock = [];
        // Unique pairs of product_id and location_id
        $pairs = [];

        foreach ($allCombos as $combo) {
            $lId = $combo['location_id'];
            $pId = $combo['product_id'];

            $loc = $locations[$lId] ?? null;
            if (!$loc) {
                continue;
            }

            $available = $this->segregationService->getAvailableStock($lId, $pId, $combo['batch_code'], $combo['grade']);

            // Setup key for stock alerts
            $pairs["{$pId}_{$lId}"] = [
                'product_id' => $pId,
                'location_id' => $lId,
            ];

            if (!isset($locProductStock[$lId][$pId])) {
                $locProductStock[$lId][$pId] = 0.00;
            }
            $locProductStock[$lId][$pId] += $available;

            if ($loc->type === 'shop') {
                $shopTotal += $available;
                $shopStocks[$lId] = ($shopStocks[$lId] ?? 0.00) + $available;

                if (!isset($shopStockMap[$lId][$pId])) {
                    $shopStockMap[$lId][$pId] = 0.00;
                }
                $shopStockMap[$lId][$pId] += $available;
            } else {
                $warehouseTotal += $available;
                $warehouseStocks[$lId] = ($warehouseStocks[$lId] ?? 0.00) + $available;

                if (!isset($warehouseStockMap[$lId][$pId])) {
                    $warehouseStockMap[$lId][$pId] = 0.00;
                }
                $warehouseStockMap[$lId][$pId] += $available;
            }
        }

        return [
            'warehouseTotal' => $warehouseTotal,
            'shopTotal' => $shopTotal,
            'warehouseStocks' => $warehouseStocks,
            'shopStocks' => $shopStocks,
            'warehouseStockMap' => $warehouseStockMap,
            'shopStockMap' => $shopStockMap,
            'locProductStock' => $locProductStock,
            'pairs' => $pairs,
        ];
    }
}
