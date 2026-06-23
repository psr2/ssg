<?php

namespace Modules\StockManagement\Requests\StockIn;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;


class PurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {

        Log::debug('reched stock in request');

        return [

            // Core fields
            'stock_type'       => 'required|string',
            'reference_no'     => 'required|string|max:255',
            'movement_date'    => 'required|date',

            // Stock IN-specific fields
            // 'source'           => 'required|string|max:255',
            'in_type'          => 'required|in:purchase,return',

            // Only required if in_type is 'return'
            'return_source'    => 'required_if:in_type,return|string|max:255',
            'return_reason'    => 'required_if:in_type,return|string|max:500',
            'customer_name'    => 'required_if:in_type,return|string|max:255',
            'bill_number' => 'required_if:in_type,return|int|max:20',

            // Dynamic product rows 
            'items'                 => 'required',

            /**Product name was added on 18/11/2025 , 
              *make sure it reflects with other parts of the code 
              */

            'items.*.product_id'    => 'required|numeric',
            'items.*.product_name'  => 'required|string|max:30',

            'items.*.grade'         => 'required|string|max:100',
            'items.*.location_id'   => 'required|numeric',
            'items.*.quantity'      => 'required|numeric|min:0.01',
            'items.*.unit'          => 'required|string|max:50',
            'items.*.unit_cost'     => 'required|numeric|min:0',
            'items.*.total'         => 'required|numeric|min:0',
            'items.*.remarks'       => 'required|string|max:1000',
            'items.*.invoice_number'     => 'required|string|max:100',
            'items.*.vendor'             => 'required|string|max:255',
            'items.*.purchase_date'      => 'required|date',


        ];
    }

    /**
     * Custom error messages for validation.
     */
    public function messages(): array
    {

        return [
            'stock_type.in' => 'Only stock IN operations are supported by this request.',


            'reference_no.required' => 'Reference number is required.',
            'movement_date.required' => 'Movement date is required.',
            'movement_date.date'     => 'Invalid date format for movement date.',

            'source.required'    => 'Source is required for stock in.',
            'in_type.required'   => 'Please select the stock in type.',
            'in_type.in' => 'Return type should be either purchase or return',

            'return_source.required_if' => 'Return source is required for return type.',
            'return_reason.required_if' => 'Return reason is required for return type.',

            'customer_name.required_if' => 'Customer name is required for return type.',
            'customer_contact.required_if' => 'Customer contact is required for return type.',

            'items.required'                   => 'At least one item must be added.',
            'items.array'                      => 'Items must be in an array format.',

            'items.*.product.required'         => 'Please enter a product name.',

            'items.*.quantity.required'        => 'Please enter a quantity.',
            'items.*.quantity.numeric'         => 'Quantity must be a valid number.',

            'items.*.unit.required'            => 'Please enter a unit.',

            'items.*.grade.required'           => 'Please select a grade.',

            'items.*.location_id.required' => 'Please select a location.',
            'items.*.location_id.exists'   => 'Selected location does not exist.',


            'items.*.unit_cost.required'       => 'Please enter unit cost.',
            'items.*.unit_cost.numeric'        => 'Unit cost must be a valid number.',

            'items.*.total.required'           => 'Total cannot be empty.',
            'items.*.total.numeric'            => 'Total must be a valid number.',

            'items.*.remarks.required'         => 'Remarks cannot be empty.',


            'items.*.invoice_number.required'  => 'Invoice cannot be empty.',

            'items.*.purchase_date.required'   => 'Date cannot be empty.',
            'items.*.purchase_date.date'       => 'Date must be a valid date.',

            'items.*.vendor.required'          => 'Vendor name cannot be empty.',
            'items.*.vendor.string'            => 'Vendor name must be valid text.',







        ];
    }
}
