<?php

namespace Modules\ShopManagement\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSale extends Model
{
    protected $table = 'shop_sales';

    protected $fillable = [
        'customer_id',
        'sale_date',
        'total_amount',
        'paid_amount',
        'due_amount',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(ShopCustomer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(ShopSaleItem::class, 'sale_id');
    }

    public function payments()
    {
        return $this->hasMany(ShopPayment::class, 'sale_id');
    }
}
