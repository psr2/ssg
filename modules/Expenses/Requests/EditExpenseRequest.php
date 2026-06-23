<?php


namespace Modules\Expenses\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditExpenseRequest extends FormRequest
{
    /**
     * Authorize the request
     */
    public function authorize(): bool
    {
        // Allow all authenticated users; modify as needed
        return true;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'id'            => ['required', 'integer', 'exists:expenses,id'], // ensure record exists
            'expense_date'  => 'required|date',
            'category_id'   => 'required|exists:expense_categories,id',
            'amount'        => 'required|numeric|min:5',
            'payment_mode'  => ['required', Rule::in(['cash','bank','upi','cheque','credit'])],
            'paid_to'       => 'required|string|max:255',
            'description'   => 'nullable|string|max:500',
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'id.required'          => 'Expense record is required.',
            'id.exists'            => 'Expense record does not exist or has been removed.',
            'expense_date.required' => 'Please select a date.',
            'expense_date.date'     => 'Invalid date format.',
            'category_id.required'  => 'Please choose a category.',
            'category_id.exists'    => 'Selected category does not exist.',
            'amount.required'       => 'Enter the expense amount.',
            'amount.numeric'        => 'Amount must be a number.',
            'amount.min'            => 'Amount must be at least 5 rupees.',
            'payment_mode.required' => 'Select a payment mode.',
            'payment_mode.in'       => 'Invalid payment mode selected.',
            'paid_to.required'      => 'Please enter who the payment was made to.',
            'paid_to.string'        => 'Paid To must be text.',
            'paid_to.max'           => 'Paid To cannot exceed 255 characters.',
            'description.string'    => 'Description must be text.',
            'description.max'       => 'Description cannot exceed 500 characters.',
        ];
    }
}
