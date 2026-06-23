<?php

namespace Modules\StockManagement;

use Illuminate\Support\ServiceProvider;
use Modules\StockManagement\Providers\Views\ViewServiceProvider as StockManagementViews;

use Modules\StockManagement\Providers\Migrations\MigrationsServiceProvider as StockManagementMigrations;

class BootstrapStockManagementProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    $this->app->register(StockManagementViews::class);
    $this->app->register(StockManagementMigrations::class);

  }

  /**
   * Bootstrap services.
   */
  public function boot(): void {}
}
