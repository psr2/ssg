<?php

namespace Modules\ShopManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Locations\Models\LocationModel as Location;
use Modules\Inventory\Models\Products;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Modules\StockManagement\Models\StockTransfer\StockTransfer;

class ShopInventory extends Model
{
    protected $table = 'shop_inventory';

    protected $fillable = [
        'shop_id',
        'stock_transfer_id',
        'grade',
        'batch_id',
        'product_id',
        'qty',
        'unit_cost',
    ];

    public function shop()
    {
        return $this->belongsTo(Location::class, 'shop_id');
    }

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function batch()
    {
        return $this->belongsTo(StockPurchaseItem::class, 'batch_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class);
    }
}
