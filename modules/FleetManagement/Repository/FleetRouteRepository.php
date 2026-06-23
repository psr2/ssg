<?php

namespace Modules\FleetManagement\Repository;

use Modules\FleetManagement\Models\FleetRoutes  as FleetRoute;


class FleetRouteRepository {
    public function all() {
        return FleetRoute::all();
    }

    public function find($id) {
        return FleetRoute::findOrFail($id);
    }

    public function create(array $data) {
        return FleetRoute::create($data);
    }

    public function update($id, array $data) {
        $route = FleetRoute::findOrFail($id);
        $route->update($data);
        return $route;
    }

    public function delete($id) {
        $route = FleetRoute::findOrFail($id);
        return $route->delete();
    }
}
