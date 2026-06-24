<?php

namespace Modules\FleetManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Container\Attributes\Log;

class CreateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {

            return [
            // Basic Trip Fields
            'route_id'   => 'required|exists:fleet_routes,id',
            // 'vehicle_id' => 'required|exists:fleet_vehicles,id',
            'vehicle_id' => 'required',

            'start_date' => 'required|date|after_or_equal:today',
            'tag'        => 'required|string|max:255',

            // Products Sent
            'sent' => 'required|array|min:1',
            'sent.*.product_id'  => 'required|exists:products,id',
            'sent.*.batch'       => 'required|string|max:255',
            'sent.*.grade'       => 'required|string|max:255',
            'sent.*.quantity'    => 'required|numeric|min:0.01',
            'sent.*.location_id' => 'required|exists:locations,id',

            // Products Returned (optional but must be valid if provided)
            // 'returned' => 'nullable|array',
            // 'returned.*.product_id'  => 'required_with:returned|exists:products,id',
            // 'returned.*.batch'       => 'required|string|max:255',
            // 'returned.*.grade'       => 'required|string|max:255',
            // 'returned.*.quantity'    => 'required_with:returned|numeric|min:0',
            // 'returned.*.location_id' => 'required_with:returned|exists:locations,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors'  => $validator->errors(),
            'message' => 'Validation failed',
        ], 422));
    }

    public function messages()
    {
        return [
            // Trip
            'route_id.required'   => 'Please select a route.',
            'vehicle_id.required' => 'Please select a vehicle.',
            'start_date.required' => 'Trip start date is required.',
            'tag.required'        => 'Tag is required.',

            // Sent products
            'sent.required'                 => 'At least one sent product must be added.',
            'sent.*.product_id.required'    => 'Product is required.',
            'sent.*.quantity.required'      => 'Quantity is required.',
            'sent.*.quantity.min'           => 'Quantity must be greater than zero.',
            'sent.*.location_id.required'   => 'Location is required for each sent item.',
            'sent.*.batch.required'         => 'Batch is required for each sent item.',
            'sent.*.grade.required'         => 'Grade is required for each sent item.',



            // Returned products
            // 'returned.*.product_id.required_with'    => 'Returned product is required.',
            // 'returned.*.quantity.required_with'      => 'Returned quantity is required.',
            // 'returned.*.location_id.required_with'   => 'Returned location is required.',
        ];
    }
}
