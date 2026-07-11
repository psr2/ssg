<?php

namespace Modules\StockLedger\Services\StockAdjustment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\StockLedger\Models\StockAdjustment\StockAdjustment;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;

/**
 * CRITICAL: Stock Adjustment Service (Pre-Movement Human Error Correction Only)
 *
 * THIS SERVICE MAY ONLY BE USED UNDER STRICT CONDITIONS:
 * 
 * CORE INVARIANTS (BREAKING THESE = PERMANENT STOCK DRIFT)
 * 
 * 1. Batch code is GLOBALLY UNIQUE across the entire system
 * 2. One StockPurchaseItem row = ONE COMPLETE PHYSICAL BATCH in ONE location
 *     → Batches are NEVER split or partially moved using this service
 * 3. Stock adjustments are ONLY for correcting mistakes made during initial stock-in
 *     → Can ONLY be used BEFORE any stock transfer, sale, or issue
 * 4. After any adjustment, the OLD location's inventory record is completely removed
 * 5. product_id in WarehouseInventory / ShopInventory / StockAdjustment = actual Product ID
 *     → NEVER use StockPurchaseItem->id as product_id
 *
 * SUPPORTED SCENARIOS (exactly 7 – all combinations of changes)
 * 
 * 1. Unit only
 * 2. Quantity only
 * 3. Location only
 * 4. Unit + Quantity
 * 5. Unit + Location
 * 6. Quantity + Location
 * 7. Unit + Quantity + Location
 *
 * Any other use case (partial transfer, damage after sale, batch merging, etc.)
 * MUST use a different flow (Stock Transfer, Damage Write-off, etc.)
 */
class StockAdjustmentService
{
    /**
     * Perform a stock adjustment.
     *
     * Payload can contain:
     * - id (stock_purchase_item id)
     * - batch
     * - new_location_id / new_location_type (shop / warehouse)
     * - quantity
     * - unit
     * - remarks
     */
    public function adjust(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $item = StockPurchaseItem::findOrFail($payload['id']);
            $oldLocationId = isset($payload['location_id']) ? (int)$payload['location_id'] : $item->location_id;

            $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);

            $currentQty = $ledgerService->getAvailableStock($oldLocationId, $item->product, $item->batch, $item->grade);
            $currentUnit = $ledgerService->getLatestUnit($oldLocationId, $item->product, $item->batch, $item->grade);
            $currentLocationId = $oldLocationId;

            // 1. Create adjustment log record (for audit/tracking)
            $adjustment = $this->createStockAdjustmentEntry($item, $payload, $currentLocationId, $currentQty, $currentUnit);

            $newQty = isset($payload['quantity']) ? (float)$payload['quantity'] : $currentQty;
            $newUnit = $payload['unit'] ?? $currentUnit;
            $newLocationId = isset($payload['new_location_id']) ? (int)$payload['new_location_id'] : $currentLocationId;

            $qtyChanged = $newQty != $currentQty;
            $unitChanged = $newUnit != $currentUnit;
            $locationChanged = $newLocationId != $currentLocationId;

            if ($unitChanged || $locationChanged) {
                // Deduct entire current quantity from old unit/location
                if ($currentQty > 0) {
                    $ledgerService->recordEntry([
                        'transaction_type' => 'ADJUSTMENT',
                        'location_id'      => $currentLocationId,
                        'product_id'       => $item->product,
                        'batch_code'       => $item->batch,
                        'grade'            => $item->grade,
                        'quantity'         => -$currentQty,
                        'unit'             => $currentUnit,
                        'unit_cost'        => $item->unit_cost ?? 0.00,
                        'reference_id'     => $adjustment->id,
                        'reference_type'   => get_class($adjustment),
                        'remarks'          => $payload['remarks'] ?? 'Unit/Location adjustment deduction',
                    ]);
                }

                // Add new quantity to new unit/location
                if ($newQty > 0) {
                    $ledgerService->recordEntry([
                        'transaction_type' => 'ADJUSTMENT',
                        'location_id'      => $newLocationId,
                        'product_id'       => $item->product,
                        'batch_code'       => $item->batch,
                        'grade'            => $item->grade,
                        'quantity'         => $newQty,
                        'unit'             => $newUnit,
                        'unit_cost'        => $item->unit_cost ?? 0.00,
                        'reference_id'     => $adjustment->id,
                        'reference_type'   => get_class($adjustment),
                        'remarks'          => $payload['remarks'] ?? 'Unit/Location adjustment addition',
                    ]);
                }
            } else if ($qtyChanged) {
                // Delta quantity correction
                $delta = $newQty - $currentQty;
                if ($delta != 0) {
                    $ledgerService->recordEntry([
                        'transaction_type' => 'ADJUSTMENT',
                        'location_id'      => $currentLocationId,
                        'product_id'       => $item->product,
                        'batch_code'       => $item->batch,
                        'grade'            => $item->grade,
                        'quantity'         => $delta,
                        'unit'             => $currentUnit,
                        'unit_cost'        => $item->unit_cost ?? 0.00,
                        'reference_id'     => $adjustment->id,
                        'reference_type'   => get_class($adjustment),
                        'remarks'          => $payload['remarks'] ?? 'Quantity correction',
                    ]);
                }
            }

            return $adjustment;
        });
    }

    /**
     * Void a stock purchase item.
     * Writes a contra-entry to void all available quantity.
     */
    public function void(int $id, string $remarks)
    {
        return DB::transaction(function () use ($id, $remarks) {
            $item = StockPurchaseItem::findOrFail($id);
            $oldLocationId = $item->location_id;

            $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);

            // Check if there are active movements
            if ($ledgerService->hasMovements($oldLocationId, $item->product, $item->batch, $item->grade)) {
                throw new \Exception("Active stock movements have already occurred for this batch. Human error corrections are no longer permitted.");
            }

            // Get current available stock details
            $currentQty = $ledgerService->getAvailableStock($oldLocationId, $item->product, $item->batch, $item->grade);
            $currentUnit = $ledgerService->getLatestUnit($oldLocationId, $item->product, $item->batch, $item->grade);
            $currentLocationId = $ledgerService->getLatestLocationId($oldLocationId, $item->product, $item->batch, $item->grade);

            if ($currentQty <= 0) {
                throw new \Exception("This stock item is already at zero quantity or voided.");
            }

            // 1. Create a StockAdjustment log record (audit) with type 'OTHER'
            $oldUnitId = \Modules\Inventory\Models\UnitOfMeasurement::where('abbreviation', $currentUnit)->orWhere('name', $currentUnit)->first()?->id;

            $adjustment = StockAdjustment::create([
                'stock_purchase_item_id' => $item->id,
                'old_quantity'           => $currentQty,
                'new_quantity'           => 0.00,
                'old_unit_id'            => $oldUnitId,
                'new_unit_id'            => $oldUnitId,
                'old_location_id'        => $currentLocationId,
                'new_location_id'        => $currentLocationId,
                'adjustment_type'        => 'OTHER',
                'remarks'                => $remarks,
                'created_by'             => auth()->id(),
            ]);

            // 2. Record VOID contra-entry to the StockLedger
            $ledgerService->recordEntry([
                'transaction_type' => 'VOID',
                'location_id'      => $currentLocationId,
                'product_id'       => $item->product,
                'batch_code'       => $item->batch,
                'grade'            => $item->grade,
                'quantity'         => -$currentQty,
                'unit'             => $currentUnit,
                'unit_cost'        => $item->unit_cost ?? 0.00,
                'reference_id'     => $adjustment->id,
                'reference_type'   => get_class($adjustment),
                'remarks'          => $remarks,
            ]);

            return $adjustment;
        });
    }

    private function createStockAdjustmentEntry(
        StockPurchaseItem $item,
        array $payload,
        int $currentLocationId,
        float $currentQty,
        string $currentUnit
    ): StockAdjustment {
        $qtyChanged = isset($payload['quantity']) && $payload['quantity'] != $currentQty;
        $unitChanged = isset($payload['unit']) && $payload['unit'] != $currentUnit;
        $locationChanged = isset($payload['new_location_id']) && $payload['new_location_id'] != $currentLocationId;

        $adjustmentType = 'CORRECTION';
        if ($locationChanged && !$qtyChanged && !$unitChanged) {
            $adjustmentType = 'LOCATION_CHANGE';
        } elseif ($qtyChanged && !$locationChanged && !$unitChanged) {
            $adjustmentType = 'QUANTITY_CHANGE';
        } elseif ($unitChanged && !$locationChanged && !$qtyChanged) {
            $adjustmentType = 'UNIT_CHANGE';
        }

        $oldUnitId = \Modules\Inventory\Models\UnitOfMeasurement::where('abbreviation', $currentUnit)->orWhere('name', $currentUnit)->first()?->id;

        $newUnit = $payload['unit'] ?? $currentUnit;
        $newUnitId = $newUnit ? (\Modules\Inventory\Models\UnitOfMeasurement::where('abbreviation', $newUnit)->orWhere('name', $newUnit)->first()?->id) : null;

        return StockAdjustment::create([
            'stock_purchase_item_id' => $item->id,
            'old_quantity'           => $currentQty,
            'new_quantity'           => $payload['quantity'] ?? $currentQty,
            'old_unit_id'            => $oldUnitId,
            'new_unit_id'            => $newUnitId,
            'old_location_id'        => $currentLocationId,
            'new_location_id'        => $payload['new_location_id'] ?? $currentLocationId,
            'adjustment_type'        => $adjustmentType,
            'remarks'                => $payload['remarks'] ?? null,
            'created_by'             => auth()->id(),
        ]);
    }
}
