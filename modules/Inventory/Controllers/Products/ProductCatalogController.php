<?php

namespace Modules\Inventory\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\UnitOfMeasurement as Unit;

class ProductCatalogController extends Controller
{
    /**
     * Render the product catalog page.
     */
    public function productListing(): View
    {
        $units = Unit::orderBy('name')->get();

        return view('inventory::product-catalog', ['units' => $units]);
    }

    /**
     * Return all products as JSON (for JS table population).
     */
    public function list(): JsonResponse
    {
        $products = Products::with('unit')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'abbreviation' => $p->abbreviation,
                'sku'          => $p->sku,
                'unit_id'      => $p->unit_id,
                'unit_name'    => $p->unit?->name ?? '—',
                'category'     => $p->category,
                'description'  => $p->description ?? '',
            ]);

        return response()->json($products);
    }

    /**
     * Create a new product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:products,name',
            'abbreviation' => 'required|string|max:50',
            'unit_id'      => 'required|exists:units,id',
            'category'     => 'nullable|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'sku'          => 'nullable|string|max:100|unique:products,sku',
        ]);

        $product = Products::create($validated);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product,
        ], 201);
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Products::findOrFail($id);

        $validated = $request->validate([
            'name'         => "required|string|max:255|unique:products,name,{$id}",
            'abbreviation' => 'required|string|max:50',
            'unit_id'      => 'required|exists:units,id',
            'category'     => 'nullable|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'sku'          => "nullable|string|max:100|unique:products,sku,{$id}",
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->fresh(),
        ]);
    }

    /**
     * Delete a product.
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Products::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
