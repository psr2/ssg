@extends('dashboard::dashboard')

@section('content')
<style>
    .reporting-container {
        padding: 24px;
        color: #1e293b;
    }
    .report-card-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        transition: all 0.25s ease-in-out;
        cursor: pointer;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .report-card-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        border-color: #3b82f6;
    }
    .report-card-item.active {
        border-color: #2563eb;
        background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
        box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.15);
    }
    .report-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 12px;
    }
    .kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .table-reporting {
        font-size: 0.875rem;
        width: 100%;
    }
    .table-reporting thead th {
        background: #0f172a;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 14px;
    }
    .table-reporting tbody td {
        padding: 12px 14px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-reporting tbody tr:hover {
        background-color: #f8fafc;
    }
    .filter-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px 24px;
        margin-bottom: 24px;
    }
</style>

<div class="reporting-container">

    <!-- Header & Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: #0f172a;"><i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Standard Reports Center</h3>
            <p class="text-muted small m-0">Generate, view, and export PDF reports across all operational inventory modules.</p>
        </div>
        <div class="d-flex gap-2">
            <a id="btn-preview-pdf" href="/reports/preview/{{ $type }}?{{ http_build_query($filters) }}" target="_blank" class="btn btn-outline-dark btn-sm d-flex align-items-center">
                <i class="bi bi-eye me-1"></i> Print / Preview PDF
            </a>
            <a id="btn-download-pdf" href="/reports/download/pdf/{{ $type }}?{{ http_build_query($filters) }}" class="btn btn-danger btn-sm d-flex align-items-center">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF
            </a>
            <a id="btn-export-csv" href="/reports/download/csv/{{ $type }}?{{ http_build_query($filters) }}" class="btn btn-outline-success btn-sm d-flex align-items-center">
                <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Standard Reports Navigation Grid (8 Standard Reports) -->
    <div class="row g-3 mb-4">
        <!-- 1. Stock & Inventory -->
        <div class="col-md-3 col-sm-6">
            <a href="/reports/stock" class="text-decoration-none">
                <div class="report-card-item {{ $type === 'stock' ? 'active' : '' }}">
                    <div class="report-card-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Stock & Inventory</h6>
                    <p class="text-muted small m-0">Warehouse & Shop stock summary by grade and product.</p>
                </div>
            </a>
        </div>

        <!-- 2. Stock Ledger -->
        <div class="col-md-3 col-sm-6">
            <a href="/reports/ledger" class="text-decoration-none">
                <div class="report-card-item {{ $type === 'ledger' ? 'active' : '' }}">
                    <div class="report-card-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Stock Ledger Audit</h6>
                    <p class="text-muted small m-0">Immutable record of all stock ins, outs, & transfers.</p>
                </div>
            </a>
        </div>

        <!-- 3. Warehouse Sales -->
        <div class="col-md-3 col-sm-6">
            <a href="/reports/warehouse" class="text-decoration-none">
                <div class="report-card-item {{ $type === 'warehouse' ? 'active' : '' }}">
                    <div class="report-card-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-building"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Warehouse Sales</h6>
                    <p class="text-muted small m-0">Bulk sales, customer bills, paid and credit tracking.</p>
                </div>
            </a>
        </div>

        <!-- 4. Shop Sales -->
        <div class="col-md-3 col-sm-6">
            <a href="/reports/shop" class="text-decoration-none">
                <div class="report-card-item {{ $type === 'shop' ? 'active' : '' }}">
                    <div class="report-card-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-shop"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Shop POS Sales</h6>
                    <p class="text-muted small m-0">Counter sales performance, payment modes & receipts.</p>
                </div>
            </a>
        </div>

        <!-- 5. Fleet Operations -->
        <div class="col-md-3 col-sm-6">
            <a href="/reports/fleet" class="text-decoration-none">
                <div class="report-card-item {{ $type === 'fleet' ? 'active' : '' }}">
                    <div class="report-card-icon bg-indigo bg-opacity-10 text-primary">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Fleet Operations</h6>
                    <p class="text-muted small m-0">Route sales, trip dispatches, and vehicle billing.</p>
                </div>
            </a>
        </div>

        <!-- 6. Expense Report -->
        <div class="col-md-3 col-sm-6">
            <a href="/reports/expenses" class="text-decoration-none">
                <div class="report-card-item {{ $type === 'expenses' ? 'active' : '' }}">
                    <div class="report-card-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Expenses & Costs</h6>
                    <p class="text-muted small m-0">Operational spending by category and payment mode.</p>
                </div>
            </a>
        </div>

        <!-- 7. Stock Adjustments -->
        <div class="col-md-3 col-sm-6">
            <a href="/reports/adjustments" class="text-decoration-none">
                <div class="report-card-item {{ $type === 'adjustments' ? 'active' : '' }}">
                    <div class="report-card-icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-sliders"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Stock Adjustments</h6>
                    <p class="text-muted small m-0">Physical audit reconciliations, yield & shrinkage.</p>
                </div>
            </a>
        </div>

        <!-- 8. Credits & Receivables -->
        <div class="col-md-3 col-sm-6">
            <a href="/reports/credits" class="text-decoration-none">
                <div class="report-card-item {{ $type === 'credits' ? 'active' : '' }}">
                    <div class="report-card-icon bg-dark bg-opacity-10 text-dark">
                        <i class="bi bi-credit-card-2-front"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Credits & Aging</h6>
                    <p class="text-muted small m-0">Cross-module outstanding balances and receivables.</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Filter Control Panel -->
    <div class="filter-panel shadow-sm">
        <form method="GET" action="/reports/type/{{ $type }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Date Range (Start Date)</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $filters['start_date'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $filters['end_date'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Search Keywords</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by customer, SKU, product, ref..." value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Apply Filter</button>
                <a href="/reports/type/{{ $type }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    @if(!empty($reportData['kpis']))
        <div class="row g-3 mb-4">
            @foreach($reportData['kpis'] as $label => $val)
                <div class="col">
                    <div class="kpi-card text-center">
                        <div class="small text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">{{ $label }}</div>
                        <div class="fs-4 fw-bold text-dark mt-1">{{ $val }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Live Data Preview Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="fw-bold m-0 text-dark d-flex align-items-center">
                <i class="bi bi-table text-primary me-2"></i> {{ $reportData['title'] }} Preview
            </h6>
            <span class="badge bg-light text-dark border fw-normal">Total Records: {{ count($reportData['rows']) }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-reporting m-0">
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
                                <td>
                                    @if(str_contains($cell, 'PAID'))
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">{{ $cell }}</span>
                                    @elseif(str_contains($cell, 'PARTIAL'))
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">{{ $cell }}</span>
                                    @elseif(str_contains($cell, 'CREDIT'))
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">{{ $cell }}</span>
                                    @else
                                        {{ $cell }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($reportData['columns']) }}" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                No records found for the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
