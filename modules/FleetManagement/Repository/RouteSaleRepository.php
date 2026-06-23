<?php

namespace Modules\FleetManagement\Repository;

use Modules\FleetManagement\Models\FleetSale;
use Modules\FleetManagement\Models\FleetSaleItem;
use Modules\FleetManagement\Models\FleetSalePayment;
use Modules\FleetManagement\Exceptions\Fleet\FleetPaymentStoreFailureException;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Facades\Log;
use Modules\FleetManagement\Exceptions\Fleet\FleetCustomerNotFoundException;
use Modules\FleetManagement\Models\FleetCustomer;

/**
 * Todo - use dto , add logging levels , check for exception relevance.
 */

class RouteSaleRepository
{
    /**
     * Create a Fleet Sale with items and payments
     *
     * @param array $payload
     * @return FleetSale
     * @throws FleetPaymentStoreFailureException
     */
    public function store(array $payload, $grandTotal): bool
    {
        Log::debug("payload reached repository: " . json_encode($payload));
        Log::debug("grand total is: " . $grandTotal);

        try {
            return DB::transaction(function () use ($payload, $grandTotal) {


                $customerId = null;

                if (!empty($payload['customer_id']) && $this->hasCustomerWithId($payload['customer_id'])) {

                    $customerId = $payload['customer_id'];
                    Log::debug("if executed");
                    
                } elseif (!empty($payload['customer_name'])) {

                    Log::debug("else if executed");


                    $customer = FleetCustomer::create([
                        'customer_name' => $payload['customer_name'],
                        'customer_phone' => $payload['customer_contact'] ?? null,
                        'location' => $payload['location_name'] ?? null,
                        'route_id' => $payload['trip_id'],
                    ]);

                    $customerId = $customer->id;

                } else {
                    Log::debug("else executed");

                    throw new FleetCustomerNotFoundException("Customer information missing.");
                }

                // 1. Insert Sale , update customer id here
                $sale = FleetSale::create([
                    'fleet_trip_id'  => $payload['trip_id'],
                    'bill_number'    => $payload['bill_no'] ?? null, // fixed from bill_number
                    'customer_name'  => $payload['customer_name'] ?? null,
                    'total_amount'   => $grandTotal, // fixed from total_amount
                ]);

                // 2. Insert Sale Items
                foreach ($payload['items'] as $item) {
                    FleetSaleItem::create([
                        'fleet_sale_id' => $sale->id,
                        'product_name'  => $item['product'],
                        'quantity'      => $item['quantity'],
                        'unit'          => $item['unit'] ?? null,
                        'unit_price'    => $item['unit_price'],
                        'total_price'   => $item['total_price'], // fixed
                    ]);
                }

                // 3. Insert Payment (outside the loop!)
                FleetSalePayment::create([
                    'fleet_sale_id' => $sale->id,
                    'amount'        => $payload['amount_paid'],
                    'payment_date'  => $payload['payment_date'] ?? now(), // fixed: moved from item
                    'payment_mode'  => $payload['payment_mode'] ?? null,
                    'notes'         => $payload['notes'] ?? null,
                ]);

                return true;
            });
        } catch (Throwable $e) {
            throw new FleetPaymentStoreFailureException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Verifies that id is not tampered/poisoned by malicious user
     * Todo - Implement lock out mechanism for enhanced security
     */

    protected function hasCustomerWithId($id): bool
    {
        $exists = FleetCustomer::where('id', $id)->exists();

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
