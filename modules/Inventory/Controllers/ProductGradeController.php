<?php

namespace Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\ProductGrade;

class ProductGradeController extends Controller
{
    /**
     * Display a listing of product grades.
     */
    public function index()
    {
        $grades = ProductGrade::orderBy('name', 'asc')->get();
        return view('inventory::grades', compact('grades'));
    }

    /**
     * Store a newly created product grade in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_grades,name',
            'code' => 'required|string|max:50|unique:product_grades,code',
            'description' => 'nullable|string',
        ]);

        ProductGrade::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Product Grade created successfully!');
    }

    /**
     * Remove the specified product grade from storage.
     */
    public function destroy($id)
    {
        $grade = ProductGrade::findOrFail($id);
        $grade->delete();

        return redirect()->back()->with('success', 'Product Grade deleted successfully!');
    }
}
