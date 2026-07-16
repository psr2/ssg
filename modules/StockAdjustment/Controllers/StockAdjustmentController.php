<?php

namespace Modules\StockAdjustment\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;
use Modules\Locations\API\Internal\Repository\LocationRepository;
use Modules\Inventory\API\Contracts\ProductInterface;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\Inventory\Models\ProductGrade;
use Modules\StockLedger\Services\StockLedgerService;
use Modules\StockAdjustment\Services\StockAdjustmentService;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected LocationsInterface $locationService,
        protected ProductInterface $products,
        protected StockAdjustmentService $adjustmentService
    ) {}

    /**
     * Display a listing of stock adjustments and the adjustment entry form.
     */
    public function index(LocationRepository $repo): View
    {
        $locationJson = $this->locationService->shareLocation($repo);
        $locations = json_decode($locationJson, true);
        $productList = $this->products->shareProductList();
        $units = UnitOfMeasurement::all();

        $grades = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_grades')) {
                $grades = ProductGrade::where('is_active', true)->orderBy('name', 'asc')->get();
            }
        } catch (\Exception $e) {
            // DB not ready or migrated
        }

        $adjustments = DB::table('stock_adjustments')
            ->leftJoin('locations', 'stock_adjustments.location_id', '=', 'locations.id')
            ->leftJoin('products', 'stock_adjustments.product_id', '=', 'products.id')
            ->leftJoin('users', 'stock_adjustments.adjusted_by', '=', 'users.id')
            ->select(
                'stock_adjustments.*',
                'locations.name as location_name',
                'products.name as product_name',
                'users.name as adjusted_by_name'
            )
            ->orderBy('stock_adjustments.id', 'desc')
            ->paginate(15);

        return view('stock_adjustment::stock_adjustments', compact('locations', 'productList', 'units', 'grades', 'adjustments'));
    }

    /**
     * Fetch the dynamic available quantity and unit for a batch.
     */
    public function getBatchStock(Request $request, StockLedgerService $ledgerService)
    {
        $locationId = (int) $request->query('location_id');
        $productId = (int) $request->query('product_id');
        $batchCode = $request->query('batch_code');
        $grade = $request->query('grade');

        if (!$locationId || !$productId || !$batchCode) {
            return response()->json([
                'available_qty' => 0.00,
                'unit' => 'pcs'
            ]);
        }

        $qty = $ledgerService->getAvailableStock($locationId, $productId, $batchCode, $grade);
        $unit = $ledgerService->getLatestUnit($locationId, $productId, $batchCode, $grade);

        return response()->json([
            'available_qty' => $qty,
            'unit' => $unit
        ]);
    }

    /**
     * Store a new stock adjustment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'product_id' => 'required|integer|exists:products,id',
            'batch_code' => 'required|string',
            'grade' => 'nullable|string',
            'original_qty' => 'required|numeric',
            'adjusted_qty' => 'required|numeric',
            'new_qty' => 'required|numeric',
            'reason' => 'required|string',
            'remarks' => 'nullable|string',
        ]);

        $adjustment = $this->adjustmentService->createAdjustment($validated);

        $msg = $adjustment->status === 'approved' 
            ? 'Stock adjustment applied and recorded in ledger successfully!'
            : 'Adjustment requires manager approval and has been queued.';

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data' => $adjustment
        ], 201);
    }

    /**
     * Approve a pending adjustment.
     */
    public function approve(int $id)
    {
        try {
            $adjustment = $this->adjustmentService->approveAdjustment($id);
            return response()->json([
                'success' => true,
                'message' => 'Adjustment approved and posted to stock ledger.',
                'data' => $adjustment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
