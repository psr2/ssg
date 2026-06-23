<?php

use Illuminate\Support\Facades\Route;
use Modules\Locations\Controllers\LocationsResourceController as LocationsController;

/**
 * All routes realted to Units Of measurement
 */ 
 Route::get('/locations' ,[LocationsController::class ,'index']); 

Route::post('/create-location' ,[LocationsController::class ,'store']);

Route::post('/edit-location' ,[LocationsController::class ,'edit']);

Route::post('/update-location', [LocationsController::class, 'update']);

Route::delete('/delete-location/{id}', [LocationsController::class, 'destroy']);


//API Internal Routes






    