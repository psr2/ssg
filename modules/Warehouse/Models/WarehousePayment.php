<?php

namespace Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;

class WarehousePayment extends Model
{
    protected $table = 'warehouse_payments';

    protected $fillable = [
        'sale_id',
        'amount',
        'method',
        'reference_number',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(WarehouseSale::class, 'sale_id');
    }
}
