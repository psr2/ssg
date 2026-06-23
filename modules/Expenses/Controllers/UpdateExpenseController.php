<?php

namespace Modules\Expenses\Controllers;

use App\Http\Controllers\Controller;
use Modules\Expenses\Requests\EditExpenseRequest;
use Modules\Expenses\Services\EditExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UpdateExpenseController extends Controller
{
    public function __construct(protected EditExpense $perform) {}

    public function index(EditExpenseRequest $request): JsonResponse
    {
        
        try {
            $expense = $this->perform->edit($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully',
                'expense' => $expense,
            ]);

        } catch (\Exception $e) {
            Log::debug($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
