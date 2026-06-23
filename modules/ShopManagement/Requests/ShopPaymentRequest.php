<?php

namespace Modules\ShopManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\ShopManagement\Models\ShopCustomer;
use Modules\ShopManagement\Models\ShopInventory;

/**
 * Todo -  Refactor the class , validate  against inventory using batch and grade value to identify stock
 */

class ShopPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for storing payment with multiple items.
     */
    public function rules()
    {

        return [

            'customer_name' => ['required', 'string', 'max:255'],
            'bill_no' => ['required', 'string', 'max:50', 'unique:fleet_sales,bill_number'],
            'payment_status' => ['required', 'string', 'in:paid,partial,unpaid'],
            'amount_paid' => ['required_if:payment_status,paid,partial', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date', Rule::date()->beforeOrEqual(today()),],
            'payment_mode' => ['required', 'in:upi,cash,other'],
            'notes' => ['sometimes', 'required', 'string'],

            'customer_contact' => ['sometimes', 'required'],
            'business_name' => ['sometimes', 'required'],
            'location_name' => ['sometimes', 'required'],


            //Payment and sale quantity inputs
            'items' => ['required', 'array', 'min:1'],
            'items.*.product' => ['required', 'regex:/^[a-zA-Z0-9\s\-]+$/', 'max:15'],
            'items.*.quantity' => ['required', 'string', 'numeric', 'min:0.01'],
            'items.*.unit' => ['required', 'string', 'in:kg,pcs'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.total_price' => ['required', 'numeric', 'min:0'],


        ];
    }


    /**
     * Handle failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors'  => $validator->errors(),
            'message' => 'Validation failed',
        ], 422));
    }

    /**
     * Apply custom validation rules after the base validation.
     *
     * Business Rules:
     * 1. If payment status is "paid", the amount paid must equal the grand total.
     * 2. If payment status is "unpaid", the amount paid must be zero.
     * 3. If payment status is "partial", the amount paid must be less than the grand total.
     * 4. The amount paid must never exceed the grand total.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $grandTotal = $this->calculateGrandTotal();
            $status = $this->input('payment_status');
            $amountPaid = floatval($this->input('amount_paid', 0));
            $quantity = $this->input('items.*.quantity');

            if ($this->isPaidMismatch($status, $amountPaid, $grandTotal)) {
                $validator->errors()->add(
                    'amount_paid',
                    $this->messages()['amount_paid.full_match']
                );
            }

            if ($this->isPartialMismatch($status, $amountPaid, $grandTotal)) {
                $validator->errors()->add(
                    'amount_paid',
                    $this->messages()['amount_paid.partial_mismatch']
                );
            }

            if ($this->isOverLimit($amountPaid, $grandTotal)) {
                $validator->errors()->add(
                    'amount_paid',
                    $this->messages()['amount_paid.over_limit']
                );
            }

            if ($this->isPartialZero($amountPaid, $status)) {
                $validator->errors()->add(
                    'amount_paid',
                    $this->messages()['amount_paid.partial_cant_be_zero']
                );
            }

            if ($this->isUnpaidMismatch($status, $amountPaid)) {
                $validator->errors()->add(
                    'amount_paid',
                    $this->messages()['amount_paid.unpaid_not_allowed']
                );
            }

            // if ($this->isGreaterThanAvailableStock($status, $quantity)) {
            //     $validator->errors()->add(
            //         'amount_paid',
            //         $this->messages()['amount_paid.full_match']
            //     );
            // }


            // ---- NEW: Customer phone duplicate check ----
            if ($this->filled('customer_contact')) {
                $exists = ShopCustomer::where('phone', $this->input('customer_contact'))
                    ->where('shop_id', $this->input('shop_id'))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'customer_contact',
                        'This phone number is already registered .'
                    );
                }
            }

            $this->validateInventoryStock($validator);
        });
    }

    /**
     * Calculate the grand total based on item prices.
     *
     * @return float
     */
    protected function calculateGrandTotal(): float
    {
        $items = $this->input('items', []);
        return array_sum(array_map('floatval', array_column($items, 'total_price')));
    }

    /**
     * Check if "paid" status has mismatched amount.
     */
    protected function isPaidMismatch($status, $amountPaid, $grandTotal): bool
    {
        return $status === 'paid' && $amountPaid !== $grandTotal;
    }

    /**
     * Check if "partial" status is invalid (amount >= grand total).
     */
    protected function isPartialMismatch($status, $amountPaid, $grandTotal): bool
    {
        return $status === 'partial' && $amountPaid >= $grandTotal;
    }

    /**
     * Check if amount paid exceeds the grand total.
     */
    protected function isOverLimit($amountPaid, $grandTotal): bool
    {
        return $amountPaid > $grandTotal;
    }

    /**
     * Check if "unpaid" status has non-zero amount.
     */
    protected function isUnpaidMismatch($status, $amountPaid): bool
    {
        return $status === 'unpaid' && $amountPaid > 0;
    }

    //needs checking not working

    protected function isPartialZero($status, $amountPaid)
    {

        return $status === 'partial' && $amountPaid <= 0;
    }
    private function validateInventoryStock($validator)
    {
        Log::debug("Reached inventory stock validation");

        $items = $this->input('items', []);
        $shopId = $this->input('shop_id');

        Log::debug('Inventory validation: items received', $items);

        foreach ($items as $index => $item) {
            $productId = $item['product'] ?? null; // Note: your input key is 'product', not 'product_id'
            $requestedQty = floatval($item['quantity'] ?? 0);
            $batchCode = $item['batch_code'] ?? null;

            Log::debug("Validating item at index {$index}", [
                'product_id' => $productId,
                'batch_code' => $batchCode,
                'requested_qty' => $requestedQty,
                'shop_id' => $shopId
            ]);

            if (!$productId || !$requestedQty || !$shopId || !$batchCode) {
                Log::warning("Missing data for inventory check", [
                    'product_id' => $productId,
                    'batch_code' => $batchCode,
                    'requested_qty' => $requestedQty,
                    'shop_id' => $shopId
                ]);
                continue;
            }

            $inventory = ShopInventory::where('product_id', $productId)
                ->where('shop_id', $shopId)
                ->where('batch_id', $batchCode)
                ->first();

            if (!$inventory) {
                Log::warning("Inventory not found", [
                    'product_id' => $productId,
                    'batch_code' => $batchCode,
                    'shop_id' => $shopId
                ]);
                $validator->errors()->add(
                     "common_error",
                    "Inventory not found for product ID {$productId} and batch {$batchCode}."
                );
                continue;
            }

            Log::debug("Inventory found", [
                'available_qty' => $inventory->qty
            ]);

            if ($requestedQty > $inventory->qty) {
                Log::warning("Requested quantity exceeds available stock", [
                    'requested' => $requestedQty,
                    'available' => $inventory->qty
                ]);

                $validator->errors()->add(
                    "common_error",
                    "Requested quantity ({$requestedQty}) exceeds available stock ({$inventory->qty}) for batch {$batchCode}."
                );
            }
        }
    }




    /**
     * Custom error messages.
     */
    public function messages()
    {
        return [
            'trip_id.required' => 'The trip ID is required.',
            'trip_id.exists' => 'The selected trip does not exist.',
            'customer_name.required' => 'The customer name is required.',
            'bill_no.unique' => 'This bill number is already used.',
            'payment_status.in' => 'Please select a valid payment status.',
            'amount_paid.required_if' => 'The amount paid is required when the status is paid.',

            'items.required' => 'At least one product item must be added. Items cannot be empty.',
            'items.*.product.required' => 'The product name is required.',
            'items.*.qty_sold.required' => 'Quantity is required.',
            'items.*.qty_sold.min' => 'Quantity must be greater than 0.',
            'items.*.unit.in' => 'Unit must be either kg or ton.',
            'items.*.total_amount.required' => 'Total amount for each item is required.',

            // custom payment logic messages
            'amount_paid.full_match' => 'Amount paid must exactly match the total when payment status is paid.',
            'amount_paid.partial_mismatch' => 'Partial payments must be less than the grand total amount.',
            'amount_paid.over_limit' => 'Amount paid cannot exceed the total amount of the items.',
            'amount_paid.unpaid_not_allowed' => 'Amount paid must be zero if payment status is unpaid.',
            'amount_paid.partial_cant_be_zero' => 'The payment can not be zero when payment status is partial.',


        ];
    }
}
