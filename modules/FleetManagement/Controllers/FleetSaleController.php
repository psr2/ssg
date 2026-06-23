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

        return view('fleet_management::fleet_sale', [
            'data' => $this->getRouteNames(),
            'saleRecords' => $saleRecords
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
    public function routeName(Request $request): array|string
    {
        $validator = Validator::make($request->all(), [
            'trip_date' => 'required|date',
            'routeName' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        Log::debug('validated', $validator->validated());

        return $this->search->findRoute($validator->validated());
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
}
