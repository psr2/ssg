<?php

namespace Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Billing\Services\BillingAdjustmentService;
use Modules\Billing\Models\BillingAdjustment;

class BillingAdjustmentController extends Controller
{
    public function __construct(
        protected BillingAdjustmentService $adjustmentService
    ) {}

    /**
     * Display listing and form.
     */
    public function index()
    {
        $adjustments = BillingAdjustment::with('user')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('billing::billing_adjustments', compact('adjustments'));
    }

    /**
     * Get sales of a given type via AJAX.
     */
    public function getSales(Request $request)
    {
        $request->validate([
            'type' => 'required|in:warehouse,shop,fleet'
        ]);

        $sales = $this->adjustmentService->getPendingSalesForType($request->type);

        return response()->json($sales);
    }

    /**
     * Store a billing adjustment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_type'  => 'required|in:warehouse,shop,fleet',
            'sale_id'    => 'required|integer',
            'new_amount' => 'required|numeric|min:0',
            'reason'     => 'required|string|max:255',
            'remarks'    => 'nullable|string',
        ]);

        // Inject the currently authenticated user
        $validated['adjusted_by'] = auth()->id() ?? 1;

        try {
            $adjustment = $this->adjustmentService->createAdjustment($validated);

            return response()->json([
                'success' => true,
                'message' => 'Billing adjustment applied successfully.',
                'data'    => $adjustment
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
