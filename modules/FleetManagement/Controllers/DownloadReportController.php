<?php

namespace Modules\FleetManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\FleetManagement\Services\FleetSale\ReportDownload;

class DownloadReportController extends Controller
{
    protected ReportDownload $reportService;

    public function __construct(ReportDownload $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Download pending credit report as CSV
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function download(Request $request)
    {
        $routeId = $request->route_id;

        // Get pending credits for the selected route
        $credits = $this->reportService->getPendingCreditsByRoute($routeId);

        // Define CSV file name
        $fileName = "fleet_credit_report_route_{$routeId}.csv";

        // Set headers for CSV download
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        // Stream CSV content
        $callback = function () use ($credits) {
            $output = fopen('php://output', 'w');

            // Column headers
            fputcsv($output, [
                'Bill Number',
                'Customer Name',
                'Total Amount',
                'Total Paid',
                'Pending Amount',
               
                'Route Name',
            ]);

            // Write each row
            foreach ($credits as $item) {
                fputcsv($output, [
                    $item->bill_number,
                    $item->customer_name,
                    $item->total_amount,
                    $item->total_paid,
                    $item->pending_amount,
                  
                    $item->route_name,
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
