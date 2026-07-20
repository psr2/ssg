<?php

namespace Tests\Workflow\Concerns;

use Modules\StockLedger\Services\StockLedgerService;

trait CreatesWorkflowFixtures
{
    /**
     * Seeds initial warehouse stock into master_stock_in, stock_purchase, stock_purchase_items,
     * warehouse_inventory, and stock_ledger_entries.
     */
    protected function seedInitialWarehouseStock(
        int $locationId,
        int $productId,
        string $batchCode,
        string $gradeCode,
        float $quantity = 100.00,
        float $unitCost = 10.00,
        string $unit = 'kg'
    ): void {
        $masterId = \DB::table('master_stock_in')->insertGetId([
            'reference_number' => 'REF-' . uniqid(),
            'stock_movement_type' => 'in',
            'stock_in_type' => 'purchase',
            'stock_in_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchaseId = \DB::table('stock_purchase')->insertGetId([
            'master_stock_in_id' => $masterId,
            'vendor' => 'Test Vendor',
            'invoice_number' => 'INV-' . uniqid(),
            'purchase_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('stock_purchase_items')->insert([
            'stock_in_purchase_id' => $purchaseId,
            'location_id' => $locationId,
            'product' => $productId,
            'batch' => $batchCode,
            'grade' => $gradeCode,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_cost' => $unitCost,
            'total' => $quantity * $unitCost,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('warehouse_inventory')->insert([
            'warehouse_id' => $locationId,
            'product_id'   => $productId,
            'batch'        => $batchCode,
            'grade'        => $gradeCode,
            'qty'          => $quantity,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        app(StockLedgerService::class)->recordEntry([
            'transaction_type' => 'PURCHASE',
            'location_id'      => $locationId,
            'product_id'       => $productId,
            'batch_code'       => $batchCode,
            'grade'            => $gradeCode,
            'quantity'         => $quantity,
            'unit'             => $unit,
            'unit_cost'        => $unitCost,
            'remarks'          => 'Fixture Initial Stock'
        ]);
    }
}
