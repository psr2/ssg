@extends('dashboard::dashboard')

@section('warehouse_overview')
    <div class="row g-3 ms-2 mt-3">

        <!-- Pending Receivables / Dues -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 d-flex flex-column" style="background-color: #d0ebff; border-radius: 8px;">
                <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                    <h3 class="fw-bold p-2 text-primary">
                        <i class="bi bi-currency-rupee me-1"></i>{{ number_format($totalReceivables ?? 0, 2) }}/-
                    </h3>
                    <small class="text-muted">Total Receivables (Dues)</small>
                </div>
            </div>
        </div>

        <!-- Today's Sales -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 d-flex flex-column" style="background-color: #e2f9df; border-radius: 8px;">
                <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                    <h3 class="fw-bold p-2 text-success">
                        <i class="bi bi-currency-rupee me-1"></i>{{ number_format($todaySales ?? 0, 2) }}/-
                    </h3>
                    <small class="text-muted">Today's Sales</small>
                </div>
            </div>
        </div>

        <!-- Low Stock Items Count -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 d-flex flex-column" style="background-color: #fde8e8; border-radius: 8px;">
                <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                    <h3 class="fw-bold p-2 text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-1"
                            style="animation: blinkWarning 1.4s infinite ease-in-out;"></i>
                        {{ $lowStockCount ?? 0 }} Items
                    </h3>
                    <small class="text-muted">Low Stock Batches (< 10 qty)</small>
                </div>
            </div>
        </div>

    </div>
@endsection
