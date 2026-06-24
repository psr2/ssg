<?php


return [
    
    App\Providers\AppServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    Modules\Dashboard\BootstrapServiceProvider::class,
    Modules\FleetManagement\BootstrapFleetManagementProvider::class,
    Modules\Inventory\BootstrapInventoryProvider::class,
    Modules\Locations\BootstrapLocationManagementProvider::class,
    Modules\StockManagement\BootstrapStockManagementProvider::class,
    Modules\ShopManagement\BootstrapShopManagementProvider::class,
    Modules\Warehouse\BootstrapWarehouseManagementProvider::class,
    Modules\Expenses\BootstrapExpenseServiceProvider::class,
    Modules\Settings\BootstrapServiceProvider::class
];
