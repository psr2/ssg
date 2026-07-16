<?php

namespace Modules\StockAdjustment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use App\Models\User;

class StockAdjustment extends Model
{
    protected $table = 'stock_adjustments';

    protected $fillable = [
        'location_id',
        'product_id',
        'batch_code',
        'grade',
        'original_qty',
        'adjusted_qty',
        'new_qty',
        'reason',
        'status',
        'adjusted_by',
        'approved_by',
        'remarks',
    ];

    /**
     * Get the location associated with the adjustment.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(LocationModel::class, 'location_id');
    }

    /**
     * Get the product associated with the adjustment.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    /**
     * Get the user who recorded the adjustment.
     */
    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Get the user/manager who approved the adjustment.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
