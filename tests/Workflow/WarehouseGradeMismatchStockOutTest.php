<?php

namespace Tests\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchase;

class WarehouseGradeMismatchStockOutTest extends TestCase
{
    use RefreshDatabase;

    protected $product;
    protected $grade;
    protected $unit;
    protected $warehouse;

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

        // Grade with name 'Big' and code 'BO'
        $this->grade = ProductGrade::firstOrCreate(
            ['code' => 'BO'],
            [
                'name' => 'Big',
                'is_active' => true
            ]
        );

        $this->warehouse = LocationModel::create([
            'name' => 'Theni Warehouse',
            'type' => 'warehouse',
            'abbreviation' => 'TW',
            'status' => 'active'
        ]);
    }

    /**
     * Test grade name/code interchangeable resolution during stock out.
     */
    public function test_warehouse_grade_mismatch_stock_out(): void
    {
        // 1. Purchase 1000kg at Warehouse with Grade NAME 'Big'
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-GRADE-MISMATCH',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'grade' => 'Big', // Grade Name
                    'location_id' => $this->warehouse->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'Purchase with Grade Name',
                    'invoice_number' => 'INV-MISMATCH',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Perform Stock Out of 300kg using Grade CODE 'BO'
        $stockOutPayload = [
            'stock_type'    => 'out',
            'reference_no'  => 'SOUT-MISMATCH-001',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'spoiled',
            'destination'   => 'spoiled',
            'remarks'       => 'Spoil 300kg using Grade Code BO',
            'items'         => [
                [
                    'product_id' => $this->product->id,
                    'grade'      => 'BO', // Grade Code
                    'location_id'=> $this->warehouse->id,
                    'quantity'   => 300.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 3000.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ];

        $response = $this->postJson('/stock-out-entry', $stockOutPayload);
        $response->assertStatus(201);

        // 3. Assert Warehouse final balance is decremented correctly to 700.00
        // (Our resolution service normalizes queries using grade name 'Big')
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->warehouse->id,
            'batch'        => $batchCode,
            'product_id'   => $this->product->id,
            'grade'        => 'Big',
            'qty'          => 700.00
        ]);
    }
}
