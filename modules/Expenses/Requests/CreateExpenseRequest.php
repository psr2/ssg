<?php

namespace Modules\Expenses\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CreateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow all authenticated users — modify if needed
        return true;
    }

    public function rules(): array
    {
        $request=new Request;
        
        Log::debug($request->all());

        return [
            'expense_date'  => 'required|date',
            'category_id'   => 'required|exists:expense_categories,id',
            'amount'        => 'required|numeric|min:0',
            'payment_mode'  => 'required|in:cash,bank,upi,cheque,credit',
            'paid_to'       => 'required|string|max:255',
            'description'   => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'expense_date.required' => 'Please select a date.',
            'category_id.required'  => 'Please choose a category.',
            'amount.required'       => 'Enter the expense amount.',
            'payment_mode.required' => 'Select a payment mode.',
        ];
    }
}
