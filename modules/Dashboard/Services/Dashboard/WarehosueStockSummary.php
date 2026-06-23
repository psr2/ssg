<?php

namespace Modules\Dashboard\Services\Dashboard;

use Modules\StockManagement\Models\Warehouse\WarehouseInventory;

class WarehosueStockSummary
{
    /**
     * Get total stock quantity in all warehouses
     *
     * @return int|float
     */
    public function stockInWarehouse()
    {
        return WarehouseInventory::sum('qty') ?? 0;
    }
}

