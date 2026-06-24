<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Controllers\ProductGradeController;

Route::middleware('web')->group(function () {
    Route::get('/settings/grades', [ProductGradeController::class, 'index']);
    Route::post('/settings/grades', [ProductGradeController::class, 'store']);
    Route::delete('/settings/grades/{id}', [ProductGradeController::class, 'destroy']);
});
