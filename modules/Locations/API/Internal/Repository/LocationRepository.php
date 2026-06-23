<?php

namespace Modules\Locations\API\Internal\Repository;

use Illuminate\Support\Facades\Log;
use Modules\Locations\Models\LocationModel as Location;

/**
 * Repository class for accessing location data from the database.
 *
 * WARNING:
 * This repository is intended **only for internal module communications**.
 * Do not use it directly in controllers or external APIs. 
 * Instead, rely on the service layer (e.g., ShareLocationService).
 */
class LocationRepository
{
    /**
     * Fetch all locations with only `id` and `name` fields.
     *
     * @return \Illuminate\Support\Collection List of locations
     */
    public function get()
    {
        $data = Location::select('id', 'name')->get();
        return $data;
    }
}
