<?php

namespace Modules\StockManagement\Models\StockSummary;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockSummary extends Model
{
    use HasFactory;

    protected $table = 'stock_summary';

    protected $fillable = [
        'product_id',
        'location_id',
        'batch_id',
        'current_qty',
        'reserved_qty',
        'unit',
        'grade'
    ];
}
