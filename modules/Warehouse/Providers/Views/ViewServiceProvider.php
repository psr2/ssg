<?php

namespace Modules\Warehouse\Providers\Views;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class ViewServiceProvider extends ServiceProvider
{
    public const VIEW_NAME = 'warehouse';
    private const MODULE_PATH = __DIR__;
    private const VIEW_PATH = self::MODULE_PATH . '/../../Views';

    public function boot(): void
    {
        $resolvedPath = realpath(self::VIEW_PATH);
        if ($resolvedPath && is_dir($resolvedPath)) {
            $this->loadViewsFrom($resolvedPath, self::VIEW_NAME);
        } else {
            Log::warning('Warehouse view directory not found at: ' . self::VIEW_PATH);
        }
    }
}
