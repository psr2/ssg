<?php

namespace Modules\FleetManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\Products as Product;
use Modules\Locations\Models\LocationModel as Location;

/**
 * FleetTripStock Model
 *
 * Represents dispatched stock records tied to fleet trips.
 *
 * @property int $id
 * @property int $fleet_trip_id
 * @property int $product_id
 * @property int|null $location_id
 * @property string|null $batch
 * @property float $qty_sent
 * @property float $qty_returned
 */
class FleetStockDispatch extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fleet_trip_stocks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fleet_trip_id',
        'product_id',
        'location_id',
        'batch',
        'grade',
        'unit',
        'qty_sent',
        'qty_returned',
    ];

    /**
     * Relationships
     */

    /**
     * Get the fleet trip this stock belongs to.
     */
    public function fleetTrip(): BelongsTo
    {
        return $this->belongsTo(FleetTrip::class);
    }

    /**
     * Get the product associated with this stock record.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location associated with this stock record.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
