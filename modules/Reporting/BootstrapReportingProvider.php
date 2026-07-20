<?php

namespace Modules\Reporting;

use Illuminate\Support\ServiceProvider;
use Modules\Reporting\Providers\Views\ViewServiceProvider as ReportingViews;

class BootstrapReportingProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(ReportingViews::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
