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
    public function boot(): void {}
}
