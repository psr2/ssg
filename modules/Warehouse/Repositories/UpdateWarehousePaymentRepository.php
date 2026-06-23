<?php

declare(strict_types=1);

namespace Modules\Warehouse\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Modules\Warehouse\Models\WarehouseSale;
use Modules\Warehouse\Models\WarehousePayment;

/**
 * Race-condition-safe payment update.
 * Uses pessimistic locking + timestamp comparison — same pattern as ShopManagement.
 */
class UpdateWarehousePaymentRepository
{
    public function process(array $data): void
    {
        DB::beginTransaction();

        try {
            $sale = WarehouseSale::where('id', $data['sale_id'])
                ->lockForUpdate()
                ->first();

            if (!$sale) {
                throw new \Exception('WarehouseSale record not found.');
            }

            // Race condition guard: reject if another update has happened since the client fetched the record
            $clientTimestamp = Carbon::parse($data['last_updated']);
            if ($sale->updated_at > $clientTimestamp) {
                Log::warning('Race condition detected on WarehouseSale update', [
                    'sale_id'        => $data['sale_id'],
                    'db_updated_at'  => $sale->updated_at->toDateTimeString(),
                    'client_ts'      => $clientTimestamp->toDateTimeString(),
                ]);
                throw new \Exception('The sale record has been updated by another process. Please refresh and try again.');
            }

            // Update sale totals atomically
            $now = Carbon::now();
            $sale->paid_amount += $data['new_amount'];
            $sale->due_amount  -= $data['new_amount'];
            $sale->updated_at   = $now;
            $sale->save();

            // Record the new payment
            WarehousePayment::create([
                'sale_id'    => $data['sale_id'],
                'amount'     => $data['new_amount'],
                'method'     => $data['payment_method'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
