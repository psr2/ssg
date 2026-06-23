@extends('dashboard::dashboard')

@section('stock_transfer')

<div class="container py-4">
    <h2 class="mb-4">🚚 Stock Transit</h2>
    

    <!-- Transit Header -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label for="transitDate" class="form-label">Transit Date</label>
            <input type="date" class="form-control" id="transitDate">
        </div>
        <div class="col-md-4">
            <label for="transitRef" class="form-label">Reference No.</label>
            <input type="text" class="form-control" id="transitRef" placeholder="Auto/manual">
        </div>
        <div class="col-md-4">
            <label for="transitType" class="form-label">Transit Type</label>
            <select class="form-select" id="transitType">
                <option value="">-- Select --</option>
                <option value="inter">Inter-location</option>
                <option value="to_fleet">To Fleet Vehicle</option>
            </select>
        </div>
    </div>

    <!-- Locations -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="transitFrom" class="form-label">From Location</label>
            <select class="form-select" id="transitFrom">
                <option>Warehouse</option>
                <option>Shop 1</option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="transitTo" class="form-label">To Location</label>
            <select class="form-select" id="transitTo">
                <option>Warehouse</option>
                <option>Shop 1</option>
                <option>Fleet Vehicle</option>
            </select>
        </div>
    </div>

    <!-- Fleet Details (Conditional) -->
    <div id="fleetTransitDetails" class="mb-3 d-none">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Vehicle No.</label>
                <input type="text" class="form-control" placeholder="KA-00-AA-0000">
            </div>
            <div class="col-md-4">
                <label class="form-label">Driver Name</label>
                <input type="text" class="form-control" placeholder="Driver name">
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact No.</label>
                <input type="text" class="form-control" placeholder="9876543210">
            </div>
        </div>
    </div>

    <!-- Product Rows -->
    <div id="transitRows" class="mb-3">
        <div class="row g-2 align-items-end product-row border rounded p-2 mb-2">
            <div class="col-md-3">
                <label class="form-label">Product</label>
                <input type="text" class="form-control" placeholder="Product name">
            </div>
            <div class="col-md-2">
                <label class="form-label">Batch</label>
                <input type="text" class="form-control" placeholder="Batch ID">
            </div>
            <div class="col-md-2">
                <label class="form-label">Grade</label>
                <select class="form-select">
                    <option>A</option>
                    <option>B</option>
                    <option>C</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Qty</label>
                <input type="number" class="form-control" placeholder="Qty">
            </div>
            <div class="col-md-2">
                <label class="form-label">Unit</label>
                <input type="text" class="form-control" placeholder="Kg, Box">
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-danger btn-sm removeRowBtn">✖</button>
            </div>
        </div>
    </div>

    <!-- Add Product Row -->
    <div class="mb-3">
        <button id="addTransitRowBtn" class="btn btn-outline-primary btn-sm">➕ Add Row</button>
    </div>

    <!-- Notes + Submit -->
    <div class="d-flex justify-content-between">
        <textarea class="form-control w-50" rows="2" placeholder="Remarks..."></textarea>
        <div>
            <button class="btn btn-secondary">Reset</button>
            <button class="btn btn-success">📦 Mark As In Transit</button>
        </div>
    </div>
</div>

<script>
    const transitTo = document.getElementById('transitTo');
    const fleetTransitDetails = document.getElementById('fleetTransitDetails');
    const addTransitRowBtn = document.getElementById('addTransitRowBtn');
    const transitRows = document.getElementById('transitRows');

    transitTo.addEventListener('change', () => {
        if (transitTo.value === 'Fleet Vehicle') {
            fleetTransitDetails.classList.remove('d-none');
        } else {
            fleetTransitDetails.classList.add('d-none');
        }
    });

    addTransitRowBtn.addEventListener('click', () => {
        const row = transitRows.querySelector('.product-row');
        const clone = row.cloneNode(true);
        clone.querySelectorAll('input, select').forEach(el => el.value = '');
        transitRows.appendChild(clone);
    });

    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('removeRowBtn')) {
            const rows = transitRows.querySelectorAll('.product-row');
            if (rows.length > 1) e.target.closest('.product-row').remove();
        }
    });
</script>


@endsection