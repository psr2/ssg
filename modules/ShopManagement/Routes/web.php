<?php

use Illuminate\Support\Facades\Route;

use Modules\ShopManagement\Controllers\Sale\CustomerLookupController;
use Modules\ShopManagement\Controllers\Sale\SaleController;
use Modules\ShopManagement\Controllers\Sale\SaleListingController;
use Modules\ShopManagement\Controllers\Sale\ShopOverview;

Route::prefix('shop')->group(function () {
    Route::get('/sale', [SaleListingController::class, 'index']);
    Route::post('/sale/store/payments', [SaleController::class, 'store']);
    Route::get('/product/list', [SaleController::class, 'productlist']);
    Route::post('/sale/payments/update', [SaleController::class, 'updatePayments']);

  
});

Route::prefix('shop/customer')->group(function () {
    Route::post('/search', [CustomerLookupController::class, 'index']);
});


  /**Shop overview */

Route::prefix('shop')->group(function () {

    Route::get('/overview', [ShopOverview::class, 'index']);
     
});
