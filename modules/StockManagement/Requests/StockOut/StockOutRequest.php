<?php

namespace Modules\StockManagement\Requests\StockOut;


use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Modules\StockManagement\Models\StockSummary\StockSummary ;


class StockOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        Log::debug('reached stock out request');

        return [

            // Core fields
            'stock_type'       => 'required|string',
            'reference_no'     => 'nullable|string|max:255',
            'movement_date'    => 'required|date',
            'destination'      => 'required|string',
            'out_type'         => 'required|string',


            // Dynamic product rows 
            'items'                 => 'required|array',
            'items.*.product_id'    => 'required|numeric',
            'items.*.grade'         => 'required|string|max:100',
            'items.*.location_id'   => 'required|numeric',
            'items.*.quantity'      => [
                'required',
                'numeric',
                'min:0.01',
                $this->hasStockForBatchAtLocation()
            ],
            'items.*.unit'          => 'required|string|max:50',
            'items.*.unit_cost'     => 'required|numeric|min:0',
            'items.*.total'         => 'required|numeric|min:0',
            'items.*.remarks'       => 'nullable|string|max:1000',
            'items.*.batch_code'    => 'required|string|max:100',

        ];
    }

    /**
     * Custom error messages for validation.
     */
    public function messages(): array
    {

        return [
            'stock_type.in' => 'Only stock IN operations are supported by this request.',


            'reference_no.required' => 'Reference number is required.',
            'movement_date.required' => 'Movement date is required.',
            'movement_date.date'     => 'Invalid date format for movement date.',

            'source.required'    => 'Source is required for stock in.',
            'in_type.required'   => 'Please select the stock in type.',
            'in_type.in' => 'Return type should be either purchase or return',

            'return_source.required_if' => 'Return source is required for return type.',
            'return_reason.required_if' => 'Return reason is required for return type.',

            'customer_name.required_if' => 'Customer name is required for return type.',
            'customer_contact.required_if' => 'Customer contact is required for return type.',

            'items.required'                   => 'At least one item must be added.',
            'items.array'                      => 'Items must be in an array format.',

            'items.*.product.required'         => 'Please enter a product name.',

            'items.*.quantity.required'        => 'Please enter a quantity.',
            'items.*.quantity.numeric'         => 'Quantity must be a valid number.',

            'items.*.unit.required'            => 'Please enter a unit.',

            'items.*.grade.required'           => 'Please select a grade.',

            'items.*.location_id.required' => 'Please select a location.',
            'items.*.location_id.exists'   => 'Selected location does not exist.',


            'items.*.unit_cost.required'       => 'Please enter unit cost.',
            'items.*.unit_cost.numeric'        => 'Unit cost must be a valid number.',

            'items.*.total.required'           => 'Total cannot be empty.',
            'items.*.total.numeric'            => 'Total must be a valid number.',

            'items.*.remarks.required'         => 'Remarks cannot be empty.',

            'items.*.batch_code.required'      => 'Batch code cannot be empty.',

            'items.*.invoice_number.required'  => 'Invoice cannot be empty.',

            'items.*.purchase_date.required'   => 'Date cannot be empty.',
            'items.*.purchase_date.date'       => 'Date must be a valid date.',

            'items.*.vendor.required'          => 'Vendor name cannot be empty.',
            'items.*.vendor.string'            => 'Vendor name must be valid text.',







        ];
    }

    /*
    |-----------------------------------------------------------------------------
    | Check if sufficient stock exists for a given batch location and grade
    |-----------------------------------------------------------------------------
    | 
    | The validation rule ensures the stock summary table has enough stock left
    | for the given location,grade and batch code. $itemIndex is used inorder 
    | to find respective batch code , grade and location id from the items 
    | array based on array index.
    */

    private function hasStockForBatchAtLocation()
    {
        return new class implements ValidationRule {
            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                $itemIndex = explode('.', $attribute)[1];
                $item = request()->input("items.{$itemIndex}");
                $batchCode = $item['batch_code'] ?? null;
                $locationId = $item['location_id'] ?? null;
                $grade = $item['grade'] ?? null;
                $productId = $item['product_id'] ?? null;

                if (!$productId || !$locationId || !$batchCode || !$grade) {
                    $fail("Missing required product, location, batch, or grade details for item " . ($itemIndex + 1));
                    return;
                }

                $service = app(\Modules\StockLedger\Services\StockLedgerService::class);
                $available = $service->getAvailableStock((int)$locationId, (int)$productId, $batchCode, $grade);

                if ($available < $value) {
                    $fail("The quantity for item " . ($itemIndex + 1) . " ($value) exceeds available stock ($available).");
                }
            }
        };
    }

}
