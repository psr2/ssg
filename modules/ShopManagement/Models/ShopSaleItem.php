<?php

namespace Modules\ShopManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\Products;

class ShopSaleItem extends Model
{
    protected $table = 'shop_sale_items';

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(ShopSale::class, 'sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
