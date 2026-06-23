<?php

namespace Modules\Warehouse;

use Illuminate\Support\ServiceProvider;

use Modules\Warehouse\Providers\Views\ViewServiceProvider as InventoryViews;
use Modules\Warehouse\Providers\Migrations\MigrationsServiceProvider as InventoryMigrations;

/**
 * @deprecated  Superseded by BootstrapWarehouseManagementProvider.
 *              This file is no longer registered in bootstrap/providers.php.
 */
class BootstrapInventoryProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(InventoryViews::class);
        $this->app->register(InventoryMigrations::class);
    }

    public function boot(): void {}
}

