<?php

namespace Modules\FleetManagement;

use Illuminate\Support\ServiceProvider;
use Modules\FleetManagement\API\Contracts\RouteNamesInterface;
use Modules\FleetManagement\API\Internal\RouteNames;
use Modules\FleetManagement\Providers\Migrations\MigrationsServiceProvider as FleetMigrations;
use Modules\FleetManagement\Providers\Views\ViewServiceProvider as StockManagementViews;
// use Modules\FleetManagement\Database\Seeders\FleetRouteSeeder;
use Illuminate\Pagination\Paginator;



class BootstrapFleetManagementProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    $this->app->register(StockManagementViews::class);
    $this->app->register(FleetMigrations::class);
    // $this->call(\Modules\FleetManagement\Database\Seeders\FleetRouteSeeder::class);
    $this->app->bind(RouteNamesInterface::class, RouteNames::class);
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void {
        Paginator::useBootstrapFive();

  }
}
