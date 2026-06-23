<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Database\Factories\UnitOfMeasurementFactory; // make sure this is the correct namespace

class UnitOfMeasurement extends Model
{
    use HasFactory;

    public $table = 'units';

    protected $fillable = ['name', 'abbreviation'];

    /**
     * Laravel 8+ way to specify custom factory location
     */
    protected static function newFactory()
    {
        return UnitOfMeasurementFactory::new();
    }
}
