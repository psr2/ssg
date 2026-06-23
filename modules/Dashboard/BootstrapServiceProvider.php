<?php

namespace Modules\Dashboard;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Modules\Dashboard\Providers\ViewServiceProvider;

class BootstrapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
      $this->app->register(ViewServiceProvider::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        
    }
}
