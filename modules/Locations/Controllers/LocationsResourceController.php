<?php

namespace Modules\Locations\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Locations\DTO\LocationsDto;
use Modules\Locations\Models\LocationModel;
use Modules\Locations\Requests\EditRequest;
use Modules\Locations\Requests\LocationsRequests;
use Modules\Locations\Requests\UpdateLocationRequest;
use Modules\Locations\Services\StoreLocationService;
use Ramsey\Uuid\Type\Integer;

class LocationsResourceController extends Controller
{

    public function index(): View
    {
        $data = LocationModel::orderBy('id', 'desc')->paginate(request('per_page', 15));
        return view('locations::locations_dashboard', ['data' => $data]);
    }

    public function store(LocationsRequests $request, StoreLocationService $store): JsonResponse
    {
        try {
            $dto = LocationsDto::fromArray($request);
            $store->create($dto);
            
        } catch (\Exception $e) {
            Log::error('Error creating location: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong'], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Location created successfully!'
        ], 201);
    }

    public function edit(EditRequest $request): JsonResponse
    {

        Log::debug('call reached in edit');

        try {
            $location = LocationModel::findOrFail($request->id);
            return response()->json([
                'success' => true,
                'data' => $location
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found'
            ], 404);
        }
    }

    public function update(UpdateLocationRequest $request): JsonResponse
    {
        try {

            $location = LocationModel::findOrFail($request->id);



            $location->update([
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location.'
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $location = LocationModel::findOrFail($id);
            $location->delete();

            return response()->json([
                'success' => true,
                'message' => 'Location deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete location.'
            ], 500);
        }
    }
}
