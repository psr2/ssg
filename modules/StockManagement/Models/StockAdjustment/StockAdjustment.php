<?php

namespace Modules\StockManagement\Models\StockAdjustment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $table = 'stock_adjustments';

    protected $fillable = [
        'stock_purchase_item_id',
        'old_quantity',
        'new_quantity',
        'old_unit_id',
        'new_unit_id',
        'old_location_id',
        'new_location_id',
        'adjustment_type',
        'remarks',
        'created_by',
    ];

 
}
