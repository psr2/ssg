<?php

namespace Modules\FleetManagement\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\FleetManagement\Services\FleetVehicles\FleetVehicle;

class FleetVehicleController extends Controller
{
    protected $service;

    public function __construct(FleetVehicle $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->listVehicles());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'registration_number' => 'required|unique:fleet_vehicles',
            'model' => 'required',
            'type' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        $vehicle = $this->service->createVehicle($validated);
        return response()->json($vehicle, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'registration_number' => 'required|unique:fleet_vehicles,registration_number,' . $id,
            'model' => 'required',
            'type' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        return response()->json($this->service->updateVehicle($id, $validated));
    }

    public function destroy($id)
    {
        $this->service->deleteVehicle($id);
        return response()->json(['message' => 'Vehicle deleted successfully']);
    }
}
