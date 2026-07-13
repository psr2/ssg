<?php

use Illuminate\Support\Facades\DB;

require '/var/www/html/clients/inv/vendor/autoload.php';
$app = require_once '/var/www/html/clients/inv/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DIAGNOSTIC START ===\n";

// 1. Find locations matching "kum"
$locations = DB::table('locations')->where('name', 'like', '%kum%')->get();
echo "Locations matching 'kum':\n";
foreach ($locations as $loc) {
    echo "- ID: {$loc->id}, Name: {$loc->name}, Type: {$loc->type}\n";
}

if ($locations->isEmpty()) {
    echo "No locations found matching 'kum'. Listing all locations:\n";
    $allLocs = DB::table('locations')->get();
    foreach ($allLocs as $loc) {
        echo "- ID: {$loc->id}, Name: {$loc->name}, Type: {$loc->type}\n";
    }
    exit;
}

$kumilyId = $locations->first()->id;

// 2. Find transfers involving this location
echo "\nTransfers to/from Kumily (ID: $kumilyId):\n";
$transfers = DB::table('stock_transfers')
    ->where('from_location_id', $kumilyId)
    ->orWhere('to_location_id', $kumilyId)
    ->get();

foreach ($transfers as $t) {
    echo "- Transfer ID: {$t->id}, Reference: {$t->reference_no}, From: {$t->from_location_id}, To: {$t->to_location_id}, Status: {$t->status}\n";
    // Get transfer items
    $items = DB::table('stock_transfer_items')->where('stock_transfer_id', $t->id)->get();
    foreach ($items as $item) {
        echo "  * Product ID: {$item->product_id}, Batch: {$item->batch_code}, Grade: {$item->grade}, Qty: {$item->quantity}, Unit: {$item->unit}\n";
    }
}

// 3. Check warehouse_inventory for this location
echo "\nwarehouse_inventory rows for Kumily (ID: $kumilyId):\n";
$inv = DB::table('warehouse_inventory')->where('warehouse_id', $kumilyId)->get();
foreach ($inv as $row) {
    echo "- Product ID: {$row->product_id}, Batch: {$row->batch}, Grade: {$row->grade}, Initial Qty: {$row->qty}\n";
}

// 4. Check stock_ledger_entries for this location
echo "\nstock_ledger_entries rows for Kumily (ID: $kumilyId):\n";
$entries = DB::table('stock_ledger_entries')->where('location_id', $kumilyId)->get();
foreach ($entries as $row) {
    echo "- Tx ID: {$row->id}, Type: {$row->transaction_type}, Product ID: {$row->product_id}, Batch: {$row->batch_code}, Grade: {$row->grade}, Qty: {$row->quantity}, Unit: {$row->unit}\n";
}

// 5. Run StockLedgerService availability check
echo "\nRunning StockLedgerService::getAvailableStock for Kumily:\n";
$ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
// Get all unique product/batch/grade combinations in ledger for this location
$combinations = DB::table('stock_ledger_entries')
    ->where('location_id', $kumilyId)
    ->select('product_id', 'batch_code', 'grade')
    ->distinct()
    ->get();

foreach ($combinations as $c) {
    $qty = $ledgerService->getAvailableStock($kumilyId, $c->product_id, $c->batch_code, $c->grade);
    echo "- Product ID: {$c->product_id}, Batch: {$c->batch_code}, Grade: {$c->grade} => Available: {$qty}\n";
}

echo "=== DIAGNOSTIC END ===\n";
