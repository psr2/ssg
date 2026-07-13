<?php

namespace Modules\StockManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;
use Modules\Locations\API\Internal\Repository\LocationRepository;
use Modules\Inventory\API\Contracts\ProductInterface;

use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Services\StockMovement\ReferenceNumber\PurchaseReferenceNumberGenerator as ReferenceNumber;


class StockViewManagementResourceController extends Controller
{

    public function __construct(
         protected LocationsInterface $locationService,
         protected ProductInterface $products
         ){}

    public function stockPurchase(ReferenceNumber $service, LocationRepository $repo): View
    {

        $locationJson = $this->locationService->shareLocation($repo); // returns JSON
        $locations = json_decode($locationJson, true);

        $productList=$this->listOfProducts();
        $units = UnitOfMeasurement::all();

        $grades = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_grades')) {
                $grades = \Modules\Inventory\Models\ProductGrade::where('is_active', true)->orderBy('name', 'asc')->get();
            }
        } catch (\Exception $e) {
            // Database not ready or table doesn't exist
        }

        return view(
            'stock_management::stock_management_dashboard',
            [
                'location' => $locations ,
                'productList'=>$productList,
                'units' => $units,
                'grades' => $grades
            ]
        );
    }

    public function stockTransfer(): View
    {
        $productList=$this->listOfProducts();
        $units = UnitOfMeasurement::all();

        $grades = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_grades')) {
                $grades = \Modules\Inventory\Models\ProductGrade::where('is_active', true)->orderBy('name', 'asc')->get();
            }
        } catch (\Exception $e) {
            // Database not ready or table doesn't exist
        }

        $transfers = \Modules\StockManagement\Models\StockTransfer\StockTransfer::with([
            'fromLocation', 
            'toLocation', 
            'items.product'
        ])->orderBy('id', 'desc')->paginate(15);

        return view('stock_management::Components.stock_transfer', compact('productList', 'grades', 'units', 'transfers'));
    }

    public function stockTransit(): View
    {
        return view('stock_management::Components.stock_transit');
    }

     public function overview(): View
    {
        return view('stock_management::Components.overview');
    }




    private function listOfProducts(){

        return $this->products->shareProductList();

    }
}
