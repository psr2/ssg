<?php

namespace Modules\FleetManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FleetRoutes extends Model
{
    use HasFactory;

    protected $table = 'fleet_routes';

    protected $fillable = [
        'name',
        'description',
    ];

    protected static function newFactory()
    {
        return \Modules\FleetManagement\Database\factories\FleetRouteFactory::new();
    }
}
