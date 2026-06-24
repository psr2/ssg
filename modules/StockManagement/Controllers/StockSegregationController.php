<?php

namespace Modules\StockManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockManagement\Services\StockSegregation\StockSegregationService;
use Exception;

class StockSegregationController extends Controller
{
    protected StockSegregationService $segregationService;

    public function __construct(StockSegregationService $segregationService)
    {
        $this->segregationService = $segregationService;
    }

    /**
     * Get details of a parent batch.
     */
    public function batchDetails(Request $request)
    {
        $request->validate([
            'batch' => 'required|string',
            'location_id' => 'required|integer',
        ]);

        try {
            $details = $this->segregationService->getBatchDetails(
                $request->query('batch'),
                (int)$request->query('location_id')
            );
            return response()->json($details);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Store a new segregation entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'segregation_date' => 'required|date',
            'location_id' => 'required|integer',
            'parent_batch_code' => 'required|string',
            'product_id' => 'required|integer',
            'remarks' => 'nullable|string',
            'outputs' => 'required|array|min:1',
            'outputs.*.grade' => 'required|string',
            'outputs.*.quantity' => 'required|numeric|min:0.01',
            'outputs.*.unit' => 'required|string',
            'outputs.*.unit_cost' => 'required|numeric',
            'outputs.*.remarks' => 'nullable|string',
        ]);

        try {
            $this->segregationService->processSegregation($validated);
            return response()->json([
                'success' => true,
                'message' => 'Stock segregation saved successfully.'
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Search parent batches for segregation.
     */
    public function searchBatches(Request $request, \Modules\StockManagement\Repositories\BatchCode\BatchCodeRepository $repo)
    {
        $results = $repo->searchUnsegregated($request->all());
        return response()->json($results);
    }
}
