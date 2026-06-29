<?php

namespace Modules\StockLedger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockLedgerEntry extends Model
{
    protected $table = 'stock_ledger_entries';

    protected $fillable = [
        'transaction_type',
        'location_id',
        'product_id',
        'batch_code',
        'grade',
        'quantity',
        'unit',
        'unit_cost',
        'reference_id',
        'reference_type',
        'remarks',
        'created_by',
    ];

    /**
     * Get the owning reference model.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relation to location.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(\Modules\Locations\Models\LocationModel::class, 'location_id');
    }

    /**
     * Relation to product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Models\Products::class, 'product_id');
    }
}
