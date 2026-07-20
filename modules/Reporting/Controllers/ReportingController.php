<?php

namespace Modules\Reporting\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Reporting\Services\ReportingService;

class ReportingController extends Controller
{
    public function __construct(protected ReportingService $service) {}

    /**
     * Display Reporting Module Center dashboard.
     */
    public function index(Request $request, string $type = 'stock')
    {
        $allowedTypes = ['stock', 'ledger', 'warehouse', 'shop', 'fleet', 'expenses', 'adjustments', 'credits'];
        if (!in_array($type, $allowedTypes)) {
            $type = 'stock';
        }

        $filters = $request->only(['start_date', 'end_date', 'search', 'transaction_type']);
        $reportData = $this->service->getReportData($type, $filters);

        return view('reporting::index', compact('reportData', 'type', 'filters'));
    }

    /**
     * Get report dataset via AJAX JSON.
     */
    public function data(Request $request, string $type = 'stock'): JsonResponse
    {
        $filters = $request->only(['start_date', 'end_date', 'search', 'transaction_type']);
        return response()->json($this->service->getReportData($type, $filters));
    }

    /**
     * Download Report as Custom Binary PDF file.
     */
    public function downloadPdf(Request $request, string $type = 'stock'): Response
    {
        $filters = $request->only(['start_date', 'end_date', 'search', 'transaction_type']);
        $pdfContent = $this->service->generatePdf($type, $filters);

        $filename = 'report-' . $type . '-' . date('Ymd-His') . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => strlen($pdfContent),
        ]);
    }

    /**
     * Display full-screen printable HTML view for PDF printing/saving.
     */
    public function previewPdf(Request $request, string $type = 'stock')
    {
        $filters = $request->only(['start_date', 'end_date', 'search', 'transaction_type']);
        $reportData = $this->service->getReportData($type, $filters);

        return view('reporting::pdf_preview', compact('reportData', 'type', 'filters'));
    }

    /**
     * Download Report as CSV file.
     */
    public function downloadCsv(Request $request, string $type = 'stock'): Response
    {
        $filters = $request->only(['start_date', 'end_date', 'search', 'transaction_type']);
        $csvContent = $this->service->generateCsv($type, $filters);

        $filename = 'report-' . $type . '-' . date('Ymd-His') . '.csv';

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function overview(): JsonResponse
    {
        return response()->json($this->service->getOverviewReport());
    }
}

