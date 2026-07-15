<?php

namespace Modules\StockManagement\Repositories\StockOut;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\StockManagement\Models\StockOut\MasterStockOut;
use Modules\StockManagement\Models\StockOut\StockOutItem;

class StockOutRepository
{
    public function __construct(
        protected \Modules\StockLedger\Services\StockLedgerService $ledgerService
    ) {}

    /**
     * Create master stock out + items in DB
     */
    public function createStockOut(array $data): MasterStockOut
    {
        return DB::transaction(function () use ($data) {
            // Insert into master_stock_out
            $master = MasterStockOut::create([
                'location_id'   => $data['location_id'] ?? $data['items'][0]['location_id'],
                'reference_no'  => $data['reference_no'] ?? null,
                'out_type'      => $data['out_type'] ?? 'sale',
                'out_date'      => $data['movement_date'] ?? now(),
                'remarks'       => $data['remarks'] ?? null,
            ]);

            // Insert items
            foreach ($data['items'] as $item) {
                $purchaseItem = \Modules\StockManagement\Models\StockIn\StockPurchaseItem::where('batch', $item['batch_code'])
                    ->where('location_id', $item['location_id'])
                    ->first();

                $master->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'unit_id'    => $item['unit_id'] ?? null,
                    'quantity'   => $item['quantity'],
                    'unit_cost'  => $item['unit_cost'] ?? null,
                    'total_cost' => $item['total'] ?? null,
                    'location_id'=> $item['location_id'],
                    'stock_purchase_item_id' => $purchaseItem ? $purchaseItem->id : null,
                    'grade'      => $item['grade'] ?? null,
                    'batch_code' => $item['batch_code'] ?? null,
                ]);

                // Record negative ledger entry for Stock Out
                $this->ledgerService->recordEntry([
                    'transaction_type' => 'STOCK_OUT',
                    'location_id'      => $item['location_id'],
                    'product_id'       => $item['product_id'],
                    'batch_code'       => $item['batch_code'],
                    'grade'            => $item['grade'] ?? null,
                    'quantity'         => -$item['quantity'], // Negative quantity
                    'unit'             => $item['unit'],
                    'unit_cost'        => $item['unit_cost'] ?? 0.00,
                    'reference_id'     => $master->id,
                    'reference_type'   => 'master_stock_out',
                    'remarks'          => $data['remarks'] ?? $item['remarks'] ?? null,
                ]);
            }

            return $master;
        });
    }
}
