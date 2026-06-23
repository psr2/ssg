<?php

declare(strict_types=1);

namespace Modules\ShopManagement\Repository;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ShopManagement\Exceptions\CustomerNotFoundException;
use Modules\ShopManagement\Exceptions\ShopPaymentFailedException;
use Modules\ShopManagement\Models\ShopSale;
use Modules\ShopManagement\Models\ShopSaleItem;
use Modules\ShopManagement\Models\ShopPayments;
use Modules\ShopManagement\Models\ShopInventory;
use Modules\ShopManagement\Models\ShopCustomer;
use Modules\ShopManagement\Enums\PAYMENT_METHOD;

use function Psy\debug;

/**
 * Refactor - for milli seconds in the database  sale entry class
 * 
 * Todo - update the stock summary table as well
 */

class ShopSaleRepository
{
    /**
     * Handle the entire sale transaction.
     *
     * Todo - move the customer checking into ValidateCustomer service layer
     * 
     * @param array $payload
     * @param float $grandTotal
     * @return void
     * @throws ShopPaymentFailedException 
     * @throws CustomerNotFoundException 
     * 
     */
    public function handle($payload, float $grandTotal): void
    {


        DB::transaction(function () use ($payload, $grandTotal) {

            /**
             * Get or create customer based on payload:
             * - If customer_id is present and valid (via hasCustomerWithId), use it.
             * - Otherwise, create a new customer from new_customer_name and related fields.
             * 
             * @throws CustomerNotFoundException If neither existing ID nor new details are provided.
             */

            $customerId = null;

            if (!empty($payload['customer_id']) && $this->hasCustomerWithId($payload['customer_id'])) {

                $customerId = $payload['customer_id'];
                Log::debug("if executed");
            } elseif (!empty($payload['customer_name'])) {

                Log::debug("else if executed");


                $customer = ShopCustomer::create([
                    'name' => $payload['customer_name'],
                    'address' => $payload['business_name'] ?? null,
                    'phone' => $payload['customer_contact'] ?? null,
                    'location' => $payload['location_name'] ?? null,
                    'shop_id' => $payload['shop_id'],
                ]);
                $customerId = $customer->id;
            } else {
                Log::debug("else executed");

                throw new CustomerNotFoundException("Customer information missing.");
            }

            // 2. Create Sale
            $sale = ShopSale::create([
                'customer_id'  => $customerId,
                'sale_date'    => $payload['payment_date'] ?? now(),
                'total_amount' => $grandTotal,
                'paid_amount'  => $payload['amount_paid'] ?? 0,
                'due_amount'   => $grandTotal - ($payload['amount_paid'] ?? 0),
            ]);

            // 3. Store the sale items
            foreach ($payload['items'] as $item) {
                ShopSaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $item['product'],
                    'product_name' => "Nil", // Can be improved
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'total_price'  => $item['total_price'],
                    'grade'        => $item['grade']
                ]);
            }

            // 4. Record the payment if any
            if (!empty($payload['amount_paid']) && $payload['amount_paid'] > 0) {
                ShopPayments::create([
                    'sale_id'          => $sale->id,
                    'amount'           => $payload['amount_paid'],
                    'method'           => $payload['payment_mode'] ?? PAYMENT_METHOD::DEFAULT->value,
                    'reference_number' => $payload['bill_no'] ?? null,
                    'paid_at'          => $payload['payment_date'] ?? now(),
                ]);
            }

            $this->updateStock($payload);
        });
    }


    /**
     * Update shop inventory on the sale based on Grade , Batch Id , PRoduct Id
     *
     * @param array $payload
     * @return void
     * @throws ShopPaymentFailedException
     */
    protected function updateStock($payload): void
    {
        foreach ($payload['items'] as $item) {
            $inventory = ShopInventory::where('shop_id', $payload['shop_id'])
                ->where('product_id', $item['product'])
                ->where('batch_id', $item['batch_code'])
                ->where('grade',$item['grade'])
                ->first();

            if (!$inventory) {
                throw new ShopPaymentFailedException("Inventory not found for product_id {$item['product']} and batch_id {$item['batch_code']}");
            }

            if ($inventory->qty < $item['quantity']) {
                throw new ShopPaymentFailedException("Insufficient stock for product_id {$item['product']} in batch_id {$item['batch_code']}");
            }

            $inventory->decrement('qty', $item['quantity']);
        }
    }

    /**
     * Verifies that id is not tampered/poisoned by malicious user
     * Todo - Implement lock out mechanism for enhanced security
     */

    protected function hasCustomerWithId($id): bool
    {
        $exists = ShopCustomer::where('id', $id)->exists();

        if (!$exists) {
            Log::warning('Suspicious customer_id provided: ' . $id, [
                'context' => request()->all(), // or custom payload
                'ip' => request()->ip(),
            ]);

            // Optional: trigger ban logic, alert, or throw custom exception
        }

        return $exists;
    }
}
