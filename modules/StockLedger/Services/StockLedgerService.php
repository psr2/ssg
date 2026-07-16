<?php

namespace Modules\StockLedger\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Modules\StockLedger\Models\StockLedgerEntry;

class StockLedgerService
{
    /**
     * Record a new ledger entry and synchronize cached balance tables.
     */
    public function recordEntry(array $data): StockLedgerEntry
    {
        return DB::transaction(function () use ($data) {
            $entry = StockLedgerEntry::create([
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

            $this->syncCachedBalances(
                (int)$data['location_id'],
                (int)$data['product_id'],
                $data['batch_code'],
                $data['grade'] ?? null,
                (float)$data['quantity'],
                $data['unit'],
                (float)($data['unit_cost'] ?? 0.00)
            );

            return $entry;
        });
    }

    /**
     * Helper to synchronize cache tables for a ledger entry.
     */
    protected function syncCachedBalances(
        int $locationId,
        int $productId,
        string $batchCode,
        ?string $grade,
        float $quantity,
        string $unit,
        float $unitCost
    ): void {
        $location = DB::table('locations')->where('id', $locationId)->first();
        if (!$location) {
            return;
        }

        $isShop = $location->type === 'shop';
        $gradeOptions = $this->getGradeOptions($grade);

        if ($isShop) {
            $matchingRow = DB::table('shop_inventory')
                ->where('shop_id', $locationId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchCode)
                ->where(function ($q) use ($grade, $gradeOptions) {
                    if ($grade === null || $grade === '') {
                        $q->whereNull('grade')->orWhere('grade', '');
                    } else {
                        $q->whereIn('grade', $gradeOptions);
                    }
                })
                ->first();

            if ($matchingRow) {
                DB::table('shop_inventory')
                    ->where('shop_id', $locationId)
                    ->where('product_id', $productId)
                    ->where('batch_id', $batchCode)
                    ->where('grade', $matchingRow->grade)
                    ->update([
                        'qty' => DB::raw("qty + {$quantity}"),
                        'unit_cost' => $unitCost,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('shop_inventory')->insert([
                    'shop_id'    => $locationId,
                    'batch_id'   => $batchCode,
                    'product_id' => $productId,
                    'grade'      => $grade,
                    'qty'        => $quantity,
                    'unit_cost'  => $unitCost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            $matchingRow = DB::table('warehouse_inventory')
                ->where('warehouse_id', $locationId)
                ->where('product_id', $productId)
                ->where('batch', $batchCode)
                ->where(function ($q) use ($grade, $gradeOptions) {
                    if ($grade === null || $grade === '') {
                        $q->whereNull('grade')->orWhere('grade', '');
                    } else {
                        $q->whereIn('grade', $gradeOptions);
                    }
                })
                ->first();

            if ($matchingRow) {
                DB::table('warehouse_inventory')
                    ->where('warehouse_id', $locationId)
                    ->where('product_id', $productId)
                    ->where('batch', $batchCode)
                    ->where('grade', $matchingRow->grade)
                    ->update([
                        'qty' => DB::raw("qty + {$quantity}"),
                        'unit_cost' => $unitCost,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('warehouse_inventory')->insert([
                    'warehouse_id' => $locationId,
                    'batch'        => $batchCode,
                    'product_id'   => $productId,
                    'grade'        => $grade,
                    'qty'          => $quantity,
                    'unit_cost'    => $unitCost,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        $matchingSummary = DB::table('stock_summary')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('batch_id', $batchCode)
            ->where(function ($q) use ($grade, $gradeOptions) {
                if ($grade === null || $grade === '') {
                    $q->whereNull('grade')->orWhere('grade', '');
                } else {
                    $q->whereIn('grade', $gradeOptions);
                }
            })
            ->first();

        if ($matchingSummary) {
            DB::table('stock_summary')
                ->where('location_id', $locationId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchCode)
                ->where('grade', $matchingSummary->grade)
                ->update([
                    'current_qty' => DB::raw("current_qty + {$quantity}"),
                    'updated_at'  => now(),
                ]);
        } else {
            DB::table('stock_summary')->insert([
                'product_id'   => $productId,
                'location_id'  => $locationId,
                'batch_id'     => $batchCode,
                'current_qty'  => $quantity,
                'reserved_qty' => 0.00,
                'unit'         => $unit,
                'grade'        => $grade,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    /**
     * Helper to resolve grade name/code options to prevent mismatch issues.
     */
    protected function getGradeOptions(?string $grade): array
    {
        if ($grade === null || $grade === '') {
            return [];
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_grades')) {
                $gradeModel = \Modules\Inventory\Models\ProductGrade::where('code', $grade)
                    ->orWhere('name', $grade)
                    ->first();
                if ($gradeModel) {
                    return [$gradeModel->code, $gradeModel->name];
                }
            }
        } catch (\Exception $e) {
            // Table doesn't exist yet or db not migrated
        }

        return [$grade];
    }

    /**
     * Get the latest unit for a given batch, product, grade, and location.
     */
    public function getLatestUnit(int $locationId, int $productId, string $batchCode, ?string $grade): string
    {
        $gradeOptions = $this->getGradeOptions($grade);
        $latestLedgerEntry = StockLedgerEntry::where([
            'location_id' => $locationId,
            'product_id'  => $productId,
            'batch_code'  => $batchCode,
        ])
        ->when($grade, function($q) use ($gradeOptions) {
            $q->whereIn('grade', $gradeOptions);
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
        ->when($grade, function($q) use ($gradeOptions) {
            $q->whereIn('grade', $gradeOptions);
        })
        ->first();

        return $purchaseItem ? $purchaseItem->unit : 'pcs';
    }

    /**
     * Get the latest location ID for a given batch, product, and grade.
     */
    public function getLatestLocationId(int $initialLocationId, int $productId, string $batchCode, ?string $grade): int
    {
        $gradeOptions = $this->getGradeOptions($grade);
        $latestLedgerEntry = StockLedgerEntry::where([
            'product_id'  => $productId,
            'batch_code'  => $batchCode,
        ])
        ->when($grade, function($q) use ($gradeOptions) {
            $q->whereIn('grade', $gradeOptions);
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
        $gradeOptions = $this->getGradeOptions($grade);
        $sum = (float) StockLedgerEntry::where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('batch_code', $batchCode)
            ->where(function ($q) use ($grade, $gradeOptions) {
                if ($grade === null || $grade === '') {
                    $q->whereNull('grade')->orWhere('grade', '');
                } else {
                    $q->whereIn('grade', $gradeOptions);
                }
            })
            ->sum('quantity');

        return max(0.00, $sum);
    }

    /**
     * Check if a batch has had any active stock movements/transactions after initial purchase.
     */
    public function hasMovements(int $locationId, int $productId, string $batchCode, ?string $grade): bool
    {
        $gradeOptions = $this->getGradeOptions($grade);
        $transfersExist = DB::table('stock_transfer_items')
            ->where('batch_code', $batchCode)
            ->where('product_id', $productId)
            ->when($grade, function($q) use ($gradeOptions) {
                $q->whereIn('grade', $gradeOptions);
            })
            ->exists();

        $segregationsExist = false;

        $salesExist = DB::table('warehouse_sale_items')
            ->where('batch_code', $batchCode)
            ->where('product_id', $productId)
            ->when($grade, function($q) use ($gradeOptions) {
                $q->whereIn('grade', $gradeOptions);
            })
            ->exists();

        $ledgerMovementsExist = StockLedgerEntry::where([
            'location_id' => $locationId,
            'product_id'  => $productId,
            'batch_code'  => $batchCode,
        ])
        ->when($grade, function($q) use ($gradeOptions) {
            $q->whereIn('grade', $gradeOptions);
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
