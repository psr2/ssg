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

    protected function prepareForValidation(): void
    {
        if ($this->has('payment_status')) {
            $this->merge([
                'payment_status' => strtolower($this->input('payment_status')),
            ]);
        }

        if ($this->has('payment_mode')) {
            $this->merge([
                'payment_mode' => strtolower($this->input('payment_mode')),
            ]);
        }

        if ($this->has('items') && is_array($this->input('items'))) {
            $items = $this->input('items');
            foreach ($items as $index => $item) {
                if (isset($item['unit'])) {
                    $items[$index]['unit'] = strtolower($item['unit']);
                }
            }
            $this->merge(['items' => $items]);
        }
    }

    public function rules(): array
    {
        return [
            'customer_name'  => ['required', 'string', 'max:255'],
            'customer_id'    => ['sometimes', 'nullable', 'integer'],
            'bill_no'        => ['required', 'string', 'max:50'],
            'payment_status' => ['required', 'string', 'in:paid,partial,unpaid'],
            'amount_paid'    => ['required_if:payment_status,paid,partial', 'numeric', 'min:0'],
            'payment_date'   => ['required', 'date', 'before_or_equal:' . today()->addDays(2)->toDateString()],
            'payment_mode'   => ['required', 'in:upi,cash,bank,other,UPI,Cash,Other,Bank,Other'],
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
            'items.*.grade'        => ['required', 'string'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.01'],
            'items.*.unit'         => ['required', 'string'],
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

        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);

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

            // 1. Check unit matches purchase unit case-insensitively
            try {
                $latestUnit = $ledgerService->getLatestUnit($warehouseId, (int)$productId, $batchCode, $grade);
                if (strtolower($item['unit'] ?? '') !== strtolower($latestUnit)) {
                    $validator->errors()->add(
                        "items.{$index}.unit",
                        "The selected unit '" . ($item['unit'] ?? '') . "' does not match the purchase unit '{$latestUnit}' for batch '{$batchCode}'."
                    );
                }
            } catch (\Exception $e) {
                Log::error("Error checking unit matching: " . $e->getMessage());
            }

            // 2. Check stock availability
            try {
                $availableQty = $ledgerService->getAvailableStock($warehouseId, $productId, $batchCode, $grade);
            } catch (\Exception $e) {
                Log::error("Error calculating dynamic stock: " . $e->getMessage());
                $availableQty = 0.00;
            }

            if ($requestedQty > $availableQty) {
                $validator->errors()->add(
                    'common_error',
                    "Requested quantity ({$requestedQty}) exceeds available warehouse stock ({$availableQty}) for batch {$batchCode} (Grade: {$grade})."
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
