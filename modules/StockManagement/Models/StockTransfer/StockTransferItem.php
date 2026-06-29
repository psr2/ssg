<?php

namespace Modules\StockManagement\Models\StockTransfer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\Products as Product;
use Modules\Inventory\Models\UnitOfMeasurement as Unit;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $table = 'stock_transfer_items';

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'batch_code',
        'grade',
        'quantity',
        'unit',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit', 'abbreviation');
    }
}
