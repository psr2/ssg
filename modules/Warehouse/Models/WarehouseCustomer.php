<?php

namespace Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Locations\Models\LocationModel as Location;

class WarehouseCustomer extends Model
{
    protected $table = 'warehouse_customers';

    protected $fillable = [
        'warehouse_id',
        'name',
        'phone',
        'address',
        'location',
        'credit_balance',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Location::class, 'warehouse_id');
    }

    public function sales()
    {
        return $this->hasMany(WarehouseSale::class, 'customer_id');
    }
}
