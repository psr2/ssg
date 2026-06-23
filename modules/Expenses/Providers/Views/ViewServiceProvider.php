<?php

namespace Modules\Expenses\Providers\Views;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class ViewServiceProvider extends ServiceProvider
{
    public const VIEW_NAME = 'expense';
    public const BASE_PATH = __DIR__ . '/../.';
    public const VIEW_PATH = self::BASE_PATH . './Views';

    public function boot(): void
    {

        $this->loadViewsFrom(self::VIEW_PATH, self::VIEW_NAME) ;
        
    }
}
