<?php

namespace Modules\StockLedger\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockLedger\Services\StockLedgerService;
use Modules\StockLedger\Models\StockLedgerEntry;
use Modules\StockManagement\Models\StockIn\MasterStockIn;
use Modules\StockManagement\Models\StockIn\StockPurchase;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;

class StockLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockLedgerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StockLedgerService::class);
    }

    /**
     * Helper to create necessary database rows to satisfy foreign keys.
     */
    protected function createPurchaseItemReference($location, $product, string $batchCode, ?string $grade): void
    {
        $master = MasterStockIn::create([
            'reference_number'    => 'REF-' . uniqid(),
            'stock_in_type'       => 'purchase',
            'stock_movement_type' => 'in',
            'stock_in_date'       => now(),
        ]);

        $purchase = StockPurchase::create([
            'master_stock_in_id' => $master->id,
            'vendor'             => 'Test Vendor',
            'invoice_number'     => 'INV-' . uniqid(),
            'purchase_date'      => now(),
            'batch_code'         => $batchCode,
        ]);

        StockPurchaseItem::create([
            'stock_in_purchase_id' => $purchase->id,
            'location_id'          => $location->id,
            'product'              => $product->id,
            'batch'                => $batchCode,
            'grade'                => $grade,
            'quantity'             => 1000,
            'unit'                 => 'Kg',
            'unit_cost'            => 10,
            'total'                => 10000,
        ]);
    }

    /**
     * Test recording a purchase (positive ledger entry) for a warehouse.
     */
    public function test_record_purchase_updates_warehouse_cache(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create(['unit_id' => $unit->id]);
        $warehouse = LocationModel::factory()->create(['type' => 'warehouse']);

        $batchCode = 'BATCH001';
        $grade = 'Grade A';

        // Satisfy DB constraints
        $this->createPurchaseItemReference($warehouse, $product, $batchCode, $grade);

        $data = [
            'transaction_type' => 'PURCHASE',
            'location_id'      => $warehouse->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'grade'            => $grade,
            'quantity'         => 100.00,
            'unit'             => 'Kg',
            'unit_cost'        => 15.00,
            'remarks'          => 'Purchase test',
        ];

        // 1. Record Entry
        $entry = $this->service->recordEntry($data);

        // Assert ledger entry created
        $this->assertInstanceOf(StockLedgerEntry::class, $entry);
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'PURCHASE',
            'location_id'      => $warehouse->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'grade'            => $grade,
            'quantity'         => 100.00,
        ]);

        // Assert warehouse_inventory cached balance is created
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $warehouse->id,
            'batch'        => $batchCode,
            'product_id'   => $product->id,
            'grade'        => $grade,
            'qty'          => 100.00,
            'unit_cost'    => 15.00,
        ]);

        // Assert stock_summary cached balance is created
        $this->assertDatabaseHas('stock_summary', [
            'location_id' => $warehouse->id,
            'batch_id'    => $batchCode,
            'product_id'  => $product->id,
            'grade'       => $grade,
            'current_qty' => 100.00,
        ]);

        // 2. Perform second entry (incrementing)
        $this->service->recordEntry([
            'transaction_type' => 'PURCHASE',
            'location_id'      => $warehouse->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'grade'            => $grade,
            'quantity'         => 50.00,
            'unit'             => 'Kg',
            'unit_cost'        => 15.00,
        ]);

        // Assert warehouse_inventory cached balance is incremented
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $warehouse->id,
            'batch'        => $batchCode,
            'qty'          => 150.00,
        ]);

        // Assert stock_summary is incremented
        $this->assertDatabaseHas('stock_summary', [
            'location_id' => $warehouse->id,
            'batch_id'    => $batchCode,
            'current_qty' => 150.00,
        ]);
    }

    /**
     * Test recording an entry for a shop.
     */
    public function test_record_entry_updates_shop_cache(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create(['unit_id' => $unit->id]);
        $shop = LocationModel::factory()->create(['type' => 'shop']);

        $batchCode = 'BATCH002';
        $grade = 'Grade B';

        // Satisfy DB constraints
        $this->createPurchaseItemReference($shop, $product, $batchCode, $grade);

        $data = [
            'transaction_type' => 'PURCHASE',
            'location_id'      => $shop->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'grade'            => $grade,
            'quantity'         => 80.00,
            'unit'             => 'Kg',
            'unit_cost'        => 12.00,
        ];

        $this->service->recordEntry($data);

        // Assert shop_inventory cached balance is created
        $this->assertDatabaseHas('shop_inventory', [
            'shop_id'    => $shop->id,
            'batch_id'   => $batchCode,
            'product_id' => $product->id,
            'grade'      => $grade,
            'qty'        => 80.00,
        ]);

        // Assert stock_summary cached balance is created
        $this->assertDatabaseHas('stock_summary', [
            'location_id' => $shop->id,
            'batch_id'    => $batchCode,
            'product_id'  => $product->id,
            'grade'       => $grade,
            'current_qty' => 80.00,
        ]);
    }

    /**
     * Test negative ledger entries (deductions) update the cached balances.
     */
    public function test_negative_entry_decrements_cache(): void
    {
        $unit = UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create(['unit_id' => $unit->id]);
        $warehouse = LocationModel::factory()->create(['type' => 'warehouse']);

        $batchCode = 'BATCH003';
        $grade = 'Grade A';

        // Satisfy DB constraints
        $this->createPurchaseItemReference($warehouse, $product, $batchCode, $grade);

        // First add stock
        $this->service->recordEntry([
            'transaction_type' => 'PURCHASE',
            'location_id'      => $warehouse->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'grade'            => $grade,
            'quantity'         => 100.00,
            'unit'             => 'Kg',
            'unit_cost'        => 10.00,
        ]);

        // Deduct stock via negative ledger entry
        $this->service->recordEntry([
            'transaction_type' => 'STOCK_OUT',
            'location_id'      => $warehouse->id,
            'product_id'       => $product->id,
            'batch_code'       => $batchCode,
            'grade'            => $grade,
            'quantity'         => -40.00, // Negative delta
            'unit'             => 'Kg',
            'unit_cost'        => 10.00,
        ]);

        // Assert warehouse_inventory cached balance is decremented
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $warehouse->id,
            'batch'        => $batchCode,
            'qty'          => 60.00, // 100.00 - 40.00
        ]);

        // Assert stock_summary is decremented
        $this->assertDatabaseHas('stock_summary', [
            'location_id' => $warehouse->id,
            'batch_id'    => $batchCode,
            'current_qty' => 60.00, // 100.00 - 40.00
        ]);
    }
}
