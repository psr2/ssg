<?php

namespace Modules\StockManagement\Models\StockOut;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Locations\Models\LocationModel as Location;
use Modules\StockManagement\Models\StockOut\StockOutItem;


class MasterStockOut extends Model
{
    use HasFactory;

    protected $table = 'master_stock_out';

    protected $fillable = [
        'location_id',
        'reference_no',
        'out_type',
        'out_date',
        'remarks',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // A Master Stock Out has many StockOutItems
    public function items()
    {
        return $this->hasMany(StockOutItem::class, 'stock_out_id');
    }

    // Stock out happens from one location
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
