<?php

namespace Modules\Locations;

use Illuminate\Support\ServiceProvider;
use Modules\Locations\Providers\Views\ViewServiceProvider as InventoryViews;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;
use Modules\Locations\API\Internal\Services\ShareLocationService;
use Modules\Locations\Providers\Migrations\MigrationsServiceProvider as LocationMigrations;

class BootstrapLocationManagementProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    $this->app->register(InventoryViews::class);
    $this->app->register(LocationMigrations::class);

    $this->app->bind(LocationsInterface::class, ShareLocationService::class);
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void {}
}
