<?php

namespace Modules\Expenses\Services\ExpenseCategory;

use Modules\Expenses\Models\ExpenseCategory as Category;
use Illuminate\Support\Facades\Log;

class CreateExpenseCategory
{
    public function createExpense(array $payload)
    {
        
        $name = strtolower(trim($payload['new_category']));

        // Check if category already exists (case-insensitive)
        $existing = Category::whereRaw('LOWER(name) = ?', [$name])->first();

      

        if ($existing) {
            return $existing->id;
        }

        // Create new category
        $category = Category::create(['name' => $name]);

        Log::debug("Created category ID: ".$category->id);

        return [
            'name' => $payload["new_category"],
            'id'   => $category->id,
        ];
    }
}
