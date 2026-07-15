<?php

namespace Modules\StockManagement\Tests\Feature\StockOut;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;
use Modules\StockManagement\Models\StockSummary\StockSummary;
use Modules\StockLedger\Models\StockLedgerEntry;

class StockOutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful stock out operation.
     */
    public function test_stock_out_successful(): void
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

        // 1. Perform a purchase to establish stock and valid batch code references
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-20260714-99999',
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
                    'invoice_number' => 'INV-999',
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

        // Verify initial stock summary is in place
        $this->assertDatabaseHas('stock_summary', [
            'location_id' => $location->id,
            'batch_id'    => $batchCode,
            'product_id'  => $product->id,
            'current_qty' => 100.00,
        ]);

        $payload = [
            'stock_type'    => 'out',
            'reference_no'  => 'SOUT-20260714-00001',
            'movement_date' => '2026-07-14',
            'destination'   => 'spoiled',
            'out_type'      => 'spoiled',
            'remarks'       => 'Some spoiled stock',
            'items'         => [
                [
                    'product_id'   => $product->id,
                    'grade'        => $grade->name,
                    'location_id'  => $location->id,
                    'quantity'     => 30.00,
                    'unit'         => 'Kg',
                    'unit_cost'    => 10.00,
                    'total'        => 300.00,
                    'batch_code'   => $batchCode,
                ]
            ]
        ];

        // 2. Perform Stock Out
        $response = $this->postJson('/stock-out-entry', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Stock entry saved successfully.'
            ]);

        // 3. Assert stock_out records
        $this->assertDatabaseHas('master_stock_out', [
            'location_id'  => $location->id,
            'reference_no' => 'SOUT-20260714-00001',
            'out_type'     => 'spoiled',
            'remarks'      => 'Some spoiled stock',
        ]);

        $this->assertDatabaseHas('stock_out_items', [
            'product_id' => $product->id,
            'quantity'   => 30.00,
            'grade'      => $grade->name,
            'batch_code' => $batchCode,
        ]);

        // 4. Assert Ledger Entry (Negative quantity)
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'STOCK_OUT',
            'location_id'      => $location->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'grade'            => $grade->name,
            'quantity'         => -30.00,
            'unit'             => 'Kg',
        ]);

        // 5. Assert Cached Balances are decremented correctly
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $location->id,
            'batch'        => $batchCode,
            'product_id'   => $product->id,
            'grade'        => $grade->name,
            'qty'          => 70.00, // 100.00 - 30.00
        ]);

        $this->assertDatabaseHas('stock_summary', [
            'location_id' => $location->id,
            'batch_id'    => $batchCode,
            'product_id'  => $product->id,
            'grade'       => $grade->name,
            'current_qty' => 70.00, // 100.00 - 30.00
        ]);
    }
}
