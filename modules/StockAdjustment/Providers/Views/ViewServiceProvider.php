<?php

namespace Modules\StockAdjustment\Providers\Views;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public const VIEW_NAME = 'stock_adjustment';
    private const MODULE_PATH = __DIR__;
    private const VIEW_PATH = self::MODULE_PATH . '/../../Views';

    public function boot(): void
    {
        $resolvedPath = realpath(self::VIEW_PATH);

        if ($resolvedPath && is_dir($resolvedPath)) {
            $this->loadViewsFrom($resolvedPath, self::VIEW_NAME);
        } else {
            Log::warning('View directory not found or invalid at: ' . self::VIEW_PATH);
        }
    }
}
