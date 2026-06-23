<?php

namespace Modules\FleetManagement\Services\FleetSale;

//Framework
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

//Exceptions
use Modules\FleetManagement\Exceptions\Fleet\FleetRouteNotFoundException;

//Modal
use Modules\FleetManagement\Models\FleetTrip;


/**
 * Search and finds route name
 */
class SearchFleetRoute
{
    /**
     * Find the route name based on the provided date and route ID.
     *
     * @param Request $request
     * @return string
     */
    public function findRoute($validated): mixed
    {

        try {
            $fleetTrip = FleetTrip::where('start_date', $validated['trip_date'])
                ->where('route_id', $validated['routeName'])
                ->get();

            // Check if a record was found
            if (!$fleetTrip) {
                Log::debug($fleetTrip);
                Log::info('No route found for date: ' . $validated['date'] . ' and route: ' . $validated['route']);
                return 'No route found';
            }

            // Return the route name (assuming route_name is a field in FleetTrip)
            return $fleetTrip;
        } catch (\Exception $e) {
            Log::error('Error finding route: ' . $e->getMessage());
            return 'An error occurred while searching for the route';
        }
    }
}
