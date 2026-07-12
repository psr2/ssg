<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = \Modules\StockManagement\Models\StockIn\StockPurchaseItem::all();
echo "--- Stock Purchase Items ---\n";
foreach ($items as $item) {
    echo "ID: {$item->id} | Product ID: {$item->product} | Batch: {$item->batch} | Qty: {$item->quantity} | Unit: {$item->unit} | Location ID: {$item->location_id}\n";
}

$entries = \Modules\StockLedger\Models\StockLedgerEntry::all();
echo "\n--- Stock Ledger Entries ---\n";
foreach ($entries as $e) {
    echo "ID: {$e->id} | Type: {$e->transaction_type} | Batch: {$e->batch_code} | Qty: {$e->quantity} | Unit: {$e->unit} | Location ID: {$e->location_id}\n";
}

