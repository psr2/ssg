<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BillingAdjustment extends Model
{
    protected $table = 'billing_adjustments';

    protected $fillable = [
        'sale_type',
        'sale_id',
        'original_amount',
        'adjusted_amount',
        'new_amount',
        'reason',
        'adjusted_by',
        'remarks',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Polymorphic relation to the adjusted sale record.
     */
    public function sale()
    {
        return $this->morphTo('sale', 'sale_type', 'sale_id');
    }

    public function getSaleObjectAttribute()
    {
        return $this->sale;
    }
}
