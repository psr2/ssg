<?php

namespace Tests\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchase;
use Modules\Warehouse\Models\WarehouseSale;
use Modules\Warehouse\Models\WarehouseCustomer;
use Modules\Billing\Services\BillingAdjustmentService;
use Modules\StockLedger\Services\StockLedgerService;
use App\Models\User;

class WarehouseBillingAdjustmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $coffee;
    protected $gradeA;
    protected $unitKg;
    protected $warehouseA;
    protected $warehouseB;
    protected $customer;
    protected $user;
    protected BillingAdjustmentService $billingService;
    protected StockLedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->billingService = $this->app->make(BillingAdjustmentService::class);
        $this->ledgerService = $this->app->make(StockLedgerService::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->unitKg = UnitOfMeasurement::firstOrCreate(
            ['abbreviation' => 'kg'],
            ['name' => 'Kilogram']
        );

        $this->coffee = Products::firstOrCreate(
            ['sku' => 'cof_test'],
            [
                'name' => 'Coffee Test',
                'abbreviation' => 'cft',
                'unit_id' => $this->unitKg->id
            ]
        );

        $this->gradeA = ProductGrade::firstOrCreate(
            ['code' => 'A'],
            [
                'name' => 'Grade A',
                'is_active' => true
            ]
        );

        $this->warehouseA = LocationModel::create([
            'name' => 'Warehouse A',
            'type' => 'warehouse',
            'abbreviation' => 'WHA',
            'status' => 'active'
        ]);

        $this->warehouseB = LocationModel::create([
            'name' => 'Warehouse B',
            'type' => 'warehouse',
            'abbreviation' => 'WHB',
            'status' => 'active'
        ]);

        $this->customer = WarehouseCustomer::create([
            'name' => 'Workflow Customer',
            'warehouse_id' => $this->warehouseB->id,
        ]);
    }

    /**
     * Workflow: Stock purchase in Warehouse A -> moved to Warehouse B -> sold from warehouse B ->
     * verify stock reduced -> bill adjusted to 0 -> stock quantity restored and verified at expected warehouses.
     */
    public function test_purchase_transfer_sale_adjustment_workflow(): void
    {
        // 1. Purchase: Buy 100.00 kg of Coffee at Warehouse A
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WFA',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->coffee->id,
                    'product_name' => $this->coffee->name,
                    'grade' => $this->gradeA->code,
                    'location_id' => $this->warehouseA->id,
                    'quantity' => 100.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 1000.00,
                    'remarks' => 'Purchase WFA',
                    'invoice_number' => 'INV-WFA',
                    'vendor' => 'Vendor Test',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        
        $purchase = StockPurchase::first();
        $this->assertNotNull($purchase);
        $batchCode = $purchase->batch_code;

        // Verify initial stock of Warehouse A
        $this->assertEquals(100.00, $this->ledgerService->getAvailableStock($this->warehouseA->id, $this->coffee->id, $batchCode, $this->gradeA->code));
        $this->assertEquals(0.00, $this->ledgerService->getAvailableStock($this->warehouseB->id, $this->coffee->id, $batchCode, $this->gradeA->code));

        // 2. Transfer: Move 40.00 kg from Warehouse A to Warehouse B
        $transferResponse = $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->warehouseA->id,
            't_toLocation'   => (string) $this->warehouseB->id,
            't_product_name' => (string) $this->coffee->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeA->code,
            't_quantity'     => 40.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transfer WFA to WFB',
        ]);
        $transferResponse->assertStatus(200);

        // Verify stock post-transfer
        $this->assertEquals(60.00, $this->ledgerService->getAvailableStock($this->warehouseA->id, $this->coffee->id, $batchCode, $this->gradeA->code));
        $this->assertEquals(40.00, $this->ledgerService->getAvailableStock($this->warehouseB->id, $this->coffee->id, $batchCode, $this->gradeA->code));

        // 3. Sale: Sell 30.00 kg from Warehouse B
        $saleResponse = $this->postJson('/warehouse/sale/store', [
            'shop_id' => $this->warehouseB->id, // warehouse_id from UI is mapped to shop_id in request payload
            'customer_name' => 'Workflow Customer',
            'customer_id' => $this->customer->id,
            'payment_status' => 'unpaid',
            'payment_date' => now()->format('Y-m-d'),
            'amount_paid' => 0.00,
            'payment_mode' => 'cash',
            'bill_no' => 'BILL-WFB-1',
            'items' => [
                [
                    'product' => $this->coffee->id,
                    'batch_code' => $batchCode,
                    'grade' => $this->gradeA->code,
                    'quantity' => 30.00,
                    'unit' => 'kg',
                    'unit_price' => 15.00,
                    'total_price' => 450.00,
                ]
            ]
        ]);
        $saleResponse->assertStatus(200);

        // Verify intermediate stock levels
        $this->assertEquals(60.00, $this->ledgerService->getAvailableStock($this->warehouseA->id, $this->coffee->id, $batchCode, $this->gradeA->code));
        $this->assertEquals(10.00, $this->ledgerService->getAvailableStock($this->warehouseB->id, $this->coffee->id, $batchCode, $this->gradeA->code));

        // Get the recorded sale
        $sale = WarehouseSale::orderBy('id', 'desc')->first();
        $this->assertNotNull($sale);
        $this->assertEquals('active', $sale->status);
        $this->assertEquals(450.00, $sale->total_amount);

        // Assert SALE ledger entry unit and unit_cost matches the original purchase at Warehouse A
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'SALE',
            'location_id'      => $this->warehouseB->id,
            'product_id'       => $this->coffee->id,
            'batch_code'       => $batchCode,
            'quantity'         => -30.00,
            'unit'             => 'kg',
            'unit_cost'        => 10.00,
        ]);

        // 4. Adjust the bill to 0.00 (cancellation)
        $adjustmentResponse = $this->postJson('/billing-adjustments', [
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 0.00,
            'reason' => 'voided_sale',
            'remarks' => 'Adjusted bill to zero',
        ]);
        $adjustmentResponse->assertStatus(201);

        // 5. Final stock level verification
        // Warehouse A should remain unaffected (60.00 kg)
        $this->assertEquals(60.00, $this->ledgerService->getAvailableStock($this->warehouseA->id, $this->coffee->id, $batchCode, $this->gradeA->code));

        // Warehouse B should have the 30.00 kg restored (10.00 + 30.00 = 40.00 kg)
        $this->assertEquals(40.00, $this->ledgerService->getAvailableStock($this->warehouseB->id, $this->coffee->id, $batchCode, $this->gradeA->code));

        // Sale record should be marked as cancelled
        $sale->refresh();
        $this->assertEquals('cancelled', $sale->status);
        $this->assertEquals(0.00, $sale->total_amount);
        $this->assertEquals(0.00, $sale->due_amount);

        // Assert SALE_RETURN ledger entry unit and unit_cost matches the original purchase at Warehouse A
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'SALE_RETURN',
            'location_id'      => $this->warehouseB->id,
            'product_id'       => $this->coffee->id,
            'batch_code'       => $batchCode,
            'quantity'         => 30.00,
            'unit'             => 'kg',
            'unit_cost'        => 10.00,
        ]);
    }

    /**
     * Workflow: Purchase A -> Move to B -> Sell from B -> Check Dashboard
     * 1. Check dashboard dues show expected values.
     * 2. Adjust sale to void -> Check dashboard dues are 0.
     * 3. Sell again -> Adjust to partial value -> Check dashboard dues update to partial.
     */
    public function test_warehouse_purchase_transfer_sale_adjustment_dashboard_flow(): void
    {
        $dashboardService = $this->app->make(\Modules\Dashboard\Services\Dashboard\GetDashboardData::class);

        // 1. Initial Dashboard Check: totalReceivables should be 0.00
        $data = $dashboardService->execute();
        $this->assertEquals(0.00, $data['totalReceivables']);

        // 2. Purchase: Buy 100.00 kg of Coffee at Warehouse A
        $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-DASH-1',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->coffee->id,
                    'product_name' => $this->coffee->name,
                    'grade' => $this->gradeA->code,
                    'location_id' => $this->warehouseA->id,
                    'quantity' => 100.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 1000.00,
                    'remarks' => 'Purchase',
                    'invoice_number' => 'INV-DASH-1',
                    'vendor' => 'Vendor Test',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ])->assertStatus(201);

        // Verify dashboard reflects purchase at Warehouse A
        $data = $dashboardService->execute();
        $this->assertEquals(100.00, $data['warehouse']);
        $this->assertEquals(100.00, $data['warehouseStocks'][$this->warehouseA->id]);
        $this->assertEquals(0.00, $data['warehouseStocks'][$this->warehouseB->id] ?? 0.00);
        
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // 3. Transfer: Move 50.00 kg to Warehouse B
        $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->warehouseA->id,
            't_toLocation'   => (string) $this->warehouseB->id,
            't_product_name' => (string) $this->coffee->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeA->code,
            't_quantity'     => 50.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transfer WHA to WFB',
        ])->assertStatus(200);

        // Verify dashboard reflects the stock transfer between warehouses
        $data = $dashboardService->execute();
        $this->assertEquals(100.00, $data['warehouse']); // Total remains 100
        $this->assertEquals(50.00, $data['warehouseStocks'][$this->warehouseA->id]); // A is reduced to 50
        $this->assertEquals(50.00, $data['warehouseStocks'][$this->warehouseB->id]); // B is increased to 50

        // Verify stock alerts after transfer
        $data = $dashboardService->execute();
        $alerts = collect($data['stockAlerts'] ?? []);
        
        // Find alert for Warehouse A
        $alertA = $alerts->firstWhere('location_name', 'Warehouse A');
        $this->assertNotNull($alertA);
        $this->assertEquals(50.00, $alertA['current_qty']);
        $this->assertEquals(100.00, $alertA['received']);
        $this->assertEquals(50.0, $alertA['percentage']);

        // Find alert for Warehouse B
        $alertB = $alerts->firstWhere('location_name', 'Warehouse B');
        $this->assertNotNull($alertB);
        $this->assertEquals(50.00, $alertB['current_qty']);
        $this->assertEquals(50.00, $alertB['received']); // Correctly resolves incoming transfer received quantity
        $this->assertEquals(100.0, $alertB['percentage']);

        // 4. Sale 1: Sell 20.00 kg from Warehouse B for 300.00 (unpaid)
        $this->postJson('/warehouse/sale/store', [
            'shop_id' => $this->warehouseB->id,
            'customer_name' => 'Dashboard Customer',
            'customer_id' => $this->customer->id,
            'payment_status' => 'unpaid',
            'payment_date' => now()->format('Y-m-d'),
            'amount_paid' => 0.00,
            'payment_mode' => 'cash',
            'bill_no' => 'BILL-DASH-1',
            'items' => [
                [
                    'product' => $this->coffee->id,
                    'batch_code' => $batchCode,
                    'grade' => $this->gradeA->code,
                    'quantity' => 20.00,
                    'unit' => 'kg',
                    'unit_price' => 15.00,
                    'total_price' => 300.00,
                ]
            ]
        ])->assertStatus(200);

        // Dashboard Check: totalReceivables should now be 300.00
        $data = $dashboardService->execute();
        $this->assertEquals(300.00, $data['totalReceivables']);
        $this->assertEquals(300.00, $data['warehouseDues'][$this->warehouseB->id]);
        // Stock should reflect Sale 1: A = 50, B = 30
        $this->assertEquals(80.00, $data['warehouse']);
        $this->assertEquals(50.00, $data['warehouseStocks'][$this->warehouseA->id]);
        $this->assertEquals(30.00, $data['warehouseStocks'][$this->warehouseB->id]);

        $sale1 = WarehouseSale::orderBy('id', 'desc')->first();

        // 5. Adjust Sale 1 to Void (0.00)
        $this->postJson('/billing-adjustments', [
            'sale_type' => 'warehouse',
            'sale_id' => $sale1->id,
            'new_amount' => 0.00,
            'reason' => 'voided_sale',
            'remarks' => 'Adjusted to zero',
        ])->assertStatus(201);

        // Dashboard Check: totalReceivables should go back to 0.00
        $data = $dashboardService->execute();
        $this->assertEquals(0.00, $data['totalReceivables']);
        $this->assertEquals(0.00, $data['warehouseDues'][$this->warehouseB->id]);
        // Stock should be restored after void adjustment: A = 50, B = 50
        $this->assertEquals(100.00, $data['warehouse']);
        $this->assertEquals(50.00, $data['warehouseStocks'][$this->warehouseA->id]);
        $this->assertEquals(50.00, $data['warehouseStocks'][$this->warehouseB->id]);

        // 6. Sale 2: Sell 10.00 kg from Warehouse B for 150.00 (unpaid)
        $this->postJson('/warehouse/sale/store', [
            'shop_id' => $this->warehouseB->id,
            'customer_name' => 'Dashboard Customer',
            'customer_id' => $this->customer->id,
            'payment_status' => 'unpaid',
            'payment_date' => now()->format('Y-m-d'),
            'amount_paid' => 0.00,
            'payment_mode' => 'cash',
            'bill_no' => 'BILL-DASH-2',
            'items' => [
                [
                    'product' => $this->coffee->id,
                    'batch_code' => $batchCode,
                    'grade' => $this->gradeA->code,
                    'quantity' => 10.00,
                    'unit' => 'kg',
                    'unit_price' => 15.00,
                    'total_price' => 150.00,
                ]
            ]
        ])->assertStatus(200);

        // Dashboard Check: totalReceivables should be 150.00
        $data = $dashboardService->execute();
        $this->assertEquals(150.00, $data['totalReceivables']);
        // Stock should reflect Sale 2: A = 50, B = 40
        $this->assertEquals(90.00, $data['warehouse']);
        $this->assertEquals(50.00, $data['warehouseStocks'][$this->warehouseA->id]);
        $this->assertEquals(40.00, $data['warehouseStocks'][$this->warehouseB->id]);

        $sale2 = WarehouseSale::orderBy('id', 'desc')->first();

        // 7. Adjust Sale 2 to a partial value (e.g. 50.00)
        $this->postJson('/billing-adjustments', [
            'sale_type' => 'warehouse',
            'sale_id' => $sale2->id,
            'new_amount' => 50.00,
            'reason' => 'price_correction',
            'remarks' => 'Adjusted to 50.00',
        ])->assertStatus(201);

        // Dashboard Check: totalReceivables should now be 50.00
        $data = $dashboardService->execute();
        $this->assertEquals(50.00, $data['totalReceivables']);
        $this->assertEquals(50.00, $data['warehouseDues'][$this->warehouseB->id]);
        // Stock should remain unchanged after partial adjustment: A = 50, B = 40
        $this->assertEquals(90.00, $data['warehouse']);
        $this->assertEquals(50.00, $data['warehouseStocks'][$this->warehouseA->id]);
        $this->assertEquals(40.00, $data['warehouseStocks'][$this->warehouseB->id]);

        // Stock quantity should remain exactly 40.00 kg (not restored or modified)
        $this->assertEquals(40.00, $this->ledgerService->getAvailableStock($this->warehouseB->id, $this->coffee->id, $batchCode, $this->gradeA->code));
    }

    /**
     * Workflow: Decimal purchase -> Decimal transfer -> Decimal sale -> Dashboard and ledger check
     */
    public function test_purchase_transfer_sale_adjustment_dashboard_flow_with_decimals(): void
    {
        $dashboardService = $this->app->make(\Modules\Dashboard\Services\Dashboard\GetDashboardData::class);

        // 1. Initial Dashboard Check: totalReceivables should be 0.00
        $data = $dashboardService->execute();
        $this->assertEquals(0.00, $data['totalReceivables']);

        // 2. Purchase: Buy 102.58 kg of Coffee at Warehouse A with unit cost 20.05
        // Total cost: 102.58 * 20.05 = 2056.729 -> 2056.73
        $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-DEC-WFA',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->coffee->id,
                    'product_name' => $this->coffee->name,
                    'grade' => $this->gradeA->code,
                    'location_id' => $this->warehouseA->id,
                    'quantity' => 102.58,
                    'unit' => 'kg',
                    'unit_cost' => 20.05,
                    'total' => 2056.73,
                    'remarks' => 'Purchase decimal',
                    'invoice_number' => 'INV-DEC-WFA',
                    'vendor' => 'Vendor Test',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ])->assertStatus(201);

        // Verify dashboard reflects purchase at Warehouse A
        $data = $dashboardService->execute();
        $this->assertEquals(102.58, round($data['warehouse'], 2));
        $this->assertEquals(102.58, round($data['warehouseStocks'][$this->warehouseA->id], 2));
        
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // 3. Transfer: Move 58.02 kg to Warehouse B
        $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->warehouseA->id,
            't_toLocation'   => (string) $this->warehouseB->id,
            't_product_name' => (string) $this->coffee->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeA->code,
            't_quantity'     => 58.02,
            't_unit'         => 'kg',
            't_textarea'     => 'Transfer WHA to WFB with decimals',
        ])->assertStatus(200);

        // Verify dashboard reflects the stock transfer between warehouses
        $data = $dashboardService->execute();
        $this->assertEquals(102.58, round($data['warehouse'], 2)); // Total remains 102.58
        $this->assertEquals(44.56, round($data['warehouseStocks'][$this->warehouseA->id], 2)); // 102.58 - 58.02 = 44.56
        $this->assertEquals(58.02, round($data['warehouseStocks'][$this->warehouseB->id], 2)); // 58.02

        // Verify stock alerts after transfer
        $alerts = collect($data['stockAlerts'] ?? []);
        
        // Find alert for Warehouse A
        $alertA = $alerts->firstWhere('location_name', 'Warehouse A');
        $this->assertNotNull($alertA);
        $this->assertEquals(44.56, round($alertA['current_qty'], 2));
        $this->assertEquals(102.58, round($alertA['received'], 2));
        // 44.56 / 102.58 * 100 = 43.439...% -> 43.44%
        $this->assertEquals(43.44, round($alertA['percentage'], 2));

        // Find alert for Warehouse B
        $alertB = $alerts->firstWhere('location_name', 'Warehouse B');
        $this->assertNotNull($alertB);
        $this->assertEquals(58.02, round($alertB['current_qty'], 2));
        $this->assertEquals(58.02, round($alertB['received'], 2));
        $this->assertEquals(100.0, $alertB['percentage']);

        // 4. Sale: Sell 24.45 kg from Warehouse B for unit price 25.50 (unpaid)
        // Total price: 24.45 * 25.50 = 623.475 -> 623.48
        $this->postJson('/warehouse/sale/store', [
            'shop_id' => $this->warehouseB->id,
            'customer_name' => 'Dashboard Customer Decimals',
            'customer_id' => $this->customer->id,
            'payment_status' => 'unpaid',
            'payment_date' => now()->format('Y-m-d'),
            'amount_paid' => 0.00,
            'payment_mode' => 'cash',
            'bill_no' => 'BILL-DEC-WFB-1',
            'items' => [
                [
                    'product' => $this->coffee->id,
                    'batch_code' => $batchCode,
                    'grade' => $this->gradeA->code,
                    'quantity' => 24.45,
                    'unit' => 'kg',
                    'unit_price' => 25.50,
                    'total_price' => 623.48,
                ]
            ]
        ])->assertStatus(200);

        // Dashboard Check: totalReceivables should now be 623.48
        $data = $dashboardService->execute();
        $this->assertEquals(623.48, $data['totalReceivables']);
        $this->assertEquals(623.48, $data['warehouseDues'][$this->warehouseB->id]);
        
        // Stock should reflect Sale: A = 44.56, B = 58.02 - 24.45 = 33.57
        $this->assertEquals(78.13, round($data['warehouse'], 2)); // 44.56 + 33.57 = 78.13
        $this->assertEquals(44.56, round($data['warehouseStocks'][$this->warehouseA->id], 2));
        $this->assertEquals(33.57, round($data['warehouseStocks'][$this->warehouseB->id], 2));

        // Get the recorded sale
        $sale = WarehouseSale::orderBy('id', 'desc')->first();
        $this->assertNotNull($sale);

        // 5. Adjust the bill to a partial value (e.g. 300.25)
        $this->postJson('/billing-adjustments', [
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 300.25,
            'reason' => 'price_correction',
            'remarks' => 'Adjusted to 300.25',
        ])->assertStatus(201);

        // Dashboard Check: totalReceivables should now be 300.25
        $data = $dashboardService->execute();
        $this->assertEquals(300.25, $data['totalReceivables']);
        $this->assertEquals(300.25, $data['warehouseDues'][$this->warehouseB->id]);

        // 6. Adjust the bill to 0.00 (cancellation)
        $this->postJson('/billing-adjustments', [
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 0.00,
            'reason' => 'voided_sale',
            'remarks' => 'Adjusted to zero',
        ])->assertStatus(201);

        // Dashboard Check: totalReceivables should go back to 0.00
        $data = $dashboardService->execute();
        $this->assertEquals(0.00, $data['totalReceivables']);

        // Stock should be restored after void adjustment: A = 44.56, B = 58.02
        $this->assertEquals(102.58, round($data['warehouse'], 2));
        $this->assertEquals(44.56, round($data['warehouseStocks'][$this->warehouseA->id], 2));
        $this->assertEquals(58.02, round($data['warehouseStocks'][$this->warehouseB->id], 2));
    }

    /**
     * Workflow: Decimal purchase -> Decimal transfer -> Decimal partial sale -> Dashboard and ledger check
     */
    public function test_purchase_transfer_partial_sale_adjustment_dashboard_flow_with_decimals(): void
    {
        $dashboardService = $this->app->make(\Modules\Dashboard\Services\Dashboard\GetDashboardData::class);

        // 1. Initial Dashboard Check
        $data = $dashboardService->execute();
        $this->assertEquals(0.00, $data['totalReceivables']);

        // 2. Purchase: Buy 100.00 kg of Coffee at Warehouse A with unit cost 20.00
        $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-PARTIAL-WFA',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->coffee->id,
                    'product_name' => $this->coffee->name,
                    'grade' => $this->gradeA->code,
                    'location_id' => $this->warehouseA->id,
                    'quantity' => 100.00,
                    'unit' => 'kg',
                    'unit_cost' => 20.00,
                    'total' => 2000.00,
                    'remarks' => 'Purchase for partial test',
                    'invoice_number' => 'INV-PARTIAL-WFA',
                    'vendor' => 'Vendor Test',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ])->assertStatus(201);

        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // 3. Transfer: Move 50.00 kg to Warehouse B
        $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->warehouseA->id,
            't_toLocation'   => (string) $this->warehouseB->id,
            't_product_name' => (string) $this->coffee->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeA->code,
            't_quantity'     => 50.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transfer WHA to WFB for partial test',
        ])->assertStatus(200);

        // 4. Sale: Sell 30.50 kg from Warehouse B for unit price 25.50 (partial payment)
        // Total price: 30.50 * 25.50 = 777.75
        // Amount Paid: 250.25
        // Due Amount: 777.75 - 250.25 = 527.50
        $this->postJson('/warehouse/sale/store', [
            'shop_id' => $this->warehouseB->id,
            'customer_name' => 'Dashboard Partial Customer',
            'customer_id' => $this->customer->id,
            'payment_status' => 'partial',
            'payment_date' => now()->format('Y-m-d'),
            'amount_paid' => 250.25,
            'payment_mode' => 'upi',
            'bill_no' => 'BILL-PARTIAL-WFB-1',
            'items' => [
                [
                    'product' => $this->coffee->id,
                    'batch_code' => $batchCode,
                    'grade' => $this->gradeA->code,
                    'quantity' => 30.50,
                    'unit' => 'kg',
                    'unit_price' => 25.50,
                    'total_price' => 777.75,
                ]
            ]
        ])->assertStatus(200);

        // Dashboard Check: totalReceivables and warehouseDues should be the due amount (527.50)
        $data = $dashboardService->execute();
        $this->assertEquals(527.50, $data['totalReceivables']);
        $this->assertEquals(527.50, $data['warehouseDues'][$this->warehouseB->id]);

        $sale = WarehouseSale::orderBy('id', 'desc')->first();
        $this->assertNotNull($sale);
        $this->assertEquals(777.75, $sale->total_amount);
        $this->assertEquals(250.25, $sale->paid_amount);
        $this->assertEquals(527.50, $sale->due_amount);

        // 5. Adjust the bill to a new total (600.00)
        // Since paid amount is 250.25, new due should be 600.00 - 250.25 = 349.75
        $this->postJson('/billing-adjustments', [
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 600.00,
            'reason' => 'discount',
            'remarks' => 'Adjusted to 600.00',
        ])->assertStatus(201);

        $data = $dashboardService->execute();
        $this->assertEquals(349.75, $data['totalReceivables']);
        $this->assertEquals(349.75, $data['warehouseDues'][$this->warehouseB->id]);

        // 6. Adjust the bill to exactly paid amount (250.25) -> due becomes 0.00
        $this->postJson('/billing-adjustments', [
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 250.25,
            'reason' => 'price_match',
            'remarks' => 'Adjusted to 250.25',
        ])->assertStatus(201);

        $data = $dashboardService->execute();
        $this->assertEquals(0.00, $data['totalReceivables']);
        $this->assertEquals(0.00, $data['warehouseDues'][$this->warehouseB->id] ?? 0.00);
    }
}
