<?php

namespace Modules\Warehouse\Providers\Routes;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    private const ROUTES_PATH = __DIR__ . '/../../Routes/web.php';

    public function boot(): void
    {
        Route::middleware('web')->group(function () {
            $path = realpath(self::ROUTES_PATH);
            if ($path) {
                require $path;
            }
        });
    }
}
