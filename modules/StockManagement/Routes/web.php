<?php

use Illuminate\Support\Facades\Route;
use Modules\StockManagement\Controllers\BatchCodeSearchController;
use Modules\StockManagement\Controllers\StockAdjustmentController;
use Modules\StockManagement\Controllers\StockViewManagementResourceController;
use Modules\StockManagement\Controllers\StockInController;
use Modules\StockManagement\Controllers\StockOutController;
use Modules\StockManagement\Controllers\StockTransferController;
use Modules\StockManagement\Controllers\StockSegregationController;
use Modules\StockManagement\Services\StockMovement\BatchCode\GenerateBatchCode;
use Modules\StockManagement\Services\StockMovement\ReferenceNumber\PurchaseReferenceNumberGenerator;
use Modules\StockManagement\Services\StockMovement\ReferenceNumber\StockReturnReferenceNumberGenerator;


 // All routes realted to Stock Management views

 Route::get('/stock-transit' ,[StockViewManagementResourceController::class ,'stockTransit']); 

//View for stock in 
 Route::get('/stock-movements' ,[StockViewManagementResourceController::class ,'stockPurchase']); 
 
 Route::get('/stock-overview' ,[StockViewManagementResourceController::class ,'overview']); 




//Stock Purchase

 Route::post('/stock-in-entry' ,[StockInController::class ,'stockIn']); 

//Reference Numbers and Batch Id for Stock Purchase

 Route::get('/stock-return-reference-id' ,[StockReturnReferenceNumberGenerator::class ,'generate']);
 Route::get('/stock-purchase-reference-id' ,[PurchaseReferenceNumberGenerator::class ,'generate']); 

  Route::post('/search-batch-code' ,[BatchCodeSearchController::class ,'search']); 

  // Scoped batch searches
  Route::post('/stock-segregation/search-batch-code', [StockSegregationController::class, 'searchBatches']);
  Route::post('/stock-transfer/search-batch-code', [StockTransferController::class, 'searchBatches']);
  Route::post('/stock-out/search-batch-code', [StockOutController::class, 'searchBatches']);


//Stock Out

 Route::post('/stock-out-entry' ,[StockOutController::class ,'stockOut']); 


 //Stock transfer

 Route::get('/stock-transfer' ,[StockViewManagementResourceController::class ,'stockTransfer']); 
 Route::post('/stock-transfer' ,[StockTransferController::class ,'index']); 


 //stock adjustments
 Route::get('/stock-adjustments' ,[StockAdjustmentController::class ,'index']); 


 Route::post('/stock-adjustments' ,[StockAdjustmentController::class ,'adjustStock']); 
 Route::post('/stock-adjustments/{id}/void' ,[StockAdjustmentController::class ,'voidStock']); 

  //stock segregation
  Route::get('/stock-segregation', [StockViewManagementResourceController::class, 'stockSegregation']);
  Route::get('/stock-segregation/batch-details', [StockSegregationController::class, 'batchDetails']);
  Route::post('/stock-segregation/store', [StockSegregationController::class, 'store']);
