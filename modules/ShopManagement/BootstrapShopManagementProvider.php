<?php

namespace Modules\ShopManagement;

use Illuminate\Support\ServiceProvider;

use Modules\ShopManagement\Providers\Views\ViewServiceProvider as Views;
use Modules\ShopManagement\Providers\Migrations\MigrationsServiceProvider as ShopMigrations;

class BootstrapShopManagementProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    $this->app->register(Views::class);
    $this->app->register(ShopMigrations::class);

  }

  /**
   * Bootstrap services.
   */
  public function boot(): void {}
}
