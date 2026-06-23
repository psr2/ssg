<?php

namespace Modules\Expenses\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class CreateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow all authenticated users — modify if needed
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'new_category' => trim(strtolower($this->new_category)),
        ]);
    }


    public function rules(): array
    {
        return [

            'new_category' => 'required|string|max:25|unique:expense_categories,name',

        ];
    }

    public function messages(): array
    {
        return [
            'new_category.required' => 'Please enter a category.',
            'new_category.string'  => 'Category must contain string only.', // ADD THIS
            'new_category.unique'=>'This category name exists , Please enter a unique name'
        ];
    }
}
