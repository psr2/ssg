<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Controllers\Products\ProductCatalogController;
use Modules\Inventory\Controllers\UnitController;

/**
 * Product Catalog routes
 */
Route::get('/product/catalog', [ProductCatalogController::class, 'productListing']);

// JSON CRUD API consumed by product.js
Route::prefix('api/products')->group(function () {
    Route::get('/',          [ProductCatalogController::class, 'list']);
    Route::post('/',         [ProductCatalogController::class, 'store']);
    Route::put('/{id}',      [ProductCatalogController::class, 'update']);
    Route::delete('/{id}',   [ProductCatalogController::class, 'destroy']);
});


/**
 * Units Of Measurement routes
 */
Route::get('/units',              [UnitController::class, 'index']);

// JSON CRUD API consumed by unit.js
Route::prefix('api/units')->group(function () {
    Route::get('/',        [UnitController::class, 'list']);
    Route::post('/',       [UnitController::class, 'store']);
    Route::put('/{id}',    [UnitController::class, 'update']);
    Route::delete('/{id}', [UnitController::class, 'destroy']);
});