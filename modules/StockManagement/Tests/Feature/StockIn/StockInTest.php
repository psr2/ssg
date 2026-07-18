<?php

namespace Modules\StockManagement\Tests\Feature\StockIn;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\MasterStockIn;
use Modules\StockManagement\Models\StockIn\StockPurchase;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Modules\StockManagement\Models\StockSummary\StockSummary;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;
use Modules\ShopManagement\Models\ShopInventory;

class StockInTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test rendering the stock purchase dashboard.
     */
    public function test_can_render_stock_purchase_dashboard(): void
    {
        $unit = UnitOfMeasurement::factory()->create();
        $product = Products::factory()->create(['unit_id' => $unit->id]);
        $location = LocationModel::factory()->create();
        $grade = ProductGrade::factory()->create(['is_active' => true]);

        $response = $this->get('/stock-movements');

        $response->assertStatus(200);
        $response->assertViewIs('stock_management::stock_management_dashboard');
        $response->assertViewHas('location');
        $response->assertViewHas('productList');
        $response->assertViewHas('units');
        $response->assertViewHas('grades');
    }

    /**
     * Test generating a purchase reference number.
     */
    public function test_can_generate_purchase_reference_number(): void
    {
        $response = $this->getJson('/stock-purchase-reference-id');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Stock return reference number generated successfully.'
            ]);

        $data = $response->json();
        $this->assertStringStartsWith('PRCH-', $data['reference_no']);
    }

    /**
     * Test generating a return reference number.
     */
    public function test_can_generate_return_reference_number(): void
    {
        $response = $this->getJson('/stock-return-reference-id');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Stock return reference number generated successfully.'
            ]);

        $data = $response->json();
        $this->assertStringStartsWith('RTN-', $data['reference_no']);
    }

    /**
     * Test a successful stock in purchase operation targeting a warehouse location.
     */
    public function test_stock_in_purchase_successful_for_warehouse(): void
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

        $payload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-20260628-00001',
            'movement_date' => '2026-06-28',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => $grade->name,
                    'location_id' => $location->id,
                    'quantity' => 150.50,
                    'unit' => 'Kg',
                    'unit_cost' => 12.00,
                    'total' => 1806.00,
                    'remarks' => 'First purchase batch',
                    'invoice_number' => 'INV-001',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => '2026-06-28',
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Stock entry saved successfully.'
            ]);

        // Assert MasterStockIn entry
        $this->assertDatabaseHas('master_stock_in', [
            'reference_number' => 'PRCH-20260628-00001',
            'stock_in_type' => 'purchase',
            'stock_movement_type' => 'in',
            'stock_in_date' => '2026-06-28',
        ]);

        $masterRecord = MasterStockIn::first();
        $this->assertNotNull($masterRecord);

        // Assert StockPurchase entry
        $this->assertDatabaseHas('stock_purchase', [
            'master_stock_in_id' => $masterRecord->id,
            'vendor' => 'Vendor ABC',
            'invoice_number' => 'INV-001',
            'purchase_date' => '2026-06-28',
        ]);

        $stockPurchase = StockPurchase::first();
        $this->assertNotNull($stockPurchase);

        $batchCode = $stockPurchase->batch_code;
        $this->assertNotEmpty($batchCode);

        // Assert StockPurchaseItem entry
        $this->assertDatabaseHas('stock_purchase_items', [
            'stock_in_purchase_id' => $stockPurchase->id,
            'location_id' => $location->id,
            'product' => $product->id,
            'batch' => $batchCode,
            'grade' => $grade->name,
            'quantity' => 150.50,
            'unit' => 'Kg',
            'unit_cost' => 12.00,
            'total' => 1806.00,
            'remarks' => 'First purchase batch',
        ]);

        // Assert StockSummary entry
        $this->assertDatabaseHas('stock_summary', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'batch_id' => $batchCode,
            'current_qty' => 150.50,
            'unit' => 'Kg',
            'grade' => $grade->name,
        ]);

        // Assert StockLedgerEntry entry
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'PURCHASE',
            'location_id' => $location->id,
            'product_id' => $product->id,
            'batch_code' => $batchCode,
            'grade' => $grade->name,
            'quantity' => 150.50,
            'unit' => 'Kg',
            'unit_cost' => 12.00,
            'reference_id' => $stockPurchase->id,
            'reference_type' => 'stock_purchases',
        ]);

        // Assert WarehouseInventory entry (synced by ledger service)
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $location->id,
            'batch' => $batchCode,
            'product_id' => $product->id,
            'grade' => $grade->name,
            'qty' => 150.50,
            'unit_cost' => 12.00,
        ]);

        // Assert that no shop inventory was created
        $this->assertEquals(0, ShopInventory::count());
    }

    /**
     * Test a successful stock in purchase operation targeting a shop location.
    
    public function test_stock_in_purchase_successful_for_shop(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        
        $product = Products::factory()->create([
            'name' => 'Potato',
            'abbreviation' => 'PT',
            'unit_id' => $unit->id
        ]);
        $location = LocationModel::factory()->create([
            'type' => 'shop',
            'abbreviation' => 'SH'
        ]);
        $grade = ProductGrade::factory()->create([
            'name' => 'Grade B',
            'code' => 'GB',
            'is_active' => true
        ]);

        $payload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-20260628-00002',
            'movement_date' => '2026-06-28',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => $grade->name,
                    'location_id' => $location->id,
                    'quantity' => 80.00,
                    'unit' => 'Kg',
                    'unit_cost' => 8.50,
                    'total' => 680.00,
                    'remarks' => 'Shop purchase batch',
                    'invoice_number' => 'INV-002',
                    'vendor' => 'Vendor XYZ',
                    'purchase_date' => '2026-06-28',
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Stock entry saved successfully.'
            ]);

        // Assert MasterStockIn entry
        $this->assertDatabaseHas('master_stock_in', [
            'reference_number' => 'PRCH-20260628-00002',
            'stock_in_type' => 'purchase',
        ]);

        $stockPurchase = StockPurchase::first();
        $this->assertNotNull($stockPurchase);
        $batchCode = $stockPurchase->batch_code;

        // Assert ShopInventory entry
        $this->assertDatabaseHas('shop_inventory', [
            'shop_id' => $location->id,
            'batch_id' => $batchCode,
            'product_id' => $product->id,
            'grade' => $grade->name,
            'qty' => 80.00,
            'unit_cost' => 8.50,
        ]);

        // Assert that no warehouse inventory was created
        $this->assertEquals(0, WarehouseInventory::count());
    */

    /**
     * Test successful stock in purchase operation with decimal unit costs (e.g. 10.45, 10.02, 10.00).
     */
    public function test_stock_in_purchase_with_decimal_costs(): void
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

        // Decimal prices: 10.45, 10.02, 10.00
        $decimalCosts = [10.45, 10.02, 10.00];

        foreach ($decimalCosts as $index => $cost) {
            $payload = [
                'stock_type' => 'in',
                'reference_no' => 'PRCH-DECIMAL-00' . $index,
                'movement_date' => '2026-06-28',
                'in_type' => 'purchase',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'grade' => $grade->name,
                        'location_id' => $location->id,
                        'quantity' => 10.00,
                        'unit' => 'Kg',
                        'unit_cost' => $cost,
                        'total' => $cost * 10.00,
                        'remarks' => 'Purchase batch with decimal cost ' . $cost,
                        'invoice_number' => 'INV-DEC-' . $index,
                        'vendor' => 'Vendor ABC',
                        'purchase_date' => '2026-06-28',
                    ]
                ]
            ];

            $response = $this->postJson('/stock-in-entry', $payload);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Stock entry saved successfully.'
                ]);
        }
    }

    /**
     * Test validation failure when essential fields are missing.
     */
    public function test_stock_in_purchase_validation_fails_on_missing_fields(): void
    {
        $response = $this->postJson('/stock-in-entry', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'stock_type',
                'reference_no',
                'movement_date',
                'in_type',
                'items'
            ]);
    }

    /**
     * Test validation failure when quantities or costs are invalid.
     */
    public function test_stock_in_purchase_validation_fails_for_invalid_quantities(): void
    {
        $payload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-20260628-00003',
            'movement_date' => '2026-06-28',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Tomato',
                    'grade' => 'Grade A',
                    'location_id' => 1,
                    'quantity' => -10.00,
                    'unit' => 'Kg',
                    'unit_cost' => -5.00,
                    'total' => -50.00,
                    'remarks' => 'Invalid quantity/cost',
                    'invoice_number' => 'INV-003',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => '2026-06-28',
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'items.0.quantity',
                'items.0.unit_cost',
            ]);
    }

    /**
     * Test that StockInTableSeeder successfully seeds all warehouses.
     */
    public function test_stock_in_table_seeder_seeds_all_warehouses(): void
    {
        // 1. Setup multiple warehouses, products, units, and grades
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        
        $product1 = Products::factory()->create([
            'name' => 'Tomato',
            'abbreviation' => 'TM',
            'unit_id' => $unit->id
        ]);
        $product2 = Products::factory()->create([
            'name' => 'Onion',
            'abbreviation' => 'ON',
            'unit_id' => $unit->id
        ]);

        $warehouse1 = LocationModel::factory()->create([
            'name' => 'Warehouse 1',
            'type' => 'warehouse',
            'abbreviation' => 'WH1'
        ]);
        $warehouse2 = LocationModel::factory()->create([
            'name' => 'Warehouse 2',
            'type' => 'warehouse',
            'abbreviation' => 'WH2'
        ]);

        $grade = ProductGrade::factory()->create([
            'name' => 'Big',
            'code' => 'B',
            'is_active' => true
        ]);

        // 2. Run the seeder
        $seeder = new \Modules\StockManagement\Database\Seeders\StockInTableSeeder();
        $seeder->run();

        // 3. Assert stock purchases exist for both warehouses
        $this->assertDatabaseHas('stock_purchase_items', [
            'location_id' => $warehouse1->id,
            'product' => $product1->id,
            'grade' => $grade->code,
        ]);
        $this->assertDatabaseHas('stock_purchase_items', [
            'location_id' => $warehouse1->id,
            'product' => $product2->id,
            'grade' => $grade->code,
        ]);

        $this->assertDatabaseHas('stock_purchase_items', [
            'location_id' => $warehouse2->id,
            'product' => $product1->id,
            'grade' => $grade->code,
        ]);
        $this->assertDatabaseHas('stock_purchase_items', [
            'location_id' => $warehouse2->id,
            'product' => $product2->id,
            'grade' => $grade->code,
        ]);

        // 4. Assert stock summaries were correctly updated for all products in all warehouses
        $this->assertDatabaseHas('stock_summary', [
            'location_id' => $warehouse1->id,
            'product_id' => $product1->id,
        ]);
        $this->assertDatabaseHas('stock_summary', [
            'location_id' => $warehouse2->id,
            'product_id' => $product2->id,
        ]);
    }
}
