<?php

namespace Modules\FleetManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\FleetManagement\Requests\CreateTripRequest;
use Modules\FleetManagement\Services\FleetTrips\FleetTripService;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;
use Modules\FleetManagement\Models\FleetTrip;

/***
 * Add proper commenting later
 */


class FleetTripController extends Controller
{
    public function __construct(
        protected LocationsInterface $locations,
        protected  FleetTripService $trip
    ) {}

    /**
     * Returns view with Location and Trip details
     */
    public function index()
    {
        $locations = json_decode($this->locations->shareLocation(), true);

        $trips = $this->trip->allTrips(); // fetch paginated trips

        return view('fleet_management::fleet_trip', compact('locations', 'trips'));
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
        $this->trip->createTrip($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Trip created successfully'
        ]);
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
                'product_id'  => $stock->product_id,
                'product_name' => $stock->product->name ?? '',
                'batch'       => $stock->batch,
                'grade'       => $stock->grade,
                'quantity'    => $stock->qty_sent,
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
}
