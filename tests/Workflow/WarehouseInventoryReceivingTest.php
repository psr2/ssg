<?php

namespace Tests\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchase;

class WarehouseInventoryReceivingTest extends TestCase
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
     * Test standard inventory receiving at a warehouse.
     */
    public function test_standard_inventory_receiving_in_warehouse(): void
    {
        $payload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-001',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'grade' => $this->grade->code,
                    'location_id' => $this->warehouse->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'WH Receiving Test',
                    'invoice_number' => 'INV-WH-001',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $payload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // Assert that the Warehouse Inventory has been correctly loaded
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->warehouse->id,
            'batch' => $batchCode,
            'product_id' => $this->product->id,
            'grade' => $this->grade->code,
            'qty' => 1000.00
        ]);

        // Assert ledger entry matches
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'PURCHASE',
            'location_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_code' => $batchCode,
            'grade' => $this->grade->code,
            'quantity' => 1000.00
        ]);
    }
}
