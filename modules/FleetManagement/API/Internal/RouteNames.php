<?php

namespace Modules\FleetManagement\API\Internal;

use Modules\FleetManagement\API\Contracts\RouteNamesInterface;
use Modules\FleetManagement\Models\FleetRoutes;

/**
 * Returns route names and IDs
 *
 * Used in all internal service calls.
 */
class RouteNames implements RouteNamesInterface
{
    /**
     * Get an array of route names and IDs.
     *
     * @return array
     */
    public function routeNames(): array
    {
        return FleetRoutes::select('id', 'name')->get()->toArray();
    }
}
