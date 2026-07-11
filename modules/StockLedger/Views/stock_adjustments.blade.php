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
                        <td>{{ $item->productRelation->name ?? $item->product }}</td>
                        <td>{{ $item->batch }}</td>
                        <td>{{ $item->gradeRelation->name ?? $item->grade }}</td>
                        <td>
                            {{ $item->quantity }}
                            @if(isset($item->original_quantity) && $item->original_quantity != $item->quantity && $item->location_id == $item->original_location_id)
                                <br><small class="text-muted" title="Original Purchase Quantity">Original: {{ $item->original_quantity }}</small>
                            @endif
                        </td>
                        <td>{{ $item->unit }}</td>
                        <td>{{ $item->location->name ?? '—' }}</td>
                        <td>{{ $item->remarks ?? '—' }}</td>
                        <td>
                            <div style="display: flex; gap: 5px; align-items: center; justify-content: center;">
                                @if ($item->quantity <= 0)
                                    <span class="badge bg-secondary p-2"><i class="bi bi-slash-circle"></i> Voided / Empty</span>
                                @else
                                    <button class="btn btn-sm btn-primary update-btn" data-id="{{ $item->id }}"
                                        data-product="{{ $item->productRelation->name ?? $item->product }}" data-batch="{{ $item->batch }}"
                                        data-quantity="{{ $item->quantity }}" data-unit="{{ $item->unit }}"
                                        data-remarks="{{ $item->remarks }}" data-location-id="{{ $item->location_id ?? '' }}"
                                        data-location-name="{{ $item->location->name ?? '—' }}">
                                        <i class="bi bi-pencil-square"></i> Adjust
                                    </button>

                                    <button class="btn btn-sm btn-danger void-btn" data-id="{{ $item->id }}"
                                        data-product="{{ $item->productRelation->name ?? $item->product }}" data-batch="{{ $item->batch }}"
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
                            <span class="text-danger small" id="error_id"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_batch" class="form-label">Batch</label>
                            <input type="text" readonly class="form-control" id="edit_batch">
                            <span class="text-danger small" id="error_batch"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_quantity" class="form-label">Quantity</label>
                            <input type="number" step="0.01" class="form-control" id="edit_quantity">
                            <span class="text-danger small" id="error_quantity"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_unit" class="form-label">Unit</label>
                            <select class="form-select" id="edit_unit">
                                <option value="">Select unit</option>
                                <option value="pcs">pcs</option>
                                <option value="bx">bx</option>
                                <option value="kg">kg</option>
                            </select>
                            <span class="text-danger small" id="error_unit"></span>
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
                            <span class="text-danger small" id="error_new_location_id"></span>
                        </div>

                        <div class="col-md-12">
                            <label for="edit_remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="edit_remarks" rows="2"></textarea>
                            <span class="text-danger small" id="error_remarks"></span>
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

<script>
document.addEventListener("DOMContentLoaded", function() {

    const modal = new bootstrap.Modal(document.getElementById('updateStockModal'));
    const submitBtn = document.getElementById('submitAdjustment');

    let currentButton = null;
    let original = {};

    // Open modal + populate fields
    document.querySelectorAll('.update-btn').forEach(btn => {
        btn.addEventListener('click', function() {

            currentButton = this;

            original = {
                quantity: parseFloat(this.dataset.quantity),
                unit: this.dataset.unit || "",
                locationId: this.dataset.locationId || ""
            };

            // Fill modal fields
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_product').value = this.dataset.product;
            document.getElementById('edit_batch').value = this.dataset.batch;
            document.getElementById('edit_quantity').value = original.quantity;
            document.getElementById('edit_unit').value = original.unit;
            document.getElementById('edit_remarks').value = this.dataset.remarks || '';
            document.getElementById('current_location').value = this.dataset.locationName || '';
            document.getElementById('new_location').value = "";

            // Clear all previous field errors
            clearFieldErrors();

            // Disable submit by default
            submitBtn.disabled = true;

            modal.show();
        });
    });

    // Enable submit if something changed
    function checkSubmitEnabled() {
        const newQuantity = parseFloat(document.getElementById('edit_quantity').value);
        const newUnit = document.getElementById('edit_unit').value;
        const newLocation = document.getElementById('new_location').value;

        const quantityChanged = newQuantity !== original.quantity;
        const unitChanged = newUnit !== "" && newUnit !== original.unit;
        const locationChanged = newLocation !== "" && newLocation !== original.locationId;

        submitBtn.disabled = !(quantityChanged || unitChanged || locationChanged);
    }

    document.getElementById('edit_quantity').addEventListener('input', checkSubmitEnabled);
    document.getElementById('edit_unit').addEventListener('change', checkSubmitEnabled);
    document.getElementById('new_location').addEventListener('change', checkSubmitEnabled);

    // Handle modal submit
    submitBtn.addEventListener('click', function() {

        if (!currentButton) return;

        const payload = {};

        const newQuantity = parseFloat(document.getElementById('edit_quantity').value);
        const newUnit = document.getElementById('edit_unit').value;
        const newLocationId = document.getElementById('new_location').value;
        const remarks = document.getElementById('edit_remarks').value;
        const batch = document.getElementById('edit_batch').value;
        const id = currentButton.dataset.id;

        // Original values
        const originalQuantity = original.quantity;
        const originalUnit = original.unit;
        const originalLocationId = original.locationId;

        // Build payload only with meaningful updates
        payload.id = id;
        payload.batch = batch;
        payload.remarks = remarks;
        payload.location_id = originalLocationId;

        if (newQuantity !== originalQuantity) payload.quantity = newQuantity;
        if (newUnit && newUnit !== originalUnit) payload.unit = newUnit;
        if (newLocationId && newLocationId !== originalLocationId) payload.new_location_id = newLocationId;

        // Clear previous errors
        clearFieldErrors();

        // AJAX POST
        fetch(`/stock-adjustments`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
        .then(async response => {

            if (response.status === 422) {
                const data = await response.json();
                showFieldErrors(data.errors);
                return;
            }

            if (!response.ok) {
                alert("Something went wrong.");
                return;
            }

            // Success: reload page
            location.reload();
        })
        .catch(err => {
            console.error("AJAX Error:", err);
        });
    });

    // Void Modal Handlers
    const voidModal = new bootstrap.Modal(document.getElementById('voidStockModal'));
    const submitVoidBtn = document.getElementById('submitVoid');

    document.querySelectorAll('.void-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('void_id').value = this.dataset.id;
            document.getElementById('void_product_display').textContent = this.dataset.product;
            document.getElementById('void_batch_display').textContent = this.dataset.batch;
            document.getElementById('void_qty_display').textContent = this.dataset.quantity;
            document.getElementById('void_remarks').value = '';
            document.getElementById('error_void_remarks').textContent = '';

            voidModal.show();
        });
    });

    submitVoidBtn.addEventListener('click', function() {
        const id = document.getElementById('void_id').value;
        const remarks = document.getElementById('void_remarks').value;
        document.getElementById('error_void_remarks').textContent = '';

        if (!remarks || remarks.trim().length < 10) {
            document.getElementById('error_void_remarks').textContent = 'Please provide a detailed remark of at least 10 characters.';
            return;
        }

        fetch(`/stock-adjustments/${id}/void`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ remarks: remarks })
        })
        .then(async response => {
            if (response.status === 422) {
                const data = await response.json();
                if (data.errors && data.errors.remarks) {
                    document.getElementById('error_void_remarks').textContent = data.errors.remarks.join(", ");
                } else if (data.message) {
                    document.getElementById('error_void_remarks').textContent = data.message;
                }
                return;
            }

            if (!response.ok) {
                alert("Void operation failed. Make sure there are no downstream movements for this batch.");
                return;
            }

            location.reload();
        })
        .catch(err => {
            console.error("AJAX Error:", err);
        });
    });

    // Clear all field error spans
    function clearFieldErrors() {
        const errorSpans = document.querySelectorAll('[id^="error_"]');
        errorSpans.forEach(span => span.textContent = "");
    }

    // Show backend validation errors under each input
    function showFieldErrors(errors) {
        let unmappedErrors = [];
        Object.keys(errors).forEach(field => {
            const spanId = "error_" + field.replace(/\./g, "_"); // replace dots in nested keys
            const span = document.getElementById(spanId);
            if (span) {
                span.textContent = errors[field].join(", ");
            } else {
                unmappedErrors.push(errors[field].join(", "));
            }
        });
        if (unmappedErrors.length > 0) {
            alert(unmappedErrors.join("\n"));
        }
    }

});
</script>
