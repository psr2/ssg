<?php

namespace Modules\Warehouse;

use Illuminate\Support\ServiceProvider;

use Modules\Warehouse\Providers\Views\ViewServiceProvider as WarehouseViews;
use Modules\Warehouse\Providers\Migrations\MigrationsServiceProvider as WarehouseMigrations;
use Modules\Warehouse\Providers\Routes\RouteServiceProvider as WarehouseRoutes;

class BootstrapWarehouseManagementProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(WarehouseViews::class);
        $this->app->register(WarehouseMigrations::class);
        $this->app->register(WarehouseRoutes::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
