@extends('dashboard::dashboard')

@section('fleet_sale')
    {{-- 
<button type="button" class="btn active mt-3 ms-3" data-bs-toggle="modal" data-bs-target="#saleModal">
  New Sale <i class="bi-cart-plus"></i>

</button> --}}

    <button type="button" class="btn active mt-3 ms-2 btn-launch border-0" data-bs-toggle="modal" data-bs-target="#saleModal">
        New Sale <i class="bi-cart-plus"></i>



    </button>

    
    {{-- <hr class="ms-3" style="color: rgba(128, 128, 128, 0.788);"> --}}
    

    <div class="d-flex justify-content-between align-items-center w-100 ms-3 mt-3">
        

        <!-- Left side: Title + Dropdown -->
        <div class="d-flex align-items-center ms-1">

            <h5 class="mb-0 me-3 fw-light" style="font-size: 1em;">Sales Records</h5>

            <!-- Per-page form -->
            <form method="GET" id="perPageForm" class="mb-0">
                <select class="form-select form-select-sm border-0 shadow-none p-0" name="per_page" id="perPageSelect"
                    onchange="document.getElementById('perPageForm').submit()">
                    <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="15" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="25" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>

                </select>
            </form>


        </div>

        <!-- Right side: Controls -->
        <div class="d-flex align-items-center me-4">

            <!-- Download (optional) -->
            <button data-bs-toggle="modal" data-bs-target="#reportModal" href="#" class=" btn-sm"
                style="border:0;background-color:rgba(255, 255, 255, 0.678);">
                <i class="bi bi-download" style="border:0;"></i> Download
            </button>

           
        </div>

    </div>



    <div class="container mt-3">

        <hr class="mb-4" style="color: rgb(153, 152, 151);">





        <table class="table  table-striped">
            <thead>
                <tr style="text-align: center;">
                    <th style="background-color: #08b325d3; color: white;">Bill No</th>
                    <th style="background-color: #08b325d3; color: white;">Customer</th>
                    <th style="background-color: #08b325d3; color: white;">Sale</th>
                    <th style="background-color: #08b325d3; color: white;">Status</th>
                    <th style="background-color: #08b325d3; color: white;">Paid</th>
                    <th style="background-color: #08b325d3; color: white;">Balance</th>
                    <th style="background-color: #08b325d3; color: white;">Action</th>
                </tr>
            </thead>
            <tbody style="text-align: center">
                @foreach ($saleRecords as $record)
                    <tr>
                        <td>{{ $record['bill_number'] }}</td>
                        <td>{{ $record['customer_name'] }} </td>
                        <td>₹{{ $record['total_amount'] }}</td>
                        <td>
                            <span class="badge {{ $record['balance'] == 0 ? 'bg-success' : 'bg-warning' }}">
                                {{ $record['balance'] == 0 ? 'Paid' : 'Partial' }}
                            </span>
                        </td>
                        <td>₹{{ $record['paid'] }}</td>
                        <td>₹{{ $record['balance'] }}</td>
                        <td>
                            @if ($record['balance'] != 0)
                                <button class="btn btn-sm btn-primary btn-payments" data-bs-toggle="modal"
                                    data-id="{{ $record['bill_id'] }}" data-bs-target="#updatePaymentModal">Update
                                    Payment</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination Links -->
        {{ $saleRecords->appends(request()->except('page'))->links() }}
    </div>

    <!-- SALES MODAL -->
    <div class="modal fade" id="saleModal" tabindex="-1" aria-labelledby="saleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form class="needs-validation" novalidate>
                    <div class="modal-header" style="background-color: #f1f5f1ff;">
                        <h5 class="modal-title" id="saleModalLabel">New Sale <i class="bi-cart-plus"></i></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <!-- Bill Info -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="trip_id" class="form-label">Trip</label>
                                <div class="input-group"> <!-- position-relative for absolute dropdown -->
                                    <input id="trip_id" type="text" data-bs-target="#tripSearchModal"
                                        data-bs-toggle="modal" data-bs-dismiss="modal" class="form-control"
                                        placeholder="Select Trip" autocomplete="off" required />

                                    <!-- Move the dropdown here inside the position-relative container -->

                                </div>


                                <input type="hidden" name="trip_hidden" id="trip_hidden" />
                                <span class="error-trip_id text-danger text-small"></span>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" id="customer_name" placeholder="Customer Name"
                                    required>
                                <span class="error-customer_name text-danger text-small"></span>

                                <ul id="drop-down" class="dropdown-menu">
                                </ul>

                                <input type="hidden" name="customer_id" id="customer_id" />

                                <div id="no-match-message" style="display: none;">



                                    <!-- <a href="/create-customer" id="create-customer-link">No match found, Create new customer</a> -->
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Bill #</label>
                                <input type="text" class="form-control" id="bill_no" placeholder="Bill #" required>
                                <span class="error-bill_no text-danger text-small"></span>
                            </div>
                        </div>

                        <!--customer details-->

                        <div id="newCustomerFields" class="mt-3" style="display: none;">


                            <hr class="pt-1 pb-1">
                            <!-- <h6>Customer Details</h6> -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">New Customer Name</label>
                                    <input type="text" class="form-control" id="new_customer_name"
                                        placeholder="Enter name">
                                    <span class="text-danger error-new_customer_name text-small"></span>
                                </div>

                                <!-------
                      Remove eroute name column if not needed and a
                      collect route name dynamic or append route name dynamic here with id
                      and store id instead of route name
                      ------->

                                <div class="col-md-6">
                                    <label class="form-label">Route Name</label>
                                    <input type="text" class="form-control" id="new_route_name"
                                        placeholder="Enter route name">
                                    <span class="text-danger error-new_route_name text-small"></span>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Customer Contact</label>
                                    <input type="text" class="form-control" id="new_customer_contact"
                                        placeholder="Enter contact number">
                                    <span class="text-danger error-new_customer_contact text-small"></span>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Location Name</label>
                                    <input type="text" class="form-control" id="new_location_name"
                                        placeholder="Enter location">
                                    <span class="text-danger error-new_location_name text-small"></span>
                                </div>
                            </div>
                            <hr class="pt-1">
                        </div>

                        <!-- Items Section -->
                        <h6>Items</h6>
                        <table class="table table-bordered" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Unit</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic rows will go here -->
                            </tbody>


                        </table>
                        <span class="mb-2 error-items-general text-danger text-small text-center"></span>
                        <button type="button" class="btn btn-sm btn-warning" id="addItemBtn">
                            <i class="bi-plus-circle"></i> Add Item
                        </button>

                        <hr>

                        <!-- Payment Section -->
                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label">Payment Status</label>
                                <select class="form-select" id="payment_status" required>
                                    <option selected disabled>Select Status</option>
                                    <option value="paid">Paid</option>
                                    <option value="partial">Partial</option>
                                    <option value="unpaid">Unpaid</option>
                                </select>
                                <span class="error-payment_status text-danger text-small"></span>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Amount Paid</label>
                                <input type="number" class="form-control" id="amount_paid"
                                    placeholder="Enter amount paid">
                                <span class="error-amount_paid text-danger text-small"></span>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Payment Date</label>
                                <input type="date" class="form-control" id="payment_date">
                                <span class="error-payment_date text-small text-danger"></span>
                            </div>


                            <div class="col-md-4">
                                <label class="form-label">Payment Mode</label>
                                <select class="form-select" id="payment_mode" required>
                                    <option selected disabled>Select Mode</option>
                                    <option value="upi">UPI</option>
                                    <option value="cash">Cash</option>
                                    <option value="Other">Other</option>
                                </select>
                                <span class="error-payment_mode text-danger text-small"></span>
                            </div>


                            <div class="col-md-4">
                                <label class="form-label">Notes</label>
                                <input type="text" class="form-control" id="notes"
                                    placeholder="Please enter notes if any">
                                <span class="error-notes text-small text-danger"></span>
                            </div>




                            <div class="col-md-4">
                                <label class="form-label">Grand Total</label>
                                <input type="number" class="form-control" id="grand_total" readonly>
                            </div>



                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="btn-sale">Save Sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- TRIP SEARCH MODAL -->
    <div class="modal fade" id="tripSearchModal" tabindex="-1" aria-labelledby="tripSearchModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="tripSearchModalLabel">Search Trip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Search Filters -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="trip_date" class="form-label">Trip Date</label>
                            <input type="date" class="form-control" id="trip-date" name="trip-date">
                            <span class="error_trip_date text-danger"></span>
                        </div>
                        <div class="col-md-6">
                            <label for="route_name" class="form-label">Route Name</label>
                            <select class="form-select" aria-label="Select Route" id="route-name" name="route-name">
                                <option selected disabled value="">select route names</option>
                                @foreach ($data as $route)
                                    <option value="{{ $route['id'] }}">{{ $route['name'] }}</option>
                                @endforeach
                            </select>

                            <span class="error_routeName text-danger"></span>

                        </div>

                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Trip Tag</th>
                                    <th>Route</th>
                                    <th>Trip Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="trip-table">

                                <!-- Repeat rows dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Update Payment Modal -->
    <div class="modal fade" id="updatePaymentModal" tabindex="-1" aria-labelledby="updatePaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- Dummy Payment Update Form -->
                    <form id="payment-update-form">
                        <div class="mb-3">
                            <label for="payment-amount" class="form-label">Enter Payment</label>
                            <input type="number" class="form-control" id="paymentAmount"
                                placeholder="Enter remaining amount">
                            <span class="error-paymentAmount text-danger"></span>
                        </div>

                        <div class="mb-3">
                            <label for="payment-date" class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="payment-date">
                            <span class="error-payment-date text-danger"></span>
                        </div>

                        <div class="mb-3">
                            <label for="payment-method" class="form-label">Payment Method</label>
                            <select id="payment-method" class="form-select">
                                <option selected disabled>Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                            <span class="error-payment-method text-danger"></span>
                        </div>
                    </form>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success " id="btn-update-payments">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Generate Credit Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Filter Form -->
                    <form id="filterForm">

                        <!-- Select Route -->
                        <div class="mb-3">
                            <label for="route" class="form-label">Select Route</label>
                            <select class="form-select" id="route" aria-label="Select Route">
                                <option selected>Choose a route</option>
                                <option value="route1">Route 1</option>
                                <option value="route2">Route 2</option>
                                <option value="route3">Route 3</option>
                            </select>
                        </div>


                        <!-- Select Date -->
                        <div class="mb-3">
                            <label for="date" class="form-label">Select Trip Date</label>
                            <input type="date" class="form-control" id="date" aria-label="Select Date">
                        </div>

                        <!-- Select Trip -->
                        <div class="mb-3">
                            <label for="trip" class="form-label">Select Trip</label>
                            <select class="form-select" id="trip" aria-label="Select Trip">
                                <option selected>Choose a trip</option>
                                <option value="trip1">Trip 1</option>
                                <option value="trip2">Trip 2</option>
                                <option value="trip3">Trip 3</option>
                            </select>
                        </div>


                        <!-- Advanced Options Trigger -->
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="" id="advancedOptions">
                            <label class="form-check-label" for="advancedOptions">
                                Show Advanced Options
                            </label>
                        </div>

                        <!-- Select Between Dates (Hidden by Default) -->
                        <div class="row" id="advancedOptionsFields" style="display: none;">
                            <div class="mb-3 col-md-6">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="startDate">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="endDate">
                            </div>
                        </div>


                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header" style="background-color: #f1f5f1ff">
                    <h5 class="modal-title" id="reportModalLabel" style="font-size: 0.95em;">Download Credit Report <i
                            class="bi bi-download me-1"></i> </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label for="locationSelect" class="form-label">Select Route</label>
                        <select class="form-select" id="routeSelect" name="location">
                            <option value="1">poopara</option>
                            <option value="2">anachal</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                    <button type="button" class="btn btn-success" id="download-report">
                        <i class="bi bi-download me-1"></i> Download
                    </button>
                </div>

            </div>
        </div>
    </div>


    @vite(['resources/js/fleet/fleet_sale.js'])
    @vite(['resources/js/fleet/update_fleet_payments.js'])
    @vite(['resources/js/fleet/credit_report.js'])
    {{-- @vite(['resources/js/fleet/customer_search.js']) --}}
@endsection
