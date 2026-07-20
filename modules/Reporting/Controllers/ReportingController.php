<?php

namespace Modules\Reporting\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Reporting\Services\ReportingService;

class ReportingController extends Controller
{
    public function __construct(protected ReportingService $service) {}

    public function index()
    {
        return view('reporting::index');
    }

    public function overview(): JsonResponse
    {
        return response()->json($this->service->getOverviewReport());
    }
}
