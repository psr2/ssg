<?php

namespace Tests\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchase;

class WarehouseToWarehouseTransferTest extends TestCase
{
    use RefreshDatabase;

    protected $product;
    protected $grade;
    protected $unit;
    protected $warehouseA;
    protected $warehouseB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = UnitOfMeasurement::firstOrCreate(
            ['abbreviation' => 'kg'],
            ['name' => 'Kilogram']
        );

        $this->product = Products::firstOrCreate(
            ['sku' => 'veg_on'],
            [
                'name' => 'Onion',
                'abbreviation' => 'on',
                'unit_id' => $this->unit->id
            ]
        );

        $this->grade = ProductGrade::firstOrCreate(
            ['code' => 'BO'],
            [
                'name' => 'Big',
                'is_active' => true
            ]
        );

        $this->warehouseA = LocationModel::create([
            'name' => 'Theni Warehouse',
            'type' => 'warehouse',
            'abbreviation' => 'TW',
            'status' => 'active'
        ]);

        $this->warehouseB = LocationModel::create([
            'name' => 'Madurai Warehouse',
            'type' => 'warehouse',
            'abbreviation' => 'MW',
            'status' => 'active'
        ]);
    }

    /**
     * Test warehouse-to-warehouse stock transfer.
     */
    public function test_warehouse_to_warehouse_stock_transfer(): void
    {
        // 1. Purchase 1000kg at Warehouse A
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-A',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'grade' => $this->grade->code,
                    'location_id' => $this->warehouseA->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'Initial Purchase at Warehouse A',
                    'invoice_number' => 'INV-WH-A',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Transfer 400kg from Warehouse A to Warehouse B using correct form fields
        $transferPayload = [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->warehouseA->id,
            't_toLocation'   => (string) $this->warehouseB->id,
            't_product_name' => (string) $this->product->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->grade->code,
            't_quantity'     => 400.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transferring 400 kg of Onion to Warehouse B',
        ];

        $response = $this->postJson('/stock-transfer', $transferPayload);
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Stock transfer successful'
            ]);

        // 3. Assert Warehouse A inventory decreased by 400kg (leaving 600kg)
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->warehouseA->id,
            'batch' => $batchCode,
            'product_id' => $this->product->id,
            'grade' => $this->grade->code,
            'qty' => 600.00
        ]);

        // 4. Assert Warehouse B inventory increased to 400kg
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->warehouseB->id,
            'batch' => $batchCode,
            'product_id' => $this->product->id,
            'grade' => $this->grade->code,
            'qty' => 400.00
        ]);

        // 5. Assert the ledger contains the expected entries
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'PURCHASE',
            'location_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'batch_code' => $batchCode,
            'grade' => $this->grade->code,
            'quantity' => 1000.00
        ]);

        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'TRANSFER_OUT',
            'location_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'batch_code' => $batchCode,
            'grade' => $this->grade->code,
            'quantity' => -400.00
        ]);

        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'TRANSFER_IN',
            'location_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'batch_code' => $batchCode,
            'grade' => $this->grade->code,
            'quantity' => 400.00
        ]);
    }
}
