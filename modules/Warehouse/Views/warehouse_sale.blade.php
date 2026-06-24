@extends('dashboard::dashboard')

@section('warehouse_sale')

<button type="button" class="btn active mt-3 ms-2 btn-launch border-0" data-bs-toggle="modal" data-bs-target="#warehouseSaleModal">
    New Sale <i class="bi-cart-plus"></i>
</button>

<div class="container mt-2">

    <hr style="color:grey;">

    <!-- Sales Table -->
    <div style="display: flex; justify-content: space-between; width: 100%;">
        <div class="title" style="display: inline-flex;">
            <div>
                <h5 class="mb-3 mt-1" style="font-weight:300;font-size:1em;">Warehouse Sales Records</h5>
            </div>
            <div class="ms-2" style="padding-top:2px;">
                <form method="GET" id="perPageForm" style="margin-bottom: 5px;margin-top:-0.38em;">
                    <select style="background-color:none;" class="form-select border-0" name="per_page"
                        id="perPageSelect" onchange="document.getElementById('perPageForm').submit()">
                        @foreach ([10, 25, 50] as $size)
                            <option value="{{ $size }}"
                                {{ request('per_page', 10) == $size ? 'selected' : '' }}>
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

    <table class="table table-striped">
        <thead>
            <tr style="text-align: center;">
                <th style="background-color: #08b325d3; color: white;">Bill No</th>
                <th style="background-color: #08b325d3; color: white;">Customer</th>
                <th style="background-color: #08b325d3; color: white;">Sale Amount</th>
                <th style="background-color: #08b325d3; color: white;">Paid Amount</th>
                <th style="background-color: #08b325d3; color: white;">
                    @php
                        $currentSortBy    = request('sort_by');
                        $currentSortOrder = request('sort_order') ?? 'asc';
                        $newSortOrder     = 'asc';
                        if ($currentSortBy === 'warehouse_sales.due_amount') {
                            $newSortOrder = $currentSortOrder === 'asc' ? 'desc' : 'asc';
                        }
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'warehouse_sales.due_amount', 'sort_order' => $newSortOrder, 'only_due' => true]) }}"
                        style="color:white; text-decoration:none;">
                        Balance
                        @if ($currentSortBy === 'warehouse_sales.due_amount')
                            <i class="fa-solid fa-sort-{{ $currentSortOrder === 'asc' ? 'up' : 'down' }}"
                                style="font-size:0.85em;"></i>
                        @else
                            <i class="fa-solid fa-sort" style="font-size:0.85em;"></i>
                        @endif
                    </a>
                </th>
                <th style="background-color: #08b325d3; color: white;">Action</th>
            </tr>
        </thead>
        <tbody style="text-align: center;">
            @foreach ($data as $item)
                <tr>
                    <td>{{ $item['bill_no'] ?? '-' }}</td>
                    <td>{{ $item['customer_name'] }}</td>
                    <td>&#8377;{{ number_format($item['total_amount'], 2) }}</td>
                    <td>&#8377;{{ number_format($item['paid_amount'], 2) }}</td>
                    <td>&#8377;{{ number_format($item['due_amount'], 2) }}</td>
                    <td>
                        <div style="display: flex; gap: 5px; align-items: center; justify-content: center;">
                            <i class="bi bi-pencil-square text-success wh-edit-sale"
                                data-bs-toggle="modal" data-bs-target="#warehouseUpdatePaymentsModal"
                                style="cursor:pointer; {{ $item['due_amount'] > 0 ? '' : 'visibility:hidden;' }}"
                                data-sale_id="{{ $item['sale_id'] }}"
                                data-customer_id="{{ $item['customer_id'] }}"
                                data-timestamp="{{ $item['last_updated'] }}">
                            </i>

                            <i class="ms-3 bi bi-trash wh-delete-sale text-danger"
                                style="cursor:pointer;" data-sale_id="{{ $item['sale_id'] }}">
                            </i>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    {{ $data->appends(request()->query())->links() }}

</div>


<!-- ===================== NEW SALE MODAL ===================== -->
<div class="modal fade" id="warehouseSaleModal" tabindex="-1" aria-labelledby="warehouseSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form class="needs-validation" novalidate>
                <div class="modal-header" style="background-color: #f1f5f1ff;">
                    <h5 class="modal-title" id="warehouseSaleModalLabel">New Warehouse Sale <i class="bi-cart-plus"></i></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Header Info -->
                    <div class="row g-3 mb-4">

                        <div class="col-md-4">
                            <label class="form-label">Select Warehouse</label>
                            <select class="form-select" id="wh_shop_id" aria-label="Select Warehouse">
                                <option selected disabled>Choose warehouse</option>
                                @foreach ($location as $loc)
                                    <option value="{{ $loc['id'] }}">{{ $loc['name'] }}</option>
                                @endforeach
                            </select>
                            <span class="error-shop_id text-danger text-small"></span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="wh_customer_name" placeholder="Customer Name" required>
                            <span class="error-customer_name text-danger text-small"></span>

                            <ul id="wh_drop_down" class="dropdown-menu"></ul>
                            <input type="hidden" name="customer_id" id="wh_customer_id" />

                            <div id="wh_no_match" style="display: none;"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Bill #</label>
                            <input type="text" class="form-control" id="wh_bill_no" placeholder="Bill #" required>
                            <span class="error-bill_no text-danger text-small"></span>
                        </div>

                    </div>

                    <div id="wh_error_common" class="text-danger text-center mb-2"></div>

                    <!-- New Customer Fields (hidden by default) -->
                    <div id="wh_newCustomerFields" class="mt-3" style="display: none;">
                        <hr class="pt-1 pb-1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">New Customer Name</label>
                                <input type="text" class="form-control" id="wh_new_customer_name" placeholder="Enter name">
                                <span class="text-danger error-new_customer_name text-small"></span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Business Name &amp; Address</label>
                                <input type="text" class="form-control" id="wh_business_name" placeholder="Business name and address">
                                <span class="text-danger error-business_name text-small"></span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Customer Contact</label>
                                <input type="text" class="form-control" id="wh_customer_contact" placeholder="Contact number">
                                <span class="text-danger error-customer_contact text-small"></span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" id="wh_location_name" placeholder="Location">
                                <span class="text-danger error-location_name text-small"></span>
                            </div>
                        </div>
                        <hr class="pt-1">
                    </div>

                    <!-- Items Table -->
                    <h6>Items</h6>
                    <table class="table table-bordered" id="wh_itemsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Batch Code</th>
                                <th>Grade</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Rows added dynamically via JS -->
                        </tbody>
                    </table>

                    <div id="wh_error_common_items" class="text-danger text-center"></div>
                    <span class="mb-2 error-items-general text-danger text-small text-center"></span>

                    <button type="button" class="btn btn-sm btn-warning" id="wh_addItemBtn">
                        <i class="bi-plus-circle"></i> Add Item
                    </button>

                    <hr>

                    <!-- Payment Section -->
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Payment Status</label>
                            <select class="form-select" id="wh_payment_status" required>
                                <option selected disabled>Select Status</option>
                                <option value="paid">Paid</option>
                                <option value="partial">Partial</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                            <span class="error-payment_status text-danger text-small"></span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Amount Paid</label>
                            <input type="number" class="form-control" id="wh_amount_paid" placeholder="Enter amount paid">
                            <span class="error-amount_paid text-danger text-small"></span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="wh_payment_date" value="{{ date('Y-m-d') }}" required>
                            <span class="error-payment_date text-small text-danger"></span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Payment Mode</label>
                            <select class="form-select" id="wh_payment_mode" required>
                                <option selected disabled>Select Mode</option>
                                <option value="upi">UPI</option>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="other">Other</option>
                            </select>
                            <span class="error-payment_mode text-danger text-small"></span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Notes</label>
                            <input type="text" class="form-control" id="wh_notes" placeholder="Optional notes">
                            <span class="error-notes text-small text-danger"></span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Grand Total</label>
                            <input type="number" class="form-control" id="wh_grand_total" readonly>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="wh_btn_sale">Save Sale</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ===================== UPDATE PAYMENTS MODAL ===================== -->
<div class="modal fade" id="warehouseUpdatePaymentsModal" tabindex="-1" aria-labelledby="warehouseUpdatePaymentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f1f5f1ff;">
                <h5 class="modal-title" id="warehouseUpdatePaymentsModalLabel" style="font-weight:400;font-size:1em;">
                    <i class="bi bi-pencil-square"></i> Update Warehouse Sale Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="wh_filterForm">
                    <div class="row">
                        <div class="mb-4 col-md-6">
                            <label class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="wh_update_customer_name">
                            <span class="error_update_customer_name text-danger"></span>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="form-label">Total Bill Amount</label>
                            <input disabled type="text" class="form-control" id="wh_update_total_bill">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label">Pending Amount</label>
                            <input disabled type="text" class="form-control" id="wh_update_pending_amount">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label">New Amount</label>
                            <input type="text" class="form-control" id="wh_new_amount">
                            <span class="error_new_amount text-danger"></span>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select id="wh_payment_method" class="form-select">
                                <option disabled>Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                            <span class="error-payment-method text-danger"></span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="wh_update_fetch">Apply Update</button>
            </div>
        </div>
    </div>
</div>


<div id="grades-data" data-grades="{{ json_encode($grades) }}" class="d-none"></div>
<div id="wh-customers-data" data-customers="{{ json_encode($warehouseCustomers) }}" class="d-none"></div>

@include('stock_management::Components.Modals.batch_code', [
    'locations' => $location,
    'products'  => $productList
])

@vite(['resources/js/warehouse/warehouse_sale.js', 'resources/js/warehouse/warehouse_customer_search.js'])

@endsection
