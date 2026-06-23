@extends('dashboard::dashboard')

@section('warehouse_overview')
    {{-- We yield this under warehouse_overview section or a custom yield. In dashboard.blade.php we added yield('warehouse_overview') --}}
@endsection

@section('warehouse_sale')
    {{-- Let's put it under warehouse_sale section as that is already a main section yield, or we can use any section that is loaded. Let's look at dashboard.blade.php yields. We have:
    - @yield('warehouse_sale')
    - @yield('warehouse_overview')
    Let's yield under @section('warehouse_sale') so it renders in the main slot. --}}

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-light" style="font-size: 1.25em;">Warehouse Credit Details</h4>
            <div>
                <button type="button" class="btn btn-primary btn-sm border-0" data-bs-toggle="modal" data-bs-target="#creditFilterModal">
                    <i class="bi bi-calendar-range me-1"></i> Date Filter
                </button>
                <a href="{{ url()->current() }}" class="btn btn-secondary btn-sm ms-2 border-0">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </div>

        <hr style="color: grey;">

        <div class="card border-0 shadow-sm" style="border-radius: 8px;">
            <div class="card-body p-0">
                <table class="table table-striped mb-0" id="creditsTable">
                    <thead>
                        <tr style="text-align: center;">
                            <th style="background-color: #08b325d3; color: white;">Customer Name</th>
                            <th style="background-color: #08b325d3; color: white;">Sale Date</th>
                            <th style="background-color: #08b325d3; color: white;">Total Amount</th>
                            <th style="background-color: #08b325d3; color: white;">Paid Amount</th>
                            <th style="background-color: #08b325d3; color: white;">Pending Balance (Credit)</th>
                        </tr>
                    </thead>
                    <tbody style="text-align: center;" id="creditsTableBody">
                        @forelse ($credits as $credit)
                            <tr>
                                <td>{{ $credit->customer->name ?? 'Unknown' }}</td>
                                <td>{{ \Carbon\Carbon::parse($credit->sale_date)->format('Y-m-d') }}</td>
                                <td>&#8377;{{ number_format($credit->total_amount, 2) }}</td>
                                <td>&#8377;{{ number_format($credit->paid_amount, 2) }}</td>
                                <td class="text-danger fw-bold">&#8377;{{ number_format($credit->due_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted py-4">No active credit records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3" id="creditsPagination">
            {{ $credits->links() }}
        </div>
    </div>

    <!-- DATE FILTER MODAL -->
    <div class="modal fade" id="creditFilterModal" tabindex="-1" aria-labelledby="creditFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #f1f5f1ff;">
                    <h5 class="modal-title" id="creditFilterModalLabel" style="font-weight: 400; font-size: 1.1em;">
                        <i class="bi bi-calendar-range"></i> Search Credits Between Dates
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="wh_creditFilterForm">
                        <div class="mb-3">
                            <label for="wh_credit_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="wh_credit_start_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="wh_credit_end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="wh_credit_end_date" required>
                        </div>
                        <div class="text-danger id="wh_credit_error" class="mb-2"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="wh_run_credit_search">Run Search</button>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/warehouse/warehouse_credits.js'])
@endsection
