<?php

namespace Modules\Warehouse\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class WarehousePaymentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'  => 'required|string|max:255',
            'total_bill'     => 'required|numeric|min:1',
            'pending_amount' => 'required|numeric|min:1',
            'new_amount'     => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'customer_id'    => 'required|integer',
            'sale_id'        => 'required|integer',
            'last_updated'   => 'required',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors'  => $validator->errors(),
            'message' => 'Validation failed',
        ], 422));
    }
}
