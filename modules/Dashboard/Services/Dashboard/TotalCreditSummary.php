<?php

namespace Modules\Dashboard\Services\Dashboard;

use Modules\Warehouse\Models\WarehouseSale;
use Modules\ShopManagement\Models\ShopSale;

class TotalCreditSummary
{
    /**
     * Calculate dues/receivables for warehouses and shops.
     *
     * @param \Illuminate\Support\Collection $warehouses
     * @param \Illuminate\Support\Collection $shops
     * @return array
     */
    public function getReceivables($warehouses, $shops): array
    {
        $warehouseDues = [];
        foreach ($warehouses as $wh) {
            $warehouseDues[$wh->id] = WarehouseSale::where('warehouse_id', $wh->id)->sum('due_amount') ?? 0;
        }

        $shopDues = [];
        foreach ($shops as $sh) {
            $shopDues[$sh->id] = ShopSale::join('shop_customers', 'shop_sales.customer_id', '=', 'shop_customers.id')
                ->where('shop_customers.shop_id', $sh->id)
                ->sum('shop_sales.due_amount') ?? 0;
        }

        $totalReceivables = array_sum($warehouseDues) + array_sum($shopDues);

        return [
            'warehouseDues' => $warehouseDues,
            'shopDues' => $shopDues,
            'totalReceivables' => $totalReceivables,
        ];
    }
}
