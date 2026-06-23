<?php

namespace Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use Modules\Dashboard\Services\Dashboard\WarehosueStockSummary;
use Modules\Dashboard\Services\Dashboard\ShopStockSummary;
use Modules\Locations\Models\LocationModel;
use Modules\StockManagement\Models\StockSummary\StockSummary;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Modules\StockManagement\Models\StockOut\StockOutItem;
use Modules\Warehouse\Models\WarehouseSale;
use Modules\Warehouse\Models\WarehouseSaleItem;
use Modules\ShopManagement\Models\ShopSale;
use Modules\ShopManagement\Models\ShopSaleItem;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;
use Modules\ShopManagement\Models\ShopInventory;
use Modules\Inventory\Models\Products;

class DashboardViewController extends Controller
{
    protected WarehosueStockSummary $warehouse;
    protected ShopStockSummary $shop;

    public function __construct(
        WarehosueStockSummary $warehouse,
        ShopStockSummary $shop
    ) {
        $this->warehouse = $warehouse;
        $this->shop = $shop;
    }

    public function index()
    {
        $warehouseTotal = $this->warehouse->stockInWarehouse();
        $shopTotal = $this->shop->stockInShop();

        // Get Warehouses and Shops
        $warehouses = LocationModel::where('type', 'warehouse')->get();
        $shops = LocationModel::where('type', 'shop')->get();

        // Calculate Stock per Location
        $warehouseStocks = [];
        foreach ($warehouses as $wh) {
            $warehouseStocks[$wh->id] = WarehouseInventory::where('warehouse_id', $wh->id)->sum('qty') ?? 0;
        }

        $shopStocks = [];
        foreach ($shops as $sh) {
            $shopStocks[$sh->id] = ShopInventory::where('shop_id', $sh->id)->sum('qty') ?? 0;
        }

        // Receivables
        $warehouseDues = [];
        foreach ($warehouses as $wh) {
            $warehouseDues[$wh->id] = WarehouseSale::where('warehouse_id', $wh->id)->sum('due_amount') ?? 0;
        }

        $shopDues = [];
        foreach ($shops as $sh) {
            $shopDues[$sh->id] = ShopSale::join('shop_customers', 'shop_sales.customer_id', '=', 'shop_customers.id')
                ->where('shop_customers.shop_id', $sh->id)
                ->sum('shop_sales.due_amount') ?? 0;
        }

        $totalReceivables = array_sum($warehouseDues) + array_sum($shopDues);

        // Low stock count (< 10)
        $lowStockTotal = WarehouseInventory::where('qty', '<', 10)->count() + ShopInventory::where('qty', '<', 10)->count();

        $lowStockCounts = [];
        foreach ($warehouses as $wh) {
            $lowStockCounts['wh_' . $wh->id] = WarehouseInventory::where('warehouse_id', $wh->id)->where('qty', '<', 10)->count();
        }
        foreach ($shops as $sh) {
            $lowStockCounts['sh_' . $sh->id] = ShopInventory::where('shop_id', $sh->id)->where('qty', '<', 10)->count();
        }

        // Build Stock Alerts table
        $products = Products::with('unit')->get()->keyBy('id');
        $locations = LocationModel::all()->keyBy('id');

        $pairs = [];

        // 1. From StockPurchaseItem
        $purchasePairs = StockPurchaseItem::select('product as product_id', 'location_id')
            ->groupBy('product', 'location_id')
            ->get();
        foreach ($purchasePairs as $p) {
            if ($p->product_id && $p->location_id) {
                $pairs[$p->product_id . '_' . $p->location_id] = [
                    'product_id' => $p->product_id,
                    'location_id' => $p->location_id,
                ];
            }
        }

        // 2. From WarehouseInventory
        $whInventoryPairs = WarehouseInventory::select('product_id', 'warehouse_id as location_id')
            ->groupBy('product_id', 'warehouse_id')
            ->get();
        foreach ($whInventoryPairs as $p) {
            if ($p->product_id && $p->location_id) {
                $pairs[$p->product_id . '_' . $p->location_id] = [
                    'product_id' => $p->product_id,
                    'location_id' => $p->location_id,
                ];
            }
        }

        // 3. From ShopInventory
        $shopInventoryPairs = ShopInventory::select('product_id', 'shop_id as location_id')
            ->groupBy('product_id', 'shop_id')
            ->get();
        foreach ($shopInventoryPairs as $p) {
            if ($p->product_id && $p->location_id) {
                $pairs[$p->product_id . '_' . $p->location_id] = [
                    'product_id' => $p->product_id,
                    'location_id' => $p->location_id,
                ];
            }
        }

        // Pre-aggregate current quantities
        $whCurrentSums = WarehouseInventory::groupBy('product_id', 'warehouse_id')
            ->selectRaw('product_id, warehouse_id, SUM(qty) as total_qty')
            ->get()
            ->keyBy(fn($item) => $item->product_id . '_' . $item->warehouse_id);

        $shopCurrentSums = ShopInventory::groupBy('product_id', 'shop_id')
            ->selectRaw('product_id, shop_id, SUM(qty) as total_qty')
            ->get()
            ->keyBy(fn($item) => $item->product_id . '_' . $item->shop_id);

        // Pre-aggregate received quantities
        $receivedSums = StockPurchaseItem::groupBy('product', 'location_id')
            ->selectRaw('product, location_id, SUM(quantity) as total_qty')
            ->get()
            ->keyBy(fn($item) => $item->product . '_' . $item->location_id);

        // Pre-fetch last purchase dates
        $lastPurchaseDates = StockPurchaseItem::join('stock_purchase', 'stock_purchase_items.stock_in_purchase_id', '=', 'stock_purchase.id')
            ->select('stock_purchase_items.product', 'stock_purchase_items.location_id', \Illuminate\Support\Facades\DB::raw('MAX(stock_purchase.purchase_date) as max_date'))
            ->groupBy('stock_purchase_items.product', 'stock_purchase_items.location_id')
            ->get()
            ->keyBy(fn($item) => $item->product . '_' . $item->location_id);

        $stockAlerts = [];
        foreach ($pairs as $key => $pair) {
            $pId = $pair['product_id'];
            $lId = $pair['location_id'];

            $product = $products[$pId] ?? null;
            $location = $locations[$lId] ?? null;

            if (!$product || !$location) {
                continue;
            }

            // Get current qty in this specific location
            if ($location->type === 'shop') {
                $currentQty = $shopCurrentSums[$pId . '_' . $lId]->total_qty ?? 0;
            } else {
                $currentQty = $whCurrentSums[$pId . '_' . $lId]->total_qty ?? 0;
            }

            $receivedQty = $receivedSums[$pId . '_' . $lId]->total_qty ?? 0;
            $lastPurchaseDate = $lastPurchaseDates[$pId . '_' . $lId]->max_date ?? 'N/A';

            // Percentage remaining
            $percentage = $receivedQty > 0 ? ($currentQty / $receivedQty) * 100 : ($currentQty > 0 ? 100 : 0);

            if ($percentage <= 15) {
                $status = 'Severe (' . number_format($percentage, 0) . '%)';
                $badgeClass = 'bg-danger';
            } elseif ($percentage <= 40) {
                $status = 'Low (' . number_format($percentage, 0) . '%)';
                $badgeClass = 'bg-warning';
            } else {
                $status = 'Normal (' . number_format($percentage, 0) . '%)';
                $badgeClass = 'bg-success';
            }

            $stockAlerts[] = [
                'product_name'   => $product->name,
                'sku'            => $product->sku,
                'category'       => $product->category,
                'location_name'  => $location->name,
                'location_type'  => ucfirst($location->type),
                'current_qty'    => $currentQty,
                'unit'           => $product->unit ? $product->unit->abbreviation : 'kg',
                'received'       => $receivedQty,
                'received_date'  => $lastPurchaseDate,
                'status'         => $status,
                'badge_class'    => $badgeClass,
                'percentage'     => $percentage
            ];
        }

        // Fetch all products for the dropdown select lists
        $productList = Products::select('id', 'name')->orderBy('name')->get();

        $warehouseStockMap = [];
        foreach ($whCurrentSums as $key => $sum) {
            $warehouseStockMap[$sum->warehouse_id][$sum->product_id] = (float)$sum->total_qty;
        }

        $shopStockMap = [];
        foreach ($shopCurrentSums as $key => $sum) {
            $shopStockMap[$sum->shop_id][$sum->product_id] = (float)$sum->total_qty;
        }

        return view('dashboard::dash', [
            'warehouse'        => $warehouseTotal,
            'shopStock'        => $shopTotal,
            'warehouses'       => $warehouses,
            'shops'            => $shops,
            'warehouseStocks'  => $warehouseStocks,
            'shopStocks'       => $shopStocks,
            'warehouseDues'    => $warehouseDues,
            'shopDues'         => $shopDues,
            'totalReceivables' => $totalReceivables,
            'lowStockTotal'    => $lowStockTotal,
            'lowStockCounts'   => $lowStockCounts,
            'stockAlerts'      => $stockAlerts,
            'productList'      => $productList,
            'warehouseStockMap'=> $warehouseStockMap,
            'shopStockMap'     => $shopStockMap,
        ]);
    }
}

