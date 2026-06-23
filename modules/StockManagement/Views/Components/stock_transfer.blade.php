@extends('dashboard::dashboard')

@section('stock_transit')
<div class="container py-4" id="stockTransferForm">
    <h6 class="mb-4" style="font-weight:400"> Stock Transfer - Internal <i class="bi bi-truck"></i> </h6>

        <hr style="color:grey;" class="mb-4">

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
                    <option value="1">Onion</option>
                    <option>B</option>
                    <option>C</option>
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
                    <option>A</option>
                    <option>B</option>
                    <option>C</option>
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
                <input type="text" class="form-control" placeholder="Kg, Box..." id="t_unit">
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
@include('stock_management::Components.Modals.stock_transfer_batch_code')

@vite(['resources/js/stock-management/stock_transfer.js'])

@endsection