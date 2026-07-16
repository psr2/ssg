<?php

use Illuminate\Support\Facades\Route;
use Modules\StockAdjustment\Controllers\StockAdjustmentController;

Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index']);
Route::get('/stock-adjustments/batch-stock', [StockAdjustmentController::class, 'getBatchStock']);
Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store']);
Route::post('/stock-adjustments/{id}/approve', [StockAdjustmentController::class, 'approve']);
