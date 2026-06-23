<?php

namespace Modules\FleetManagement\Services\FleetVehicles;

use Modules\FleetManagement\Repository\FleetVehicleRepository;

class FleetVehicle
{
    protected $repository;

    public function __construct(FleetVehicleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listVehicles() {
        return $this->repository->getAll();
    }

    public function createVehicle(array $data) {
        return $this->repository->create($data);
    }

    public function updateVehicle($id, array $data) {
        return $this->repository->update($id, $data);
    }

    public function deleteVehicle($id) {
        return $this->repository->delete($id);
    }
}
