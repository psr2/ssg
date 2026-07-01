<?php

namespace Modules\StockLedger;

use Illuminate\Support\ServiceProvider;
use Modules\StockLedger\Providers\Migrations\MigrationsServiceProvider as LedgerMigrations;

class BootstrapStockLedgerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(LedgerMigrations::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $viewPath = __DIR__ . '/Views';
        $resolvedPath = realpath($viewPath);
        if ($resolvedPath && is_dir($resolvedPath)) {
            $this->loadViewsFrom($resolvedPath, 'stock_ledger');
        }
    }
}
