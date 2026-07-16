<?php

namespace Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\Products;

class WarehouseSaleItem extends Model
{
    protected $table = 'warehouse_sale_items';

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'batch_code',
        'grade',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(WarehouseSale::class, 'sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function gradeRelation()
    {
        return $this->belongsTo(\Modules\Inventory\Models\ProductGrade::class, 'grade', 'code');
    }

    public function unitRelation()
    {
        return $this->belongsTo(\Modules\Inventory\Models\UnitOfMeasurement::class, 'unit', 'abbreviation');
    }
}
