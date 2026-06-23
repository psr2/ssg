<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory;

    public $table = 'products';

    protected $fillable = ['name', 'abbreviation', 'sku', 'unit_id', 'category', 'description'];

    public function unit()
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'unit_id');
    }

    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\ProductFactory::new();
    }
}
