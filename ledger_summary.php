<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "==================================================\n";
echo "           INVENTORY LEDGER SUMMARY               \n";
echo "==================================================\n\n";

// 1. PURCHASE DETAILS (STOCK IN)
echo "--- 1. PURCHASES (STOCK IN) ---\n";
$purchases = DB::table('stock_ledger_entries')
    ->join('products', 'stock_ledger_entries.product_id', '=', 'products.id')
    ->join('locations', 'stock_ledger_entries.location_id', '=', 'locations.id')
    ->select(
        'products.name as product_name',
        'locations.name as location_name',
        'stock_ledger_entries.batch_code',
        'stock_ledger_entries.grade',
        DB::raw('SUM(stock_ledger_entries.quantity) as total_qty'),
        'stock_ledger_entries.unit'
    )
    ->where('stock_ledger_entries.transaction_type', 'PURCHASE')
    ->groupBy('products.name', 'locations.name', 'stock_ledger_entries.batch_code', 'stock_ledger_entries.grade', 'stock_ledger_entries.unit')
    ->get();

if ($purchases->isEmpty()) {
    echo "No purchases recorded.\n";
} else {
    foreach ($purchases as $p) {
        echo "Product: {$p->product_name} | Location: {$p->location_name} | Batch: {$p->batch_code} | Grade: {$p->grade} | Purchased Qty: {$p->total_qty} {$p->unit}\n";
    }
}
echo "\n";

// 2. MOVEMENT DETAILS (TRANSFERS)
echo "--- 2. MOVEMENTS (STOCK TRANSFERS) ---\n";
$transfers = DB::table('stock_ledger_entries')
    ->join('products', 'stock_ledger_entries.product_id', '=', 'products.id')
    ->join('locations', 'stock_ledger_entries.location_id', '=', 'locations.id')
    ->select(
        'products.name as product_name',
        'locations.name as location_name',
        'stock_ledger_entries.transaction_type',
        'stock_ledger_entries.batch_code',
        'stock_ledger_entries.grade',
        DB::raw('SUM(stock_ledger_entries.quantity) as total_qty'),
        'stock_ledger_entries.unit'
    )
    ->whereIn('stock_ledger_entries.transaction_type', ['TRANSFER_IN', 'TRANSFER_OUT'])
    ->groupBy('products.name', 'locations.name', 'stock_ledger_entries.transaction_type', 'stock_ledger_entries.batch_code', 'stock_ledger_entries.grade', 'stock_ledger_entries.unit')
    ->get();

if ($transfers->isEmpty()) {
    echo "No transfers recorded.\n";
} else {
    foreach ($transfers as $t) {
        echo "Type: {$t->transaction_type} | Product: {$t->product_name} | Location: {$t->location_name} | Batch: {$t->batch_code} | Grade: {$t->grade} | Qty: {$t->total_qty} {$t->unit}\n";
    }
}
echo "\n";

// 3. SALES / STOCK OUT DETAILS
echo "--- 3. SALES & STOCK OUTS ---\n";
$outs = DB::table('stock_ledger_entries')
    ->join('products', 'stock_ledger_entries.product_id', '=', 'products.id')
    ->join('locations', 'stock_ledger_entries.location_id', '=', 'locations.id')
    ->select(
        'products.name as product_name',
        'locations.name as location_name',
        'stock_ledger_entries.transaction_type',
        'stock_ledger_entries.batch_code',
        'stock_ledger_entries.grade',
        DB::raw('SUM(stock_ledger_entries.quantity) as total_qty'),
        'stock_ledger_entries.unit'
    )
    ->whereIn('stock_ledger_entries.transaction_type', ['STOCK_OUT', 'SALE', 'WAREHOUSE_SALE', 'SHOP_SALE'])
    ->groupBy('products.name', 'locations.name', 'stock_ledger_entries.transaction_type', 'stock_ledger_entries.batch_code', 'stock_ledger_entries.grade', 'stock_ledger_entries.unit')
    ->get();

if ($outs->isEmpty()) {
    echo "No sales/stock outs recorded.\n";
} else {
    foreach ($outs as $o) {
        echo "Type: {$o->transaction_type} | Product: {$o->product_name} | Location: {$o->location_name} | Batch: {$o->batch_code} | Grade: {$o->grade} | Qty: {$o->total_qty} {$o->unit}\n";
    }
}
echo "\n";

// 4. CURRENT STOCK BALANCE BY BATCH/GRADE
echo "--- 4. CURRENT BALANCES ---\n";
$balances = DB::table('stock_ledger_entries')
    ->join('products', 'stock_ledger_entries.product_id', '=', 'products.id')
    ->join('locations', 'stock_ledger_entries.location_id', '=', 'locations.id')
    ->select(
        'products.name as product_name',
        'locations.name as location_name',
        'stock_ledger_entries.batch_code',
        'stock_ledger_entries.grade',
        DB::raw('SUM(stock_ledger_entries.quantity) as current_qty'),
        'stock_ledger_entries.unit'
    )
    ->groupBy('products.name', 'locations.name', 'stock_ledger_entries.batch_code', 'stock_ledger_entries.grade', 'stock_ledger_entries.unit')
    ->get();

if ($balances->isEmpty()) {
    echo "No stock ledger balances recorded.\n";
} else {
    foreach ($balances as $b) {
        if (abs($b->current_qty) > 0.0001) {
            echo "Product: {$b->product_name} | Location: {$b->location_name} | Batch: {$b->batch_code} | Grade: {$b->grade} | Current Qty: {$b->current_qty} {$b->unit}\n";
        }
    }
}
echo "==================================================\n";
