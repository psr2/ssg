<?php

namespace Modules\FleetManagement\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\FleetManagement\Services\Routes\FleetRoutes as FleetRouteService;
use Illuminate\Validation\ValidationException;

class FleetRouteController extends Controller
{
    protected $service;

    public function __construct(FleetRouteService $service)
    {
        $this->service = $service;
    }

    // List all routes
    public function index()
    {
        return response()->json($this->service->list());
    }

    // Create a new route
    public function store(Request $request)
    {
        try {
            $route = $this->service->create($request->all());
            return response()->json(['success' => true, 'data' => $route]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    // Update route
    public function update(Request $request, $id)
    {
        try {
            $route = $this->service->update($id, $request->all());
            return response()->json(['success' => true, 'data' => $route]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    // Delete route
    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['success' => true]);
    }
}
