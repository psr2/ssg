<?php

namespace Modules\ShopManagement\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;

class ShopBatchCodeSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test shop batch code search returns available batches in shop location.
     */
    public function test_shop_batch_code_search_returns_available_shop_batches(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create([
            'name' => 'Cardamom',
            'abbreviation' => 'CD',
            'unit_id' => $unit->id
        ]);
        $whLocation = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WH'
        ]);
        $shopLocation = LocationModel::factory()->create([
            'type' => 'shop',
            'abbreviation' => 'SH'
        ]);
        $grade = ProductGrade::factory()->create([
            'name' => 'Grade A',
            'code' => 'GA',
            'is_active' => true
        ]);

        // 1. Initial purchase into Warehouse
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-20260721-00001',
            'movement_date' => '2026-07-21',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => $grade->name,
                    'location_id' => $whLocation->id,
                    'quantity' => 100.00,
                    'unit' => 'Kg',
                    'unit_cost' => 500.00,
                    'total' => 50000.00,
                    'remarks' => 'Initial purchase for shop test',
                    'invoice_number' => 'INV-SHOP-01',
                    'vendor' => 'Cardamom Farmer',
                    'purchase_date' => '2026-07-21',
                ]
            ]
        ];

        $purchaseResponse = $this->postJson('/stock-in-entry', $purchasePayload);
        $purchaseResponse->assertStatus(201);

        $purchaseItem = StockPurchaseItem::first();
        $this->assertNotNull($purchaseItem);
        $batchCode = $purchaseItem->batch;

        // 2. Transfer 40 Kg from Warehouse to Shop
        $transferPayload = [
            't_transferDate' => '2026-07-21',
            't_transferType' => 'inter',
            't_fromLocation' => (string) $whLocation->id,
            't_toLocation'   => (string) $shopLocation->id,
            't_product_name' => (string) $product->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $grade->name,
            't_quantity'     => 40.00,
            't_unit'         => 'Kg',
            't_textarea'     => 'Transfer to shop',
        ];

        $transferResponse = $this->postJson('/stock-transfer', $transferPayload);
        $transferResponse->assertStatus(200);

        // 3. Search batch code via Shop endpoint /shop/search-batch-code
        $response = $this->postJson('/shop/search-batch-code', [
            'location' => $shopLocation->id,
            'product_listing' => $product->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'batch_code' => $batchCode,
                'location_id' => $shopLocation->id,
                'product_id' => $product->id,
                'grade' => $grade->name,
                'available_qty' => 40,
            ]);
    }
}
