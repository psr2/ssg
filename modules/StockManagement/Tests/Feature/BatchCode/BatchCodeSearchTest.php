<?php

namespace Modules\StockManagement\Tests\Feature\BatchCode;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;

class BatchCodeSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful batch code search returns available stock details.
     */
    public function test_batch_code_search_returns_available_batches(): void
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

        // 1. Setup stock (100 Kg) in warehouse
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-20260714-11111',
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
                    'invoice_number' => 'INV-111',
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

        // 2. Perform Batch Code Search
        $response = $this->postJson('/search-batch-code', [
            'location' => $location->id,
            'product_listing' => $product->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'batch_code' => $batchCode,
                'location_id' => $location->id,
                'product_id' => $product->id,
                'grade' => $grade->name,
                'product' => 'Tomato',
                'available_qty' => 100,
            ]);
    }

    /**
     * Test batch code search filters out batches with 0 available quantity.
     */
    public function test_batch_code_search_excludes_zero_quantity_batches(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create([
            'name' => 'Onion',
            'abbreviation' => 'ON',
            'unit_id' => $unit->id
        ]);
        $location = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WH'
        ]);
        $destLocation = LocationModel::factory()->create([
            'type' => 'shop',
            'abbreviation' => 'SH'
        ]);
        $grade = ProductGrade::factory()->create([
            'name' => 'Grade B',
            'code' => 'GB',
            'is_active' => true
        ]);

        // 1. Setup stock (50 Kg) in warehouse
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-20260714-22222',
            'movement_date' => '2026-07-14',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => $grade->name,
                    'location_id' => $location->id,
                    'quantity' => 50.00,
                    'unit' => 'Kg',
                    'unit_cost' => 12.00,
                    'total' => 600.00,
                    'remarks' => 'Initial purchase',
                    'invoice_number' => 'INV-222',
                    'vendor' => 'Vendor XYZ',
                    'purchase_date' => '2026-07-14',
                ]
            ]
        ];

        $purchaseResponse = $this->postJson('/stock-in-entry', $purchasePayload);
        $purchaseResponse->assertStatus(201);

        $purchaseItem = StockPurchaseItem::first();
        $this->assertNotNull($purchaseItem);
        $batchCode = $purchaseItem->batch;

        // 2. Transfer all 50 Kg to shop (leaving 0 Kg in warehouse)
        $transferPayload = [
            't_transferDate' => '2026-07-14',
            't_transferType' => 'inter',
            't_fromLocation' => (string) $location->id,
            't_toLocation'   => (string) $destLocation->id,
            't_product_name' => (string) $product->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $grade->name,
            't_quantity'     => 50.00,
            't_unit'         => 'Kg',
            't_textarea'     => 'Transfer all stock',
        ];

        $transferResponse = $this->postJson('/stock-transfer', $transferPayload);
        $transferResponse->assertStatus(200);

        // 3. Perform Batch Code Search for warehouse (should be empty for this product/batch)
        $response = $this->postJson('/search-batch-code', [
            'location' => $location->id,
            'product_listing' => $product->id,
        ]);

        $response->assertStatus(200);
        $this->assertEmpty($response->json());

        // 4. Perform Batch Code Search for shop (should show the 50 Kg available)
        $responseShop = $this->postJson('/search-batch-code', [
            'location' => $destLocation->id,
            'product_listing' => $product->id,
        ]);

        $responseShop->assertStatus(200)
            ->assertJsonFragment([
                'batch_code' => $batchCode,
                'location_id' => $destLocation->id,
                'available_qty' => 50,
            ]);
    }


    public function test_batch_code_search_returns_batches_with_null_grade(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create([
            'name' => 'Cabbage',
            'abbreviation' => 'CB',
            'unit_id' => $unit->id
        ]);
        $location = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WH'
        ]);

        // 1. Register a purchase entry with a valid grade first
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-20260714-33333',
            'movement_date' => '2026-07-14',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => 'Unsorted',
                    'location_id' => $location->id,
                    'quantity' => 80.00,
                    'unit' => 'Kg',
                    'unit_cost' => 5.00,
                    'total' => 400.00,
                    'vendor' => 'Test Vendor',
                    'invoice_number' => 'INV-123',
                    'purchase_date' => '2026-07-14',
                    'remarks' => 'Cabbage purchase'
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $purchaseItem = StockPurchaseItem::where('product', $product->id)->first();
        $this->assertNotNull($purchaseItem);
        $batchCode = $purchaseItem->batch;

        // 2. Update the grade to empty string directly in the database
        \DB::table('warehouse_inventory')->where('product_id', $product->id)->update(['grade' => '']);
        \DB::table('stock_ledger_entries')->where('product_id', $product->id)->update(['grade' => '']);
        \DB::table('stock_purchase_items')->where('product', $product->id)->update(['grade' => '']);

        // 3. Search for batch code
        $searchResponse = $this->postJson('/search-batch-code', [
            'location' => $location->id,
            'product_listing' => $product->id,
        ]);

        $searchResponse->assertStatus(200)
            ->assertJsonFragment([
                'batch_code' => $batchCode,
                'location_id' => $location->id,
                'available_qty' => 80,
                'grade' => '',
            ]);
    }
}
