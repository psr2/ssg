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
            DB::raw('COALESCE(SUM(fleet_trip_stocks.qty_sent), 0) as total_sent'),
            DB::raw('COALESCE(SUM(fleet_trip_stocks.qty_returned), 0) as total_returned'),
            DB::raw('(SELECT COALESCE(SUM(fsi.quantity), 0) FROM fleet_sales fs JOIN fleet_sale_items fsi ON fsi.fleet_sale_id = fs.id WHERE fs.fleet_trip_id = fleet_trips.id) as total_billed'),
            DB::raw('((SELECT COALESCE(SUM(fs.total_amount), 0) FROM fleet_sales fs WHERE fs.fleet_trip_id = fleet_trips.id) - (SELECT COALESCE(SUM(fsp.amount), 0) FROM fleet_sales fs JOIN fleet_sale_payments fsp ON fsp.fleet_sale_id = fs.id WHERE fs.fleet_trip_id = fleet_trips.id)) as outstanding_credit')
        )
            ->leftJoin('fleet_routes', 'fleet_trips.route_id', '=', 'fleet_routes.id')
            ->leftJoin('fleet_vehicles', 'fleet_trips.vehicle_id', '=', 'fleet_vehicles.id')

            // join stock table
            ->leftJoin('fleet_trip_stocks', 'fleet_trip_stocks.fleet_trip_id', '=', 'fleet_trips.id')

            // group by trip so SUM works properly
            ->groupBy('fleet_trips.id', 'fleet_routes.name', 'fleet_vehicles.registration_number')

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
        Dispatch::create([
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
            $this->updateStockSummary($item);
            $this->updateLocationInventory($trip, $item);
        }
    }

    private function updateStockSummary(array $item): void
    {
        if (!isset($item['location_id'], $item['batch'], $item['quantity'], $item['product_id'])) {
            throw new \Exception("Missing location, product, batch or quantity for stock reduction.");
        }

        $quantity = (int) $item['quantity'];
        if ($quantity <= 0) {
            throw new \Exception("Requested quantity for product ID {$item['product_id']} must be greater than zero.");
        }

        $summary = null;
        if (isset($item['grade']) && $item['grade'] !== '') {
            $summary = StockSummary::where('location_id', $item['location_id'])
                ->where('batch_id', $item['batch'])
                ->where('product_id', $item['product_id'])
                ->where('grade', $item['grade'])
                ->lockForUpdate()
                ->first();
        }

        if (!$summary) {
            // Fallback ignoring grade to match parent batch summary record
            $summary = StockSummary::where('location_id', $item['location_id'])
                ->where('batch_id', $item['batch'])
                ->where('product_id', $item['product_id'])
                ->lockForUpdate()
                ->first();
        }

        if (!$summary) {
            $msg = "No stock summary found for product ID {$item['product_id']}, batch {$item['batch']}";
            if (isset($item['grade']) && $item['grade'] !== '') {
                $msg .= ", grade {$item['grade']}";
            }
            $msg .= " at location ID {$item['location_id']}.";
            throw new \Exception($msg);
        }

        if ($summary->current_qty < $quantity) {
            throw new \Exception("Insufficient stock in summary. Available: {$summary->current_qty}, Requested: {$quantity}.");
        }

        $summary->decrement('current_qty', $quantity);
    }

    /**
     * Quantity adjustment based on location type - Shop or Warehouse
     */
    private function updateLocationInventory(FleetTrip $trip, array $item): void
    {
        $location = LocationModel::findOrFail($item['location_id']);
        $quantity = (int) $item['quantity'];
        $batch = $item['batch'] ?? null;
        $grade = $item['grade'] ?? null;

        switch (strtolower($location->type)) {
            case 'shop':
                $inventory = ShopInventory::where([
                    'shop_id'     => $location->id,
                    'batch_id'    => $batch,
                    'grade'       => $grade,
                    'product_id'  => $item['product_id'],
                ])
                ->lockForUpdate()
                ->first();

                if (!$inventory) {
                    // Fallback ignoring grade
                    $inventory = ShopInventory::where([
                        'shop_id'     => $location->id,
                        'batch_id'    => $batch,
                        'product_id'  => $item['product_id'],
                    ])
                    ->lockForUpdate()
                    ->first();
                }

                if (!$inventory) {
                    throw new \Exception("No shop inventory record found for product ID {$item['product_id']}, batch {$batch} at location '{$location->name}'.");
                }

                if ($inventory->qty < $quantity) {
                    throw new \Exception("Insufficient shop inventory stock. Available: {$inventory->qty}, Requested: {$quantity}.");
                }

                $inventory->qty -= $quantity;
                $inventory->stock_transfer_id = $trip->id;
                $inventory->save();
                break;

            case 'warehouse':
                $inventory = WarehouseInventory::where([
                    'warehouse_id' => $location->id,
                    'batch'        => $batch,
                    'grade'       => $grade,
                    'product_id'  => $item['product_id'],
                ])
                ->lockForUpdate()
                ->first();

                if (!$inventory) {
                    // Fallback ignoring grade
                    $inventory = WarehouseInventory::where([
                        'warehouse_id' => $location->id,
                        'batch'        => $batch,
                        'product_id'  => $item['product_id'],
                    ])
                    ->lockForUpdate()
                    ->first();
                }

                if (!$inventory) {
                    throw new \Exception("No warehouse inventory record found for product ID {$item['product_id']}, batch {$batch} at location '{$location->name}'.");
                }

                if ($inventory->qty < $quantity) {
                    throw new \Exception("Insufficient warehouse inventory stock. Available: {$inventory->qty}, Requested: {$quantity}.");
                }

                $inventory->qty -= $quantity;
                $inventory->save();
                break;

            default:
                Log::warning("Unknown location type: {$location->type}", [
                    'location_id' => $location->id
                ]);
                break;
        }
    }
}
