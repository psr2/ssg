<?php

namespace Modules\Inventory\Controllers\Products;

use Modules\Inventory\Models\Products;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CreateProductController extends Controller
{
    public function index(Request $request)
    {
        // Validate request based on your HTML form fields
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string|max:255',
            'product_name_abbreviation' => 'required|string|max:50',
            'unit_id' => 'required|exists:unit_of_measurements,id',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            // Return validation errors as JSON
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Create product
        $product = Products::create([
            'name' => $request->product_name,
            'abbreviation' => $request->product_name_abbreviation,
            'unit_id' => $request->unit_id,
            'category' => $request->category,
            'description' => $request->description,
            'sku' => null, // Optional: You may generate an SKU here
        ]);

        return response()->json([
            'message' => 'Product created successfully!',
            'product' => $product
        ], 201);
    }
}
