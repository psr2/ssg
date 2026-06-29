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
        $latestUnit = $this->getLatestUnit($locationId, $productId, $batchCode, $grade);

        // 1. Get base quantity from original purchase items matching location, product, batch, grade, and unit
        $purchaseItems = \Modules\StockManagement\Models\StockIn\StockPurchaseItem::where([
            'location_id' => $locationId,
            'product'     => $productId,
            'batch'       => $batchCode,
            'unit'        => $latestUnit,
        ])
        ->when($grade, function($q) use ($grade) {
            $q->where('grade', $grade);
        })
        ->get();

        $baseQty = 0.00;
        foreach ($purchaseItems as $item) {
            $baseQty += (float)$item->quantity;
        }

        // 2. Sum up adjustments and voids for this unit and location
        $adjustmentQty = (float)StockLedgerEntry::where([
            'location_id'      => $locationId,
            'product_id'       => $productId,
            'batch_code'       => $batchCode,
            'unit'             => $latestUnit,
        ])
        ->whereIn('transaction_type', ['ADJUSTMENT', 'VOID', 'TRANSFER_IN', 'TRANSFER_OUT'])
        ->when($grade, function($q) use ($grade) {
            $q->where('grade', $grade);
        })
        ->sum('quantity');

        return max(0.00, $baseQty + $adjustmentQty);
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

        $segregationsExist = DB::table('stock_segregation_items')
            ->join('stock_segregations', 'stock_segregations.id', '=', 'stock_segregation_items.stock_segregation_id')
            ->where('stock_segregations.parent_batch_code', $batchCode)
            ->where('stock_segregations.product_id', $productId)
            ->when($grade, function($q) use ($grade) {
                $q->where('stock_segregation_items.grade', $grade);
            })
            ->exists();

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
        ->where('transaction_type', '!=', 'ADJUSTMENT')
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
