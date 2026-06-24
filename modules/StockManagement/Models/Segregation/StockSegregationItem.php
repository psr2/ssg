<?php

namespace Modules\StockManagement\Models\Segregation;

use Illuminate\Database\Eloquent\Model;

class StockSegregationItem extends Model
{
    protected $table = 'stock_segregation_items';

    protected $fillable = [
        'stock_segregation_id',
        'grade',
        'quantity',
        'unit',
        'unit_cost',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function segregation()
    {
        return $this->belongsTo(StockSegregation::class, 'stock_segregation_id');
    }
}
