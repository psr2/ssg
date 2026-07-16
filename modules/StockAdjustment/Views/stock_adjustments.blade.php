@extends('dashboard::dashboard')

@section('content')
<div class="container py-4" id="stockAdjustmentPage">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h6 class="mb-1" style="font-weight:400; font-size: 1.25rem;">Stock Adjustment <i class="bi bi-sliders"></i></h6>
            <p class="text-muted small mb-0">Record adjustments, audit corrections, and manage stock reconciliation under the immutable ledger policy.</p>
        </div>
        <div>
            <span class="badge bg-dark px-3 py-2 text-uppercase">Immutable Ledger Active</span>
        </div>
    </div>
    <hr style="color:grey;" class="mb-4">

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-4" id="adjustmentTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="new-adjustment-tab" data-bs-toggle="tab" data-bs-target="#new-adjustment" type="button" role="tab" aria-controls="new-adjustment" aria-selected="true">
                <i class="bi bi-plus-circle me-1"></i> New Adjustment
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="adjustment-history-tab" data-bs-toggle="tab" data-bs-target="#adjustment-history" type="button" role="tab" aria-controls="adjustment-history" aria-selected="false">
                <i class="bi bi-clock-history me-1"></i> Adjustment History
            </button>
        </li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content" id="adjustmentTabContent">
        <!-- Tab 1: New Adjustment Form -->
        <div class="tab-pane fade show active" id="new-adjustment" role="tabpanel" aria-labelledby="new-adjustment-tab">
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
                <form id="stockAdjustmentForm">
                    @csrf
                    
                    <!-- Locations & Products Selection -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="adj_location" class="form-label">Location</label>
                            <select class="form-select" id="adj_location" required>
                                <option value="" disabled selected>-- Select Location --</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc['id'] }}">{{ $loc['name'] }}</option>
                                @endforeach
                            </select>
                            <span id="adj_location_error" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="adj_product" class="form-label">Product</label>
                            <select class="form-select" id="adj_product" required>
                                <option value="" disabled selected>-- Select Product --</option>
                                @foreach ($productList as $product)
                                    <option value="{{ $product['id'] }}">{{ $product['name'] }}</option>
                                @endforeach
                            </select>
                            <span id="adj_product_error" class="text-danger small"></span>
                        </div>
                    </div>

                    <!-- Batch Selection & Grade Info -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="adj_batch_code" class="form-label">Batch Code (Click to Search)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control bg-white cursor-pointer" placeholder="Select Batch ID..." id="adj_batch_code" readonly data-bs-toggle="modal" data-bs-target="#staticBackdropBatchCode" required style="cursor: pointer;">
                            </div>
                            <span id="adj_batch_code_error" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="adj_grade" class="form-label">Grade</label>
                            <select class="form-select" id="adj_grade" required>
                                <option value="" disabled selected>-- Select Grade --</option>
                                @forelse ($grades ?? [] as $grade)
                                    <option value="{{ $grade->code }}">{{ $grade->name }}</option>
                                @empty
                                    <option value="A">Grade A</option>
                                    <option value="B">Grade B</option>
                                    <option value="C">Grade C</option>
                                @endforelse
                            </select>
                            <span id="adj_grade_error" class="text-danger small"></span>
                        </div>
                    </div>

                    <!-- Stock Quantities Info -->
                    <div class="row g-3 mb-4 p-3 bg-light rounded border border-light-subtle">
                        <div class="col-md-4">
                            <label for="adj_available_qty" class="form-label text-muted small">Current Available Qty</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control bg-light" id="adj_available_qty" readonly value="0.00">
                                <span class="input-group-text bg-light border-start-0" id="adj_unit_label">pcs</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="adj_new_qty" class="form-label fw-semibold">New Qty (Reconciled Total)</label>
                            <input type="number" step="0.01" class="form-control border-primary" id="adj_new_qty" placeholder="Enter new quantity..." required disabled>
                            <span id="adj_new_qty_error" class="text-danger small"></span>
                        </div>

                        <div class="col-md-4">
                            <label for="adj_delta_qty" class="form-label text-muted small">Adjustment Delta</label>
                            <div class="d-flex align-items-center h-100">
                                <span id="adj_delta_badge" class="badge bg-secondary fs-6 px-3 py-2 w-100 text-center">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Real-Time Rule Checkers -->
                    <div class="mb-4 d-none" id="adj_validation_feedback">
                        <div class="alert border-0 d-flex align-items-start gap-2 py-3 px-4 shadow-sm" id="adj_validation_alert" role="alert">
                            <i class="fs-4 bi" id="adj_validation_icon"></i>
                            <div>
                                <h6 class="alert-heading fw-semibold mb-1" id="adj_validation_title">Validation</h6>
                                <p class="mb-0 small" id="adj_validation_text">Validating adjustment boundaries...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Reasons and Remarks -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="adj_reason" class="form-label">Reason for Adjustment</label>
                            <select class="form-select" id="adj_reason" required disabled>
                                <option value="" disabled selected>-- Select Reason --</option>
                                <option value="audit_difference">Audit/Stocktake Difference</option>
                                <option value="damage">Damaged Goods</option>
                                <option value="theft">Theft/Loss</option>
                                <option value="moisture_loss">Moisture/Shrinkage Loss</option>
                                <option value="expired">Expired Goods</option>
                                <option value="reconciliation">General Reconciliation</option>
                            </select>
                            <span id="adj_reason_error" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="adj_remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="adj_remarks" rows="2" placeholder="Add detailed context (e.g. audit notes, damage details)..." disabled></textarea>
                            <span id="adj_remarks_error" class="text-danger small"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <button class="btn btn-outline-secondary px-4" type="reset" id="btn_reset_adjustment">Reset Form</button>
                        <button class="btn btn-success px-4" type="submit" id="btn_submit_adjustment" disabled>
                            <i class="bi bi-check2-circle"></i> Save Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab 2: Adjustment History Logs -->
        <div class="tab-pane fade" id="adjustment-history" role="tabpanel" aria-labelledby="adjustment-history-tab">
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle border border-light-subtle">
                        <thead>
                            <tr class="table-dark text-center">
                                <th>Date</th>
                                <th>Location</th>
                                <th>Product / Batch</th>
                                <th>Original Qty</th>
                                <th>Adjusted Qty</th>
                                <th>New Qty</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Adjusted By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $adj)
                                <tr class="text-center">
                                    <td>{{ $adj->created_at ? \Carbon\Carbon::parse($adj->created_at)->format('Y-m-d H:i') : '—' }}</td>
                                    <td><span class="badge bg-light text-dark border"><i class="bi bi-geo-alt"></i> {{ $adj->location_name ?? '—' }}</span></td>
                                    <td>
                                        <div class="fw-semibold">{{ $adj->product_name ?? '—' }}</div>
                                        <div class="text-muted small">Batch: <code class="font-monospace">{{ $adj->batch_code }}</code> @if($adj->grade) (Grade: {{ $adj->grade }}) @endif</div>
                                    </td>
                                    <td>{{ number_format($adj->original_qty, 2) }}</td>
                                    <td>
                                        @if($adj->adjusted_qty < 0)
                                            <span class="text-danger fw-bold"><i class="bi bi-arrow-down-short"></i> {{ number_format($adj->adjusted_qty, 2) }}</span>
                                        @else
                                            <span class="text-success fw-bold"><i class="bi bi-arrow-up-short"></i> +{{ number_format($adj->adjusted_qty, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ number_format($adj->new_qty, 2) }}</td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle text-uppercase small">{{ str_replace('_', ' ', $adj->reason) }}</span>
                                        @if($adj->remarks)
                                            <i class="bi bi-info-circle text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" title="{{ $adj->remarks }}"></i>
                                        @endif
                                    </td>
                                    <td>
                                        @if($adj->status === 'approved')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1"><i class="bi bi-check-circle"></i> Approved</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1"><i class="bi bi-hourglass-split"></i> Pending Approval</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="fw-semibold">{{ $adj->adjusted_by_name ?? 'System' }}</small>
                                    </td>
                                    <td>
                                        @if($adj->status === 'pending_approval')
                                            <button class="btn btn-sm btn-success btn-approve-adjustment px-3" data-id="{{ $adj->id }}">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </button>
                                        @else
                                            <span class="text-muted small"><i class="bi bi-lock-fill"></i> Locked</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="bi bi-sliders fs-1 d-block mb-3 text-secondary"></i>
                                        <h6 class="fw-semibold">No stock adjustments recorded yet.</h6>
                                        <p class="small text-muted mb-0">Use the form above to record physical stock corrections or audits.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $adjustments->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@include('stock_management::Components.Modals.stock_transfer_batch_code')

@vite(['resources/js/stock-management/stock_adjustment.js'])

@endsection
