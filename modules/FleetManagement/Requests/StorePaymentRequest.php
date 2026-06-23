<?php

namespace Modules\FleetManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

/**
 * Todo -  Refactor the class
 */

class StorePaymentRequest extends FormRequest
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
            'trip_id' => ['required', 'integer', 'exists:locations,id'],
            'customer_name' => ['required', 'string', 'max:255'],

            'bill_no' => ['required', 'string', 'max:8', 'unique:fleet_sales,bill_number'],

            'payment_status' => ['required', 'string', 'in:paid,partial,unpaid'],
            'amount_paid' => ['required_if:payment_status,paid,partial', 'numeric', 'min:0'],

            'payment_date' => ['required', 'date'],
            'payment_mode' => ['required', 'in:upi,cash,other'],
            'notes' => ['sometimes', 'required', 'string'],

            //  NEW OPTIONAL FIELDS
            'route_name' => ['sometimes', 'string', 'max:255'],
            'customer_contact' => ['sometimes', 'string', 'max:10'],
            'location_name' => ['sometimes', 'string', 'max:80'],

            // Items
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
