@extends('dashboard::dashboard')

@section('stock_transit')
<div class="container py-4" id="stockTransferForm">
    <h6 class="mb-4" style="font-weight:400"> Stock Transfer - Internal <i class="bi bi-truck"></i> </h6>
    <hr style="color:grey;" class="mb-4">

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-4" id="transferTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="new-transfer-tab" data-bs-toggle="tab" data-bs-target="#new-transfer" type="button" role="tab" aria-controls="new-transfer" aria-selected="true">
                <i class="bi bi-plus-circle me-1"></i> New Transfer
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="transfer-history-tab" data-bs-toggle="tab" data-bs-target="#transfer-history" type="button" role="tab" aria-controls="transfer-history" aria-selected="false">
                <i class="bi bi-clock-history me-1"></i> Transfer History
            </button>
        </li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content" id="transferTabContent">
        <!-- Tab 1: New Transfer Form -->
        <div class="tab-pane fade show active" id="new-transfer" role="tabpanel" aria-labelledby="new-transfer-tab">
            <!-- Transfer Details -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="t_transferDate" class="form-label">Transfer Date</label>
                    <input type="date" id="t_transferDate" class="form-control">
                    <span id="t_transferDate_error" class="text-danger small"></span>
                </div>

                <div class="col-md-6">
                    <label for="t_transferType" class="form-label">Transfer Type</label>
                    <select class="form-select" id="t_transferType">
                        <option value="" disabled selected>-- Select --</option>
                        <option value="inter">Inter-location</option>
                        <option value="fleet">To Fleet Vehicle</option>
                    </select>
                    <span id="t_transferType_error" class="text-danger small"></span>
                </div>
            </div>

            <!-- Locations -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="t_fromLocation" class="form-label">From Location</label>
                    <select class="form-select" id="t_fromLocation">
                        <option value="" disabled selected>-- Select --</option>
                        <option>Warehouse</option>
                        <option>Shop 1</option>
                        <option>Shop 2</option>
                    </select>
                    <span id="t_fromLocation_error" class="text-danger small"></span>
                </div>
                <div class="col-md-6">
                    <label for="t_toLocation" class="form-label">To Location</label>
                    <select class="form-select" id="t_toLocation">
                        <option value="" disabled selected>-- Select --</option>
                        <option>Warehouse</option>
                        <option>Shop 1</option>
                        <option>Shop 2</option>
                        <option>Fleet Vehicle</option>
                    </select>
                    <span id="t_toLocation_error" class="text-danger small"></span>
                </div>
            </div>

            <!-- Product Transfer Table -->
            <div id="productRowsContainer" class="mb-3">
                <div class="bg-light row g-2 align-items-end product-row border rounded p-2 mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Product</label>
                        <select class="form-select" id="t_product_name">
                            <option value="" disabled selected>-- Select --</option>
                            @if(isset($productList))
                                @foreach ($productList as $product)
                                    <option value="{{ $product['id'] }}">{{ $product['name'] }}</option>
                                @endforeach
                            @endif
                        </select>
                        <span id="t_product_name_error" class="text-danger small"></span>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Batch</label>
                        <input type="text" class="form-control" placeholder="Batch ID" id="t_batch_code" data-bs-toggle="modal" data-bs-target="#staticBackdropBatchCode">
                        <span id="t_batch_code_error" class="text-danger small"></span>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Grade</label>
                        <select class="form-select" id="t_grade">
                            <option value="" disabled selected>-- Select --</option>
                            @forelse ($grades ?? [] as $grade)
                                <option value="{{ $grade->code }}">{{ $grade->name }}</option>
                            @empty
                                <option value="A">Grade A</option>
                                <option value="B">Grade B</option>
                                <option value="C">Grade C</option>
                            @endforelse
                        </select>
                        <span id="t_grade_error" class="text-danger small"></span>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Qty</label>
                        <input type="number" class="form-control" placeholder="Qty" id="t_quantity">
                        <span id="t_quantity_error" class="text-danger small"></span>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Unit</label>
                        <select class="form-select" id="t_unit">
                            <option value="" disabled selected>-- Select --</option>
                            @if(isset($units))
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->abbreviation }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                                @endforeach
                            @endif
                        </select>
                        <span id="t_unit_error" class="text-danger small"></span>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control w-100" rows="4" placeholder="Remarks..." id="t_textarea"></textarea>
                        <span id="t_textarea_error" class="text-danger small"></span>
                    </div>
                </div>
            </div>

            <!-- Remarks and Submit -->
            <div class="d-flex justify-content-between">
                <div>
                    <button class="btn btn-secondary">Reset</button>
                    <button class="btn btn-success" id="submit_stock_transfer">✅ Submit Transfer</button>
                </div>
            </div>
        </div>

        <!-- Tab 2: Transfer History Logs -->
        <div class="tab-pane fade" id="transfer-history" role="tabpanel" aria-labelledby="transfer-history-tab">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr class="table-dark" style="text-align:center;">
                            <th>Reference No</th>
                            <th>Transfer Date</th>
                            <th>Type</th>
                            <th>From Location</th>
                            <th>To Location</th>
                            <th>Items Details</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $transfer)
                            <tr>
                                <td><strong>{{ $transfer->reference_no }}</strong></td>
                                <td style="text-align:center;">{{ $transfer->transfer_date ? $transfer->transfer_date->format('Y-m-d') : '—' }}</td>
                                <td style="text-align:center;"><span class="badge bg-info text-dark">{{ ucfirst($transfer->transfer_type) }}</span></td>
                                <td style="text-align:center;">{{ $transfer->fromLocation->name ?? '—' }}</td>
                                <td style="text-align:center;">{{ $transfer->toLocation->name ?? '—' }}</td>
                                <td>
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach($transfer->items as $item)
                                            <li>
                                                <i class="bi bi-caret-right-fill text-success"></i>
                                                {{ $item->product->name ?? '—' }} (Batch: <code>{{ $item->batch_code }}</code>, Grade: {{ $item->grade ?? '—' }}) - <strong>{{ (float)$item->quantity }} {{ $item->unit }}</strong>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td><small class="text-muted">{{ $transfer->remarks ?? '—' }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle fs-4 d-block mb-2"></i> No transfers recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $transfers->links() }}
            </div>
        </div>
    </div>
</div>

@include('stock_management::Components.Modals.stock_transfer_batch_code')

@vite(['resources/js/stock-management/stock_transfer.js'])

@endsection