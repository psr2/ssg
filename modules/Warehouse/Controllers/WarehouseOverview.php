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

        // Get warehouses (locations with type = warehouse)
        $warehouses = \Modules\Locations\Models\LocationModel::where('type', 'warehouse')->orderBy('name', 'asc')->get();

        // Get product grades
        $grades = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_grades')) {
                $grades = \Modules\Inventory\Models\ProductGrade::where('is_active', true)->orderBy('name', 'asc')->get();
            }
        } catch (\Exception $e) {
            // Database table doesn't exist yet
        }

        return view('warehouse::warehouse_overview', compact(
            'totalReceivables',
            'todaySales',
            'lowStockCount',
            'warehouses',
            'grades'
        ));
    }

    /**
     * Get warehouse inventory list via AJAX.
     */
    public function getInventory(\Illuminate\Http\Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $search = $request->query('search');
        $grade = $request->query('grade');

        // Log incoming request parameters
        \Illuminate\Support\Facades\Log::info('WarehouseInventory Request Input', [
            'warehouse_id' => $warehouseId,
            'search' => $search,
            'grade' => $grade
        ]);

        if (!$warehouseId) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse selection is required.'
            ], 400);
        }

        // 1. Get unique combinations from database sources of stock for this warehouse
        $whCombos = \Illuminate\Support\Facades\DB::table('warehouse_inventory as wi')
            ->join('products as p', 'wi.product_id', '=', 'p.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('wi.warehouse_id', $warehouseId)
            ->select([
                'wi.batch as batch_code',
                'wi.product_id',
                'wi.grade',
                'p.name as product_name',
                'p.sku as product_sku',
                'u.abbreviation as unit',
            ])->get();

        $segCombos = \Illuminate\Support\Facades\DB::table('stock_segregations as ss')
            ->join('stock_segregation_items as ssi', 'ss.id', '=', 'ssi.stock_segregation_id')
            ->join('products as p', 'ss.product_id', '=', 'p.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('ss.location_id', $warehouseId)
            ->select([
                'ss.parent_batch_code as batch_code',
                'ss.product_id',
                'ssi.grade',
                'p.name as product_name',
                'p.sku as product_sku',
                'u.abbreviation as unit',
            ])->get();

        $transCombos = \Illuminate\Support\Facades\DB::table('stock_transfers as st')
            ->join('stock_transfer_items as sti', 'st.id', '=', 'sti.stock_transfer_id')
            ->join('products as p', 'sti.product_id', '=', 'p.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('st.to_location_id', $warehouseId)
            ->select([
                'sti.batch_code',
                'sti.product_id',
                'sti.grade',
                'p.name as product_name',
                'p.sku as product_sku',
                'u.abbreviation as unit',
            ])->get();

        $allCombos = [];
        foreach ([$whCombos, $segCombos, $transCombos] as $combos) {
            foreach ($combos as $c) {
                if (!$c->batch_code || !$c->product_id || !$c->grade) {
                    continue;
                }
                $key = "{$c->product_id}_{$c->batch_code}_{$c->grade}";
                if (!isset($allCombos[$key])) {
                    $allCombos[$key] = [
                        'batch_code' => $c->batch_code,
                        'product_id' => (int)$c->product_id,
                        'grade' => $c->grade,
                        'product_name' => $c->product_name,
                        'product_sku' => $c->product_sku,
                        'unit' => $c->unit ?: 'pcs',
                    ];
                }
            }
        }

        // 2. Compute dynamic available quantities & resolve unit cost
        $service = app(\Modules\StockManagement\Services\StockSegregation\StockSegregationService::class);
        $results = [];

        foreach ($allCombos as $key => $combo) {
            $qty = $service->getAvailableStock(
                (int)$warehouseId,
                $combo['product_id'],
                $combo['batch_code'],
                $combo['grade']
            );

            // Fetch unit cost hierarchy:
            // a. Check stock_segregation_items
            $unitCost = \Illuminate\Support\Facades\DB::table('stock_segregations as ss')
                ->join('stock_segregation_items as ssi', 'ss.id', '=', 'ssi.stock_segregation_id')
                ->where('ss.location_id', $warehouseId)
                ->where('ss.product_id', $combo['product_id'])
                ->where('ss.parent_batch_code', $combo['batch_code'])
                ->where('ssi.grade', $combo['grade'])
                ->value('ssi.unit_cost');

            if ($unitCost === null) {
                // b. Check warehouse_inventory
                $unitCost = \Illuminate\Support\Facades\DB::table('warehouse_inventory')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $combo['product_id'])
                    ->where('batch', $combo['batch_code'])
                    ->value('unit_cost');
            }

            if ($unitCost === null) {
                // c. Check stock_purchase_items
                $unitCost = \Illuminate\Support\Facades\DB::table('stock_purchase_items')
                    ->where('location_id', $warehouseId)
                    ->where('product', $combo['product_id'])
                    ->where('batch', $combo['batch_code'])
                    ->value('unit_cost');
            }

            $results[] = [
                'id' => $key,
                'product_name' => $combo['product_name'],
                'product_sku' => $combo['product_sku'],
                'batch' => $combo['batch_code'],
                'grade' => $combo['grade'] ?: 'N/A',
                'qty' => (float)$qty,
                'unit' => $combo['unit'],
                'unit_cost' => (float)($unitCost ?: 0.00),
                'total_value' => (float)($qty * ($unitCost ?: 0.00)),
            ];
        }

        // 3. Apply search and grade filters
        $filteredResults = [];
        foreach ($results as $item) {
            // Apply grade filter
            if ($grade) {
                $productGrade = null;
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('product_grades')) {
                        $productGrade = \Modules\Inventory\Models\ProductGrade::where('code', $grade)
                            ->orWhere('name', $grade)
                            ->first();
                    }
                } catch (\Exception $e) {
                    // Ignore
                }

                $gradeMatches = false;
                if ($productGrade) {
                    if (strcasecmp($item['grade'], $grade) === 0 ||
                        strcasecmp($item['grade'], $productGrade->name) === 0 ||
                        strcasecmp($item['grade'], $productGrade->code) === 0) {
                        $gradeMatches = true;
                    }
                } else {
                    if (strcasecmp($item['grade'], $grade) === 0) {
                        $gradeMatches = true;
                    }
                }

                if (!$gradeMatches) {
                    continue;
                }
            }

            // Apply search filter
            if ($search) {
                $searchLower = strtolower($search);
                $nameMatches = str_contains(strtolower($item['product_name']), $searchLower);
                $skuMatches = $item['product_sku'] && str_contains(strtolower($item['product_sku']), $searchLower);
                $batchMatches = str_contains(strtolower($item['batch']), $searchLower);

                if (!$nameMatches && !$skuMatches && !$batchMatches) {
                    continue;
                }
            }

            $filteredResults[] = $item;
        }

        // 4. Sort results (grade asc, qty desc)
        usort($filteredResults, function ($a, $b) {
            $gradeCompare = strcasecmp($a['grade'], $b['grade']);
            if ($gradeCompare !== 0) {
                return $gradeCompare;
            }
            return $b['qty'] <=> $a['qty'];
        });

        // Log results count and snapshot
        \Illuminate\Support\Facades\Log::info('WarehouseInventory Results Count', [
            'count' => count($filteredResults)
        ]);

        return response()->json([
            'success' => true,
            'data' => $filteredResults
        ]);
    }
}
