<?php

namespace Modules\StockAdjustment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockLedger\Services\StockLedgerService;
use Modules\StockAdjustment\Services\StockAdjustmentService;
use Modules\StockAdjustment\Models\StockAdjustment;
use Illuminate\Validation\ValidationException;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected StockLedgerService $ledgerService;
    protected StockAdjustmentService $adjustmentService;
    protected LocationModel $warehouse;
    protected Products $product;
    protected UnitOfMeasurement $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledgerService = app(StockLedgerService::class);
        $this->adjustmentService = app(StockAdjustmentService::class);

        $this->unit = UnitOfMeasurement::firstOrCreate(
            ['abbreviation' => 'kg'],
            ['name' => 'Kilogram']
        );

        $this->product = Products::firstOrCreate(
            ['sku' => 'tom_test'],
            [
                'name' => 'Tomato Test',
                'abbreviation' => 'tomt',
                'unit_id' => $this->unit->id
            ]
        );

        $this->warehouse = LocationModel::create([
            'name' => 'Test Warehouse',
            'type' => 'warehouse',
            'abbreviation' => 'TWH',
            'status' => 'active'
        ]);
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
     * Test adjustment within normal tolerance limits is immediately approved and posted.
     */
    public function test_adjustment_within_tolerance_is_immediately_approved(): void
    {
        // 1. Seed some initial stock: 50.00 kg
        $this->seedStock('BATCH-ADJ-1', 50.00, 'A');

        // 2. Adjust by +2.00 kg (within 5% of 50.00, which is 2.50 kg, and within 100 units limit)
        $adjustment = $this->adjustmentService->createAdjustment([
            'location_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_code' => 'BATCH-ADJ-1',
            'grade' => 'A',
            'adjusted_qty' => 2.00,
            'new_qty' => 52.00,
            'reason' => 'audit_difference',
            'remarks' => 'Slight audit correction',
        ]);

        $this->assertEquals('approved', $adjustment->status);

        // Assert database adjustment recorded
        $this->assertDatabaseHas('stock_adjustments', [
            'id' => $adjustment->id,
            'status' => 'approved',
            'adjusted_qty' => 2.00,
            'new_qty' => 52.00,
        ]);

        // Assert ledger entry created for adjustment
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'ADJUSTMENT',
            'location_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_code' => 'BATCH-ADJ-1',
            'quantity' => 2.00,
            'reference_id' => $adjustment->id,
        ]);

        // Assert warehouse_inventory cache updated
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->warehouse->id,
            'batch' => 'BATCH-ADJ-1',
            'qty' => 52.00,
        ]);
    }

    /**
     * Test adjustment exceeding 5% deviation is queued as pending_approval.
     */
    public function test_adjustment_exceeding_tolerance_requires_approval(): void
    {
        // 1. Seed some initial stock: 50.00 kg
        $this->seedStock('BATCH-ADJ-2', 50.00, 'A');

        // 2. Adjust by +10.00 kg (exceeds 5% threshold of 50.00, which is 2.50 kg)
        $adjustment = $this->adjustmentService->createAdjustment([
            'location_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_code' => 'BATCH-ADJ-2',
            'grade' => 'A',
            'adjusted_qty' => 10.00,
            'new_qty' => 60.00,
            'reason' => 'damage',
            'remarks' => 'Large adjustment',
        ]);

        $this->assertEquals('pending_approval', $adjustment->status);

        // Assert database stock adjustment exists as pending
        $this->assertDatabaseHas('stock_adjustments', [
            'id' => $adjustment->id,
            'status' => 'pending_approval',
        ]);

        // Assert NO ledger entry created yet
        $this->assertDatabaseMissing('stock_ledger_entries', [
            'transaction_type' => 'ADJUSTMENT',
            'reference_id' => $adjustment->id,
        ]);

        // Assert cache remains unchanged
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->warehouse->id,
            'batch' => 'BATCH-ADJ-2',
            'qty' => 50.00,
        ]);

        // 3. Approve the adjustment
        $approvedAdj = $this->adjustmentService->approveAdjustment($adjustment->id);

        $this->assertEquals('approved', $approvedAdj->status);

        // Assert ledger entry now created
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'ADJUSTMENT',
            'reference_id' => $adjustment->id,
            'quantity' => 10.00,
        ]);

        // Assert cache is now updated
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->warehouse->id,
            'batch' => 'BATCH-ADJ-2',
            'qty' => 60.00,
        ]);
    }

    /**
     * Test safety floor validation prevents negative reconciled quantity.
     */
    public function test_safety_floor_prevents_negative_quantity(): void
    {
        // Seed initial stock
        $this->seedStock('BATCH-ADJ-3', 10.00, 'A');

        $this->expectException(ValidationException::class);

        // Try to adjust to -2.00 kg
        $this->adjustmentService->createAdjustment([
            'location_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_code' => 'BATCH-ADJ-3',
            'grade' => 'A',
            'adjusted_qty' => -12.00,
            'new_qty' => -2.00,
            'reason' => 'theft',
            'remarks' => 'Negative stock test',
        ]);
    }

    /**
     * Test that if starting quantity is 0, any positive adjustment is 100% deviation and requires approval.
     */
    public function test_adjustment_with_zero_starting_qty_requires_approval(): void
    {
        // Register batch code in database first with 0.00 qty
        $this->seedStock('BATCH-ZERO-START', 0.00, 'A');

        $adjustment = $this->adjustmentService->createAdjustment([
            'location_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_code' => 'BATCH-ZERO-START',
            'grade' => 'A',
            'adjusted_qty' => 5.00,
            'new_qty' => 5.00,
            'reason' => 'audit_difference',
            'remarks' => 'Found stock from zero',
        ]);

        $this->assertEquals('pending_approval', $adjustment->status);

        // Assert no ledger entries yet
        $this->assertDatabaseMissing('stock_ledger_entries', [
            'transaction_type' => 'ADJUSTMENT',
            'reference_id' => $adjustment->id,
        ]);
    }

    /**
     * Test that if starting quantity is 0, negative adjustment throws ValidationException immediately.
     */
    public function test_adjustment_with_zero_starting_qty_safety_floor(): void
    {
        // Register batch code in database first with 0.00 qty
        $this->seedStock('BATCH-ZERO-START-NEG', 0.00, 'A');

        $this->expectException(ValidationException::class);

        // Try to adjust below 0 when starting from 0
        $this->adjustmentService->createAdjustment([
            'location_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_code' => 'BATCH-ZERO-START-NEG',
            'grade' => 'A',
            'adjusted_qty' => -1.00,
            'new_qty' => -1.00,
            'reason' => 'theft',
            'remarks' => 'Adjust below zero',
        ]);
    }

    /**
     * Test that if adjustment exceeds absolute quantity limit of 100 units (even if within 5% percentage deviation), it requires approval.
     */
    public function test_adjustment_exceeding_quantity_limit_requires_approval(): void
    {
        // 1. Seed some large initial stock: 5000.00 kg
        $this->seedStock('BATCH-ADJ-LARGE', 5000.00, 'A');

        // 2. Adjust by +150.00 kg (only 3.0% deviation, which is < 5% percentage threshold, but exceeds 100 absolute units)
        $adjustment = $this->adjustmentService->createAdjustment([
            'location_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_code' => 'BATCH-ADJ-LARGE',
            'grade' => 'A',
            'adjusted_qty' => 150.00,
            'new_qty' => 5150.00,
            'reason' => 'audit_difference',
            'remarks' => 'Large unit adjustment',
        ]);

        $this->assertEquals('pending_approval', $adjustment->status);

        // Assert database stock adjustment exists as pending
        $this->assertDatabaseHas('stock_adjustments', [
            'id' => $adjustment->id,
            'status' => 'pending_approval',
            'adjusted_qty' => 150.00,
        ]);
    }
}

