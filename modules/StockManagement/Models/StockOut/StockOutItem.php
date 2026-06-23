<?php

namespace Modules\StockManagement\Models\StockOut;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\Products as Product;
use Modules\Inventory\Models\UnitOfMeasurement as Unit;
use Modules\Locations\Models\LocationModel as Location;




class StockOutItem extends Model
{
    use HasFactory;

    protected $table = 'stock_out_items';

    protected $fillable = [
        'stock_out_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'location_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Each item belongs to a master stock out
    public function master()
    {
        return $this->belongsTo(MasterStockOut::class, 'stock_out_id');
    }

    // Item is linked to a product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Item is linked to a unit
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    // Item is linked to a location (from which it was taken)
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
