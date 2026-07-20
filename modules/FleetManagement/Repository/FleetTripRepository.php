<?php

namespace Modules\FleetManagement\Repository;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\FleetManagement\Models\FleetTrip;
use Modules\FleetManagement\Models\FleetStockDispatch as Dispatch;
use Modules\StockManagement\Models\StockSummary\StockSummary;
use Modules\Locations\Models\LocationModel;
use Modules\ShopManagement\Models\ShopInventory;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;

class FleetTripRepository
{
    const DEFAULT_FLEET_RETURN_QUANTITY = 0;
    const RECORDS_IN_PAGINATION = 10;
    public function allTrips()
    {
        return FleetTrip::select(
            'fleet_trips.*',
            'fleet_routes.name as route_name',
            'fleet_vehicles.registration_number as vehicle_number',
            DB::raw("CASE WHEN fleet_trips.status = 'cancelled' THEN 0 ELSE (SELECT COALESCE(SUM(qty_sent), 0) FROM fleet_trip_stocks WHERE fleet_trip_stocks.fleet_trip_id = fleet_trips.id) END as total_sent"),
            DB::raw("CASE WHEN fleet_trips.status = 'cancelled' THEN 0 ELSE (SELECT COALESCE(SUM(qty_returned), 0) FROM fleet_trip_stocks WHERE fleet_trip_stocks.fleet_trip_id = fleet_trips.id) END as total_returned"),
            DB::raw("CASE WHEN fleet_trips.status = 'cancelled' THEN 0 ELSE (SELECT COALESCE(SUM(fsi.quantity), 0) FROM fleet_sales fs JOIN fleet_sale_items fsi ON fsi.fleet_sale_id = fs.id WHERE fs.fleet_trip_id = fleet_trips.id AND fs.total_amount > 0) END as total_billed"),
            DB::raw("CASE WHEN fleet_trips.status = 'cancelled' THEN 0 ELSE ((SELECT COALESCE(SUM(qty_sent - qty_returned), 0) FROM fleet_trip_stocks WHERE fleet_trip_stocks.fleet_trip_id = fleet_trips.id) - (SELECT COALESCE(SUM(fsi.quantity), 0) FROM fleet_sales fs JOIN fleet_sale_items fsi ON fsi.fleet_sale_id = fs.id WHERE fs.fleet_trip_id = fleet_trips.id AND fs.total_amount > 0)) END as remaining_stock"),
            DB::raw("CASE WHEN fleet_trips.status = 'cancelled' THEN 0 ELSE (CASE WHEN ((SELECT COALESCE(SUM(fs.total_amount), 0) FROM fleet_sales fs WHERE fs.fleet_trip_id = fleet_trips.id AND fs.total_amount > 0) - (SELECT COALESCE(SUM(fsp.amount), 0) FROM fleet_sales fs JOIN fleet_sale_payments fsp ON fsp.fleet_sale_id = fs.id WHERE fs.fleet_trip_id = fleet_trips.id AND fs.total_amount > 0)) < 0 THEN 0 ELSE ((SELECT COALESCE(SUM(fs.total_amount), 0) FROM fleet_sales fs WHERE fs.fleet_trip_id = fleet_trips.id AND fs.total_amount > 0) - (SELECT COALESCE(SUM(fsp.amount), 0) FROM fleet_sales fs JOIN fleet_sale_payments fsp ON fsp.fleet_sale_id = fs.id WHERE fs.fleet_trip_id = fleet_trips.id AND fs.total_amount > 0)) END) END as outstanding_credit")
        )
            ->leftJoin('fleet_routes', 'fleet_trips.route_id', '=', 'fleet_routes.id')
            ->leftJoin('fleet_vehicles', 'fleet_trips.vehicle_id', '=', 'fleet_vehicles.id')
            ->orderBy('fleet_trips.id', 'desc')
            ->paginate(self::RECORDS_IN_PAGINATION);
    }


    /**
     * Create a new Fleet Trip with multiple sent (dispatched) products and 
     * optional returned quantities.
     * 
     * 1. Return quantity map is created to remove nested loop and is 
     *    replaced with a hashmap in the `productReturnQuantityMap` method
     *
     * @param array $data {
     *     @var int    $route_id
     *     @var int    $vehicle_id
     *     @var string $start_date
     *     @var string $tag
     *     @var array  $sent      List of items being dispatched
     *     @var array  $returned  List of returned items with batch & quantity
     * }
     *
     * @return bool True on success, false on failure (logged + rolled back)
     */
    public function create($data): bool
    {
        DB::beginTransaction();

        try {
            // Create Fleet Trip
            $trip = FleetTrip::create([
                'route_id'   => $data['route_id'],
                'vehicle_id' => $data['vehicle_id'],
                'start_date' => $data['start_date'],
                'tag'        => $data['tag'],
            ]);

            // Build return lookup: batch => quantity (once)
            $returnMap = $this->productReturnQuantityMap($data['returned'] ?? []);

            // Process all sent items
            foreach ($data['sent'] as $sentItem) {
                $this->createDispatchAndUpdateInventory($trip, $sentItem, 'sent', $returnMap);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Fleet Trip creation failed: ' . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    /**
     * 1. Identify the product dispatched and return a map
     *    based on batch code and product return quantiy
     * 
     * 2. To allow **O(1) lookup** of return qty per batch during dispatch processing.
     *    1. Avoids nested loops.
     *    2. Enables atomic dispatch creation with correct `qty_returned`.
     * 
     * 3. Hashmap/Associative array is then used in the `create method` and then 
     *    respective product's return quantities are matched.
     *  
     */
    private function productReturnQuantityMap(array $returned): array
    {
        $map = [];
        foreach ($returned as $return) {
            if (isset($return['batch'], $return['quantity'])) {
                $map[$return['batch']] = (int) $return['quantity'];
            }
        }
        return $map;
    }

    private function createDispatchAndUpdateInventory(
        FleetTrip $trip,
        array $item,
        string $type,
        array $returnMap
    ): void {
        $qtySent = $type === 'sent' ? (int) $item['quantity'] : 0;
        $batch = $item['batch'] ?? null;
        $qtyReturned = $returnMap[$batch] ?? self::DEFAULT_FLEET_RETURN_QUANTITY;

        // Create Dispatch entry
        $dispatch = Dispatch::create([
            'fleet_trip_id' => $trip->id,
            'product_id'    => $item['product_id'],
            'qty_sent'      => $qtySent,
            'qty_returned'  => $qtyReturned,
            'location_id'   => $item['location_id'],
            'batch'         => $batch,
            'grade'         => $item['grade'] ?? null,
            'unit'          => $item['unit'] ?? null,
        ]);

        // Only update stock/inventory for sent items
        if ($type === 'sent') {
            // Retrieve unit cost from purchase item
            $purchaseItem = \Modules\StockManagement\Models\StockIn\StockPurchaseItem::where([
                'location_id' => $item['location_id'],
                'product'     => $item['product_id'],
                'batch'       => $item['batch'],
            ])
            ->when($item['grade'] ?? null, function($q) use ($item) {
                $q->where('grade', $item['grade']);
            })
            ->first();

            $unitCost = $purchaseItem ? (float) $purchaseItem->unit_cost : 0.00;

            // Record DISPATCH ledger entry using StockLedgerService
            $service = app(\Modules\StockLedger\Services\StockLedgerService::class);
            $service->recordEntry([
                'transaction_type' => 'DISPATCH',
                'location_id'      => (int) $item['location_id'],
                'product_id'       => (int) $item['product_id'],
                'batch_code'       => $item['batch'],
                'grade'            => $item['grade'] ?? null,
                'quantity'         => -(float) $item['quantity'], // Negative delta to reduce stock
                'unit'             => $item['unit'],
                'unit_cost'        => $unitCost,
                'reference_id'     => $dispatch->id,
                'reference_type'   => get_class($dispatch),
                'remarks'          => "Fleet Trip Dispatch #{$trip->id}",
            ]);
        }
    }

    /**
     * Delete a Fleet Trip and reverse its dispatches in the stock ledger.
     *
     * @param int $tripId
     * @throws \Exception
     */
    public function delete(int $tripId): void
    {
        DB::transaction(function () use ($tripId) {
            $trip = FleetTrip::lockForUpdate()->find($tripId);
            if (!$trip || $trip->status === 'cancelled') {
                throw new \Exception("Trip not found or already cancelled.");
            }

            // Zero out any existing sales & sale items for this trip
            $sales = \Modules\FleetManagement\Models\FleetSale::where('fleet_trip_id', $tripId)->get();
            foreach ($sales as $sale) {
                $sale->total_amount = 0.00;
                $sale->save();

                foreach ($sale->items as $item) {
                    $item->quantity = 0.00;
                    $item->total_price = 0.00;
                    $item->save();
                }
            }

            // Get dispatches (sent stock)
            $dispatches = DB::table('fleet_trip_stocks')
                ->where('fleet_trip_id', $tripId)
                ->get();

            $service = app(\Modules\StockLedger\Services\StockLedgerService::class);

            foreach ($dispatches as $dispatch) {
                if ($dispatch->qty_sent > 0) {
                    // Look up purchase unit cost
                    $purchaseItem = \Modules\StockManagement\Models\StockIn\StockPurchaseItem::where([
                        'location_id' => $dispatch->location_id,
                        'product'     => $dispatch->product_id,
                        'batch'       => $dispatch->batch,
                    ])
                    ->when($dispatch->grade, function($q) use ($dispatch) {
                        $q->where('grade', $dispatch->grade);
                    })
                    ->first();

                    $unitCost = $purchaseItem ? (float) $purchaseItem->unit_cost : 0.00;

                    // Log DISPATCH_REVERSAL with positive quantity delta to restore stock
                    $service->recordEntry([
                        'transaction_type' => 'DISPATCH_REVERSAL',
                        'location_id'      => (int) $dispatch->location_id,
                        'product_id'       => (int) $dispatch->product_id,
                        'batch_code'       => $dispatch->batch,
                        'grade'            => $dispatch->grade,
                        'quantity'         => (float) $dispatch->qty_sent, // Positive delta to restore stock
                        'unit'             => $dispatch->unit,
                        'unit_cost'        => $unitCost,
                        'reference_id'     => $dispatch->id,
                        'reference_type'   => 'Modules\FleetManagement\Models\FleetStockDispatch',
                        'remarks'          => "Fleet Trip Reversal #{$trip->id}",
                    ]);
                }
            }

            // Update the trip status to cancelled
            $trip->update(['status' => 'cancelled']);
        });
    }

    /**
     * Adjust a Fleet Trip's metadata and stock dispatch quantities.
     */
    public function adjust(int $tripId, array $data): void
    {
        DB::transaction(function () use ($tripId, $data) {
            $trip = FleetTrip::lockForUpdate()->find($tripId);
            if (!$trip) {
                throw new \Exception("Trip not found.");
            }

            if ($trip->status === 'cancelled') {
                throw new \Exception("Cannot adjust trip because it has been cancelled.");
            }

            // Check if sales exist
            $salesExist = DB::table('fleet_sales')->where('fleet_trip_id', $tripId)->exists();
            if ($salesExist) {
                throw new \Exception("Cannot adjust trip because sales have already been billed against it.");
            }

            // Update metadata
            $trip->update([
                'route_id'   => $data['route_id'],
                'vehicle_id' => $data['vehicle_id'],
                'start_date' => $data['start_date'],
                'tag'        => $data['tag'],
            ]);

            $service = app(\Modules\StockLedger\Services\StockLedgerService::class);

            // Process item adjustments
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $stockId = (int) $item['id'];
                    $newQty = (int) $item['quantity'];

                    if ($newQty < 0) {
                        throw new \Exception("Quantity cannot be negative.");
                    }

                    if ($newQty === 0) {
                        $stock = DB::table('fleet_trip_stocks')->where('id', $stockId)->first();
                        if (!$stock) {
                            throw new \Exception("Stock dispatch item not found.");
                        }

                        $oldQty = (int) $stock->qty_sent;
                        if ($oldQty > 0) {
                            // Retrieve purchase unit cost
                            $purchaseItem = \Modules\StockManagement\Models\StockIn\StockPurchaseItem::where([
                                'location_id' => $stock->location_id,
                                'product'     => $stock->product_id,
                                'batch'       => $stock->batch,
                            ])
                            ->when($stock->grade, function($q) use ($stock) {
                                $q->where('grade', $stock->grade);
                            })
                            ->first();

                            $unitCost = $purchaseItem ? (float) $purchaseItem->unit_cost : 0.00;

                            // Record DISPATCH_REVERSAL entry in ledger to restore the stock
                            $service->recordEntry([
                                'transaction_type' => 'DISPATCH_REVERSAL',
                                'location_id'      => (int) $stock->location_id,
                                'product_id'       => (int) $stock->product_id,
                                'batch_code'       => $stock->batch,
                                'grade'            => $stock->grade,
                                'quantity'         => (float) $oldQty, // Positive delta restores stock
                                'unit'             => $stock->unit,
                                'unit_cost'        => $unitCost,
                                'reference_id'     => $stock->id,
                                'reference_type'   => 'Modules\FleetManagement\Models\FleetStockDispatch',
                                'remarks'          => "Fleet Trip Adjustment Item Removal #{$tripId}",
                            ]);
                        }

                        DB::table('fleet_trip_stocks')->where('id', $stockId)->delete();
                        continue;
                    }

                    $stock = DB::table('fleet_trip_stocks')->where('id', $stockId)->first();
                    if (!$stock) {
                        throw new \Exception("Stock dispatch item not found.");
                    }

                    $oldQty = (int) $stock->qty_sent;
                    $delta = $oldQty - $newQty;

                    if ($delta !== 0) {
                        // If increasing dispatch (delta < 0), check available stock at source location
                        if ($delta < 0) {
                            $additionalQtyNeeded = abs($delta);
                            $availableQty = $service->getAvailableStock(
                                (int) $stock->location_id,
                                (int) $stock->product_id,
                                $stock->batch,
                                $stock->grade
                            );

                            if ($additionalQtyNeeded > $availableQty) {
                                throw new \Exception("Insufficient stock for batch {$stock->batch}. Available: {$availableQty}.");
                            }
                        }

                        // Update qty_sent in database
                        DB::table('fleet_trip_stocks')->where('id', $stockId)->update([
                            'qty_sent' => $newQty
                        ]);

                        // Retrieve purchase unit cost
                        $purchaseItem = \Modules\StockManagement\Models\StockIn\StockPurchaseItem::where([
                            'location_id' => $stock->location_id,
                            'product'     => $stock->product_id,
                            'batch'       => $stock->batch,
                        ])
                        ->when($stock->grade, function($q) use ($stock) {
                            $q->where('grade', $stock->grade);
                        })
                        ->first();

                        $unitCost = $purchaseItem ? (float) $purchaseItem->unit_cost : 0.00;

                        // Record ADJUSTMENT entry in ledger
                        $service->recordEntry([
                            'transaction_type' => 'ADJUSTMENT',
                            'location_id'      => (int) $stock->location_id,
                            'product_id'       => (int) $stock->product_id,
                            'batch_code'       => $stock->batch,
                            'grade'            => $stock->grade,
                            'quantity'         => (float) $delta, // Positive restores, negative reduces
                            'unit'             => $stock->unit,
                            'unit_cost'        => $unitCost,
                            'reference_id'     => $stock->id,
                            'reference_type'   => 'Modules\FleetManagement\Models\FleetStockDispatch',
                            'remarks'          => "Fleet Trip Adjustment #{$tripId}",
                        ]);
                    }
                }
            }
        });
    }
}
