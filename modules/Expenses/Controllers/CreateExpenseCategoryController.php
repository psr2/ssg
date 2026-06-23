<?php

namespace Modules\Expenses\Controllers;

use App\Http\Controllers\Controller;
use Modules\Expenses\Requests\CreateCategoryRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Modules\Expenses\Services\ExpenseCategory\CreateExpenseCategory;

class CreateExpenseCategoryController extends Controller
{
    public function __construct(protected CreateExpenseCategory $service)
    {}

    public function store(CreateCategoryRequest $request)
    {
      return  $this->service->createExpense($request->validated());

    }
}
