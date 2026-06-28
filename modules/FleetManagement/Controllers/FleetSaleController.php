<?php

namespace Modules\FleetManagement\Controllers;

//Framework
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

//Services
use Modules\FleetManagement\Requests\StorePaymentRequest;
use Modules\FleetManagement\Services\FleetSale\StorePayments;
use Modules\FleetManagement\Services\FleetSale\SearchFleetRoute;
use Modules\FleetManagement\Services\FleetSale\StoreRouteSalePayments;
use Modules\FleetManagement\Services\FleetSale\SaleRecords;
//API Interface
use Modules\FleetManagement\API\Contracts\RouteNamesInterface;

/**
 * Shows all records
 */

class FleetSaleController extends Controller
{
    /**
     * Route name service instance.
     *
     * @var RouteNamesInterface
     */
    protected RouteNamesInterface $routes;

    protected SearchFleetRoute $search;

    protected StoreRouteSalePayments $payments;

    protected SaleRecords $saleRecordsService;

    const RECORDS_PER_PAGE = 10;

    /**
     * Inject dependencies.
     *
     * @param RouteNamesInterface $routes
     */
    public function __construct(
        RouteNamesInterface $routes,
        SearchFleetRoute $search,
        StoreRouteSalePayments $payments,
        SaleRecords $saleRecordsService
    ) {
        $this->routes = $routes;
        $this->search = $search;
        $this->payments = $payments;
        $this->saleRecordsService = $saleRecordsService;
    }

    /**
     * Display the fleet sale page with route names and sale records
     *
     * @return View
     */
    public function index(Request $request): View
    {
        $perPage = $request->input('per_page', self::RECORDS_PER_PAGE);
        $saleRecords = $this->saleRecordsService->scan($perPage);

        // Fetch latest 5 created trips
        $latestTrips = \Modules\FleetManagement\Models\FleetTrip::orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // Load route names map
        $routeMap = \Modules\FleetManagement\Models\FleetRoutes::pluck('name', 'id')->toArray();

        // Format latest trips for display
        $formattedTrips = $latestTrips->map(function ($trip) use ($routeMap) {
            $routeName = $routeMap[$trip->route_id] ?? 'Unknown Route';
            $formattedDate = $trip->start_date ? date('d/m/Y', strtotime($trip->start_date)) : '';
            return [
                'id' => $trip->id,
                'tag' => $trip->tag,
                'route_id' => $trip->route_id,
                'route_name' => $routeName,
                'start_date' => $formattedDate,
                'display' => "{$routeName} - {$formattedDate}"
            ];
        });

        $productList = app(\Modules\Inventory\API\Contracts\ProductInterface::class)->shareProductList();
        $units = \Modules\Inventory\Models\UnitOfMeasurement::all();
        $grades = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_grades')) {
                $grades = \Modules\Inventory\Models\ProductGrade::where('is_active', true)->orderBy('name', 'asc')->get();
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch grades for FleetSale: ' . $e->getMessage());
        }

        return view('fleet_management::fleet_sale', [
            'data' => $this->getRouteNames(),
            'saleRecords' => $saleRecords,
            'latestTrips' => $formattedTrips,
            'productList' => $productList,
            'units' => $units,
            'grades' => $grades
        ]);
    }



    /**
     * Searches for a fleet route using the provided trip date and route ID.
     * 
     * Todo - add exception , separate request 
     *
     * @param  SearchFleetRouteRequest  $request  Validated trip date and route ID from the search fleet UI
     * 
     * @return \Illuminate\Http\JsonResponse  The found route data or an error response if the search fails
     * 
     * @throws \Modules\FleetManagement\Exceptions\FleetRouteNotFoundException If the route is not found
     */
    public function routeName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_date' => 'nullable|date',
            'routeName' => 'nullable|numeric',
            'tag'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        Log::debug('validated', $validator->validated());

        $result = $this->search->findRoute($validator->validated());
        return response()->json($result);
    }

    /**
     * Store validated fleet payments in the database 
     * 
     * @param StorePaymentRequest $request Validated payment data for storage
     * @throws \Modules\FleetManagement\Exceptions\FleetPaymentsStorageException If payment storage fails
     */
    public function storePayments(StorePaymentRequest $request)
    {
        $this->payments->store($request->validated());

        Log::debug($request->validated());

        return response()->json([
            'message' => 'Payment stored successfully.',
            'status' => 'success',
            // You can return saved data if needed
        ], 200);
    }

    /**
     * Fetch fleet route names from the service.
     *
     * @return array
     */
    private function getRouteNames(): array
    {
        return $this->routes->routeNames();
    }

    /**
     * Get the latest 5 trips formatted for select dropdown
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function latestTrips()
    {
        $latestTrips = \Modules\FleetManagement\Models\FleetTrip::orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $routeMap = \Modules\FleetManagement\Models\FleetRoutes::pluck('name', 'id')->toArray();

        $formattedTrips = $latestTrips->map(function ($trip) use ($routeMap) {
            $routeName = $routeMap[$trip->route_id] ?? 'Unknown Route';
            $formattedDate = $trip->start_date ? date('d/m/Y', strtotime($trip->start_date)) : '';
            return [
                'id' => $trip->id,
                'display' => "{$routeName} - {$formattedDate}"
            ];
        });

        return response()->json($formattedTrips);
    }

    /**
     * Upload and simulate processing of fleet sale report
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id'     => 'required|exists:fleet_trips,id',
            'report_file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ], [
            'report_file.mimes' => 'The report must be a file of type: xlsx, xls.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tripId = $request->input('trip_id');
            $file = $request->file('report_file');
            
            // Store file to storage/app/fleet_reports for record keeping
            $path = $file->store('fleet_reports');

            Log::info("Fleet Sale Report uploaded: Trip ID: {$tripId}, Path: {$path}");
            
            return response()->json([
                'status' => 'success',
                'message' => 'Fleet sale report uploaded and processed successfully!'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error processing fleet sale report upload: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process report: ' . $e->getMessage()
            ], 500);
        }
    }
}
