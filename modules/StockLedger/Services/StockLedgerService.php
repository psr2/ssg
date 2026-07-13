<?php

namespace Modules\StockLedger\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Modules\StockLedger\Models\StockLedgerEntry;

class StockLedgerService
{
    /**
     * Record a new ledger entry.
     */
    public function recordEntry(array $data): StockLedgerEntry
    {
        return StockLedgerEntry::create([
            'transaction_type' => $data['transaction_type'],
            'location_id'      => $data['location_id'],
            'product_id'       => $data['product_id'],
            'batch_code'       => $data['batch_code'],
            'grade'            => $data['grade'] ?? null,
            'quantity'         => $data['quantity'],
            'unit'             => $data['unit'],
            'unit_cost'        => $data['unit_cost'] ?? 0.00,
            'reference_id'     => $data['reference_id'] ?? null,
            'reference_type'   => $data['reference_type'] ?? null,
            'remarks'          => $data['remarks'] ?? null,
            'created_by'       => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Get the latest unit for a given batch, product, grade, and location.
     */
    public function getLatestUnit(int $locationId, int $productId, string $batchCode, ?string $grade): string
    {
        $latestLedgerEntry = StockLedgerEntry::where([
            'location_id' => $locationId,
            'product_id'  => $productId,
            'batch_code'  => $batchCode,
        ])
        ->when($grade, function($q) use ($grade) {
            $q->where('grade', $grade);
        })
        ->whereNotNull('unit')
        ->orderBy('id', 'desc')
        ->first();

        if ($latestLedgerEntry) {
            return $latestLedgerEntry->unit;
        }

        // Fall back to original StockPurchaseItem unit
        $purchaseItem = \Modules\StockManagement\Models\StockIn\StockPurchaseItem::where([
            'location_id' => $locationId,
            'product'     => $productId,
            'batch'       => $batchCode,
        ])
        ->when($grade, function($q) use ($grade) {
            $q->where('grade', $grade);
        })
        ->first();

        return $purchaseItem ? $purchaseItem->unit : 'pcs';
    }

    /**
     * Get the latest location ID for a given batch, product, and grade.
     */
    public function getLatestLocationId(int $initialLocationId, int $productId, string $batchCode, ?string $grade): int
    {
        $latestLedgerEntry = StockLedgerEntry::where([
            'product_id'  => $productId,
            'batch_code'  => $batchCode,
        ])
        ->when($grade, function($q) use ($grade) {
            $q->where('grade', $grade);
        })
        ->whereNotNull('location_id')
        ->orderBy('id', 'desc')
        ->first();

        return $latestLedgerEntry ? $latestLedgerEntry->location_id : $initialLocationId;
    }

    /**
     * Get the current dynamic available stock for a batch, product, grade, and location.
     */
    public function getAvailableStock(int $locationId, int $productId, string $batchCode, ?string $grade): float
    {
        $location = DB::table('locations')->where('id', $locationId)->first();
        $isShop = $location && $location->type === 'shop';

        if ($isShop) {
            $parentInventory = DB::table('shop_inventory')->where('shop_id', $locationId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchCode)
                ->first();
        } else {
            $parentInventory = DB::table('warehouse_inventory')->where('warehouse_id', $locationId)
                ->where('product_id', $productId)
                ->where('batch', $batchCode)
                ->first();
        }

        $baseQty = $parentInventory ? (float)$parentInventory->qty : 0.00;

        // 2. Subtract sold qty
        $soldQty = DB::table('warehouse_sale_items')
            ->join('warehouse_sales', 'warehouse_sales.id', '=', 'warehouse_sale_items.sale_id')
            ->where('warehouse_sales.warehouse_id', $locationId)
            ->where('warehouse_sale_items.product_id', $productId)
            ->where('warehouse_sale_items.batch_code', $batchCode)
            ->when($grade, function($q) use ($grade) {
                $q->where('warehouse_sale_items.grade', $grade);
            })
            ->sum('warehouse_sale_items.quantity') ?? 0.00;

        // 3. Subtract stock out qty
        $stockOutQty = DB::table('stock_out_items')
            ->join('master_stock_out', 'master_stock_out.id', '=', 'stock_out_items.stock_out_id')
            ->where('master_stock_out.location_id', $locationId)
            ->where('stock_out_items.product_id', $productId)
            ->where('stock_out_items.batch_code', $batchCode)
            ->when($grade, function($q) use ($grade) {
                $q->where('stock_out_items.grade', $grade);
            })
            ->sum('stock_out_items.quantity') ?? 0.00;

        // 4. Subtract transferred out qty
        $transferredOutQty = DB::table('stock_transfer_items')
            ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->where('stock_transfers.from_location_id', $locationId)
            ->where('stock_transfer_items.product_id', $productId)
            ->where('stock_transfer_items.batch_code', $batchCode)
            ->when($grade, function($q) use ($grade) {
                $q->where('stock_transfer_items.grade', $grade);
            })
            ->sum('stock_transfer_items.quantity') ?? 0.00;

        // 5. Add transferred in qty
        $transferredInQty = DB::table('stock_transfer_items')
            ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->where('stock_transfers.to_location_id', $locationId)
            ->where('stock_transfer_items.product_id', $productId)
            ->where('stock_transfer_items.batch_code', $batchCode)
            ->when($grade, function($q) use ($grade) {
                $q->where('stock_transfer_items.grade', $grade);
            })
            ->sum('stock_transfer_items.quantity') ?? 0.00;

        // 6. Add ledger adjustments & voids
        $adjustmentQty = DB::table('stock_ledger_entries')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('batch_code', $batchCode)
            ->when($grade, function($q) use ($grade) {
                $q->where('grade', $grade);
            })
            ->whereIn('transaction_type', ['ADJUSTMENT', 'VOID'])
            ->sum('quantity') ?? 0.00;

        return max(0.00, (float)($baseQty + $transferredInQty - $soldQty - $stockOutQty - $transferredOutQty + $adjustmentQty));
    }

    /**
     * Check if a batch has had any active stock movements/transactions after initial purchase.
     */
    public function hasMovements(int $locationId, int $productId, string $batchCode, ?string $grade): bool
    {
        $transfersExist = DB::table('stock_transfer_items')
            ->where('batch_code', $batchCode)
            ->where('product_id', $productId)
            ->when($grade, function($q) use ($grade) {
                $q->where('grade', $grade);
            })
            ->exists();

        $segregationsExist = false;

        $salesExist = DB::table('warehouse_sale_items')
            ->where('batch_code', $batchCode)
            ->where('product_id', $productId)
            ->when($grade, function($q) use ($grade) {
                $q->where('grade', $grade);
            })
            ->exists();

        $ledgerMovementsExist = StockLedgerEntry::where([
            'location_id' => $locationId,
            'product_id'  => $productId,
            'batch_code'  => $batchCode,
        ])
        ->when($grade, function($q) use ($grade) {
            $q->where('grade', $grade);
        })
        ->whereNotIn('transaction_type', ['PURCHASE', 'ADJUSTMENT', 'VOID'])
        ->exists();

        return $transfersExist || $segregationsExist || $salesExist || $ledgerMovementsExist;
    }

    /**
     * Fetch timeline history of ledger entries for a batch.
     */
    public function getHistory(string $batchCode): Collection
    {
        return StockLedgerEntry::where('batch_code', $batchCode)
            ->orderBy('created_at', 'asc')
            ->get();
    }

}
