<?php

namespace Modules\FleetManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\FleetManagement\Requests\CreateTripRequest;
use Modules\FleetManagement\Services\FleetTrips\FleetTripService;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;
use Modules\FleetManagement\Models\FleetTrip;
use Modules\Inventory\API\Contracts\ProductInterface;

/***
 * Add proper commenting later
 */


class FleetTripController extends Controller
{
    public function __construct(
        protected LocationsInterface $locations,
        protected FleetTripService   $trip,
        protected ProductInterface   $products
    ) {}

    /**
     * Returns view with Location and Trip details
     */
    public function index()
    {
        $locations = json_decode($this->locations->shareLocation(), true);

        $trips = $this->trip->allTrips(); // fetch paginated trips

        $productList = $this->products->shareProductList();
        $units = \Modules\Inventory\Models\UnitOfMeasurement::all();

        $grades = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_grades')) {
                $grades = \Modules\Inventory\Models\ProductGrade::where('is_active', true)->orderBy('name', 'asc')->get();
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch grades for FleetTrip view: ' . $e->getMessage());
        }

        return view('fleet_management::fleet_trip', compact('locations', 'trips', 'productList', 'grades', 'units'));
    }


    private function getTrips()
    {

        return  $this->trip->allTrips();
    }

    /**
     * Create a fleet trip 
     * 
     * 
     */


    public function createTrip(CreateTripRequest $request)
    {
        Log::debug("received on controller ");
        try {
            $this->trip->createTrip($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Trip created successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Fleet Trip creation controller exception: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }


    /**
     * Retrieve a FleetTrip along with its sent and returned stock for editing.
     *
     * @param int $tripId
     * @return array|null
     * 
     */
    public function getTripForEdit(int $tripId): ?array
    {
        $trip = FleetTrip::with([
            'vehicle',      // make sure these relations are uncommented in the model
            'route',
            'stocks.product',
            'stocks.location'
        ])
            ->find($tripId);

        if (!$trip) {
            return null;
        }

        // Separate sent and returned stock
        $productsSent = [];
        $productsReturned = [];

        foreach ($trip->stocks as $stock) {
            $rowData = [
                'id'          => $stock->id,
                'product_id'  => $stock->product_id,
                'product_name' => $stock->product->name ?? '',
                'batch'       => $stock->batch,
                'grade'       => $stock->grade,
                'quantity'    => $stock->qty_sent,
                'unit'        => $stock->unit,
                'location_id' => $stock->location_id,
                'location_name' => $stock->location->name ?? '',
            ];

            $productsSent[] = $rowData;

            // Only add returned quantity if > 0
            if ($stock->qty_returned > 0) {
                $productsReturned[] = [
                    'product_id'  => $stock->product_id,
                    'product_name' => $stock->product->name ?? '',
                    'batch'       => $stock->batch,
                    'grade'       => $stock->grade,
                    'quantity'    => $stock->qty_returned,
                    'location_id' => $stock->location_id,
                    'location_name' => $stock->location->name ?? '',
                ];
            }
        }

        return [
            'trip' => $trip,
            'products_sent' => $productsSent,
            'products_returned' => $productsReturned,
        ];
    }

    public function searchBatches(\Illuminate\Http\Request $request, \Modules\FleetManagement\Repository\FleetBatchCodeRepository $repo)
    {
        $results = $repo->search($request->all());
        return response()->json($results);
    }

    public function getTripDetails(int $tripId)
    {
        $details = $this->getTripForEdit($tripId);
        if (!$details) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found.'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $details
        ]);
    }

    public function adjustTrip(int $tripId, \Modules\FleetManagement\Requests\AdjustTripRequest $request)
    {
        try {
            $this->trip->adjustTrip($tripId, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Trip adjusted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Fleet Trip adjustment exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->trip->deleteTrip($id);
            return response()->json([
                'success' => true,
                'message' => 'Trip deleted and stock restored successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Fleet Trip deletion exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
