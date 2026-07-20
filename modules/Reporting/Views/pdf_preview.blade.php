<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportData['title'] ?? 'Report Preview' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2b303a;
            padding: 20px;
        }
        .report-card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 35px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .report-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .kpi-badge {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 18px;
        }
        .table-custom {
            width: 100%;
            font-size: 0.88rem;
            margin-top: 15px;
        }
        .table-custom th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 10px 12px;
        }
        .table-custom td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .table-custom tr:nth-child(even) {
            background-color: #f8fafc;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .report-card {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- Action Toolbar (Hidden when printing) -->
    <div class="container-fluid max-width-1100 mb-4 no-print" style="max-width: 1100px;">
        <div class="d-flex justify-content-between align-items-center bg-dark text-white p-3 rounded shadow-sm">
            <div>
                <a href="/reports" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-arrow-left"></i> Back to Reports</a>
                <span class="fw-semibold">{{ $reportData['title'] }}</span>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-success btn-sm me-2"><i class="bi bi-printer"></i> Print / Save PDF</button>
                <a href="/reports/download/pdf/{{ $type }}?{{ http_build_query($filters) }}" class="btn btn-primary btn-sm me-2"><i class="bi bi-file-earmark-pdf"></i> Direct Download PDF</a>
                <a href="/reports/download/csv/{{ $type }}?{{ http_build_query($filters) }}" class="btn btn-outline-info btn-sm"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
            </div>
        </div>
    </div>

    <!-- Printable Report Document -->
    <div class="report-card">
        <div class="report-header d-flex justify-content-between align-items-end">
            <div>
                <h6 class="text-uppercase text-primary fw-bold mb-1" style="letter-spacing: 1px;">Inventory Management System</h6>
                <h3 class="fw-bold m-0 text-dark">{{ $reportData['title'] }}</h3>
            </div>
            <div class="text-end">
                <p class="small text-muted mb-1">Generated: {{ date('Y-m-d H:i:s') }}</p>
                @if(!empty($reportData['metadata']))
                    @foreach($reportData['metadata'] as $k => $v)
                        <span class="badge bg-secondary me-1">{{ ucfirst($k) }}: {{ $v }}</span>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Summary KPIs -->
        @if(!empty($reportData['kpis']))
            <div class="row g-3 mb-4">
                @foreach($reportData['kpis'] as $label => $val)
                    <div class="col">
                        <div class="kpi-badge text-center">
                            <div class="small text-uppercase text-muted fw-semibold" style="font-size: 0.7rem;">{{ $label }}</div>
                            <div class="fs-5 fw-bold text-dark mt-1">{{ $val }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Data Table -->
        <table class="table-custom">
            <thead>
                <tr>
                    @foreach($reportData['columns'] as $col)
                        <th>{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($reportData['columns']) }}" class="text-center text-muted py-4">No records found for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Document Footer -->
        <div class="mt-4 pt-3 border-top d-flex justify-content-between text-muted small">
            <span>Confidential - Inventory Management Reporting System</span>
            <span>Page 1 of 1</span>
        </div>
    </div>

</body>
</html>
