<?php

namespace Modules\FleetManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetSaleItem extends Model
{
    use HasFactory;

    protected $table = 'fleet_sale_items';

    protected $fillable = [
        'fleet_sale_id',
        'product_name',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(FleetSale::class, 'fleet_sale_id');
    }
}
