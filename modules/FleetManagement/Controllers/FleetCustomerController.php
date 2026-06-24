<?php

namespace Modules\FleetManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\FleetManagement\Models\FleetCustomer;
use Illuminate\Support\Facades\Log;

/**
 * Class FleetCustomerController
 *
 * Handles requests related to fleet customers, such as fetching
 * customer names by route.
 * Implements fuzzy search on the frontend using fuss.js to reduce 
 * the load on the database.
 *
 * TODO:
 * - Implement a Repository pattern for database actions to decouple
 *   logic from the controller.
 * - Establish a proper logging strategy with structured logging.
 * - Move the business logic for collecting route names into a separate
 *   Service class for better separation of concerns.
 * - Implement custom exception handling to provide clearer error responses.
 * - Use DTO as well
 */
class FleetCustomerController extends Controller
{
    public function getCustomers(Request $request)
    {

        $validated = $request->validate([
            'route_id' => 'required|integer|exists:fleet_routes,id',
        ]);

        try {

            $customers = FleetCustomer::where('route_id', $validated['route_id'])
                ->pluck('customer_name' ,'id');

            return response()->json([
                'success' => true,
                'data' => $customers,
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching customers: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }
}
