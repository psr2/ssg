<?php

namespace Modules\Inventory;

use Illuminate\Support\ServiceProvider;

use Modules\Inventory\Providers\Contracts\RegisterContracts;
use Modules\Inventory\Providers\Views\ViewServiceProvider as InventoryViews;
use Modules\Inventory\Providers\Migrations\MigrationsServiceProvider as InventoryMigrations;

class BootstrapInventoryProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    $this->app->register(InventoryViews::class);
    $this->app->register(InventoryMigrations::class);
    $this->app->register(RegisterContracts::class);
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void {}
}
