<?php

namespace Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Locations\Models\LocationModel as Location;

class WarehouseSale extends Model
{
    protected $table = 'warehouse_sales';

    protected $fillable = [
        'customer_id',
        'warehouse_id',
        'sale_date',
        'total_amount',
        'paid_amount',
        'due_amount',
    ];

    protected $casts = [
        'sale_date'    => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
        'due_amount'   => 'decimal:2',
    ];

    protected $with = [
        'customer',
        'warehouse',
        'items.product',
        'items.gradeRelation',
        'items.unitRelation',
    ];

    public function customer()
    {
        return $this->belongsTo(WarehouseCustomer::class, 'customer_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Location::class, 'warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(WarehouseSaleItem::class, 'sale_id');
    }

    public function payments()
    {
        return $this->hasMany(WarehousePayment::class, 'sale_id');
    }
}
