<?php

namespace Modules\Warehouse\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\Warehouse\Models\WarehouseCustomer;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;

class WarehouseSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'  => ['required', 'string', 'max:255'],
            'customer_id'    => ['sometimes', 'nullable', 'integer'],
            'bill_no'        => ['required', 'string', 'max:50'],
            'payment_status' => ['required', 'string', 'in:paid,partial,unpaid'],
            'amount_paid'    => ['required_if:payment_status,paid,partial', 'numeric', 'min:0'],
            'payment_date'   => ['required', 'date', Rule::date()->beforeOrEqual(today())],
            'payment_mode'   => ['required', 'in:upi,cash,bank,other'],
            'notes'          => ['sometimes', 'nullable', 'string'],
            'shop_id'        => ['required', 'integer', 'exists:locations,id'],

            // New customer fields (optional — shown when customer not found)
            'customer_contact' => ['sometimes', 'nullable', 'string'],
            'business_name'    => ['sometimes', 'nullable', 'string'],
            'location_name'    => ['sometimes', 'nullable', 'string'],

            // Sale items — all columns needed by repository must be listed here
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product'      => ['required', 'integer', 'exists:products,id'],
            'items.*.batch_code'   => ['required', 'string'],
            'items.*.grade'        => ['required', 'integer', 'in:1,2'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.01'],
            'items.*.unit'         => ['required', 'string', 'in:kg,pcs'],
            'items.*.unit_price'   => ['required', 'numeric', 'min:0'],
            'items.*.total_price'  => ['required', 'numeric', 'min:0'],
        ];

    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors'  => $validator->errors(),
            'message' => 'Validation failed',
        ], 422));
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $grandTotal  = $this->calculateGrandTotal();
            $status      = $this->input('payment_status');
            $amountPaid  = floatval($this->input('amount_paid', 0));

            if ($status === 'paid' && $amountPaid !== $grandTotal) {
                $validator->errors()->add('amount_paid', 'Amount paid must exactly match the total when payment status is paid.');
            }

            if ($status === 'partial' && $amountPaid >= $grandTotal) {
                $validator->errors()->add('amount_paid', 'Partial payments must be less than the grand total amount.');
            }

            if ($amountPaid > $grandTotal) {
                $validator->errors()->add('amount_paid', 'Amount paid cannot exceed the total amount of the items.');
            }

            if ($status === 'partial' && $amountPaid <= 0) {
                $validator->errors()->add('amount_paid', 'The payment cannot be zero when payment status is partial.');
            }

            if ($status === 'unpaid' && $amountPaid > 0) {
                $validator->errors()->add('amount_paid', 'Amount paid must be zero if payment status is unpaid.');
            }

            // Duplicate phone check for new customers
            if ($this->filled('customer_contact')) {
                $exists = WarehouseCustomer::where('phone', $this->input('customer_contact'))
                    ->where('warehouse_id', $this->input('shop_id'))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('customer_contact', 'This phone number is already registered.');
                }
            }

            $this->validateWarehouseStock($validator);
        });
    }

    protected function calculateGrandTotal(): float
    {
        $items = $this->input('items', []);
        return array_sum(array_map('floatval', array_column($items, 'total_price')));
    }

    private function validateWarehouseStock($validator): void
    {
        $items       = $this->input('items', []);
        $warehouseId = $this->input('shop_id');

        Log::debug("validateWarehouseStock: warehouseId = {$warehouseId}");

        foreach ($items as $index => $item) {
            $productId    = $item['product'] ?? null;
            $requestedQty = floatval($item['quantity'] ?? 0);
            $batchCode    = $item['batch_code'] ?? null;
            $grade        = $item['grade'] ?? null;

            Log::debug("Item at index {$index}: product = " . json_encode($productId) . ", quantity = " . json_encode($requestedQty) . ", batch_code = " . json_encode($batchCode) . ", grade = " . json_encode($grade));

            if (!$productId || !$requestedQty || !$warehouseId || !$batchCode) {
                Log::debug("Skipping validation due to missing fields");
                continue;
            }

            Log::debug("Product ID: " . $productId);
            Log::debug("Batch Code: " . $batchCode);
            Log::debug("Grade: " . $grade);
            Log::debug("Warehouse ID: " . $warehouseId);

            $inventory = WarehouseInventory::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch', $batchCode)
                ->where('grade', $grade)
                ->first();

            if (!$inventory) {
                // Fallback: search ignoring grade
                $inventory = WarehouseInventory::where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->where('batch', $batchCode)
                    ->first();
            }

            Log::debug("Query result for item at index {$index}: " . json_encode($inventory));

            if (!$inventory) {
                $validator->errors()->add(
                    'common_error',
                    "Inventory not found for product ID {$productId} and batch {$batchCode}."
                );
                continue;
            }

            if ($requestedQty > $inventory->qty) {
                $validator->errors()->add(
                    'common_error',
                    "Requested quantity ({$requestedQty}) exceeds available warehouse stock ({$inventory->qty}) for batch {$batchCode}."
                );
            }
        }
    }

    public function messages(): array
    {
        return [
            'customer_name.required'  => 'The customer name is required.',
            'bill_no.required'        => 'The bill number is required.',
            'payment_status.in'       => 'Please select a valid payment status.',
            'amount_paid.required_if' => 'The amount paid is required when the status is paid or partial.',
            'items.required'          => 'At least one product item must be added.',
            'items.*.product.required' => 'The product name is required.',
            'items.*.quantity.min'    => 'Quantity must be greater than 0.',
            'items.*.unit.in'         => 'Unit must be either kg or pcs.',
            'shop_id.exists'          => 'The selected warehouse is invalid.',
        ];
    }
}
