<?php

namespace Modules\Locations\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationsRequests extends FormRequest{

     /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

  public function rules(): array
    {
        return [
            'location_name' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z\s]+$/'],
            'location_type' => ['required', 'string', 'max:80', 'alpha'],
            'location_abbreviation' => ['required', 'string', 'max:5', 'alpha'],
            'location_address' => ['required', 'string', 'max:250'],
        ];
    }

    public function messages(): array
    {
        return [
            // 'unit_name.required' => 'Unit name is required.',
            // 'unit_name.regex' => 'Unit name may only contain letters and spaces.',
            // 'unit_abbreviation.required' => 'Abbreviation is required.',
            // 'unit_abbreviation.alpha' => 'A bbreviation may only contain letters.',
        ];
    }

}
