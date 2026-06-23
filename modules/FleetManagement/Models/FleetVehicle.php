<?php

namespace Modules\FleetManagement\Models;

use Illuminate\Database\Eloquent\Model;

class FleetVehicle extends Model
{
    protected $table = 'fleet_vehicles';

    protected $fillable = [
        'registration_number',
        'model',
        'type',
        'capacity',
        'notes',
    ];
}
