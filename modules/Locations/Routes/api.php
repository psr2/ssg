<?php


use Illuminate\Support\Facades\Route;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;




//List all location names

Route::get('/api-locations', function (LocationsInterface $service) {
    return $service->shareLocation();
});

    