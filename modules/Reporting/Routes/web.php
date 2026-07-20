<?php

use Illuminate\Support\Facades\Route;
use Modules\Reporting\Controllers\ReportingController;

Route::get('/reports', [ReportingController::class, 'index']);
Route::get('/reports/overview', [ReportingController::class, 'overview']);
