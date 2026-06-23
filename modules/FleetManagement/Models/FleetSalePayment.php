<?php

namespace Modules\FleetManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetSalePayment extends Model
{
    use HasFactory;

    protected $table = 'fleet_sale_payments';

    protected $fillable = [
        'fleet_sale_id',
        'amount',
        'payment_date',
        'payment_mode',
        'notes',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(FleetSale::class, 'fleet_sale_id');
    }
}
