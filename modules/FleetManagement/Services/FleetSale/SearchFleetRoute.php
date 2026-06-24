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
            $query = FleetTrip::query();

            if (!empty($validated['tag'])) {
                $query->where('tag', 'like', '%' . $validated['tag'] . '%');
            }

            if (!empty($validated['trip_date'])) {
                $query->where('start_date', $validated['trip_date']);
            }

            if (!empty($validated['routeName'])) {
                $query->where('route_id', $validated['routeName']);
            }

            $fleetTrip = $query->get();

            return $fleetTrip;
        } catch (\Exception $e) {
            Log::error('Error finding route: ' . $e->getMessage());
            return 'An error occurred while searching for the route';
        }
    }
}
