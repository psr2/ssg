<?php

namespace Modules\FleetManagement\Repository;

use Modules\FleetManagement\Models\FleetVehicle;

class FleetVehicleRepository
{
    public function getAll() {
        return FleetVehicle::all();
    }

    public function create(array $data) {
        return FleetVehicle::create($data);
    }

    public function find($id) {
        return FleetVehicle::findOrFail($id);
    }

    public function update($id, array $data) {
        $vehicle = $this->find($id);
        $vehicle->update($data);
        return $vehicle;
    }

    public function delete($id) {
        $vehicle = $this->find($id);
        return $vehicle->delete();
    }
}
