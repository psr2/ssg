<?php

namespace Modules\Billing\Providers\Migrations;

use Illuminate\Support\ServiceProvider;

class MigrationsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(base_path('modules/Billing/Database/Migrations'));
    }
}
