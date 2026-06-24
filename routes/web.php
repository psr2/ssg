<?php

use Illuminate\Support\Facades\Route;

require base_path('modules/Dashboard/Routes/web.php');

require base_path('modules/Inventory/Routes/web.php');

require base_path('modules/Locations/Routes/web.php');
require base_path('modules/Locations/Routes/api.php');


require base_path('modules/StockManagement/Routes/web.php');

require base_path('modules/FleetManagement/Routes/web.php');

require base_path('modules/ShopManagement/Routes/web.php');

require base_path('modules/Expenses/Routes/web.php');

require base_path('modules/Warehouse/Routes/web.php');
require base_path('modules/Settings/Routes/web.php');











Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-db', function () {
    $sales = \Illuminate\Support\Facades\DB::table('warehouse_sales')->get()->toArray();
    $payments = \Illuminate\Support\Facades\DB::table('warehouse_payments')->get()->toArray();
    $customers = \Illuminate\Support\Facades\DB::table('warehouse_customers')->get()->toArray();
    $inventory = \Illuminate\Support\Facades\DB::table('warehouse_inventory')->get()->toArray();

    $data = [
        'sales' => $sales,
        'payments' => $payments,
        'customers' => $customers,
        'inventory' => $inventory
    ];
    file_put_contents(public_path('debug_output.json'), json_encode($data, JSON_PRETTY_PRINT));
    return response()->json($data);
});
