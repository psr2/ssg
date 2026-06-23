<?php

namespace Modules\Expenses\Controllers;

use App\Http\Controllers\Controller;
use Modules\Expenses\Models\Expense;
use Illuminate\Http\Request;

class DeleteExpenseController extends Controller
{
    /**
     * Delete an expense by ID
     */
    public function destroy($id, Request $request)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found.'
            ], 404);
        }

        try {
            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
