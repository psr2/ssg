<?php

namespace Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;
use Modules\Inventory\API\Contracts\ProductInterface;
use Modules\Warehouse\Services\WarehouseSaleSummary;

class WarehouseSaleListingController extends Controller
{
    const DEFAULT_PER_PAGE = 10;

    public function __construct(
        protected LocationsInterface $locations,
        protected ProductInterface   $products,
        protected WarehouseSaleSummary $summary
    ) {}

    public function index(Request $request)
    {
        $location = json_decode($this->locations->shareLocation(), true);

        $validated = $request->validate([
            'sort_by'    => 'nullable|string|in:warehouse_sales.due_amount,warehouse_sales.total_amount,warehouse_sales.paid_amount,warehouse_sales.created_at,warehouse_customers.name',
            'sort_order' => 'nullable|string|in:asc,desc',
            'only_due'   => 'nullable|boolean',
            'per_page'   => 'nullable|integer|in:10,15,25,50',
        ]);

        $options = [
            'sort_by'    => $validated['sort_by']    ?? null,
            'sort_order' => $validated['sort_order'] ?? null,
            'only_due'   => $validated['only_due']   ?? false,
        ];

        $perPage     = $validated['per_page'] ?? self::DEFAULT_PER_PAGE;
        $data        = $this->summary->handle($perPage, $options);
        $productList = $this->products->shareProductList();

        $warehouseCustomers = \Modules\Warehouse\Models\WarehouseCustomer::select('id', 'name', 'warehouse_id')->get();

        $grades = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_grades')) {
                $grades = \Modules\Settings\Models\ProductGrade::where('is_active', true)->orderBy('name', 'asc')->get();
            }
        } catch (\Exception $e) {
            // Database not ready or table doesn't exist
        }

        return view('warehouse::warehouse_sale', compact('location', 'data', 'perPage', 'productList', 'grades', 'warehouseCustomers'));
    }
}
