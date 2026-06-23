<?php

namespace Modules\ShopManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Locations\Models\LocationModel as Location;


class ShopCustomer extends Model
{
    protected $table = 'shop_customers';

    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'address',
        'credit_balance',
        'outstanding_balance',
    ];

    public function shop()
    {
        return $this->belongsTo(Location::class, 'shop_id');
    }

    public function sales()
    {
        return $this->hasMany(ShopSale::class, 'customer_id');
    }
}
