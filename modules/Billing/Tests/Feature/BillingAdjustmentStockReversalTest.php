<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Services\BillingAdjustmentService;
use Modules\StockLedger\Services\StockLedgerService;
use Modules\Locations\Models\LocationModel as Location;
use Modules\Warehouse\Models\WarehouseCustomer;
use Modules\Warehouse\Models\WarehouseSale;
use Modules\Warehouse\Models\WarehouseSaleItem;
use Modules\Warehouse\Repositories\WarehouseSaleRepository;
use Modules\StockManagement\Models\StockIn\StockPurchase;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;

class BillingAdjustmentStockReversalTest extends TestCase
{
    use RefreshDatabase;

    protected BillingAdjustmentService $billingService;
    protected StockLedgerService $ledgerService;
    protected WarehouseSaleRepository $saleRepository;
    protected Location $warehouse;
    protected WarehouseCustomer $customer;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingService = $this->app->make(BillingAdjustmentService::class);
        $this->ledgerService = $this->app->make(StockLedgerService::class);
        $this->saleRepository = $this->app->make(WarehouseSaleRepository::class);

        // Create warehouse
        $this->warehouse = Location::create([
            'name' => 'Feature Test Warehouse',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        // Create customer
        $this->customer = WarehouseCustomer::create([
            'name' => 'Feature Test Customer',
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Create unit
        $unit = \Modules\Inventory\Models\UnitOfMeasurement::firstOrCreate(
            ['abbreviation' => 'kg'],
            ['name' => 'Kilogram']
        );

        // Create product
        $this->product = \Modules\Inventory\Models\Products::firstOrCreate(
            ['sku' => 'COFFEE-123'],
            [
                'name' => 'Test Coffee',
                'abbreviation' => 'COF',
                'unit_id' => $unit->id
            ]
        );
    }

    /**
     * Helper to seed initial stock and satisfy stock_purchase_items foreign key constraints.
     */
    protected function seedStock(string $batchCode, float $qty, string $grade = 'A'): void
    {
        $master = \Modules\StockManagement\Models\StockIn\MasterStockIn::create([
            'reference_number' => 'REF-' . $batchCode,
            'stock_in_type' => 'purchase',
            'stock_movement_type' => 'in',
            'stock_in_date' => now()->format('Y-m-d'),
        ]);

        $purchase = \Modules\StockManagement\Models\StockIn\StockPurchase::create([
            'master_stock_in_id' => $master->id,
            'vendor' => 'Test Vendor',
            'invoice_number' => 'INV-' . $batchCode,
            'purchase_date' => now()->format('Y-m-d'),
            'batch_code' => $batchCode,
        ]);

        \Modules\StockManagement\Models\StockIn\StockPurchaseItem::create([
            'stock_in_purchase_id' => $purchase->id,
            'location_id' => $this->warehouse->id,
            'product' => $this->product->id,
            'batch' => $batchCode,
            'grade' => $grade,
            'quantity' => $qty,
            'unit' => 'kg',
            'unit_cost' => 10.00,
            'total' => $qty * 10.00,
        ]);

        // Only record ledger entry if quantity > 0 to avoid zero-purchase noise
        if ($qty > 0) {
            $this->ledgerService->recordEntry([
                'transaction_type' => 'PURCHASE',
                'location_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
                'batch_code' => $batchCode,
                'grade' => $grade,
                'quantity' => $qty,
                'unit' => 'kg',
                'unit_cost' => 10.00,
                'reference_id' => $purchase->id,
                'reference_type' => 'stock_purchases',
            ]);
        }
    }

    /**
     * Test that adjusting a warehouse sale bill to 0.00 cancels the sale
     * and appends a SALE_RETURN compensating entry to restore stock.
     */
    public function test_warehouse_sale_cancellation_restores_stock(): void
    {
        // 1. Initial Purchase to stock the warehouse
        $this->seedStock('BATCH-A1', 100.00, 'A');

        // Confirm inventory cache is at 100
        $this->assertEquals(100.00, $this->ledgerService->getAvailableStock($this->warehouse->id, $this->product->id, 'BATCH-A1', 'A'));

        // 2. Perform a warehouse sale of 30.00 units
        $salePayload = [
            'shop_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount_paid' => 0.00,
            'payment_mode' => 'cash',
            'bill_no' => 'BILL-1001',
            'items' => [
                [
                    'product' => $this->product->id,
                    'batch_code' => 'BATCH-A1',
                    'grade' => 'A',
                    'quantity' => 30.00,
                    'unit' => 'kg',
                    'unit_price' => 10.00,
                    'total_price' => 300.00,
                ]
            ]
        ];

        $this->saleRepository->handle($salePayload, 300.00);

        // Assert sale was recorded and stock is reduced to 70
        $sale = WarehouseSale::orderBy('id', 'desc')->first();
        $this->assertNotNull($sale);
        $this->assertEquals('active', $sale->status);
        $this->assertEquals(70.00, $this->ledgerService->getAvailableStock($this->warehouse->id, $this->product->id, 'BATCH-A1', 'A'));

        // 3. Adjust the bill to 0.00 (cancellation)
        $this->billingService->createAdjustment([
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 0.00,
            'reason' => 'cancelled_order',
            'remarks' => 'Customer cancelled order before dispatch',
            'adjusted_by' => 1,
        ]);

        // 4. Assertions
        $sale->refresh();
        $this->assertEquals('cancelled', $sale->status);
        $this->assertEquals(0.00, $sale->total_amount);
        $this->assertEquals(0.00, $sale->due_amount);

        // Verify stock is restored to 100.00
        $this->assertEquals(100.00, $this->ledgerService->getAvailableStock($this->warehouse->id, $this->product->id, 'BATCH-A1', 'A'));

        // Assert database has compensating ledger entry
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'SALE_RETURN',
            'location_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_code' => 'BATCH-A1',
            'grade' => 'A',
            'quantity' => 30.00,
        ]);
    }
}
