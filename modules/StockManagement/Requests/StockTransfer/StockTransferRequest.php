<?php

namespace Modules\StockManagement\Requests\StockTransfer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Todo - Validate for stock reduction less than zero (etc..)
 */

class StockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        Log::debug("validator reached");

        return [
            // Transfer details
            't_transferDate'   => 'required|date',
            't_transferType'   => 'required|in:inter,fleet',

            // Locations
            't_fromLocation'   => 'required|string|different:t_toLocation',
            't_toLocation'     => 'required|string',

            /*
             |--------------------------------------------------------------------------
             | Product validation
             |--------------------------------------------------------------------------
             | Supports both single product (current UI) and multi-product (future).
             | If "products" array is sent → validate each row.
             | Otherwise, validate the single t_product_* fields.
             */
            
            // For multi-product rows
            // 'products'                     => 'sometimes|array|min:1',
            // 'products.*.product_name'      => 'required_with:products|string|max:255',
            // 'products.*.batch_code'        => 'required_with:products|string|max:100',
            // 'products.*.grade'             => 'required_with:products|in:A,B,C',
            // 'products.*.quantity'          => 'required_with:products|numeric|min:1',
            // 'products.*.unit'              => 'required_with:products|string|max:50',
            // 'products.*.remarks'           => 'nullable|string|max:1000',

            // For single product (current UI)
            't_product_name'   => 'required_without:products|string|max:255',
            't_batch_code'     => 'required_without:products|string|max:100',
            't_grade'          => 'required_without:products|string|max:100',
            't_quantity'       => 'required_without:products|numeric|min:1',
            't_unit'           => 'required_without:products|string|max:50',
            't_textarea'       => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            // Transfer details
            't_transferDate.required' => 'Transfer date is required.',
            't_transferType.required' => 'Transfer type is required.',
            't_transferType.in'       => 'Transfer type must be Inter-location or Fleet.',

            // Locations
            't_fromLocation.required' => 'From Location is required.',
            't_toLocation.required'   => 'To Location is required.',
            't_fromLocation.different'=> 'From and To locations cannot be the same.',

            // Product messages
            'products.required'       => 'At least one product must be added for transfer.',
            'products.*.product_name.required_with' => 'Product name is required.',
            'products.*.batch_code.required_with'   => 'Batch code is required.',
            'products.*.grade.in'                  => 'Grade must be A, B or C.',
            'products.*.quantity.min'              => 'Quantity must be greater than zero.',

            't_product_name.required_without' => 'Product name is required.',
            't_batch_code.required_without'   => 'Batch code is required.',
            't_grade.required_without'        => 'Grade is required.',
            't_quantity.required_without'     => 'Quantity is required.',
            't_unit.required_without'         => 'Unit is required.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$validator->errors()->any()) {
                $this->validateStockAvailability($validator);
            }
        });
    }

    protected function validateStockAvailability($validator): void
    {
        $fromLocation = $this->input('t_fromLocation');
        $productId = $this->input('t_product_name');
        $batchCode = $this->input('t_batch_code');
        $grade = $this->input('t_grade');
        $requestedQty = floatval($this->input('t_quantity', 0));

        if ($fromLocation && $productId && $batchCode && $grade) {
            $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
            
            try {
                $availableQty = $ledgerService->getAvailableStock(
                    (int) $fromLocation,
                    (int) $productId,
                    $batchCode,
                    $grade
                );
            } catch (\Exception $e) {
                $availableQty = 0.00;
            }

            if ($requestedQty > $availableQty) {
                $validator->errors()->add(
                    't_quantity',
                    "Requested quantity ({$requestedQty}) exceeds available stock ({$availableQty}) for batch {$batchCode} (Grade: {$grade}) at the source location."
                );
            }
        }
    }
}
