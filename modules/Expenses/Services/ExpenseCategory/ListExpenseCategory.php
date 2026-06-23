<?php

namespace Modules\Expenses\Services\ExpenseCategory;

use Modules\Expenses\Contracts\CategoryInterface;
use Modules\Expenses\Models\ExpenseCategory as Category;
use Modules\Expenses\Exceptions\CategoryNotFoundException;
use Illuminate\Support\Facades\Log;

class ListExpenseCategory implements CategoryInterface
{
    public function getExpenseCategories()
    {
        $categories = Category::pluck('id' ,'name');

        if ($categories->isEmpty()) {
          
            throw new CategoryNotFoundException("Expense categories not found.",);
        }

        return $categories;
    }
}
