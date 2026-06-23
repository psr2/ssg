<?php

namespace Modules\StockManagement\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Locations\Models\LocationModel as Location;
use Modules\Inventory\Models\Products;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;

class WarehouseInventory extends Model
{
    use HasFactory;

    // Table name (optional if naming follows Laravel convention)
    protected $table = 'warehouse_inventory';

    // Mass assignable attributes
    protected $fillable = [
        'warehouse_id',
        'batch',
        'grade',
        'product_id',
        'qty',
        'unit_cost',
    ];

    /**
     * Relationship: The warehouse where the inventory is stored.
     */
    public function warehouse()
    {
        return $this->belongsTo(Location::class, 'id');
    }

    /**
     * Relationship: The product/item stored in the warehouse.
     */
    public function product()
    {
        return $this->belongsTo(Products::class);
    }

    /**
     * Relationship: The stock purchase item this batch came from.
     */
    public function stockPurchaseItem()
    {
        return $this->belongsTo(StockPurchaseItem::class, 'batch', 'batch');
    }
}
