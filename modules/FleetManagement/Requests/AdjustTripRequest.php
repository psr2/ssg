<?php

namespace Modules\FleetManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdjustTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id'         => 'required|exists:fleet_routes,id',
            'vehicle_id'       => 'required|exists:fleet_vehicles,id',
            'start_date'       => 'required|date',
            'tag'              => 'required|string|max:255',
            'items'            => 'required|array|min:1',
            'items.*.id'       => 'required|exists:fleet_trip_stocks,id',
            'items.*.quantity' => 'required|integer|min:0',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors'  => $validator->errors(),
            'message' => 'Validation failed',
        ], 422));
    }

    public function messages(): array
    {
        return [
            'route_id.required'         => 'Route is required.',
            'route_id.exists'           => 'Selected route is invalid.',
            'vehicle_id.required'       => 'Vehicle is required.',
            'vehicle_id.exists'         => 'Selected vehicle is invalid.',
            'start_date.required'       => 'Start date is required.',
            'start_date.date'           => 'Start date must be a valid date.',
            'tag.required'              => 'Tag is required.',
            'items.required'            => 'At least one dispatched product is required.',
            'items.*.id.required'       => 'Item reference is required.',
            'items.*.id.exists'         => 'Invalid item reference.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.integer'  => 'Quantity must be an integer.',
            'items.*.quantity.min'      => 'Quantity must be at least 0.',
        ];
    }
}
