<?php

namespace Modules\Expenses;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

use Modules\Expenses\Providers\Views\ViewServiceProvider;
use Modules\Expenses\Providers\Migrations\MigrationsServiceProvider;
use Modules\Expenses\Contracts\CategoryInterface;
use Modules\Expenses\Services\ExpenseCategory\ListExpenseCategory as CategoryService;

class BootstrapExpenseServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    $this->app->register(ViewServiceProvider::class);
    $this->app->register(MigrationsServiceProvider::class);
    $this->app->bind(
      CategoryInterface::class,
      CategoryService::class
    );
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void {}
}
