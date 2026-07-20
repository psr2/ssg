<?php

namespace Modules\FleetManagement\Models;

use Illuminate\Database\Eloquent\Model;

class FleetTrip extends Model
{
    protected $table = 'fleet_trips';

    protected $fillable = [
        'route_id',
        'vehicle_id',
        'start_date',
        'tag',
        'status'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function route()
    {
        return $this->belongsTo(\Modules\FleetManagement\Models\FleetRoutes::class, 'route_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(\Modules\FleetManagement\Models\FleetVehicle::class, 'vehicle_id');
    }

    public function stocks()
    {
        return $this->hasMany(\Modules\FleetManagement\Models\FleetStockDispatch::class, 'fleet_trip_id');
    }

    // Keep driver commented until you add driver_id column
    // public function driver()
    // {
    //     return $this->belongsTo(\Modules\FleetManagement\Models\Driver::class, 'driver_id');
    // }
}
