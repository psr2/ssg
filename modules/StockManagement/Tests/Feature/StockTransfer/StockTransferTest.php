<?php

namespace Modules\StockManagement\Tests\Feature\StockTransfer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;
use Modules\ShopManagement\Models\ShopInventory;
use Modules\StockManagement\Models\StockSummary\StockSummary;
use Modules\StockLedger\Models\StockLedgerEntry;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful stock transfer operation from Warehouse to Shop.
     */
    public function test_stock_transfer_successful(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create([
            'name' => 'Tomato',
            'abbreviation' => 'TM',
            'unit_id' => $unit->id
        ]);
        $fromLocation = LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WH'
        ]);
        $toLocation = LocationModel::factory()->create([
            'type' => 'shop',
            'abbreviation' => 'SH'
        ]);
        $grade = ProductGrade::factory()->create([
            'name' => 'Grade A',
            'code' => 'GA',
            'is_active' => true
        ]);

        // 1. Perform a purchase to establish stock in the warehouse
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-20260714-88888',
            'movement_date' => '2026-07-14',
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'grade' => $grade->name,
                    'location_id' => $fromLocation->id,
                    'quantity' => 100.00,
                    'unit' => 'Kg',
                    'unit_cost' => 10.00,
                    'total' => 1000.00,
                    'remarks' => 'Initial purchase',
                    'invoice_number' => 'INV-888',
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

        // 2. Perform Stock Transfer (from Warehouse to Shop)
        $transferPayload = [
            't_transferDate' => '2026-07-14',
            't_transferType' => 'inter',
            't_fromLocation' => (string) $fromLocation->id,
            't_toLocation'   => (string) $toLocation->id,
            't_product_name' => (string) $product->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $grade->name,
            't_quantity'     => 40.00,
            't_unit'         => 'Kg',
            't_textarea'     => 'Transferring 40 Kg of Tomato to Shop',
        ];

        $response = $this->postJson('/stock-transfer', $transferPayload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Stock transfer successful'
            ]);

        // 3. Assert Stock Ledger Entries (Both Out and In)
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'TRANSFER_OUT',
            'location_id'      => $fromLocation->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'grade'            => $grade->name,
            'quantity'         => -40.00,
            'unit'             => 'Kg',
            'unit_cost'        => 10.00,
        ]);

        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'TRANSFER_IN',
            'location_id'      => $toLocation->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'grade'            => $grade->name,
            'quantity'         => 40.00,
            'unit'             => 'Kg',
            'unit_cost'        => 10.00,
        ]);

        // 4. Assert Source Warehouse Cached balance is decremented
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $fromLocation->id,
            'batch'        => $batchCode,
            'product_id'   => $product->id,
            'grade'        => $grade->name,
            'qty'          => 60.00, // 100.00 - 40.00
        ]);

        // 5. Assert Destination Shop Cached balance is created/incremented
        $this->assertDatabaseHas('shop_inventory', [
            'shop_id'    => $toLocation->id,
            'batch_id'   => $batchCode,
            'product_id' => $product->id,
            'grade'      => $grade->name,
            'qty'        => 40.00,
        ]);

        // 6. Assert Stock Summaries are synchronized
        $this->assertDatabaseHas('stock_summary', [
            'location_id' => $fromLocation->id,
            'batch_id'    => $batchCode,
            'product_id'  => $product->id,
            'grade'       => $grade->name,
            'current_qty' => 60.00,
        ]);

        $this->assertDatabaseHas('stock_summary', [
            'location_id' => $toLocation->id,
            'batch_id'    => $batchCode,
            'product_id'  => $product->id,
            'grade'       => $grade->name,
            'current_qty' => 40.00,
        ]);
    }
}
