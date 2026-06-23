<?php

namespace Modules\FleetManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'paymentAmount' => 'required|numeric|min:100', // must be > 0
            'payment-date' => 'required|date',
            'payment-method' => 'required|in:cash,upi,bank',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Payment ID is required.',
            'id.exists' => 'The specified payment ID does not exist.',
            'paymentAmount.required' => 'Please enter a payment amount.',
            'paymentAmount.numeric' => 'Payment amount must be a valid number.',
            'paymentAmount.min' => 'Minimum expected payment is ₹100.',
            'payment-date.required' => 'Please provide a payment date.',
            'payment-date.date' => 'Payment date must be a valid date.',
            'payment-method.required' => 'Please select a payment method.',
            'payment-method.in' => 'Payment method must be one of: cash, UPI, or bank transfer.',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $paymentId = (int) $this->input('id');
            $inputAmount = (float) $this->input('paymentAmount');

            $saleId = $this->getSaleIdForPayment($paymentId);

            if (!$saleId) {
                $validator->errors()->add('id', 'Sale ID not found for this payment.');
                return;
            }

            // First: check cumulative total
            $cumulativeCheckPassed = $this->validateCumulativePayments($validator, $saleId, $paymentId, $inputAmount);

            // Second: only run this if first rule didn't fail
            if ($cumulativeCheckPassed) {
                $this->validatePaymentLessThanSaleTotal($validator, $saleId, $inputAmount);
            }
        });
    }

    // ----------------------------------------
    // 🔹 Business Rule #1: Cumulative payments must not exceed sale total
    // ----------------------------------------
    private function validateCumulativePayments(Validator $validator, int $saleId, int $paymentId, float $newAmount): bool
    {
        $sumOtherPayments = DB::table('fleet_sale_payments')
            ->where('fleet_sale_id', $saleId)
            ->where('id', '!=', $paymentId)
            ->sum('amount');

        $totalSaleAmount = DB::table('fleet_sales')
            ->where('id', $saleId)
            ->value('total_amount');

        $newCumulativeTotal = $sumOtherPayments + $newAmount;

        if ($newCumulativeTotal > (float) $totalSaleAmount) {
            $validator->errors()->add(
                'paymentAmount',
                "Total payments ({$newCumulativeTotal}) cannot exceed the sale total ({$totalSaleAmount})."
            );
            return false;
        }

        return true;
    }

    // ----------------------------------------
    // 🔹 Business Rule #2: Payment amount must be less than sale total
    // ----------------------------------------
    private function validatePaymentLessThanSaleTotal(Validator $validator, int $saleId, float $inputAmount): void
    {
        $totalSaleAmount = DB::table('fleet_sales')
            ->where('id', $saleId)
            ->value('total_amount');

        if ($inputAmount >= (float) $totalSaleAmount) {
            $validator->errors()->add(
                'paymentAmount',
                "Payment amount ({$inputAmount}) must be less than the sale total ({$totalSaleAmount})."
            );
        }
    }

    // ----------------------------------------
    // 🔹 Helper: Get sale_id from payment_id
    // ----------------------------------------
    private function getSaleIdForPayment(int $paymentId): ?int
    {
        return DB::table('fleet_sales')
            ->where('id', $paymentId)
            ->value('id');
    }
}
