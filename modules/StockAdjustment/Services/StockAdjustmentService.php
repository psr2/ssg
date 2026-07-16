<?php

namespace Modules\StockAdjustment\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockAdjustment\Models\StockAdjustment;
use Modules\StockLedger\Services\StockLedgerService;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function __construct(
        protected StockLedgerService $ledgerService
    ) {}

    /**
     * Create a stock adjustment record.
     */
    public function createAdjustment(array $data): StockAdjustment
    {
        $locationId = (int) $data['location_id'];
        $productId = (int) $data['product_id'];
        $batchCode = $data['batch_code'];
        $grade = $data['grade'] ?? null;
        $adjustedQty = (float) $data['adjusted_qty'];
        $newQty = (float) $data['new_qty'];

        // Fetch current availability from the ledger
        $availableQty = $this->ledgerService->getAvailableStock($locationId, $productId, $batchCode, $grade);
        $unit = $this->ledgerService->getLatestUnit($locationId, $productId, $batchCode, $grade) ?: 'pcs';

        // 1. Safety Floor Check
        if ($newQty < 0) {
            throw ValidationException::withMessages([
                'new_qty' => ['Safety floor violation: Reconciled quantity cannot be less than zero.']
            ]);
        }

        // 2. Deviation Threshold Check
        $absDelta = abs($adjustedQty);
        $deviation = 0;
        if ($availableQty > 0) {
            $deviation = ($absDelta / $availableQty) * 100;
        } else if ($absDelta > 0) {
            $deviation = 100;
        }

        $exceedsDeviation = $deviation > 5;
        $exceedsQty = $absDelta > 100;

        $status = ($exceedsDeviation || $exceedsQty) ? 'pending_approval' : 'approved';

        return DB::transaction(function () use ($data, $availableQty, $adjustedQty, $newQty, $unit, $status) {
            $adjustment = StockAdjustment::create([
                'location_id' => $data['location_id'],
                'product_id' => $data['product_id'],
                'batch_code' => $data['batch_code'],
                'grade' => $data['grade'] ?? null,
                'original_qty' => $availableQty,
                'adjusted_qty' => $adjustedQty,
                'new_qty' => $newQty,
                'reason' => $data['reason'],
                'status' => $status,
                'adjusted_by' => auth()->id() ?? 1,
                'remarks' => $data['remarks'] ?? null,
            ]);

            // If approved, write immediately to the immutable stock ledger
            if ($status === 'approved') {
                $this->ledgerService->recordEntry([
                    'transaction_type' => 'ADJUSTMENT',
                    'location_id' => $adjustment->location_id,
                    'product_id' => $adjustment->product_id,
                    'batch_code' => $adjustment->batch_code,
                    'grade' => $adjustment->grade,
                    'quantity' => $adjustment->adjusted_qty,
                    'unit' => $unit,
                    'unit_cost' => 0.00,
                    'reference_id' => $adjustment->id,
                    'reference_type' => StockAdjustment::class,
                    'remarks' => $adjustment->remarks,
                    'created_by' => $adjustment->adjusted_by,
                ]);
            }

            return $adjustment;
        });
    }

    /**
     * Approve a pending stock adjustment.
     */
    public function approveAdjustment(int $id): StockAdjustment
    {
        return DB::transaction(function () use ($id) {
            $adjustment = StockAdjustment::lockForUpdate()->findOrFail($id);

            if ($adjustment->status !== 'pending_approval') {
                throw new \Exception('This adjustment has already been processed or is not pending approval.');
            }

            $unit = $this->ledgerService->getLatestUnit(
                $adjustment->location_id,
                $adjustment->product_id,
                $adjustment->batch_code,
                $adjustment->grade
            ) ?: 'pcs';

            // Post the entry to the stock ledger
            $this->ledgerService->recordEntry([
                'transaction_type' => 'ADJUSTMENT',
                'location_id' => $adjustment->location_id,
                'product_id' => $adjustment->product_id,
                'batch_code' => $adjustment->batch_code,
                'grade' => $adjustment->grade,
                'quantity' => $adjustment->adjusted_qty,
                'unit' => $unit,
                'unit_cost' => 0.00,
                'reference_id' => $adjustment->id,
                'reference_type' => StockAdjustment::class,
                'remarks' => $adjustment->remarks,
                'created_by' => auth()->id() ?? 1,
            ]);

            $adjustment->update([
                'status' => 'approved',
                'approved_by' => auth()->id() ?? 1,
            ]);

            return $adjustment;
        });
    }
}
