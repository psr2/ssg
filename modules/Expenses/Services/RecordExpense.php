<?php

namespace Modules\Expenses\Services;

use Modules\Expenses\Repositories\RecordExpenseRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecordExpense
{

    public function __construct(protected RecordExpenseRepository $repository){}

    public function handle(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Add created_by automatically
            $data['created_by'] = Auth::id() ?? 1; // fallback for testing
            
            // Optional: set approved_by as null initially
            $data['approved_by'] = null;

            return $this->repository->create($data);
        });
    }
}
