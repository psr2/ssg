<?php

namespace Modules\StockManagement\Services\StockAdjustment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Modules\StockManagement\Models\StockAdjustment\StockAdjustment;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Modules\StockManagement\Models\StockSummary\StockSummary;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;
use Modules\ShopManagement\Models\ShopInventory;



/**
 * CRITICAL: Stock Adjustment Service (Pre-Movement Human Error Correction Only)
 *
 * THIS SERVICE MAY ONLY BE USED UNDER STRICT CONDITIONS:
 * 
 *
 * -----------------------------------------------------------------------------
 * CORE INVARIANTS (BREAKING THESE = PERMANENT STOCK DRIFT)
 * -----------------------------------------------------------------------------
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
 * -----------------------------------------------------------------------------
 * SUPPORTED SCENARIOS (exactly 7 – all combinations of changes)
 * -----------------------------------------------------------------------------
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
 *
 * Violating these rules will cause stock duplication, negative stock,
 * or untraceable discrepancies that cannot be fixed later.
 *
 * This is financial-grade code. Treat it as such.
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
     * - new_qty
     * - unit
     * - reason
     */
    public function adjust(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $item = StockPurchaseItem::findOrFail($payload['id']);
            $oldLocationId = $item->location_id;

            // 1. Create adjustment log
            $adjustment = $this->createStockAdjustmentEntry($item, $payload);

            // 2. Update stock purchase item if relevant
            $item = $this->updateStockPurchaseItem($item, $payload);

            // 3. Update stock summary
            $this->updateStockSummary($item, $payload, $oldLocationId);

            // 4. Update inventory by location type
            $this->updateInventoryByLocation($item, $payload, $oldLocationId);

            return $adjustment;
        });
    }

    private function createStockAdjustmentEntry(StockPurchaseItem $item, array $payload): StockAdjustment
    {
        $qtyChanged = isset($payload['quantity']) && $payload['quantity'] != $item->quantity;
        $unitChanged = isset($payload['unit']) && $payload['unit'] != $item->unit;
        $locationChanged = isset($payload['new_location_id']) && $payload['new_location_id'] != $item->location_id;

        $adjustmentType = 'CORRECTION';
        if ($locationChanged && !$qtyChanged && !$unitChanged) {
            $adjustmentType = 'LOCATION_CHANGE';
        } elseif ($qtyChanged && !$locationChanged && !$unitChanged) {
            $adjustmentType = 'QUANTITY_CHANGE';
        } elseif ($unitChanged && !$locationChanged && !$qtyChanged) {
            $adjustmentType = 'UNIT_CHANGE';
        }

        $oldUnitId = \Modules\Inventory\Models\UnitOfMeasurement::where('abbreviation', $item->unit)->orWhere('name', $item->unit)->first()?->id;

        $newUnit = $payload['unit'] ?? $item->unit;
        $newUnitId = $newUnit ? (\Modules\Inventory\Models\UnitOfMeasurement::where('abbreviation', $newUnit)->orWhere('name', $newUnit)->first()?->id) : null;

        return StockAdjustment::create([
            'stock_purchase_item_id' => $item->id,
            'old_quantity'           => $item->quantity,
            'new_quantity'           => $payload['quantity'] ?? $item->quantity,
            'old_unit_id'            => $oldUnitId,
            'new_unit_id'            => $newUnitId,
            'old_location_id'        => $item->location_id,
            'new_location_id'        => $payload['new_location_id'] ?? $item->location_id,
            'adjustment_type'        => $adjustmentType,
            'remarks'                => $payload['remarks'] ?? null,
            'created_by'             => auth()->id(),
        ]);
    }

    private function updateStockPurchaseItem(StockPurchaseItem $item, array $payload): StockPurchaseItem
    {
        $changes = [];

        if (isset($payload['quantity']) && $payload['quantity'] != $item->quantity) {
            $changes['quantity'] = $payload['quantity'];
            if ($item->unit_cost) {
                $changes['total'] = $payload['quantity'] * $item->unit_cost;
            }
        }

        if (isset($payload['unit']) && $payload['unit'] != $item->unit) {
            $changes['unit'] = $payload['unit'];
        }

        if (isset($payload['new_location_id']) && $payload['new_location_id'] != $item->location_id) {
            $changes['location_id'] = $payload['new_location_id'];
        }

        if (!empty($changes)) {
            $item->update($changes);
        }

        return $item;
    }

    private function updateStockSummary(StockPurchaseItem $item, array $payload, int $oldLocationId)
    {
        $newLocationId = $payload['new_location_id'] ?? $item->location_id;

        if ($newLocationId != $oldLocationId) {
            StockSummary::where([
                'product_id'  => $item->product,
                'location_id' => $oldLocationId,
                'batch_id'    => $item->batch,
            ])->delete();
        }

        $summary = StockSummary::firstOrNew([
            'product_id'  => $item->product,
            'location_id' => $newLocationId,
            'batch_id'    => $item->batch,
        ]);

        $summary->current_qty = $payload['quantity'] ?? $item->quantity;
        if (!$summary->exists) {
            $summary->reserved_qty = 0.00;
        }
        $summary->unit        = $payload['unit'] ?? $item->unit;
        $summary->grade       = $item->grade;
        $summary->save();
    }

    private function updateInventoryByLocation(StockPurchaseItem $item, array $payload, int $oldLocationId)
    {
        $newLocationId = $payload['new_location_id'] ?? $item->location_id;
        $oldLocationType = $this->getLocationType($oldLocationId);
        $newLocationType = $this->getLocationType($newLocationId);

        // If location changed, delete old inventory record
        if ($newLocationId != $oldLocationId) {
            if ($oldLocationType === 'warehouse') {
                WarehouseInventory::where([
                    'warehouse_id' => $oldLocationId,
                    'batch'        => $item->batch,
                    'product_id'   => $item->product,
                ])->delete();
            } else {
                ShopInventory::where([
                    'shop_id'    => $oldLocationId,
                    'batch_id'   => $item->batch,
                    'product_id' => $item->product,
                ])->delete();
            }
        }

        // Now upsert/update the record at the new location
        if ($newLocationType === 'warehouse') {
            $inventory = WarehouseInventory::firstOrNew([
                'warehouse_id' => $newLocationId,
                'batch'        => $item->batch,
                'product_id'   => $item->product,
            ]);

            $inventory->qty = $payload['quantity'] ?? $item->quantity;
            $inventory->unit_cost = $item->unit_cost;
            $inventory->grade = $item->grade;
            $inventory->save();
        } else {
            $inventory = ShopInventory::firstOrNew([
                'shop_id'    => $newLocationId,
                'batch_id'   => $item->batch,
                'product_id' => $item->product,
            ]);

            $inventory->qty = $payload['quantity'] ?? $item->quantity;
            $inventory->unit_cost = $item->unit_cost;
            $inventory->grade = $item->grade;
            $inventory->save();
        }
    }

    private function getLocationType(int $locationId): string
    {
        $loc = \Modules\Locations\Models\LocationModel::find($locationId);
        if (!$loc) {
            return 'shop'; // Default fallback
        }
        $type = $loc->type;
        if ($type instanceof \Modules\Locations\Enums\LocationType) {
            return $type->value;
        }
        return (string) $type;
    }
}
