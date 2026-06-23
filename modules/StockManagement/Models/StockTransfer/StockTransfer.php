<?php

namespace Modules\StockManagement\Models\StockTransfer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Locations\Models\LocationModel as Location;

class StockTransfer extends Model
{
    use HasFactory;

    protected $table = 'stock_transfers';

    protected $fillable = [
        'transfer_date',
        'reference_no',
        'transfer_type',
        'from_location_id',
        'to_location_id',
        'remarks',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_id');
    }
}
