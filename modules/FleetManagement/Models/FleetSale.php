<?php

namespace Modules\FleetManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetSale extends Model
{
    use HasFactory;

    protected $table = 'fleet_sales';

    protected $fillable = [
        'fleet_trip_id',
        'bill_number',
        'customer_name',
        'total_amount',
    ];

    // Relationships
    public function trip()
    {
        return $this->belongsTo(FleetTrip::class, 'fleet_trip_id');
    }

    public function items()
    {
        return $this->hasMany(FleetSaleItem::class, 'fleet_sale_id');
    }

    public function payments()
    {
        return $this->hasMany(FleetSalePayment::class, 'fleet_sale_id');
    }
}
