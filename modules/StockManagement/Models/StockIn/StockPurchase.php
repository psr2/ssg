<?php

namespace Modules\StockManagement\Models\StockIn;

use Illuminate\Database\Eloquent\Model;

class StockPurchase extends Model
{
    protected $table = 'stock_purchase';

    protected $fillable = [
        'master_stock_in_id',
        'vendor',
        'invoice_number',
        'purchase_date',
        'batch_code',
    ];

    public function masterStockIn()
    {
        return $this->belongsTo(MasterStockIn::class, 'master_stock_in_id');
    }
}
