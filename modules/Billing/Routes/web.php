<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Controllers\BillingAdjustmentController;

Route::middleware(['web'])->group(function () {
    Route::get('/billing-adjustments', [BillingAdjustmentController::class, 'index'])->name('billing.adjustments.index');
    Route::get('/billing-adjustments/sales', [BillingAdjustmentController::class, 'getSales'])->name('billing.adjustments.sales');
    Route::post('/billing-adjustments', [BillingAdjustmentController::class, 'store'])->name('billing.adjustments.store');
});
