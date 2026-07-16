<?php

namespace Tests\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchase;

class ShopInventoryReceivingTest extends TestCase
{
    use RefreshDatabase;

    protected $product;
    protected $grade;
    protected $unit;
    protected $shop;

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

        $this->shop = LocationModel::create([
            'name' => 'Theni Shop',
            'type' => 'shop',
            'abbreviation' => 'TS',
            'status' => 'active'
        ]);
    }

    /**
     * Test standard inventory receiving at a shop.
     */
    public function test_standard_inventory_receiving_in_shop(): void
    {
        $payload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-SH-001',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'grade' => $this->grade->code,
                    'location_id' => $this->shop->id,
                    'quantity' => 100.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 1000.00,
                    'remarks' => 'SH Receiving Test',
                    'invoice_number' => 'INV-SH-001',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $payload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // Assert that the Shop Inventory has been correctly loaded
        $this->assertDatabaseHas('shop_inventory', [
            'shop_id' => $this->shop->id,
            'batch_id' => $batchCode,
            'product_id' => $this->product->id,
            'grade' => $this->grade->code,
            'qty' => 100.00
        ]);

        // Assert ledger entry matches
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'PURCHASE',
            'location_id' => $this->shop->id,
            'product_id' => $this->product->id,
            'batch_code' => $batchCode,
            'grade' => $this->grade->code,
            'quantity' => 100.00
        ]);
    }
}
