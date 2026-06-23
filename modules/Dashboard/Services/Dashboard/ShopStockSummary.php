<?php

namespace Modules\Dashboard\Services\Dashboard;

use Modules\ShopManagement\Models\ShopInventory;

class ShopStockSummary
{
    /**
     * Get total stock in all shops
     *
     * @return float
     */
    public function stockInShop()
    {
        return ShopInventory::sum('qty') ?? 0;
    }
}

