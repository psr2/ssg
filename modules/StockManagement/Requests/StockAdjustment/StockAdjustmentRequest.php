<?php

namespace Modules\StockManagement\Requests\StockAdjustment;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\Locations\Models\LocationModel;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
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
     * Corrections/Adjustments are only allowed if NO sales, transfers, or other movements have occurred for this batch.
     * This is determined by verifying if the current physical inventory matches the initial stock-in quantity.
     */
    protected function validatePreMovementState($validator, StockPurchaseItem $stockItem): void
    {
        $loc = LocationModel::find($stockItem->location_id);
        if (!$loc) {
            $validator->errors()->add('id', 'The original location associated with this stock item does not exist.');
            return;
        }

        $locType = $loc->type;
        $locTypeValue = (is_object($locType) && isset($locType->value)) ? $locType->value : (string) $locType;

        if ($locTypeValue === 'shop') {
            $inventory = \Modules\ShopManagement\Models\ShopInventory::where('shop_id', $stockItem->location_id)
                ->where('batch_id', $stockItem->batch)
                ->first();
        } else {
            $inventory = \Modules\StockManagement\Models\Warehouse\WarehouseInventory::where('warehouse_id', $stockItem->location_id)
                ->where('batch', $stockItem->batch)
                ->first();
        }

        if (!$inventory) {
            $validator->errors()->add('id', 'No physical inventory matches this batch at the original location. Adjustments are not allowed.');
        } else if ($inventory->qty != $stockItem->quantity) {
            $validator->errors()->add('id', 'Active stock movements (sales or transfers) have already occurred for this batch. Human error corrections are no longer permitted.');
        }
    }

    /**
     * Enforce a hard threshold on adjustment deviation.
     * Quantity cannot be adjusted by more than 50% from the original received quantity.
     */
    protected function validateQuantityDeviation($validator, StockPurchaseItem $stockItem): void
    {
        $quantity = $this->input('quantity');
        if ($quantity !== null && $quantity != $stockItem->quantity) {
            $difference = abs($quantity - $stockItem->quantity);
            $deviationPercent = ($difference / $stockItem->quantity) * 100;
            if ($deviationPercent > 50) {
                $validator->errors()->add(
                    'quantity',
                    sprintf('Quantity deviation of %.1f%% exceeds the maximum allowed threshold of 50%%. Please contact a system administrator for large corrections.', $deviationPercent)
                );
            }
        }
    }
}
