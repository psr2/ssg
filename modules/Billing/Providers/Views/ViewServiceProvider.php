<?php

namespace Modules\Billing\Providers\Views;

use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(base_path('modules/Billing/Views'), 'billing');
    }
}
