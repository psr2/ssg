<?php

namespace Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use Modules\Warehouse\Models\WarehouseSale;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;
use Carbon\Carbon;

class WarehouseOverview extends Controller
{
    public function index()
    {
        // Calculate dynamic summary metrics for Warehouse sales & inventory
        $totalReceivables = WarehouseSale::sum('due_amount');
        $todaySales       = WarehouseSale::whereDate('sale_date', Carbon::today())->sum('total_amount');
        $lowStockCount    = WarehouseInventory::where('qty', '<', 10)->count();

        return view('warehouse::warehouse_overview', compact(
            'totalReceivables',
            'todaySales',
            'lowStockCount'
        ));
    }
}
