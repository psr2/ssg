<?php

namespace Modules\ShopManagement\Controllers\Sale;

use App\Http\Controllers\Controller;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;
use Modules\Inventory\API\Contracts\ProductInterface;
use Modules\ShopManagement\Services\CustomerSaleSummary as Summary;
use Illuminate\Http\Request;



class SaleListingController extends Controller
{

    const DEFAULT_RECORDS_PER_PAGE=10;

    public function __construct(
        protected LocationsInterface $locations,
        protected ProductInterface $products,
        protected Summary $summary
    ) {}

    /**
     * Todo - pass product list to the dynamic form in sale UI
     */
    public function index(Request $request)
    {
        $location = json_decode($this->locations->shareLocation(), true);

        //  Validate query parameters
        $validated = $request->validate([
            'sort_by' => 'nullable|string|in:shop_sales.due_amount,shop_sales.total_amount,shop_sales.paid_amount,shop_sales.created_at,shop_customers.name',
            'sort_order' => 'nullable|string|in:asc,desc',
            'only_due' => 'nullable|boolean',
            'per_page' => 'nullable|integer|in:10,15,25,50',
        ]);

        //  Use validated data with fallbacks
        $options = [
            'sort_by' => $validated['sort_by'] ?? null,
            'sort_order' => $validated['sort_order'] ?? null,
            'only_due' => $validated['only_due'] ?? false,
        ];

        $perPage = $validated['per_page'] ?? self::DEFAULT_RECORDS_PER_PAGE ;

        $data = $this->summary->handle($perPage, $options);
        $productList=$this->listOfProducts();

        return view("shop_management::shop_sale", 
                    compact('location', 
                            'data',
                            'perPage',
                            'productList'));
    }

    private function listOfProducts(){

        return $this->products->shareProductList();

    }
}
