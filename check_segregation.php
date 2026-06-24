<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Warehouse Inventory ---\n";
print_r(DB::table('warehouse_inventory')->get()->toArray());

echo "\n--- Stock Purchase Items ---\n";
print_r(DB::table('stock_purchase_items')->get()->toArray());

echo "\n--- Stock Segregations ---\n";
print_r(DB::table('stock_segregations')->get()->toArray());

echo "\n--- Stock Segregation Items ---\n";
print_r(DB::table('stock_segregation_items')->get()->toArray());
