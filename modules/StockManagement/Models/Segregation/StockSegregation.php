<?php

namespace Modules\StockManagement\Models\Segregation;

use Illuminate\Database\Eloquent\Model;
use Modules\Locations\Models\LocationModel as Location;
use Modules\Inventory\Models\Products as Product;

class StockSegregation extends Model
{
    protected $table = 'stock_segregations';

    protected $fillable = [
        'reference_no',
        'location_id',
        'product_id',
        'parent_batch_code',
        'parent_quantity',
        'remarks',
        'created_by',
        'segregation_date',
    ];

    protected $casts = [
        'parent_quantity' => 'decimal:2',
        'segregation_date' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function items()
    {
        return $this->hasMany(StockSegregationItem::class, 'stock_segregation_id');
    }
}
