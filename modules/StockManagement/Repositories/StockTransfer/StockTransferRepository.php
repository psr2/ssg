<?php

namespace Modules\StockManagement\Repositories\StockTransfer;

use Modules\Locations\Models\LocationModel;
use Modules\Locations\Enums\LocationType;
use Modules\StockManagement\Models\StockTransfer\StockTransfer;
use Modules\StockManagement\Models\StockTransfer\StockTransferItem;
use Modules\ShopManagement\Models\ShopInventory;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Exception;

/**
 * Handles stock transfer between locations, including stock increment and decrement.
 * Enum is used for reducing concrete variables
 * The stock transfer process is based on the location, and the stock levels are updated accordingly.
 * - Stock is incremented or decremented based on the transfer direction (from one location to another).
 * - The underlying business logic for stock changes (increment and decrement) is handled separately.
 * 
 * Stock updates are performed within the **Repository layer** to ensure data consistency and separation of concerns.
 * 
 * Todo - detach the enum and place it in a common module
 *      - Refactor the class
 *      - Transfer based on grade and batchcode
 * 
 * @throws  StockTransferFailedException If the stock transfer fails due to insufficient stock or other business rule violations.
 */

class StockTransferRepository
{
    public function handle(array $payload): StockTransfer
    {
        Log::debug("reached repository");

        return DB::transaction(function () use ($payload) {
            try {
                // 1. Create main transfer record
                $transfer = StockTransfer::create([
                    'transfer_date' => $payload['t_transferDate'],
                    'transfer_type' => $payload['t_transferType'],
                    'from_location_id' => $payload['t_fromLocation'],
                    'to_location_id'   => $payload['t_toLocation'],
                    'remarks'       => $payload['t_textarea'] ?? null,
                ]);

                // 2. Create transfer item
                $item = StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id'        => $payload['t_product_name'],
                    'batch_code'        => $payload['t_batch_code'],
                    'grade'             => $payload['t_grade'],
                    'quantity'          => $payload['t_quantity'],
                    'unit'              => $payload['t_unit'],
                ]);

                // 3. Update inventory based on locations
                // from_location: decrement stock
                $this->updateInventory(
                    $payload['t_fromLocation'],
                    $item,
                    'decrement',
                    $transfer->id
                );

                // to_location: increment stock
                $this->updateInventory(
                    $payload['t_toLocation'],
                    $item,
                    'increment',
                    $transfer->id
                );

                return $transfer;
            } catch (Exception $e) {
                Log::error("Error during stock transfer: " . $e->getMessage());
                // Optionally, throw a custom exception or rethrow the exception
                throw new \Exception("Stock transfer failed. Please try again later.");
            }
        });
    }

    /**
     * Todo - replace the firstornew with an upsert to reduce cpu load
     */

    protected function updateInventory(
        string|int $locationId,
        StockTransferItem $item,
        string $operation,
        $stockTransferId
    ) {
        Log::debug("items are");
        Log::debug($item);
        try {
            // Determine location type (SHOP / WAREHOUSE)
            $locationType = $this->getLocationType($locationId);


            // Identify location type using enum 
            if ($locationType === LocationType::SHOP->value) {

                $inventory = ShopInventory::firstOrNew([
                    'shop_id'    => $locationId,
                    'batch_id' => $item->batch_code,
                    'grade' => $item->grade,
                    'product_id' => $item->product_id,
                    'stock_transfer_id' => $stockTransferId
                ]);

                $inventory->qty = $operation === 'increment'
                    ? ($inventory->qty + $item->quantity)
                    : ($inventory->qty - $item->quantity);

                if ($inventory->save()) {
                    Log::debug("shop saved");
                }
            }

            if ($locationType === LocationType::WAREHOUSE->value) {
                $inventory = WarehouseInventory::firstOrNew([
                    'warehouse_id' => $locationId,
                    'batch'   => $item->batch_code,
                    'grade' => $item->grade,
                    'product_id' => $item->product_id,
                ]);

                $inventory->qty = $operation === 'increment'
                    ? ($inventory->qty + $item->quantity)
                    : ($inventory->qty - $item->quantity);

                if ($inventory->save()) {
                    Log::debug("warehouse saved");
                }
            }
        } catch (Exception $e) {
            Log::debug("Error during inventory update at location {$locationId}: " . $e->getMessage());
            // Optionally, throw a custom exception or rethrow the exception
            throw new \Exception("Inventory update failed. Please try again later.");
        }
    }

    private function getLocationType($id): string
    {
        return  strtolower(LocationModel::where('id', $id)->pluck('type')->first());
    }
}
