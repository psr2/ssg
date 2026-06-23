<?php

namespace Modules\StockManagement\Providers\Migrations;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class MigrationsServiceProvider extends ServiceProvider
{
    private const MODULE_PATH = __DIR__;
    private const MIGRATIONS_PATH = self::MODULE_PATH . '/../../Database/Migrations';

    public function boot(): void
    {
        $resolvedPath = realpath(self::MIGRATIONS_PATH);

        // Log::debug('Module path is: ' . self::MODULE_PATH);
        // Log::debug('Resolved migrations path is: ' . ($resolvedPath ?: 'Not found'));

        if ($resolvedPath) {
            $this->loadMigrationsFrom($resolvedPath);
            // Log::info('Migrations loaded from: ' . $resolvedPath);
        } else {
            Log::error('Migration path does not exist or is not readable: ' . self::MIGRATIONS_PATH);
        }
    }
}
