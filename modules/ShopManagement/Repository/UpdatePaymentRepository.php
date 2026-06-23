<?php

namespace Modules\ShopManagement\Repository;

use Modules\ShopManagement\Models\ShopSale;
use Modules\ShopManagement\Models\ShopPayments;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\ShopManagement\Exceptions\RaceConditionException;
use Illuminate\Support\Facades\Log;

/**
 * race conditions for two users through a combination of pessimistic locking (lockForUpdate), 
 * timestamp-based validation, and transaction management. The locking ensures sequential access
 * to the ShopSale record, and the timestamp check provides a fallback to catch stale data,
 * ensuring data consistency even if one user's transaction completes just before the other's begins.
 * 
 * Todo - 1.Test on Jmeter with large number of threads
 *      - 2.Add retry mechanism for higher concurrency and better UX
 *      - 3.Refactor - Locking will add a bottleneck in high concurrency scenarios 
 *      - 4
 * 
 */

class UpdatePaymentRepository
{
    public function process($request)

    {

        // Log::debug("ded".json_encode($request));



        DB::beginTransaction();

        try {

            // Lock ShopSale for update (we will modify it)
            $shopSale = ShopSale::where('id', $request['sale_id'])
                ->lockForUpdate()
                ->first();

            if (!$shopSale) {
                throw new \Exception('ShopSale record not found.');
            }

            $this->hasRaceConditions($request, $shopSale)
                ->updateShopSale($request, $shopSale)
                ->updateShopPayments($request);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /*
    * Manually update multiple fields and the last_updated timestamp in one go,
    * then save the model to perform a single atomic database update.
    *
    * Avoid using increment() and decrement() here because they each trigger
    * their own separate database queries, which can cause partial updates
    * and lead to inconsistent data if an error occurs between calls.
    */
    private function updateShopSale($request, $shopSale)
    {

        $now = Carbon::now();
        $shopSale->paid_amount += $request['new_amount'];
        $shopSale->due_amount -= $request['new_amount'];
        $shopSale->updated_at = $now;
        $shopSale->save();

        return $this;
    }

    private function updateShopPayments($request)
    {
        $now = Carbon::now();

        // Insert new ShopPayments record (no lock needed)
        ShopPayments::create([
            'customer_id'    => $request['customer_id'],
            'amount'    => $request['new_amount'],
            'sale_id'        => $request['sale_id'],
            'method'         => $request['payment_method'], // assuming you have this
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        return $this;
    }

    private function hasRaceConditions($request, $shopSale)
    {
        // Compare client timestamp with the current ShopSale timestamp
        $clientTimestamp = Carbon::parse($request['last_updated']);

        $currentSaleTimestamp = $shopSale->updated_at;

        if ($currentSaleTimestamp > $clientTimestamp) {

            Log::warning('Race condition detected on ShopSale update', [
                'customer_id'           => $request['customer_id'],
                'db_last_updated'       => $currentSaleTimestamp->toDateTimeString(),
                'client_last_updated'   => $clientTimestamp->toDateTimeString(),
                'difference_in_seconds' => $currentSaleTimestamp->diffInSeconds($clientTimestamp),
                'request_amount'        => $request['new_amount'],
            ]);

            throw new RaceConditionException(
                'The ShopSale record has been updated by another user.'
            );
        }

        return $this;
    }
}
