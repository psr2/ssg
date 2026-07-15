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
     * Test a successful warehouse sale and its subsequent deletion (restoring inventory).
     */
    public function test_warehouse_sale_and_deletion_flow(): void
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

        // 3. Delete the Sale (restoring stock via reversal entry)
        $deleteResponse = $this->deleteJson("/warehouse/sale/{$sale->id}/delete");
        $deleteResponse->assertStatus(200);

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
    }
}