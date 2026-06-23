<?php

namespace Modules\Warehouse\Providers\Migrations;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class MigrationsServiceProvider extends ServiceProvider
{
    private const MODULE_PATH = __DIR__;
    private const MIGRATIONS_PATH = self::MODULE_PATH . '/../../Database/Migrations';

    public function boot(): void
    {
        $resolvedPath = realpath(self::MIGRATIONS_PATH);

        if ($resolvedPath) {
            $this->loadMigrationsFrom($resolvedPath);
        } else {
            Log::error('Warehouse migration path does not exist: ' . self::MIGRATIONS_PATH);
        }
    }
}
