<?php

namespace Modules\Billing;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
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
    public function boot(): void
    {
        Relation::morphMap([
            'warehouse' => \Modules\Warehouse\Models\WarehouseSale::class,
            'shop'      => \Modules\ShopManagement\Models\ShopSale::class,
            'fleet'     => \Modules\FleetManagement\Models\FleetSale::class,
        ]);
    }
}
