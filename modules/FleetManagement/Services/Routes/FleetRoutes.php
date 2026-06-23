<?php

namespace Modules\FleetManagement\Services\Routes;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\FleetManagement\Repository\FleetRouteRepository as RouteRepository;



class FleetRoutes {
    protected $repo;

    public function __construct(RouteRepository $repo) {
        $this->repo = $repo;
    }

    public function list() {
        return $this->repo->all();
    }

    public function create(array $data) {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:fleet_routes,name',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->repo->create($data);
    }

    public function update($id, array $data) {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:fleet_routes,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->repo->update($id, $data);
    }

    public function delete($id) {
        return $this->repo->delete($id);
    }
}
