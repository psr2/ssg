<?php

namespace Modules\FleetManagement\Repository\Dispatch;

use Modules\FleetManagement\Models\FleetStockDispatch as Dispatch;
use Modules\StockManagement\Models\StockSummary\StockSummary;


/**
 * Repository responsible for managing fleet trip stock dispatches.
 *
 * Handles:
 * - Inserting dispatch entries into fleet_trip_stock table
 * - Updating stock summary records based on location
 */

class StockDispatchRepository
{
    CONST DEFAULT_RETURN_QUANTITY=0;

    /**
     * Create new dispatch entries in the fleet trip stock table.
     *
     * @param array $data  Validated Data related to stock dispatch
     * @return bool Returns true on success, false otherwise
     */   
    public function storeDispatchEntries($data): bool
    {
        
        return Dispatch::create([
            'fleet_trip_id' => $data['trip'],
            'product_id'    => $data['product'],
            'qty_sent'      => $data['qtySent'],
            'location_id'   => $data['location'],
            'batch'         => $data['batch'],
            'qty_returned'  => $data['qtyReturned'] ?? SELF::DEFAULT_RETURN_QUANTITY,
        ]);

    }

    /**
     * Decrease current_quantity value in the stock summary table for the given location.
     *
     * @param array $data Validated Data
     * @return void
     */
    public function updateStockSummary( $data): bool
    {
       return StockSummary::where('location_id', $data['location'])
            ->where('batch_id', $data['batch'])
            ->lockForUpdate()
            ->decrement('current_qty', $data['qtySent']);
    }

    public function hasStock($data):StockSummary{

       return StockSummary::where('location_id', $data['location'])
            ->where('batch_id', $data['batch'])
            ->lockForUpdate()
            ->first();
       
    }
}
