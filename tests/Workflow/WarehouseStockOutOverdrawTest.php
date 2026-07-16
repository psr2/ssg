<?php

namespace Tests\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchase;

class WarehouseStockOutOverdrawTest extends TestCase
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
     * Test that overdrawing stock out at a single location fails, even if global stock is sufficient.
     */
    public function test_warehouse_stock_out_overdraw(): void
    {
        // 1. Purchase 1000kg at Warehouse A
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-OVERDRAW',
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
                    'remarks' => 'Purchase for Overdraw Test',
                    'invoice_number' => 'INV-OVERDRAW',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Transfer 400kg from Warehouse A to Warehouse B
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
            't_textarea'     => 'Transferring to Warehouse B',
        ];

        $response = $this->postJson('/stock-transfer', $transferPayload);
        $response->assertStatus(200);

        // 3. Attempt to stock out 500kg from Warehouse B (Warehouse B only has 400kg)
        // Global stock is 1000kg, but Warehouse B location has only 400kg.
        $stockOutPayload = [
            'stock_type'    => 'out',
            'reference_no'  => 'SOUT-OVERDRAW-001',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'spoiled',
            'destination'   => 'spoiled',
            'remarks'       => 'Attempting to overdraw 500kg of Onion from Warehouse B',
            'items'         => [
                [
                    'product_id' => $this->product->id,
                    'grade'      => $this->grade->code,
                    'location_id'=> $this->warehouseB->id,
                    'quantity'   => 500.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 5000.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ];

        $response = $this->postJson('/stock-out-entry', $stockOutPayload);
        
        // 4. Assert that the request fails with validation status 422
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity']);

        // 5. Assert Warehouse B balance is still unchanged (400.00)
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->warehouseB->id,
            'batch'        => $batchCode,
            'product_id'   => $this->product->id,
            'grade'        => $this->grade->code,
            'qty'          => 400.00
        ]);
    }
}
