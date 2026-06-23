<?php

namespace Modules\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public const VIEW_NAME = 'dashboard';
    public const BASE_PATH = __DIR__ . '/../.';
    public const VIEW_PATH = self::BASE_PATH . '/Views';

    public function boot(): void
    {
        $this->loadViewsFrom(self::VIEW_PATH, self::VIEW_NAME);
    }
}
