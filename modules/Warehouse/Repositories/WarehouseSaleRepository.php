<?php

declare(strict_types=1);

namespace Modules\Warehouse\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Warehouse\Exceptions\CustomerNotFoundException;
use Modules\Warehouse\Exceptions\WarehouseSaleFailedException;
use Modules\Warehouse\Models\WarehouseCustomer;
use Modules\Warehouse\Models\WarehouseSale;
use Modules\Warehouse\Models\WarehouseSaleItem;
use Modules\Warehouse\Models\WarehousePayment;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;
use Modules\Warehouse\Enums\PaymentMethod;

class WarehouseSaleRepository
{
    /**
     * Handle the full warehouse sale transaction atomically.
     *
     * Steps:
     *  1. Resolve or create the customer
     *  2. Create the sale header
     *  3. Create sale line items
     *  4. Record the payment (if any)
     *  5. Decrement warehouse_inventory quantities
     *
     * @throws CustomerNotFoundException
     * @throws WarehouseSaleFailedException
     */
    public function handle(array $payload, float $grandTotal): void
    {
        DB::transaction(function () use ($payload, $grandTotal) {

            // 1. Resolve or create customer
            $customerId = $this->resolveCustomer($payload);

            // 2. Create sale header
            $sale = WarehouseSale::create([
                'customer_id'  => $customerId,
                'warehouse_id' => $payload['shop_id'],        // 'shop_id' is the warehouse_id from UI
                'sale_date'    => $payload['payment_date'] ?? now(),
                'total_amount' => $grandTotal,
                'paid_amount'  => $payload['amount_paid'] ?? 0,
                'due_amount'   => $grandTotal - ($payload['amount_paid'] ?? 0),
            ]);

            // 3. Create sale line items
            foreach ($payload['items'] as $item) {
                WarehouseSaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $item['product'],
                    'product_name' => '',
                    'batch_code'   => $item['batch_code'] ?? '',
                    'grade'        => $item['grade'] ?? 1,
                    'quantity'     => $item['quantity'],
                    'unit'         => $item['unit'],
                    'unit_price'   => $item['unit_price'],
                    'total_price'  => $item['total_price'],
                ]);
            }

            // 4. Record payment if an amount was paid
            if (!empty($payload['amount_paid']) && $payload['amount_paid'] > 0) {
                WarehousePayment::create([
                    'sale_id'          => $sale->id,
                    'amount'           => $payload['amount_paid'],
                    'method'           => $payload['payment_mode'] ?? PaymentMethod::DEFAULT->value,
                    'reference_number' => $payload['bill_no'] ?? null,
                    'paid_at'          => $payload['payment_date'] ?? now(),
                ]);
            }

            // 5. Decrement stock from warehouse_inventory
            $this->decrementWarehouseStock($payload);
        });
    }

    /**
     * Resolve an existing customer by ID or create a new one.
     *
     * @throws CustomerNotFoundException
     */
    private function resolveCustomer(array $payload): int
    {
        if (!empty($payload['customer_id']) && $this->customerExists($payload['customer_id'])) {
            Log::debug('Warehouse sale: using existing customer', ['customer_id' => $payload['customer_id']]);
            return (int) $payload['customer_id'];
        }

        if (!empty($payload['customer_name'])) {
            Log::debug('Warehouse sale: creating new customer');
            $customer = WarehouseCustomer::create([
                'name'         => $payload['customer_name'],
                'warehouse_id' => $payload['shop_id'],
                'address'      => $payload['business_name'] ?? null,
                'phone'        => $payload['customer_contact'] ?? null,
                'location'     => $payload['location_name'] ?? null,
            ]);
            return $customer->id;
        }

        Log::debug('Warehouse sale: customer information missing');
        throw new CustomerNotFoundException('Customer information missing.');
    }

    /**
     * Verify that a customer ID actually exists in warehouse_customers.
     */
    private function customerExists(mixed $id): bool
    {
        $exists = WarehouseCustomer::where('id', $id)->exists();

        if (!$exists) {
            Log::warning('Suspicious warehouse customer_id provided: ' . $id, [
                'ip' => request()->ip(),
            ]);
        }

        return $exists;
    }

    /**
     * Deduct quantities from warehouse_inventory.
     * Matches on: warehouse_id + product_id + batch + grade
     *
     * @throws WarehouseSaleFailedException
     */
    private function decrementWarehouseStock(array $payload): void
    {
        foreach ($payload['items'] as $item) {
            $inventory = WarehouseInventory::where('warehouse_id', $payload['shop_id'])
                ->where('product_id', $item['product'])
                ->where('batch', $item['batch_code'])
                ->where('grade', $item['grade'])
                ->first();

            if (!$inventory) {
                // Fallback: search ignoring grade
                $inventory = WarehouseInventory::where('warehouse_id', $payload['shop_id'])
                    ->where('product_id', $item['product'])
                    ->where('batch', $item['batch_code'])
                    ->first();
            }

            if (!$inventory) {
                throw new WarehouseSaleFailedException(
                    "Inventory not found for product_id {$item['product']} and batch {$item['batch_code']}"
                );
            }

            if ($inventory->qty < $item['quantity']) {
                throw new WarehouseSaleFailedException(
                    "Insufficient warehouse stock for product_id {$item['product']} in batch {$item['batch_code']}"
                );
            }

            $inventory->decrement('qty', $item['quantity']);
        }
    }

    /**
     * Delete a sale and restore its stock.
     *
     * @throws WarehouseSaleFailedException
     */
    public function delete(int $saleId): void
    {
        DB::transaction(function () use ($saleId) {
            $sale = WarehouseSale::with('items')->lockForUpdate()->find($saleId);

            if (!$sale) {
                throw new WarehouseSaleFailedException("Sale not found or already deleted.");
            }

            // Restore stock to warehouse_inventory
            foreach ($sale->items as $item) {
                $inventory = WarehouseInventory::where('warehouse_id', $sale->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->where('batch', $item->batch_code)
                    ->where('grade', $item->grade)
                    ->first();

                if (!$inventory) {
                    // Fallback: search ignoring grade
                    $inventory = WarehouseInventory::where('warehouse_id', $sale->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->where('batch', $item->batch_code)
                        ->first();
                }

                if ($inventory) {
                    $inventory->increment('qty', $item->quantity);
                } else {
                    // If the inventory record was somehow removed, recreate it to restore stock
                    WarehouseInventory::create([
                        'warehouse_id' => $sale->warehouse_id,
                        'product_id'   => $item->product_id,
                        'batch'        => $item->batch_code,
                        'grade'        => $item->grade,
                        'qty'          => $item->quantity,
                        'unit_cost'    => $item->unit_price,
                    ]);
                }
            }

            // Delete associated payments
            WarehousePayment::where('sale_id', $saleId)->delete();

            // Delete associated items
            WarehouseSaleItem::where('sale_id', $saleId)->delete();

            // Delete the sale header
            $sale->delete();
        });
    }
}
