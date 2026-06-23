<?php

namespace Modules\StockManagement\Models\StockIn;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterStockIn extends Model
{
    use HasFactory;

    protected $table = 'master_stock_in';

    protected $fillable = [
        'stock_in_type',
        'reference_number',
        'location_id',
        'stock_in_date',
        'total_quantity',
        'notes',
            ];

    public function purchase()
    {
        return $this->hasOne(StockPurchase::class, 'master_stock_in_id');
    }
}