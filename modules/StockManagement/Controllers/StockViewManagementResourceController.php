<?php

namespace Modules\StockManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;
use Modules\Locations\API\Internal\Repository\LocationRepository;
use Modules\Inventory\API\Contracts\ProductInterface;

use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Services\StockMovement\ReferenceNumber\PurchaseReferenceNumberGenerator as ReferenceNumber;
use Modules\StockManagement\Models\Segregation\StockSegregation;

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
                $grades = \Modules\Settings\Models\ProductGrade::where('is_active', true)->orderBy('name', 'asc')->get();
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

        $grades = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_grades')) {
                $grades = \Modules\Settings\Models\ProductGrade::where('is_active', true)->orderBy('name', 'asc')->get();
            }
        } catch (\Exception $e) {
            // Database not ready or table doesn't exist
        }

        return view('stock_management::Components.stock_transfer', compact('productList', 'grades'));
    }

    public function stockTransit(): View
    {
        return view('stock_management::Components.stock_transit');
    }

     public function overview(): View
    {
        return view('stock_management::Components.overview');
    }

    public function stockSegregation(LocationRepository $repo): View
    {
        $locationJson = $this->locationService->shareLocation($repo);
        $locations = json_decode($locationJson, true);
        $productList = $this->listOfProducts();
        $units = UnitOfMeasurement::all();
        
        $recentSegregations = StockSegregation::with(['location', 'product', 'items'])
            ->orderBy('created_at', 'desc')
            ->get();

        $grades = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_grades')) {
                $grades = \Modules\Settings\Models\ProductGrade::where('is_active', true)->orderBy('name', 'asc')->get();
            }
        } catch (\Exception $e) {
            // Database not ready or table doesn't exist
        }

        return view('stock_management::stock_segregation', [
            'location' => $locations,
            'productList' => $productList,
            'units' => $units,
            'recentSegregations' => $recentSegregations,
            'grades' => $grades
        ]);
    }


    private function listOfProducts(){

        return $this->products->shareProductList();

    }
}
