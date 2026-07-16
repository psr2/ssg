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

    public function getSaleObjectAttribute()
    {
        switch ($this->sale_type) {
            case 'warehouse':
                return \Modules\Warehouse\Models\WarehouseSale::find($this->sale_id);
            case 'shop':
                return \Modules\ShopManagement\Models\ShopSale::find($this->sale_id);
            case 'fleet':
                return \Modules\FleetManagement\Models\FleetSale::find($this->sale_id);
            default:
                return null;
        }
    }
}
