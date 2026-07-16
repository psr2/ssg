<?php

namespace Tests\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchase;

class WarehouseDirectStockOutTest extends TestCase
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
     * Scenario 1: Stock arrives in Warehouse A, stock out marked, stock quantity verified.
     */
    public function test_warehouse_direct_stock_out(): void
    {
        // 1. Purchase 1000kg at Warehouse
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-DIR-SOUT',
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
                    'remarks' => 'Purchase for Direct Stock Out',
                    'invoice_number' => 'INV-SOUT-001',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Mark a Stock Out (spoilage) of 200kg
        $stockOutPayload = [
            'stock_type'    => 'out',
            'reference_no'  => 'SOUT-WH-001',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'spoiled',
            'destination'   => 'spoiled',
            'remarks'       => 'Spoiled 200kg of Onion',
            'items'         => [
                [
                    'product_id' => $this->product->id,
                    'grade'      => $this->grade->code,
                    'location_id'=> $this->warehouse->id,
                    'quantity'   => 200.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 2000.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ];

        $response = $this->postJson('/stock-out-entry', $stockOutPayload);
        $response->assertStatus(201);

        // 3. Assert Warehouse final balance: 1000 - 200 = 800kg
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->warehouse->id,
            'batch'        => $batchCode,
            'product_id'   => $this->product->id,
            'grade'        => $this->grade->code,
            'qty'          => 800.00
        ]);

        // 4. Assert ledger entries exist and balance correctly
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'PURCHASE',
            'location_id'      => $this->warehouse->id,
            'product_id'       => $this->product->id,
            'batch_code'       => $batchCode,
            'grade'            => $this->grade->code,
            'quantity'         => 1000.00
        ]);

        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'STOCK_OUT',
            'location_id'      => $this->warehouse->id,
            'product_id'       => $this->product->id,
            'batch_code'       => $batchCode,
            'grade'            => $this->grade->code,
            'quantity'         => -200.00
        ]);
    }
}
