<?php

namespace Modules\ShopManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true; // ✅ Allow the request (or apply auth logic here)
    }

    /**
     * Validate for customer and sale id as well
     * 
     */

    public function rules()
    {
        return [
            'customer_name'   => 'required|string|max:255',
            'total_bill'      => 'required|numeric|min:1',
            'pending_amount'  => 'required|numeric|min:1',
            'new_amount'      => 'required|numeric|min:1',
            'payment_method'  => 'required',
            'customer_id'     => 'required',
            'sale_id'         => 'required',
            'last_updated'    => 'required'
        ];
    }

    public function messages()
    {
        return [
            'record_id.exists' => 'The selected record does not exist.',
        ];
    }

    private function hasEqualTotalPayment() {}

    private function hasEqualAmountPaid() {}
}
