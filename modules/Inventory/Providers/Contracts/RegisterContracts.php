<?php

namespace Modules\Inventory\Providers\Contracts;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\API\Contracts\ProductInterface;
use Modules\Inventory\API\Internal\Services\ShareProductList;

/**
 * Register all decoupled services for the Inventory Module
 */
class RegisterContracts extends ServiceProvider
{

  public function register(): void
  {
    $this->app->bind(ProductInterface::class, ShareProductList::class);
  }
}
