@extends('dashboard::dashboard')

@section('content')
<div class="container mt-2" id="stockSegregationContainer" data-grades="{{ json_encode($grades ?? []) }}" style="font-size: 0.9em;">
    <h6 class="mb-4" style="font-weight: 400;">Stock Segregation <i class="bi bi-diagram-3"></i></h6>
    <hr style="color: grey;">

    <!-- Bootstrap Nav Tabs -->
    <ul class="nav nav-tabs mb-4" id="segregationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="process-tab" data-bs-toggle="tab" data-bs-target="#process-content" type="button" role="tab" aria-controls="process-content" aria-selected="true">
                <i class="bi bi-diagram-3 me-1"></i> Perform Segregation
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-content" type="button" role="tab" aria-controls="history-content" aria-selected="false">
                <i class="bi bi-clock-history me-1"></i> Segregation History
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="segregationTabsContent">
        
        <!-- Tab 1: Perform Segregation -->
        <div class="tab-pane fade show active" id="process-content" role="tabpanel" aria-labelledby="process-tab">
            <form id="stockSegregationForm">
                @csrf
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f1f5f1ff; border-bottom: 1px solid #dee2e6;">
                        <span class="fw-medium text-dark">New Stock Segregation Entry</span>
                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#staticBackdropBatchCode">
                            <i class="bi bi-search me-1"></i> Search Parent Batch
                        </button>
                    </div>
                    <div class="card-body bg-light">
                        <!-- Parent Batch Information -->
                        <h6 class="text-secondary border-bottom pb-2 mb-3">Parent Batch Details (Source)</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label for="segregation_date" class="form-label">Segregation Date</label>
                                <input type="date" id="segregation_date" name="segregation_date" class="form-control" value="{{ date('y-m-d') }}" required>
                                <span id="error-segregation_date" class="text-danger small"></span>
                            </div>
                            <div class="col-md-3">
                                <label for="s_location_id" class="form-label">Location</label>
                                <select id="s_location_id" name="location_id" class="form-select" required>
                                    <option value="" disabled selected>Select location</option>
                                    @foreach ($location as $loc)
                                        <option value="{{ $loc['id'] }}">{{ $loc['name'] }}</option>
                                    @endforeach
                                </select>
                                <span id="error-location_id" class="text-danger small"></span>
                            </div>
                            <div class="col-md-3">
                                <label for="s_batch_code" class="form-label">Parent Batch Code</label>
                                <input type="text" id="s_batch_code" name="parent_batch_code" class="form-control bg-white" readonly placeholder="Click 'Search Parent Batch'" required>
                                <span id="error-parent_batch_code" class="text-danger small"></span>
                            </div>
                            <div class="col-md-3">
                                <label for="s_product_name" class="form-label">Product</label>
                                <input type="text" id="s_product_name" class="form-control bg-white" readonly placeholder="—">
                                <input type="hidden" id="s_product_id" name="product_id">
                                <span id="error-product_id" class="text-danger small"></span>
                            </div>
                            <div class="col-md-3">
                                <label for="s_original_qty" class="form-label">Original Batch Qty</label>
                                <div class="input-group">
                                    <input type="text" id="s_original_qty" class="form-control bg-white" readonly placeholder="0">
                                    <span class="input-group-text s-unit-label">—</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="s_available_qty" class="form-label">Available Unsorted Qty</label>
                                <div class="input-group">
                                    <input type="text" id="s_available_qty" class="form-control bg-white text-success fw-bold" readonly placeholder="0">
                                    <span class="input-group-text s-unit-label">—</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="s_unit_cost" class="form-label">Unit Cost</label>
                                <input type="text" id="s_unit_cost" class="form-control bg-white" readonly placeholder="0.00">
                            </div>
                            <div class="col-md-3">
                                <label for="s_unit" class="form-label">Unit</label>
                                <input type="text" id="s_unit" name="unit" class="form-control bg-white" readonly placeholder="—">
                            </div>
                        </div>

                        <!-- Graded Outputs Section -->
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="text-secondary mb-0">Graded Outputs (Children)</h6>
                            <button type="button" class="btn btn-sm btn-success" id="add-grade-row">
                                <i class="bi bi-plus-circle me-1"></i> Add Output Row
                            </button>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-bordered align-middle" id="graded-outputs-table">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Target Grade</th>
                                        <th style="width: 20%;">Quantity</th>
                                        <th style="width: 15%;">Unit</th>
                                        <th style="width: 15%;">Unit Cost</th>
                                        <th style="width: 20%;">Remarks</th>
                                        <th style="width: 5%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="empty-row-placeholder">
                                        <td colspan="6" class="text-center text-muted py-3">No output rows added. Click "Add Output Row" to begin segregation.</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="table-secondary fw-bold">
                                        <td>Total Output Quantity</td>
                                        <td id="total-output-qty">0.00</td>
                                        <td class="s-unit-label">—</td>
                                        <td colspan="3" class="text-end text-muted font-monospace" style="font-size: 0.9em;">
                                            Remaining: <span id="remaining-unsorted-qty">0.00</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label for="remarks" class="form-label">General Remarks</label>
                                <textarea id="remarks" name="remarks" class="form-control" rows="2" placeholder="Describe the segregation outcome, sorting conditions, etc."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-end">
                        <button type="reset" class="btn btn-secondary me-2" id="reset-segregation-btn">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary" id="submit-segregation-btn" disabled>
                            <i class="bi bi-check-circle me-1"></i> Save Segregation
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tab 2: Segregation History -->
        <div class="tab-pane fade" id="history-content" role="tabpanel" aria-labelledby="history-tab">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white">
                    <span class="fw-medium text-white">Recent Segregations History</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Parent Batch</th>
                                    <th>Original Weight</th>
                                    <th>Grade Outputs Breakdown</th>
                                    <th>Remarks</th>
                                    <th>Operator</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSegregations as $seg)
                                    <tr>
                                        <td class="text-center font-monospace">{{ $seg->reference_no }}</td>
                                        <td class="text-center">{{ $seg->segregation_date->format('Y-m-d') }}</td>
                                        <td>
                                            <strong>{{ $seg->parent_batch_code }}</strong><br>
                                            <span class="text-muted small">{{ $seg->product->name ?? '—' }} ({{ $seg->location->name ?? '—' }})</span>
                                        </td>
                                        <td class="text-end font-monospace">{{ number_format($seg->parent_quantity, 2) }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach ($seg->items as $item)
                                                    <span class="badge bg-secondary text-white font-monospace">
                                                        Grade {{ $item->grade }}: {{ number_format($item->quantity, 2) }} {{ $item->unit }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>{{ $seg->remarks ?? '—' }}</td>
                                        <td class="text-center text-muted">System</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No recent segregation logs found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@include('stock_management::Components.Modals.batch_code')

@vite(['resources/js/stock-management/stock_segregation.js'])
@endsection
