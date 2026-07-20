<?php

namespace Modules\Reporting\Services;

use Modules\Reporting\Repositories\ReportingRepository;

class ReportingService
{
    public function __construct(
        protected ReportingRepository $repository,
        protected PdfGenerator $pdfGenerator
    ) {}

    /**
     * Get Report Data Structure (KPIs, Columns, Rows, Metadata) for any standard report.
     */
    public function getReportData(string $type, array $filters = []): array
    {
        switch ($type) {
            case 'stock':
                return $this->buildStockInventoryReport($filters);
            case 'ledger':
                return $this->buildStockLedgerReport($filters);
            case 'warehouse':
                return $this->buildWarehouseSalesReport($filters);
            case 'shop':
                return $this->buildShopSalesReport($filters);
            case 'fleet':
                return $this->buildFleetSalesReport($filters);
            case 'expenses':
                return $this->buildExpenseReport($filters);
            case 'adjustments':
                return $this->buildStockAdjustmentReport($filters);
            case 'credits':
                return $this->buildCreditsReport($filters);
            default:
                return $this->buildStockInventoryReport($filters);
        }
    }

    /**
     * Generate Binary PDF Document for report.
     */
    public function generatePdf(string $type, array $filters = []): string
    {
        $data = $this->getReportData($type, $filters);
        $orientation = count($data['columns']) > 6 ? 'L' : 'P';

        return $this->pdfGenerator->generate(
            $data['title'],
            $data['metadata'],
            $data['kpis'],
            $data['columns'],
            $data['rows'],
            $orientation
        );
    }

    /**
     * Generate CSV content for report download.
     */
    public function generateCsv(string $type, array $filters = []): string
    {
        $data = $this->getReportData($type, $filters);
        $handle = fopen('php://temp', 'r+');

        // Write title and metadata
        fputcsv($handle, [$data['title']]);
        fputcsv($handle, ['Generated At', date('Y-m-d H:i:s')]);
        fputcsv($handle, []);

        // Write KPIs
        if (!empty($data['kpis'])) {
            fputcsv($handle, ['Summary Metrics']);
            foreach ($data['kpis'] as $k => $v) {
                fputcsv($handle, [$k, $v]);
            }
            fputcsv($handle, []);
        }

        // Write Table Columns
        fputcsv($handle, $data['columns']);

        // Write Rows
        foreach ($data['rows'] as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    protected function buildStockInventoryReport(array $filters): array
    {
        $raw = $this->repository->getStockInventoryReport($filters);
        
        $totalProducts = count($raw);
        $totalWhQty = array_sum(array_column($raw, 'warehouse_qty'));
        $totalShopQty = array_sum(array_column($raw, 'shop_qty'));
        $totalStockQty = $totalWhQty + $totalShopQty;

        $columns = ['Product Name', 'SKU', 'Category', 'Unit', 'Warehouse Qty', 'Shop Qty', 'Total Qty'];
        $rows = [];

        foreach ($raw as $item) {
            $rows[] = [
                $item->product_name,
                $item->sku ?? 'N/A',
                $item->category ?? 'General',
                $item->unit ?? 'Units',
                number_format($item->warehouse_qty, 2),
                number_format($item->shop_qty, 2),
                number_format($item->total_qty, 2),
            ];
        }

        return [
            'type' => 'stock',
            'title' => 'Master Stock Inventory Report',
            'metadata' => [
                'Filter' => !empty($filters['search']) ? "Search: '{$filters['search']}'" : 'All Products',
                'Total Items' => $totalProducts,
            ],
            'kpis' => [
                'Total Products' => $totalProducts,
                'Warehouse Stock' => number_format($totalWhQty, 2),
                'Shop Stock' => number_format($totalShopQty, 2),
                'Combined Total Stock' => number_format($totalStockQty, 2),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'raw' => $raw,
        ];
    }

    protected function buildStockLedgerReport(array $filters): array
    {
        $raw = $this->repository->getStockLedgerReport($filters);
        
        $totalEntries = count($raw);
        $totalQtyMoved = array_sum(array_map(fn($r) => abs($r->quantity), $raw));

        $columns = ['Date / Time', 'Tx Type', 'Product Name', 'Location', 'Batch Code', 'Grade', 'Qty', 'Unit Cost', 'Remarks'];
        $rows = [];

        foreach ($raw as $item) {
            $rows[] = [
                date('M d, Y H:i', strtotime($item->created_at)),
                $item->transaction_type,
                $item->product_name,
                $item->location_name ?? 'Central',
                $item->batch_code,
                $item->grade ?? '-',
                ($item->quantity > 0 ? '+' : '') . number_format($item->quantity, 2) . ' ' . $item->unit,
                '₹' . number_format($item->unit_cost, 2),
                $item->remarks ?? '-',
            ];
        }

        return [
            'type' => 'ledger',
            'title' => 'Stock Ledger & Audit Movement Report',
            'metadata' => [
                'Period' => ($filters['start_date'] ?? 'Beginning') . ' to ' . ($filters['end_date'] ?? 'Present'),
                'Total Transactions' => $totalEntries,
            ],
            'kpis' => [
                'Ledger Entries' => $totalEntries,
                'Total Moved Volume' => number_format($totalQtyMoved, 2),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'raw' => $raw,
        ];
    }

    protected function buildWarehouseSalesReport(array $filters): array
    {
        $raw = $this->repository->getWarehouseSalesReport($filters);
        
        $totalSales = count($raw);
        $totalRevenue = array_sum(array_column($raw, 'total_amount'));
        $totalPaid = array_sum(array_column($raw, 'paid_amount'));
        $totalDue = array_sum(array_column($raw, 'due_amount'));

        $columns = ['Sale Ref #', 'Date', 'Warehouse', 'Customer Name', 'Phone', 'Total Amount', 'Paid Amount', 'Due Amount', 'Status'];
        $rows = [];

        foreach ($raw as $item) {
            $rows[] = [
                'WH-SALE-' . $item->sale_id,
                $item->sale_date,
                $item->warehouse_name ?? 'Main Warehouse',
                $item->customer_name,
                $item->customer_phone ?? '-',
                '₹' . number_format($item->total_amount, 2),
                '₹' . number_format($item->paid_amount, 2),
                '₹' . number_format($item->due_amount, 2),
                $item->status,
            ];
        }

        return [
            'type' => 'warehouse',
            'title' => 'Warehouse Sales Performance Report',
            'metadata' => [
                'Period' => ($filters['start_date'] ?? 'Beginning') . ' to ' . ($filters['end_date'] ?? 'Present'),
                'Total Invoices' => $totalSales,
            ],
            'kpis' => [
                'Total Orders' => $totalSales,
                'Total Revenue' => '₹' . number_format($totalRevenue, 2),
                'Collected Payments' => '₹' . number_format($totalPaid, 2),
                'Outstanding Credit' => '₹' . number_format($totalDue, 2),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'raw' => $raw,
        ];
    }

    protected function buildShopSalesReport(array $filters): array
    {
        $raw = $this->repository->getShopSalesReport($filters);
        
        $totalSales = count($raw);
        $totalRevenue = array_sum(array_column($raw, 'total_amount'));
        $totalPaid = array_sum(array_column($raw, 'paid_amount'));
        $totalDue = array_sum(array_column($raw, 'due_amount'));

        $columns = ['Receipt #', 'Date', 'Customer Name', 'Phone', 'Total Amount', 'Paid Amount', 'Due Amount', 'Status'];
        $rows = [];

        foreach ($raw as $item) {
            $rows[] = [
                'SHOP-REC-' . $item->sale_id,
                $item->sale_date,
                $item->customer_name,
                $item->customer_phone ?? '-',
                '₹' . number_format($item->total_amount, 2),
                '₹' . number_format($item->paid_amount, 2),
                '₹' . number_format($item->due_amount, 2),
                $item->status,
            ];
        }

        return [
            'type' => 'shop',
            'title' => 'Shop Sales Performance Report',
            'metadata' => [
                'Period' => ($filters['start_date'] ?? 'Beginning') . ' to ' . ($filters['end_date'] ?? 'Present'),
                'Total Receipts' => $totalSales,
            ],
            'kpis' => [
                'Total Shop Receipts' => $totalSales,
                'Total Sales Revenue' => '₹' . number_format($totalRevenue, 2),
                'Cash / Paid Received' => '₹' . number_format($totalPaid, 2),
                'Pending Dues' => '₹' . number_format($totalDue, 2),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'raw' => $raw,
        ];
    }

    protected function buildFleetSalesReport(array $filters): array
    {
        $raw = $this->repository->getFleetSalesReport($filters);
        
        $totalSales = count($raw);
        $totalRevenue = array_sum(array_column($raw, 'total_amount'));

        $columns = ['Fleet Bill #', 'Date / Time', 'Route Name', 'Vehicle', 'Trip ID', 'Customer Name', 'Total Amount'];
        $rows = [];

        foreach ($raw as $item) {
            $rows[] = [
                $item->bill_number ?? ('FLEET-' . $item->sale_id),
                date('M d, Y', strtotime($item->created_at)),
                $item->route_name ?? 'Standard Route',
                $item->vehicle_reg ?? 'N/A',
                'TRIP-' . $item->trip_id,
                $item->customer_name ?? 'Route Buyer',
                '₹' . number_format($item->total_amount, 2),
            ];
        }

        return [
            'type' => 'fleet',
            'title' => 'Fleet Sales & Route Operations Report',
            'metadata' => [
                'Period' => ($filters['start_date'] ?? 'Beginning') . ' to ' . ($filters['end_date'] ?? 'Present'),
                'Total Dispatch Bills' => $totalSales,
            ],
            'kpis' => [
                'Total Fleet Sales' => $totalSales,
                'Route Revenue' => '₹' . number_format($totalRevenue, 2),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'raw' => $raw,
        ];
    }

    protected function buildExpenseReport(array $filters): array
    {
        $raw = $this->repository->getExpenseReport($filters);
        
        $totalExpenses = count($raw);
        $totalAmount = array_sum(array_column($raw, 'amount'));

        $columns = ['Expense Date', 'Category', 'Paid To', 'Payment Mode', 'Ref #', 'Description', 'Amount'];
        $rows = [];

        foreach ($raw as $item) {
            $rows[] = [
                $item->expense_date,
                $item->category_name ?? 'General',
                $item->paid_to,
                strtoupper($item->payment_mode),
                $item->reference_id ?? '-',
                $item->description ?? '-',
                '₹' . number_format($item->amount, 2),
            ];
        }

        return [
            'type' => 'expenses',
            'title' => 'Expense Summary & Categorization Report',
            'metadata' => [
                'Period' => ($filters['start_date'] ?? 'Beginning') . ' to ' . ($filters['end_date'] ?? 'Present'),
                'Total Expenses' => $totalExpenses,
            ],
            'kpis' => [
                'Total Expense Claims' => $totalExpenses,
                'Cumulative Expense Amount' => '₹' . number_format($totalAmount, 2),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'raw' => $raw,
        ];
    }

    protected function buildStockAdjustmentReport(array $filters): array
    {
        $raw = $this->repository->getStockAdjustmentReport($filters);
        
        $totalAdjustments = count($raw);
        $netDelta = array_sum(array_column($raw, 'adjusted_qty'));

        $columns = ['Date', 'Product Name', 'Location', 'Batch Code', 'Original Qty', 'Adjusted Qty', 'New Qty', 'Reason', 'Status'];
        $rows = [];

        foreach ($raw as $item) {
            $rows[] = [
                date('M d, Y', strtotime($item->created_at)),
                $item->product_name,
                $item->location_name ?? 'Central Warehouse',
                $item->batch_code,
                number_format($item->original_qty, 2),
                ($item->adjusted_qty > 0 ? '+' : '') . number_format($item->adjusted_qty, 2),
                number_format($item->new_qty, 2),
                ucwords(str_replace('_', ' ', $item->reason)),
                strtoupper($item->status),
            ];
        }

        return [
            'type' => 'adjustments',
            'title' => 'Stock Adjustments & Loss/Yield Report',
            'metadata' => [
                'Period' => ($filters['start_date'] ?? 'Beginning') . ' to ' . ($filters['end_date'] ?? 'Present'),
                'Total Adjustments' => $totalAdjustments,
            ],
            'kpis' => [
                'Total Adjustments' => $totalAdjustments,
                'Net Adjusted Qty' => ($netDelta > 0 ? '+' : '') . number_format($netDelta, 2),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'raw' => $raw,
        ];
    }

    protected function buildCreditsReport(array $filters): array
    {
        $raw = $this->repository->getCreditsReport($filters);
        
        $totalAccounts = count($raw);
        $totalTotal = array_sum(array_column($raw, 'total_amount'));
        $totalPaid = array_sum(array_column($raw, 'paid_amount'));
        $totalDue = array_sum(array_column($raw, 'due_amount'));

        $columns = ['Source Module', 'Ref Number', 'Customer Name', 'Phone', 'Invoice Date', 'Total Amount', 'Paid Amount', 'Outstanding Balance'];
        $rows = [];

        foreach ($raw as $item) {
            $rows[] = [
                $item->source_module,
                $item->ref_number,
                $item->customer_name,
                $item->customer_phone ?? '-',
                $item->invoice_date,
                '₹' . number_format($item->total_amount, 2),
                '₹' . number_format($item->paid_amount, 2),
                '₹' . number_format($item->due_amount, 2),
            ];
        }

        return [
            'type' => 'credits',
            'title' => 'Cross-Module Accounts Receivable & Credits Aging Report',
            'metadata' => [
                'As Of' => date('Y-m-d H:i'),
                'Unpaid Records' => $totalAccounts,
            ],
            'kpis' => [
                'Outstanding Customer Accounts' => $totalAccounts,
                'Total Invoice Value' => '₹' . number_format($totalTotal, 2),
                'Collected Payments' => '₹' . number_format($totalPaid, 2),
                'Total Outstanding Receivable' => '₹' . number_format($totalDue, 2),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'raw' => $raw,
        ];
    }
}

