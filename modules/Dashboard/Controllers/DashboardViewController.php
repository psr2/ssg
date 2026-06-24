<?php

namespace Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use Modules\Dashboard\Services\Dashboard\GetDashboardData;

class DashboardViewController extends Controller
{
    protected GetDashboardData $dashboardService;

    public function __construct(GetDashboardData $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $data = $this->dashboardService->execute();

        return view('dashboard::dash', $data);
    }
}


