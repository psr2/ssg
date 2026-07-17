<?php

namespace Modules\FleetManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetRoutes extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fleet_routes';

    protected $fillable = [
        'name',
        'description',
    ];

    protected static function newFactory()
    {
        return \Modules\FleetManagement\Database\Factories\FleetRouteFactory::new();
    }
}
