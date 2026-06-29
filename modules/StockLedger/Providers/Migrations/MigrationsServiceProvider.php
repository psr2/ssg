<?php

namespace Modules\StockLedger\Providers\Migrations;

use Illuminate\Support\ServiceProvider;

class MigrationsServiceProvider extends ServiceProvider
{
    private const MODULE_PATH = __DIR__;
    private const MIGRATIONS_PATH = self::MODULE_PATH . '/../../Database/Migrations';

    public function boot(): void
    {
        $resolvedPath = realpath(self::MIGRATIONS_PATH);

        if ($resolvedPath) {
            $this->loadMigrationsFrom($resolvedPath);
        }
    }
}
