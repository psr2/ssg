<?php

namespace Modules\Warehouse\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;
use Modules\StockManagement\Models\Segregation\StockSegregation;
use Modules\StockManagement\Models\Segregation\StockSegregationItem;

class WarehouseOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_inventory_calculates_dynamic_ledger_stocks_and_filters(): void
    {
        // 1. Create a unit of measurement
        $unit = UnitOfMeasurement::create([
            'name' => 'Kilogram',
            'abbreviation' => 'kg',
        ]);

        // 2. Create products
        $product1 = Products::create([
            'name' => 'Potato',
            'sku' => 'POT-001',
            'unit_id' => $unit->id,
            'category' => 'Veg',
            'abbreviation' => 'POT',
        ]);

        // 3. Create locations (warehouses)
        $warehouse = LocationModel::create([
            'name' => 'Central Warehouse',
            'type' => 'warehouse',
            'address' => '123 Main Rd',
        ]);

        // 4. Create base warehouse inventory and purchase records
        $master = DB::table('master_stock_in')->insertGetId([
            'reference_number' => 'REF-001',
            'stock_movement_type' => 'in',
            'stock_in_type' => 'purchase',
            'stock_in_date' => now()->format('Y-m-d'),
        ]);

        $purchase = DB::table('stock_purchase')->insertGetId([
            'master_stock_in_id' => $master,
            'invoice_number' => 'INV-001',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        DB::table('stock_purchase_items')->insert([
            'stock_in_purchase_id' => $purchase,
            'location_id' => $warehouse->id,
            'product' => $product1->id,
            'batch' => 'BATCH-POT-001',
            'quantity' => 100.00,
            'unit' => 'kg',
            'unit_cost' => 20.00,
            'total' => 2000.00,
            'grade' => 'Unsorted',
        ]);

        WarehouseInventory::create([
            'warehouse_id' => $warehouse->id,
            'batch' => 'BATCH-POT-001',
            'product_id' => $product1->id,
            'grade' => 'Unsorted',
            'qty' => 100.00,
            'unit_cost' => 20.00,
        ]);

        // 5. Segregate 30 kg of Potato BATCH-POT-001 into Grade A (unit cost 25.00)
        $segregation = StockSegregation::create([
            'reference_no' => 'SEG-001',
            'location_id' => $warehouse->id,
            'product_id' => $product1->id,
            'parent_batch_code' => 'BATCH-POT-001',
            'parent_quantity' => 100.00,
            'segregation_date' => now()->format('Y-m-d'),
        ]);

        StockSegregationItem::create([
            'stock_segregation_id' => $segregation->id,
            'grade' => 'Grade A',
            'quantity' => 30.00,
            'unit' => 'kg',
            'unit_cost' => 25.00,
        ]);

        // Test listing inventory via AJAX without filters
        $response = $this->getJson("/warehouse/overview/inventory?warehouse_id={$warehouse->id}");
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);

        // Verify Unsorted item
        $unsorted = collect($data)->firstWhere('grade', 'Unsorted');
        $this->assertNotNull($unsorted);
        $this->assertEquals(70.00, $unsorted['qty']);
        $this->assertEquals(20.00, $unsorted['unit_cost']);
        $this->assertEquals(1400.00, $unsorted['total_value']);

        // Verify Grade A item
        $gradeA = collect($data)->firstWhere('grade', 'Grade A');
        $this->assertNotNull($gradeA);
        $this->assertEquals(30.00, $gradeA['qty']);
        $this->assertEquals(25.00, $gradeA['unit_cost']);
        $this->assertEquals(750.00, $gradeA['total_value']);

        // Test filtering by grade = 'Grade A'
        $response = $this->getJson("/warehouse/overview/inventory?warehouse_id={$warehouse->id}&grade=Grade A");
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Grade A', $data[0]['grade']);

        // Test search filter = 'POT'
        $response = $this->getJson("/warehouse/overview/inventory?warehouse_id={$warehouse->id}&search=POT");
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }
}
