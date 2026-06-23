<?php

namespace Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Models\UnitOfMeasurement as Unit;




class UnitController extends Controller
{
    public function index(): View
    {
        return view('inventory::Units.units');
    }

    public function list(): JsonResponse
    {
        return response()->json(Unit::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:units,name',
            'abbreviation' => 'required|string|max:20',
        ]);

        $unit = Unit::create($validated);

        return response()->json(['message' => 'Unit created successfully.', 'unit' => $unit], 201);
    }

    public function show($id)
    {
        $unit = Unit::findOrFail($id);
        return response()->json($unit);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $unit = Unit::findOrFail($id);

        $request->validate([
            'name'         => "required|string|max:255|unique:units,name,{$id}",
            'abbreviation' => 'required|string|max:50',
        ]);

        $unit->update($request->only('name', 'abbreviation'));

        return response()->json(['message' => 'Unit updated successfully.', 'unit' => $unit->fresh()]);
    }

    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();

        return response()->json(['message' => 'Unit deleted successfully.'], 200);
    }
}
