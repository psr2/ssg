<?php

namespace Modules\Locations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationModel extends Model
{
    use HasFactory;

    public $table = 'locations';

    protected $fillable = ['name', 'type', 'address','abbreviation'];

    protected static function newFactory()
    {
        return \Modules\Locations\Database\Factories\LocationModelFactory::new();
    }
}
