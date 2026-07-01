<?php

use Illuminate\Support\Facades\Route;
use Modules\StockLedger\Controllers\StockAdjustmentController;

Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index']);
Route::post('/stock-adjustments', [StockAdjustmentController::class, 'adjustStock']);
Route::post('/stock-adjustments/{id}/void', [StockAdjustmentController::class, 'voidStock']);
