<?php

namespace Modules\FleetManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\FleetManagement\Models\FleetRoutes;

class FleetCustomer extends Model
{
    use HasFactory;

    protected $table = 'fleet_customers';

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'route_id',
        'location'
    ];

    protected $casts = [
        'customer_phones' => 'array', // JSON → PHP array
    ];

    /**
     * Relation to route
     */
    public function route()
    {
        return $this->belongsTo(FleetRoutes::class, 'route_id');
    }
}
