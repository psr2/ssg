@extends('dashboard::dashboard')

@section('stock_adjustments')
    <div class="container mt-2" id="stockLedgerContainer">

        <h6 class="mb-4" style="font-weight:400;">Stock Adjustment <i class="bi bi-box-seam"></i></h6>

        <hr style="color:grey;">

        {{-- Title + Per-page + Reset --}}
        <div style="display:flex; justify-content:space-between; width:100%;">
            <div class="title" style="display:inline-flex;">
                <div>
                    <h5 class="mb-3 mt-1" style="font-weight:300;font-size:1em;">Stock Ledger</h5>
                </div>
                <div class="ms-2" style="padding-top:2px;">
                    <form method="GET" id="perPageForm" style="margin-bottom:5px;margin-top:-0.38em;">
                        <select style="background-color:none;" class="form-select border-0"
                            name="per_page" id="perPageSelect"
                            onchange="document.getElementById('perPageForm').submit()">
                            @foreach ([15, 25, 50] as $size)
                                <option value="{{ $size }}"
                                    {{ request('per_page', 15) == $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                        @foreach (request()->except('per_page') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                    </form>
                </div>
            </div>
            <div class="controls">
                <a style="text-decoration:none;" href="{{ url()->current() }}" class="ms-2">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </div>

        {{-- Stock Ledger Table --}}
        <table class="table table-bordered">
            <thead>
                <tr style="text-align:center;">
                    <th style="background-color:#08b325d3; color:white;">ID</th>
                    <th style="background-color:#08b325d3; color:white;">Product</th>
                    <th style="background-color:#08b325d3; color:white;">Batch</th>
                    <th style="background-color:#08b325d3; color:white;">Grade</th>
                    <th style="background-color:#08b325d3; color:white;">Quantity</th>
                    <th style="background-color:#08b325d3; color:white;">Unit</th>
                    <th style="background-color:#08b325d3; color:white;">Location</th>
                    <th style="background-color:#08b325d3; color:white;">Remarks</th>
                    <th style="background-color:#08b325d3; color:white;">Actions</th>
                </tr>
            </thead>

            <tbody style="text-align:center;">
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->product }}</td>
                        <td>{{ $item->batch }}</td>
                        <td>{{ $item->grade }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->unit }}</td>
                        <td>{{ $item->location->name ?? '—' }}</td>
                        <td>{{ $item->remarks ?? '—' }}</td>
                        <td>
                            <div style="display: flex; gap: 5px; align-items: center; justify-content: center;">
                                @if ($item->quantity <= 0)
                                    <span class="badge bg-secondary p-2"><i class="bi bi-slash-circle"></i> Voided / Empty</span>
                                @else
                                    <button class="btn btn-sm btn-primary update-btn" data-id="{{ $item->id }}"
                                        data-product="{{ $item->product }}" data-batch="{{ $item->batch }}"
                                        data-quantity="{{ $item->quantity }}" data-unit="{{ $item->unit }}"
                                        data-remarks="{{ $item->remarks }}" data-location-id="{{ $item->location_id ?? '' }}"
                                        data-location-name="{{ $item->location->name ?? '—' }}">
                                        <i class="bi bi-pencil-square"></i> Adjust
                                    </button>

                                    <button class="btn btn-sm btn-danger void-btn" data-id="{{ $item->id }}"
                                        data-product="{{ $item->product }}" data-batch="{{ $item->batch }}"
                                        data-quantity="{{ $item->quantity }}">
                                        <i class="bi bi-x-circle"></i> Void
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-3">
            {{ $items->appends(request()->query())->links() }}
        </div>

    </div>

    {{-- Update Modal --}}
    <div class="modal fade" id="updateStockModal" tabindex="-1" aria-labelledby="updateStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg border-0">

                <div class="modal-header" style="background-color:#f1f5f1ff;">
                    <h5 class="modal-title" id="updateStockModalLabel">
                        Update Stock Item <i class="bi bi-pencil-square ms-1"></i>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="edit_id">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="edit_product" class="form-label">Product</label>
                            <input type="text" readonly class="form-control" id="edit_product">
                            <span class="text-danger small" id="error_edit_product"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_batch" class="form-label">Batch</label>
                            <input type="text" readonly class="form-control" id="edit_batch">
                            <span class="text-danger small" id="error_edit_batch"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_quantity" class="form-label">Quantity</label>
                            <input type="number" step="0.01" class="form-control" id="edit_quantity">
                            <span class="text-danger small" id="error_edit_quantity"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_unit" class="form-label">Unit</label>
                            <select class="form-select" id="edit_unit">
                                <option value="">Select unit</option>
                                <option value="pcs">pcs</option>
                                <option value="bx">bx</option>
                                <option value="kg">kg</option>
                            </select>
                            <span class="text-danger small" id="error_edit_unit"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="current_location" class="form-label">Current Location</label>
                            <input type="text" class="form-control" id="current_location" readonly>
                            <span class="text-danger small" id="error_current_location"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="new_location" class="form-label">New Location</label>
                            <select id="new_location" class="form-select">
                                <option value="" selected>Select a location</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc['id'] ?? $loc->id }}">{{ $loc['name'] ?? $loc->name }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger small" id="error_new_location"></span>
                        </div>

                        <div class="col-md-12">
                            <label for="edit_remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="edit_remarks" rows="2"></textarea>
                            <span class="text-danger small" id="error_edit_remarks"></span>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="submitAdjustment">
                        <i class="bi bi-check-circle"></i> Update
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Void Stock Confirmation Modal --}}
    <div class="modal fade" id="voidStockModal" tabindex="-1" aria-labelledby="voidStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg border-0">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="voidStockModalLabel">
                        Void Stock Receipt <i class="bi bi-exclamation-triangle ms-1"></i>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="void_id">
                    
                    <p class="text-muted">
                        Are you sure you want to mark the stock receipt for product <strong id="void_product_display"></strong> (Batch: <strong id="void_batch_display"></strong>) as invalid?
                    </p>
                    <div class="alert alert-warning small">
                        <i class="bi bi-info-circle"></i> This operation will generate a negative contra-entry in the ledger, reducing the available quantity from <strong id="void_qty_display"></strong> to <strong>0.00</strong>. This cannot be undone.
                    </div>

                    <div class="col-md-12">
                        <label for="void_remarks" class="form-label">Reason for Voiding <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="void_remarks" rows="3" placeholder="Provide a detailed explanation (min 10 characters)..."></textarea>
                        <span class="text-danger small" id="error_void_remarks"></span>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="submitVoid">
                        <i class="bi bi-check-circle"></i> Confirm Void
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@vite(['resources/js/stock-management/stock_adjustment.js'])
