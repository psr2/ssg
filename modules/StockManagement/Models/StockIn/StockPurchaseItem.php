<?php

namespace Modules\StockManagement\Models\StockIn;
use Modules\Locations\Models\LocationModel as Location;

use Illuminate\Database\Eloquent\Model;

class StockPurchaseItem extends Model
{
    protected $table = 'stock_purchase_items';

    protected $fillable = [
        'stock_in_purchase_id',
        'location_id',
        'product',
        'batch',
        'grade',
        'quantity',
        'unit',
        'unit_cost',
        'total',
        'remarks',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
