<?php

namespace Modules\StockAdjustment;

use Illuminate\Support\ServiceProvider;
use Modules\StockAdjustment\Providers\Views\ViewServiceProvider as StockAdjustmentViews;
use Modules\StockAdjustment\Providers\Migrations\MigrationsServiceProvider as StockAdjustmentMigrations;

class BootstrapStockAdjustmentProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(StockAdjustmentViews::class);
        $this->app->register(StockAdjustmentMigrations::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
