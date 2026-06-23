<?php

use Illuminate\Support\Facades\Route;
use Modules\Expenses\Controllers\CreateExpenseCategoryController;
use Modules\Expenses\Controllers\CreateExpenseController;
use Modules\Expenses\Controllers\ExpenseUIController;
use Modules\Expenses\Controllers\UpdateExpenseController;
use Modules\Expenses\Controllers\DeleteExpenseController;

 Route::get('/expenses', [ExpenseUIController::class, 'index']);

 Route::post('create-expense', [CreateExpenseController::class, 'index']);

 Route::post('create-category', [CreateExpenseCategoryController::class, 'store']);


 Route::post('update-expense', [UpdateExpenseController::class, 'index']);

 Route::delete('/delete-expense/{id}', [DeleteExpenseController::class, 'destroy'])
    ->name('expenses.delete');



   
