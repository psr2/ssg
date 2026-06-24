<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\Locations\Models\LocationModel as Location;
use Modules\Warehouse\Models\WarehouseCustomer;

echo "--- Locations ---\n";
try {
    foreach(Location::all() as $loc) {
        echo $loc->id . ': ' . $loc->name . ' (' . $loc->type . ")\n";
    }
} catch (\Exception $e) {
    echo "Error locations: " . $e->getMessage() . "\n";
}

echo "\n--- Warehouse Customers ---\n";
try {
    foreach(WarehouseCustomer::all() as $cust) {
        echo $cust->id . ': ' . $cust->name . ' (warehouse_id: ' . $cust->warehouse_id . ")\n";
    }
} catch (\Exception $e) {
    echo "Error customers: " . $e->getMessage() . "\n";
}
