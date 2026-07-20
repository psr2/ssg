<?php

use Illuminate\Support\Facades\Route;
use Modules\Reporting\Controllers\ReportingController;

Route::get('/reports', [ReportingController::class, 'index']);
Route::get('/reports/overview', [ReportingController::class, 'overview']);
Route::get('/reports/type/{type}', [ReportingController::class, 'index']);
Route::get('/reports/data/{type}', [ReportingController::class, 'data']);
Route::get('/reports/download/pdf/{type}', [ReportingController::class, 'downloadPdf']);
Route::get('/reports/preview/{type}', [ReportingController::class, 'previewPdf']);
Route::get('/reports/download/csv/{type}', [ReportingController::class, 'downloadCsv']);

// Direct shortcuts
Route::get('/reports/stock', fn(Illuminate\Http\Request $req) => app(ReportingController::class)->index($req, 'stock'));
Route::get('/reports/ledger', fn(Illuminate\Http\Request $req) => app(ReportingController::class)->index($req, 'ledger'));
Route::get('/reports/warehouse', fn(Illuminate\Http\Request $req) => app(ReportingController::class)->index($req, 'warehouse'));
Route::get('/reports/shop', fn(Illuminate\Http\Request $req) => app(ReportingController::class)->index($req, 'shop'));
Route::get('/reports/fleet', fn(Illuminate\Http\Request $req) => app(ReportingController::class)->index($req, 'fleet'));
Route::get('/reports/expenses', fn(Illuminate\Http\Request $req) => app(ReportingController::class)->index($req, 'expenses'));
Route::get('/reports/adjustments', fn(Illuminate\Http\Request $req) => app(ReportingController::class)->index($req, 'adjustments'));
Route::get('/reports/credits', fn(Illuminate\Http\Request $req) => app(ReportingController::class)->index($req, 'credits'));

