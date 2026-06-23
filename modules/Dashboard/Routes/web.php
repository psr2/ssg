<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Controllers\DashboardViewController;

// Route::get('/dashboard', function () {
//     return view('dashboard::dash'); // load the child view
// });



Route::get('/dashboard' ,[DashboardViewController::class ,'index']);

