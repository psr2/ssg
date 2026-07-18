<?php

namespace Modules\Warehouse\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Modules\Warehouse\Models\WarehouseSale;
use Modules\Warehouse\Models\WarehouseSaleItem;

class WarehouseSalesTest extends TestCase
{
    use RefreshDatabase;

    /**
    /**
     * Test a successful warehouse sale and its subsequent cancellation via billing adjustment (restoring inventory).
     */
    public function test_warehouse_sale_and_cancellation_flow(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create([
            'name' => 'Tomato',
            'abbreviation' => 'TM',
            'unit_id' => $unit->id
        ]);
        $location = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WH'
        ]);
        $grade = ProductGrade::factory()->create([
            'name' => 'Grade A',
            'code' => 'GA',
            'is_active' => true
        ]);

        // 1. Perform a purchase to establish stock (100 Kg) in warehouse
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-20260714-77777',
            'movement_date' => '2026-07-14',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => $grade->name,
                    'location_id' => $location->id,
                    'quantity' => 100.00,
                    'unit' => 'Kg',
                    'unit_cost' => 10.00,
                    'total' => 1000.00,
                    'remarks' => 'Initial purchase',
                    'invoice_number' => 'INV-777',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => '2026-07-14',
                ]
            ]
        ];

        $purchaseResponse = $this->postJson('/stock-in-entry', $purchasePayload);
        $purchaseResponse->assertStatus(201);

        $purchaseItem = StockPurchaseItem::first();
        $this->assertNotNull($purchaseItem);
        $batchCode = $purchaseItem->batch;

        // 2. Perform Warehouse Sale (30 Kg)
        $salePayload = [
            'customer_name'    => 'John Doe',
            'bill_no'          => 'BILL-0001',
            'payment_status'   => 'unpaid',
            'amount_paid'      => 0.00,
            'payment_date'     => '2026-07-14',
            'payment_mode'     => 'cash',
            'shop_id'          => $location->id,
            'items'            => [
                [
                    'product'     => $product->id,
                    'batch_code'  => $batchCode,
                    'grade'       => $grade->name,
                    'quantity'    => 30.00,
                    'unit'        => 'kg',
                    'unit_price'  => 10.00,
                    'total_price' => 300.00,
                ]
            ]
        ];

        $response = $this->postJson('/warehouse/sale/store', $salePayload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Warehouse sale recorded successfully.'
            ]);

        // Assert WarehouseSale records
        $sale = WarehouseSale::first();
        $this->assertNotNull($sale);
        $this->assertEquals(300.00, $sale->total_amount);

        // Assert negative SALE ledger entry
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'SALE',
            'location_id'      => $location->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'grade'            => $grade->name,
            'quantity'         => -30.00,
            'unit_cost'        => 10.00,
        ]);

        // Assert Warehouse Inventory cache is decremented (100 - 30 = 70)
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $location->id,
            'batch'        => $batchCode,
            'qty'          => 70.00,
        ]);

        // 3. Adjust the bill to 0.00 (cancellation / voiding)
        $adjustmentResponse = $this->postJson('/billing-adjustments', [
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 0.00,
            'reason' => 'voided_sale',
            'remarks' => 'Adjusted bill to zero',
        ]);
        $adjustmentResponse->assertStatus(201);

        // Assert positive SALE_RETURN ledger entry
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'SALE_RETURN',
            'location_id'      => $location->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'grade'            => $grade->name,
            'quantity'         => 30.00,
            'unit_cost'        => 10.00,
        ]);

        // Assert Warehouse Inventory cache is restored back to 100
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $location->id,
            'batch'        => $batchCode,
            'qty'          => 100.00,
        ]);

        // Assert the sale record remains but is marked cancelled
        $sale->refresh();
        $this->assertEquals('cancelled', $sale->status);
        $this->assertEquals(0.00, $sale->total_amount);
        $this->assertEquals(0.00, $sale->due_amount);
    }

    /**
     * Test that warehouse sale with mismatched unit fails validation.
     */
    public function test_warehouse_sale_unit_mismatch_fails_validation(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create([
            'name' => 'Tomato',
            'abbreviation' => 'TM',
            'unit_id' => $unit->id
        ]);
        $location = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WH'
        ]);
        $grade = ProductGrade::factory()->create([
            'name' => 'Grade A',
            'code' => 'GA',
            'is_active' => true
        ]);

        // 1. Purchase in 'Kg'
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-' . uniqid(),
            'movement_date' => '2026-07-14',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => $grade->name,
                    'location_id' => $location->id,
                    'quantity' => 100.00,
                    'unit' => 'Kg',
                    'unit_cost' => 10.00,
                    'total' => 1000.00,
                    'remarks' => 'Initial purchase',
                    'invoice_number' => 'INV-' . uniqid(),
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => '2026-07-14',
                ]
            ]
        ];

        $this->postJson('/stock-in-entry', $purchasePayload)->assertStatus(201);
        $purchaseItem = StockPurchaseItem::first();
        $batchCode = $purchaseItem->batch;

        // 2. Try to sell in 'pcs' (mismatched unit)
        $salePayload = [
            'customer_name'    => 'John Doe',
            'bill_no'          => 'BILL-' . uniqid(),
            'payment_status'   => 'paid',
            'amount_paid'      => 300.00,
            'payment_date'     => '2026-07-14',
            'payment_mode'     => 'cash',
            'shop_id'          => $location->id,
            'items'            => [
                [
                    'product'     => $product->id,
                    'batch_code'  => $batchCode,
                    'grade'       => $grade->name,
                    'quantity'    => 30.00,
                    'unit'        => 'pcs',
                    'unit_price'  => 10.00,
                    'total_price' => 300.00,
                ]
            ]
        ];

        $response = $this->postJson('/warehouse/sale/store', $salePayload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('items.0.unit')
            ->assertJsonFragment([
                'errors' => [
                    'items.0.unit' => [
                        "The selected unit 'pcs' does not match the purchase unit 'Kg' for batch '{$batchCode}'."
                    ]
                ]
            ]);
    }

    /**
     * Test that warehouse sale with custom unit like Liters succeeds.
     */
    public function test_warehouse_sale_custom_unit_liters_success(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Liter', 'abbreviation' => 'L']);
        $product = Products::factory()->create([
            'name' => 'Oil',
            'abbreviation' => 'OL',
            'unit_id' => $unit->id
        ]);
        $location = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WH'
        ]);
        $grade = ProductGrade::factory()->create([
            'name' => 'Grade A',
            'code' => 'GA',
            'is_active' => true
        ]);

        // 1. Purchase in 'Liters'
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-' . uniqid(),
            'movement_date' => '2026-07-14',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => $grade->name,
                    'location_id' => $location->id,
                    'quantity' => 100.00,
                    'unit' => 'Liters',
                    'unit_cost' => 15.00,
                    'total' => 1500.00,
                    'remarks' => 'Oil purchase',
                    'invoice_number' => 'INV-' . uniqid(),
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => '2026-07-14',
                ]
            ]
        ];

        $this->postJson('/stock-in-entry', $purchasePayload)->assertStatus(201);
        $purchaseItem = StockPurchaseItem::where('product', $product->id)->first();
        $batchCode = $purchaseItem->batch;

        // 2. Sell in 'Liters' (case-insensitive check: requesting as 'Liters' which prepareForValidation converts to lower 'liters')
        $salePayload = [
            'customer_name'    => 'John Doe',
            'bill_no'          => 'BILL-' . uniqid(),
            'payment_status'   => 'paid',
            'amount_paid'      => 450.00,
            'payment_date'     => '2026-07-14',
            'payment_mode'     => 'cash',
            'shop_id'          => $location->id,
            'items'            => [
                [
                    'product'     => $product->id,
                    'batch_code'  => $batchCode,
                    'grade'       => $grade->name,
                    'quantity'    => 30.00,
                    'unit'        => 'Liters',
                    'unit_price'  => 15.00,
                    'total_price' => 450.00,
                ]
            ]
        ];

        $response = $this->postJson('/warehouse/sale/store', $salePayload);
        $response->assertStatus(200);

        // Assert negative SALE ledger entry was created with unit 'liters'
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'SALE',
            'location_id'      => $location->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'quantity'         => -30.00,
            'unit'             => 'liters',
        ]);
    }

    /**
     * Test that warehouse sale creation with a phone number already registered at another warehouse fails validation.
     */
    public function test_warehouse_sale_duplicate_phone_validation_fails_even_for_different_warehouse(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create([
            'name' => 'Tomato',
            'abbreviation' => 'TM',
            'unit_id' => $unit->id
        ]);
        $warehouseA = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WHA'
        ]);
        $warehouseB = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WHB'
        ]);
        $grade = ProductGrade::factory()->create([
            'name' => 'Grade A',
            'code' => 'GA',
            'is_active' => true
        ]);

        // Establish stock in Warehouse B
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-' . uniqid(),
            'movement_date' => '2026-07-14',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => $grade->name,
                    'location_id' => $warehouseB->id,
                    'quantity' => 100.00,
                    'unit' => 'Kg',
                    'unit_cost' => 10.00,
                    'total' => 1000.00,
                    'remarks' => 'Initial purchase',
                    'invoice_number' => 'INV-' . uniqid(),
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => '2026-07-14',
                ]
            ]
        ];
        $this->postJson('/stock-in-entry', $purchasePayload)->assertStatus(201);
        $purchaseItem = StockPurchaseItem::where('location_id', $warehouseB->id)->first();
        $batchCode = $purchaseItem->batch;

        // Create an existing customer at Warehouse A with phone '9745861874'
        \Modules\Warehouse\Models\WarehouseCustomer::create([
            'name' => 'Existing Customer',
            'phone' => '9745861874',
            'warehouse_id' => $warehouseA->id,
        ]);

        // Now try to create a sale at Warehouse B with a new customer using the same phone '9745861874'
        $salePayload = [
            'customer_name'    => 'New Customer',
            'customer_contact' => '9745861874',
            'bill_no'          => 'BILL-' . uniqid(),
            'payment_status'   => 'unpaid',
            'amount_paid'      => 0.00,
            'payment_date'     => '2026-07-14',
            'payment_mode'     => 'cash',
            'shop_id'          => $warehouseB->id,
            'items'            => [
                [
                    'product'     => $product->id,
                    'batch_code'  => $batchCode,
                    'grade'       => $grade->name,
                    'quantity'    => 10.00,
                    'unit'        => 'kg',
                    'unit_price'  => 10.00,
                    'total_price' => 100.00,
                ]
            ]
        ];

        $response = $this->postJson('/warehouse/sale/store', $salePayload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('customer_contact')
            ->assertJsonFragment([
                'customer_contact' => [
                    'This phone number is already registered.'
                ]
            ]);
    }

    /**
     * Test that selling transferred stock correctly resolves the unit (e.g. 'kg') 
     * and original unit cost (COGS) from the original purchase.
     */
    public function test_warehouse_sale_unit_cost_matches_original_purchase_for_transferred_stock(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create([
            'name' => 'Coffee Beans',
            'abbreviation' => 'CF',
            'unit_id' => $unit->id
        ]);
        $warehouseA = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WHA'
        ]);
        $warehouseB = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WHB'
        ]);
        $grade = ProductGrade::factory()->create([
            'name' => 'Grade A',
            'code' => 'GA',
            'is_active' => true
        ]);

        // 1. Purchase into Warehouse A
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-' . uniqid(),
            'movement_date' => '2026-07-14',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => $grade->name,
                    'location_id' => $warehouseA->id,
                    'quantity' => 100.00,
                    'unit' => 'Kg',
                    'unit_cost' => 12.50, // <-- Original Unit Cost
                    'total' => 1250.00,
                    'remarks' => 'Initial purchase WHA',
                    'invoice_number' => 'INV-' . uniqid(),
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => '2026-07-14',
                ]
            ]
        ];
        $this->postJson('/stock-in-entry', $purchasePayload)->assertStatus(201);
        $purchaseItem = StockPurchaseItem::where('location_id', $warehouseA->id)->first();
        $batchCode = $purchaseItem->batch;

        // 2. Transfer stock from Warehouse A to Warehouse B
        $this->postJson('/stock-transfer', [
            't_transferDate' => '2026-07-14',
            't_transferType' => 'inter',
            't_fromLocation' => (string) $warehouseA->id,
            't_toLocation'   => (string) $warehouseB->id,
            't_product_name' => (string) $product->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $grade->name,
            't_quantity'     => 40.00,
            't_unit'         => 'Kg',
            't_textarea'     => 'Transfer WHA to WHB',
        ])->assertStatus(200);

        // 3. Create customer at Warehouse B
        $customer = \Modules\Warehouse\Models\WarehouseCustomer::create([
            'name' => 'John Transferred',
            'warehouse_id' => $warehouseB->id,
        ]);

        // 4. Try to sell 30.00 kg from Warehouse B
        $salePayload = [
            'customer_name'    => $customer->name,
            'customer_id'      => $customer->id,
            'bill_no'          => 'BILL-' . uniqid(),
            'payment_status'   => 'unpaid',
            'amount_paid'      => 0.00,
            'payment_date'     => '2026-07-14',
            'payment_mode'     => 'cash',
            'shop_id'          => $warehouseB->id,
            'items'            => [
                [
                    'product'     => $product->id,
                    'batch_code'  => $batchCode,
                    'grade'       => $grade->name,
                    'quantity'    => 30.00,
                    'unit'        => 'kg', // Sell using lowercase 'kg'
                    'unit_price'  => 20.00,
                    'total_price' => 600.00,
                ]
            ]
        ];

        $response = $this->postJson('/warehouse/sale/store', $salePayload);
        $response->assertStatus(200);

        // Assert that the ledger entry created for this SALE has the correct original unit cost and unit
        $this->assertDatabaseHas('stock_ledger_entries', 
            [
                'transaction_type' => 'SALE',
                'location_id'      => $warehouseB->id,
                'product_id'       => $product->id,
                'batch_code'       => $batchCode,
                'quantity'         => -30.00,
                'unit'             => 'kg',
                'unit_cost'        => 12.50, // <-- Assert unit cost matches the purchase unit cost from Warehouse A
            ]);
    }

    /**
     * Test that warehouse sale supports decimal quantities, unit prices, and amounts.
     */
    public function test_warehouse_sale_supports_decimal_numbers(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create([
            'name' => 'Tomato',
            'abbreviation' => 'TM',
            'unit_id' => $unit->id
        ]);
        $location = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WH'
        ]);
        $grade = ProductGrade::factory()->create([
            'name' => 'Grade A',
            'code' => 'GA',
            'is_active' => true
        ]);

        // 1. Establish stock (100 Kg) in warehouse
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-DECIMAL-123',
            'movement_date' => '2026-07-14',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => $grade->name,
                    'location_id' => $location->id,
                    'quantity' => 100.00,
                    'unit' => 'Kg',
                    'unit_cost' => 10.45,
                    'total' => 1045.00,
                    'remarks' => 'Initial purchase',
                    'invoice_number' => 'INV-777',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => '2026-07-14',
                ]
            ]
        ];

        $this->postJson('/stock-in-entry', $purchasePayload)->assertStatus(201);

        $purchaseItem = StockPurchaseItem::first();
        $batchCode = $purchaseItem->batch;

        // 2. Perform Warehouse Sale with decimals
        $salePayload = [
            'customer_name'    => 'John Doe Decimals',
            'bill_no'          => 'BILL-DEC-01',
            'payment_status'   => 'partial',
            'amount_paid'      => 150.55,
            'payment_date'     => '2026-07-14',
            'payment_mode'     => 'cash',
            'shop_id'          => $location->id,
            'items'            => [
                [
                    'product'     => $product->id,
                    'batch_code'  => $batchCode,
                    'grade'       => $grade->name,
                    'quantity'    => 30.45,
                    'unit'        => 'kg',
                    'unit_price'  => 10.25,
                    'total_price' => 312.11,
                ]
            ]
        ];

        $response = $this->postJson('/warehouse/sale/store', $salePayload);
        $response->assertStatus(200);

        // Assert WarehouseSale records
        $sale = WarehouseSale::first();
        $this->assertNotNull($sale);
        $this->assertEquals(312.11, $sale->total_amount);
        $this->assertEquals(150.55, $sale->paid_amount);
        $this->assertEquals(161.56, $sale->due_amount);

        // Assert negative SALE ledger entry
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'SALE',
            'location_id'      => $location->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'quantity'         => -30.45,
            'unit_cost'        => 10.45,
        ]);

        // Assert Warehouse Inventory cache is decremented (100 - 30.45 = 69.55)
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $location->id,
            'batch'        => $batchCode,
            'qty'          => 69.55,
        ]);
    }
}