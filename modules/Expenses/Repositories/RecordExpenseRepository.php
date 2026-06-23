<?php

namespace Modules\Expenses\Repositories;

use Modules\Expenses\Models\Expense;

class RecordExpenseRepository
{
    public function create(array $data)
    {

        return Expense::create([
            'expense_date'  => $data['expense_date'],
            'category_id'   => $data['category_id'],
            'amount'        => $data['amount'],
            'payment_mode'  => $data['payment_mode'],
            'paid_to'       => $data['paid_to'] ?? null,
            'description'   => $data['description'] ?? null,
         
        ]);
    }
}
