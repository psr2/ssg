<?php

namespace Modules\StockLedger\Requests\StockAdjustment;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\Locations\Models\LocationModel;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Illuminate\Support\Facades\Log;



class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [
            'id' => ['required', 'integer', 'exists:stock_purchase_items,id'],
            'batch' => ['required', 'string', 'exists:stock_purchase_items,batch'],
            'quantity' => ['sometimes', 'numeric', 'min:0.01'],
            'unit' => ['sometimes', 'string', 'in:pcs,bx,kg'],
            'new_location_id' => ['sometimes', 'integer', 'exists:locations,id'],
            'remarks' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Item ID is required.',
            'id.integer'  => 'Item ID must be a valid integer.',
            'id.exists'   => 'The selected item does not exist in purchase records.',
            'batch.required' => 'Batch is required.',
            'batch.string'   => 'Batch must be a valid string.',
            'batch.exists'   => 'The selected batch does not exist in purchase records.',
            'quantity.numeric' => 'Quantity must be a valid number.',
            'quantity.min'     => 'Quantity must be greater than zero.',
            'unit.in' => 'Unit must be one of the following: pcs, bx, kg.',
            'new_location_id.exists' => 'Selected location does not exist.',
            'remarks.required' => 'Detailed remarks (minimum 10 characters) explaining the reason for adjustment are required.',
            'remarks.string' => 'Remarks must be a valid text.',
            'remarks.min'    => 'Remarks must be at least 10 characters long to explain the correction context.',
            'remarks.max'    => 'Remarks cannot exceed 500 characters.',
        ];
    }

    /**
     * After initial validation, check if relevant fields have changed and enforce constraints
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $stockItem = $this->getStockItem($validator);
            if (!$stockItem) return;

            $this->checkForChanges($validator, $stockItem);
            $this->validatePreMovementState($validator, $stockItem);
            $this->validateQuantityDeviation($validator, $stockItem);
            $this->validateUnit($validator);
            $this->validateLocation($validator);
        });
    }

    /**
     * Fetch the stock item by ID
     */
    protected function getStockItem($validator): ?StockPurchaseItem
    {
        $stockItem = StockPurchaseItem::find($this->input('id'));
        if (!$stockItem) {
            $validator->errors()->add('id', 'Stock item not found.');
        }
        return $stockItem;
    }

    /**
     * Check if quantity, unit, or location have actually changed
     */
    protected function checkForChanges($validator, StockPurchaseItem $stockItem): void
    {
        $quantity = $this->input('quantity', $stockItem->quantity);
        $unit = $this->input('unit', $stockItem->unit);
        $locationId = $this->input('new_location_id', $stockItem->location_id);

        $quantityChanged = $quantity != $stockItem->quantity;
        $unitChanged = $unit != $stockItem->unit;
        $locationChanged = $locationId != $stockItem->location_id;

        if (!($quantityChanged || $unitChanged || $locationChanged)) {
            $validator->errors()->add(
                'no_change',
                'No changes detected in quantity, unit, or location. Please modify at least one field.'
            );
        }
    }

    /**
     * Validate unit against units table
     */
    protected function validateUnit($validator): void
    {
        $unit = $this->input('unit');
        if ($unit && !UnitOfMeasurement::where('abbreviation', $unit)->orWhere('name', $unit)->exists()) {
            $validator->errors()->add('unit', 'The selected unit is invalid.');
        }
    }

    /**
     * Validate location against locations table
     */
    protected function validateLocation($validator): void
    {
        $locationId = $this->input('new_location_id');
        if ($locationId && !LocationModel::find($locationId)) {
            $validator->errors()->add('new_location_id', 'The selected location is invalid.');
        }
    }

    /**
     * Enforce pre-movement state check.
     * If the batch has active movements, we only allow quantity adjustments at its current location.
     * We block changing the unit or location through the adjustment screen.
     */
    protected function validatePreMovementState($validator, StockPurchaseItem $stockItem): void
    {
        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);

        if ($ledgerService->hasMovements($stockItem->location_id, $stockItem->product, $stockItem->batch, $stockItem->grade)) {
            $currentUnit = $ledgerService->getLatestUnit($stockItem->location_id, $stockItem->product, $stockItem->batch, $stockItem->grade);
            $currentLocationId = $ledgerService->getLatestLocationId($stockItem->location_id, $stockItem->product, $stockItem->batch, $stockItem->grade);

            $requestedUnit = $this->input('unit');
            $requestedLocationId = $this->input('new_location_id');

            if (($requestedUnit && $requestedUnit !== $currentUnit) || 
                ($requestedLocationId && $requestedLocationId != $currentLocationId)) {
                $validator->errors()->add(
                    'id',
                    'Active stock movements have already occurred for this batch. You can only adjust the quantity at its current location; changing the unit or location is not permitted.'
                );
            }
        }
    }

    /**
     * Enforce a hard threshold on adjustment deviation.
     * Quantity cannot be adjusted by more than 50% from the original received quantity.
     */
    protected function validateQuantityDeviation($validator, StockPurchaseItem $stockItem): void
    {
        Log::debug("reached deviation error");

        $quantity = $this->input('quantity');
        if ($quantity !== null && is_numeric($quantity) && $quantity != $stockItem->quantity) {
            $difference = abs($quantity - $stockItem->quantity);
            $deviationPercent = ($difference / $stockItem->quantity) * 100;
            if ($deviationPercent > 50) {
                  Log::warning(
                    "Stock adjustment deviation limit exceeded. Batch: {$stockItem->batch}, " .
                    "Original Qty: {$stockItem->quantity}, Requested Qty: {$quantity}, " .
                    "Deviation: " . number_format($deviationPercent, 2) . "%"
                );
                $validator->errors()->add(
                    'quantity',
                    sprintf('Quantity deviation of %.1f%% exceeds the maximum allowed threshold of 50%%. Please contact a system administrator for large corrections.', $deviationPercent)
                );
            }
        }
    }
}
