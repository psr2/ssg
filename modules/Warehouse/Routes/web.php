<?php

use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Controllers\WarehouseSaleListingController;
use Modules\Warehouse\Controllers\WarehouseSaleController;
use Modules\Warehouse\Controllers\WarehouseCustomerLookupController;
use Modules\Warehouse\Controllers\WarehouseOverview;
use Modules\Warehouse\Controllers\WarehouseCreditController;

Route::prefix('warehouse')->group(function () {

    // Overview page
    Route::get('/overview', [WarehouseOverview::class, 'index'])->name('warehouse.overview.index');
    Route::get('/overview/inventory', [WarehouseOverview::class, 'getInventory'])->name('warehouse.overview.inventory');

    // Credit Details page & Search
    Route::get('/credits', [WarehouseCreditController::class, 'index'])->name('warehouse.credits.index');
    Route::post('/credits/search', [WarehouseCreditController::class, 'search'])->name('warehouse.credits.search');

    // Sale listing page
    Route::get('/sale', [WarehouseSaleListingController::class, 'index'])->name('warehouse.sale.index');

    // Store new sale
    Route::post('/sale/store', [WarehouseSaleController::class, 'store'])->name('warehouse.sale.store');

    // Product list for dynamic modal
    Route::get('/product/list', [WarehouseSaleController::class, 'productList'])->name('warehouse.product.list');

    // Update payment on existing sale
    Route::post('/sale/payments/update', [WarehouseSaleController::class, 'updatePayments'])->name('warehouse.sale.payments.update');

    // Delete sale
    Route::delete('/sale/{id}/delete', [WarehouseSaleController::class, 'destroy'])->name('warehouse.sale.destroy');

});

Route::prefix('warehouse/customer')->group(function () {

    // Customer search (Fuse.js autocomplete)
    Route::post('/search', [WarehouseCustomerLookupController::class, 'index'])->name('warehouse.customer.search');

});
