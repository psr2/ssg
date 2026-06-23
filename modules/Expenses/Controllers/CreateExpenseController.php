<?php

namespace Modules\Expenses\Controllers;

use App\Http\Controllers\Controller;
use Modules\Expenses\Services\RecordExpense;
use Modules\Expenses\Requests\CreateExpenseRequest;
use Illuminate\Support\Facades\Log;

class CreateExpenseController extends Controller
{

    public function __construct(protected RecordExpense $recordExpense){}

    /**
     * Handle expense creation
     */
    public function index(CreateExpenseRequest $request)
    {

        Log::debug("controller reached");
        Log::debug($request->validated());

        try {
            $expense = $this->recordExpense->handle($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Expense recorded successfully!',
                'data'    => $expense,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to record expense: ' . $e->getMessage(),
            ], 500);
        }
    }

   
}
