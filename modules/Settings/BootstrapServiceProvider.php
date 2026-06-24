<?php

namespace Modules\Settings;

use Illuminate\Support\ServiceProvider;
use Modules\Settings\Providers\ViewServiceProvider as SettingsViews;
use Modules\Settings\Providers\Migrations\MigrationsServiceProvider as SettingsMigrations;

class BootstrapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(SettingsViews::class);
        $this->app->register(SettingsMigrations::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
