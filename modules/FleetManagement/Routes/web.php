<?php

use Illuminate\Support\Facades\Route;
use Modules\FleetManagement\Controllers\FleetCustomerController;
use Modules\FleetManagement\Controllers\FleetViewResourceController;
use Modules\FleetManagement\Controllers\FleetRouteController as RouteController;
use Modules\FleetManagement\Controllers\FleetSaleController;
use Modules\FleetManagement\Controllers\FleetVehicleController;
use Modules\FleetManagement\Controllers\FleetTripController;
use Modules\FleetManagement\Controllers\StockDispatchController;
use Modules\FleetManagement\Controllers\UpdatePaymentsController;

use Modules\FleetManagement\Controllers\DownloadReportController;

// Fleet main views
Route::get('/fleet-management', [FleetViewResourceController::class, 'index']);
Route::get('/fleet-routes', [FleetViewResourceController::class, 'routes']);
Route::get('/fleet-vehicles', [FleetViewResourceController::class, 'vehicles']);

// Fleet Routes API
Route::prefix('api/fleet-routes')->group(function () {
    Route::get('/', [RouteController::class, 'index']);          // List
    Route::post('/', [RouteController::class, 'store']);         // Create
    Route::put('/{id}', [RouteController::class, 'update']);     // Update
    Route::delete('/{id}', [RouteController::class, 'destroy']); // Delete
});



Route::prefix('fleet/vehicles')->group(function () {
    Route::get('/', [FleetVehicleController::class, 'index']);
    Route::post('/', [FleetVehicleController::class, 'store']);
    Route::put('/{id}', [FleetVehicleController::class, 'update']);
    Route::delete('/{id}', [FleetVehicleController::class, 'destroy']);
});


Route::get('/fleet-trips', [FleetTripController::class, 'index']);
Route::post('/create-trip', [FleetTripController::class, 'createTrip']);
Route::post('/fleet/search-batch-code', [FleetTripController::class, 'searchBatches']);

//Dispatch stock to the fleet
Route::post('/stock-dispatch', [StockDispatchController::class, 'handleStockDispatch']);

//Flet Sale

// Route::get('/fleet-sale', [FleetSaleController::class, 'index']);


Route::prefix("/fleet/sale")->group(function (){
    Route::get('/', [FleetSaleController::class, 'index']);
    Route::post('/search/routes', [FleetSaleController::class, 'routeName']);
    Route::post('/store', [FleetSaleController::class, 'storePayments']);
    Route::get('/latest-trips', [FleetSaleController::class, 'latestTrips']);
    Route::post('/upload-report', [FleetSaleController::class, 'uploadReport']);
});

Route::prefix("/fleet/customers")->group(function (){
    Route::post('/', [FleetCustomerController::class, 'getCustomers']);
 

});
Route::prefix("/fleet/payment")->group(function (){
    
    Route::post('/update', [UpdatePaymentsController::class, 'index']);
 

});

Route::post('/create-trip', [FleetTripController::class, 'createTrip']);
Route::get('/search-batches', [FleetTripController::class, 'searchBatches']);
Route::get('/fleet-trips/{trip}/details', [FleetTripController::class, 'getTripDetails']);
Route::post('/fleet-trips/{trip}/adjust', [FleetTripController::class, 'adjustTrip']);

Route::get('/fs', [DownloadReportController::class, 'index']);


Route::get('/fleet-credit-report/csv', [DownloadReportController::class, 'download'])
    ->name('fleet-credit-report.download');
