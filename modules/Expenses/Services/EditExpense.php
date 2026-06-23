<?php

namespace Modules\Expenses\Services;

use Modules\Expenses\Models\Expense;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class EditExpense
{
    /**
     * Update an existing expense
     *
     * @param array $data  Validated input data from the request
     * @return Expense|null
     * @throws Exception
     */
    public function edit(array $data): ?Expense
    {
        // Wrap in transaction to ensure atomicity
        return DB::transaction(function () use ($data) {

            // Find the expense by ID
            $expense = Expense::find($data['id']);

            if (!$expense) {
                throw new Exception("Expense record not found.");
            }

            // Update only fillable fields
            $expense->update([
                'expense_date' => $data['expense_date'],
                'id'  => $data['id'],
                'amount'       => $data['amount'],
                'payment_mode' => $data['payment_mode'],
                'paid_to'      => $data['paid_to'],
                'description'  => $data['description'] ?? null,
                // Optional fields
            ]);

            return $expense;
        });
    }
}
