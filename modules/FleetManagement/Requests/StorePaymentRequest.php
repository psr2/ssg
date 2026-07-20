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
            'trip_id' => ['required', 'integer', 'exists:fleet_trips,id'],
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
            'items.*.product' => ['required', 'string', 'max:255'],
            'items.*.grade' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['required', 'string', 'max:255'],
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

            if ($this->isPartialZero($status, $amountPaid)) {
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

            // Check trip items and quantities
            $tripId = $this->input('trip_id');
            $items = $this->input('items', []);

            if ($tripId && is_array($items) && count($items) > 0) {
                // Fetch all dispatched stocks for this trip
                $dispatches = \Modules\FleetManagement\Models\FleetStockDispatch::where('fleet_trip_id', $tripId)
                    ->with('product')
                    ->get();

                // Group dispatches by product name, grade, and unit to know totals sent
                $dispatchTotals = [];
                foreach ($dispatches as $dispatch) {
                    $prodName = $dispatch->product->name ?? '';
                    $key = strtolower(trim($prodName)) . '|' . strtolower(trim($dispatch->grade)) . '|' . strtolower(trim($dispatch->unit));
                    if (!isset($dispatchTotals[$key])) {
                        $dispatchTotals[$key] = 0;
                    }
                    $dispatchTotals[$key] += max(0, (float)($dispatch->qty_sent - $dispatch->qty_returned));
                }

                // Fetch all previous active sales for this trip to calculate already sold quantities
                $previousSaleItems = \Modules\FleetManagement\Models\FleetSaleItem::whereHas('sale', function ($query) use ($tripId) {
                    $query->where('fleet_trip_id', $tripId)
                          ->where('total_amount', '>', 0);
                })->get();

                $alreadySoldTotals = [];
                foreach ($previousSaleItems as $prevItem) {
                    $key = strtolower(trim($prevItem->product_name)) . '|' . strtolower(trim($prevItem->grade)) . '|' . strtolower(trim($prevItem->unit));
                    if (!isset($alreadySoldTotals[$key])) {
                        $alreadySoldTotals[$key] = 0;
                    }
                    $alreadySoldTotals[$key] += (float)$prevItem->quantity;
                }

                // Validate each item in the request payload
                $currentRequestTotals = [];

                foreach ($items as $index => $item) {
                    $prodName = $item['product'] ?? '';
                    $grade = $item['grade'] ?? '';
                    $unit = $item['unit'] ?? '';
                    $qty = (float)($item['quantity'] ?? 0);

                    $key = strtolower(trim($prodName)) . '|' . strtolower(trim($grade)) . '|' . strtolower(trim($unit));

                    // 1. Check if the product/grade/unit combination is dispatched
                    if (!isset($dispatchTotals[$key])) {
                        $validator->errors()->add(
                            "items.{$index}.product",
                            "Product '{$prodName}' (Grade: {$grade}, Unit: {$unit}) is not assigned to this trip."
                        );
                        continue;
                    }

                    // 2. Check if the total requested quantity (previous + current request) exceeds dispatched
                    if (!isset($currentRequestTotals[$key])) {
                        $currentRequestTotals[$key] = 0;
                    }
                    $currentRequestTotals[$key] += $qty;

                    $limit = $dispatchTotals[$key];
                    $alreadySold = $alreadySoldTotals[$key] ?? 0.0;
                    $available = $limit - $alreadySold;

                    if ($currentRequestTotals[$key] > $available) {
                        $validator->errors()->add(
                            "items.{$index}.quantity",
                            "Requested quantity ({$qty}) exceeds available stock on this trip. Dispatched: {$limit}, Already Sold: {$alreadySold}, Available: {$available} {$unit}."
                        );
                    }
                }
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
