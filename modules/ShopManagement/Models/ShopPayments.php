<?php

namespace Modules\ShopManagement\Models;

use Illuminate\Database\Eloquent\Model;

class ShopPayments extends Model
{
    protected $table = 'shop_payments';

    protected $fillable = [
        'sale_id',
        'amount',
        'method',
        'reference_number',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(ShopSale::class, 'sale_id');
    }
}
