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
    public function __construct(protected \Modules\StockLedger\Services\StockLedgerService $ledgerService) {}

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

                // Look up unit cost of original batch
                $purchaseItem = \Modules\StockManagement\Models\StockIn\StockPurchaseItem::where([
                    'location_id' => $payload['t_fromLocation'],
                    'product'     => $item->product_id,
                    'batch'       => $item->batch_code,
                ])
                ->when($item->grade, function($q) use ($item) {
                    $q->where('grade', $item->grade);
                })
                ->first();

                $unitCost = $purchaseItem ? (float) $purchaseItem->unit_cost : 0.00;

                // 3. Record entries in the StockLedger
                $this->ledgerService->recordEntry([
                    'transaction_type' => 'TRANSFER_OUT',
                    'location_id'      => $payload['t_fromLocation'],
                    'product_id'       => $item->product_id,
                    'batch_code'       => $item->batch_code,
                    'grade'            => $item->grade,
                    'quantity'         => -$item->quantity,
                    'unit'             => $item->unit,
                    'unit_cost'        => $unitCost,
                    'reference_id'     => $item->id,
                    'reference_type'   => get_class($item),
                    'remarks'          => $transfer->remarks ?? 'Stock transferred out',
                ]);

                $this->ledgerService->recordEntry([
                    'transaction_type' => 'TRANSFER_IN',
                    'location_id'      => $payload['t_toLocation'],
                    'product_id'       => $item->product_id,
                    'batch_code'       => $item->batch_code,
                    'grade'            => $item->grade,
                    'quantity'         => $item->quantity,
                    'unit'             => $item->unit,
                    'unit_cost'        => $unitCost,
                    'reference_id'     => $item->id,
                    'reference_type'   => get_class($item),
                    'remarks'          => $transfer->remarks ?? 'Stock transferred in',
                ]);

                return $transfer;
            } catch (Exception $e) {
                Log::error("Error during stock transfer: " . $e->getMessage());
                // Optionally, throw a custom exception or rethrow the exception
                throw new \Exception("Stock transfer failed. Please try again later.");
            }
        });
    }

}
