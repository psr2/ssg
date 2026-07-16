<?php

namespace Modules\Billing;

use Illuminate\Support\ServiceProvider;
use Modules\Billing\Providers\Views\ViewServiceProvider as BillingViews;
use Modules\Billing\Providers\Migrations\MigrationsServiceProvider as BillingMigrations;

class BootstrapBillingProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(BillingViews::class);
        $this->app->register(BillingMigrations::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
