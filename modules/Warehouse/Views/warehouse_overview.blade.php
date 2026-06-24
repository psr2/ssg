@extends('dashboard::dashboard')

@section('warehouse_overview')
<div class="container mt-4" id="warehouseOverviewContainer" data-inventory-url="{{ route('warehouse.overview.inventory') }}">

    <!-- Header section matching other pages -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="" style="font-size: 1em;font-weight:400">Warehouse Overview <i class="bi bi-speedometer2"></i></h4>
    </div>
    <hr style="color: grey;">

    <!-- Summary Metrics Cards -->
    <div class="row g-3 mb-4 ">
        <!-- Total Receivables / Dues -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0" style="border-radius: 8px;">
                <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1" style="font-size: 0.85em;">Total Receivables</span>
                        <h4 class="fw-bold mb-0 p-2">₹{{ number_format($totalReceivables ?? 0, 2) }}</h4>
                    </div>
                    <div class="text-primary"><i class="bi bi-wallet2 fs-3"></i></div>
                </div>
            </div>
        </div>

        <!-- Today's Sales -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0" style="border-radius: 8px;">
                <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1" style="font-size: 0.85em;">Today's Sales</span>
                        <h4 class="fw-bold mb-0 pt-3">₹{{ number_format($todaySales ?? 0, 2) }}</h4>
                    </div>
                    <div class="text-success"><i class="bi bi-cart-check fs-3"></i></div>
                </div>
            </div>
        </div>

        <!-- Low Stock Items Count -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0" style="border-radius: 8px;">
                <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1" style="font-size: 0.85em;">Low Stock Batches</span>
                        <h4 class="fw-bold mb-0 p-2">{{ $lowStockCount ?? 0 }} <span class="text-muted fs-6 font-normal">Batches</span></h4>
                    </div>
                    <div class="text-danger"><i class="bi bi-exclamation-triangle fs-3"></i></div>
                </div>
            </div>
        </div>
    </div>

     <!-- <hr style="color: grey;"> -->

    <!-- Live Stock Explorer Panel -->
    <div class="row mt-2 pt-3">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 8px;">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f1f5f1ff; border-bottom: 1px solid #dee2e6;">
                    <span class="fw-medium text-dark">Live Warehouse Inventory Explorer</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-filters">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset Filters
                    </button>
                </div>
                <div class="card-body">
                    <!-- Filters Grid -->
                    <div class="row g-3 mb-4">
                        <!-- Select Warehouse -->
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-medium small mb-1">Select Warehouse</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-building"></i></span>
                                <select class="form-select" id="explorer-warehouse-select" style="font-size: 0.88rem;">
                                    <option value="" selected disabled>Choose a warehouse...</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Product / Batch Search -->
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-medium small mb-1">Search Product / Batch</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="explorer-search-input" 
                                       placeholder="Type product name, SKU, or batch..." style="font-size: 0.88rem;" disabled>
                            </div>
                        </div>

                        <!-- Product Grade Filter -->
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-medium small mb-1">Filter by Grade</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-award"></i></span>
                                <select class="form-select" id="explorer-grade-select" style="font-size: 0.88rem;" disabled>
                                    <option value="" selected>All Grades</option>
                                    @foreach($grades as $grade)
                                        <option value="{{ $grade->code }}">{{ $grade->name }} ({{ $grade->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Table Container with Loading Spinner -->
                    <div class="position-relative border rounded" style="border-radius: 6px; overflow: hidden;">
                        
                        <!-- Loading Spinner Overlay -->
                        <div id="explorer-loader" class="d-none position-absolute w-100 h-100 bg-white bg-opacity-75 d-flex flex-column align-items-center justify-content-center" style="top: 0; left: 0; z-index: 10;">
                            <div class="spinner-border text-primary mb-2" role="status" style="width: 2rem; height: 2rem; border-width: 0.2em;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="text-secondary small fw-medium">Loading inventory data...</span>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive" style="max-height: 480px;">
                            <table class="table table-striped table-hover align-middle mb-0" id="explorer-inventory-table">
                                <thead class="table-light sticky-top shadow-sm" style="z-index: 5;">
                                    <tr style="text-align: center;">
                                        <th style="background-color: #08b325d3; color: white; width: 80px;">S.No</th>
                                        <th style="background-color: #08b325d3; color: white; cursor: pointer;" class="sortable-header" data-sort="product_name">
                                            Product Name <i class="bi bi-arrow-down-up ms-1" style="font-size: 0.8em;" id="sort-icon-product_name"></i>
                                        </th>
                                        <th style="background-color: #08b325d3; color: white;">Batch Code</th>
                                        <th style="background-color: #08b325d3; color: white; width: 120px; cursor: pointer;" class="sortable-header" data-sort="grade">
                                            Grade <i class="bi bi-arrow-down-up ms-1" style="font-size: 0.8em;" id="sort-icon-grade"></i>
                                        </th>
                                        <th style="background-color: #08b325d3; color: white; width: 150px; cursor: pointer;" class="sortable-header" data-sort="qty">
                                            Available Qty <i class="bi bi-arrow-down-up ms-1" style="font-size: 0.8em;" id="sort-icon-qty"></i>
                                        </th>
                                        <th style="background-color: #08b325d3; color: white;">Unit Cost</th>
                                        <th style="background-color: #08b325d3; color: white; width: 180px; cursor: pointer;" class="sortable-header" data-sort="total_value">
                                            Stock Value <i class="bi bi-arrow-down-up ms-1" style="font-size: 0.8em;" id="sort-icon-total_value"></i>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody style="text-align: center;">
                                    <!-- Empty state display by default -->
                                    <tr id="explorer-empty-row">
                                        <td colspan="7" class="py-5 text-center text-muted">
                                            <div class="py-4">
                                                <i class="bi bi-building fs-1 text-muted opacity-50 mb-3 d-block"></i>
                                                <h6 class="fw-semibold text-secondary mb-1">No Warehouse Selected</h6>
                                                <p class="small text-muted mb-0">Please select a warehouse from the dropdown to list its products and grades.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer Summary Info -->
                <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 text-muted" style="font-size: 0.85rem;">
                    <div>
                        Showing <span id="explorer-showing-count" class="fw-semibold">0</span> items
                    </div>
                    <div id="explorer-sum-container" class="d-none">
                        Total Stock Value: <strong class="text-dark fs-6" id="explorer-total-value">₹0.00</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@vite(['resources/js/warehouse/warehouse_overview.js'])

@endsection
