<?php

namespace Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitMeasurementRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

  public function rules(): array
    {
        return [
            'unit_name' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z\s]+$/'],
            'unit_abbreviation' => ['required', 'string', 'max:10', 'alpha'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_name.required' => 'Unit name is required.',
            'unit_name.regex' => 'Unit name may only contain letters and spaces.',
            'unit_abbreviation.required' => 'Abbreviation is required.',
            'unit_abbreviation.alpha' => 'A bbreviation may only contain letters.',
        ];
    }

    
}
