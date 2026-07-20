<?php

namespace Modules\Reporting\Repositories;

use Illuminate\Support\Facades\DB;

class ReportingRepository
{
    /**
     * Get inventory summary metrics across locations.
     */
    public function getInventorySummary(): array
    {
        $warehouseStock = DB::table('warehouse_inventory')
            ->select('product_id', 'grade', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('product_id', 'grade')
            ->get();

        $shopStock = DB::table('shop_inventory')
            ->select('product_id', 'grade', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('product_id', 'grade')
            ->get();

        return [
            'warehouse_stock' => $warehouseStock,
            'shop_stock' => $shopStock,
        ];
    }
}
