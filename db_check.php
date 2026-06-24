<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- PRODUCTS ---\n";
$products = DB::table('products')->get(['id', 'name']);
foreach ($products as $p) {
    echo "ID: {$p->id}, Name: {$p->name}\n";
}

echo "\n--- WAREHOUSE INVENTORY ---\n";
$inventory = DB::table('warehouse_inventory')->get();
foreach ($inventory as $i) {
    echo "ID: {$i->id}, Product ID: {$i->product_id}, Batch: {$i->batch}, Grade: {$i->grade}, Qty: {$i->qty}\n";
}

echo "\n--- WAREHOUSE SALES ---\n";
$sales = DB::table('warehouse_sales')->get();
foreach ($sales as $s) {
    echo "ID: {$s->id}, Customer ID: {$s->customer_id}, Warehouse ID: {$s->warehouse_id}, Total: {$s->total_amount}\n";
}

echo "\n--- WAREHOUSE SALE ITEMS ---\n";
$saleItems = DB::table('warehouse_sale_items')->get();
foreach ($saleItems as $item) {
    echo "ID: {$item->id}, Sale ID: {$item->sale_id}, Product ID: {$item->product_id}, Batch: {$item->batch_code}, Grade: {$item->grade}, Qty: {$item->quantity}\n";
}
