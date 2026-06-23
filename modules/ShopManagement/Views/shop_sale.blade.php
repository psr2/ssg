@extends('dashboard::dashboard')

@section('shop_sale')


<button type="button" class="btn active mt-3 ms-2 btn-launch border-0" data-bs-toggle="modal" data-bs-target="#saleModal">
  New Sale <i class="bi-cart-plus"></i>

</button>

<div class="container mt-2">

  <hr style="color:grey;">

  <!-- Sales Table -->
  <div style="display: flex; justify-content: space-between; width: 100%;">
    <div class="title" style="display: inline-flex;">
      <div>
        <h5 class="mb-3 mt-1" style=" font-weight:300;font-size:1em;">Sales Records </h5>
      </div>
      <div class="ms-2" style="padding-top:2px;">
        <form method="GET" id="perPageForm" style="margin-bottom: 5px;margin-top:-0.38em;">

          <select style="background-color:none;" class="form-select border-0" name="per_page" id="perPageSelect" onchange="document.getElementById('perPageForm').submit()">
            @foreach([ 10, 25, 50] as $size)
            <option value="{{ $size }}" {{ (request('per_page', 10) == $size) ? 'selected' : '' }}>
              {{ $size }}
            </option>
            @endforeach
          </select>

          <!-- Preserve other query params -->
          @foreach(request()->except('per_page') as $key => $value)
          <input type="hidden" name="{{ $key }}" value="{{ $value }}">
          @endforeach
        </form>

      </div>

    </div>
    <div class="controls">
      <a style="text-decoration:none;" href="{{ url()->current() }}" class=" ms-2"> <i class="bi bi-arrow-clockwise"></i> Reset</a>


    </div>

  </div>


  <table class="table  table-striped">
    <thead>
      <thead>
        <tr style="text-align: center;">
          <th style="background-color: #08b325d3; color: white;">Bill No</th>
          <th style="background-color: #08b325d3;; color: white;">Customer</th>
          <th style="background-color: #08b325d3;; color: white;">Sale Amount</th>
          <th style="background-color: #08b325d3;; color: white;">Paid Amount</th>
          <th style="background-color: #08b325d3; color: white;">
            @php
            $currentSortBy = request('sort_by');
            $currentSortOrder = request('sort_order') ?? 'asc';
            $newSortOrder = 'asc';

            if ($currentSortBy === 'shop_sales.due_amount') {
            $newSortOrder = $currentSortOrder === 'asc' ? 'desc' : 'asc';
            }
            @endphp

            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'shop_sales.due_amount', 'sort_order' => $newSortOrder, 'only_due' => true]) }}" style="color:white; text-decoration:none;">
              Balance
              @if ($currentSortBy === 'shop_sales.due_amount')
              <i class="fa-solid fa-sort-{{ $currentSortOrder === 'asc' ? 'up' : 'down' }}" style="font-size:0.85em;"></i>
              @else
              <i class="fa-solid fa-sort" style="font-size:0.85em;"></i>
              @endif
            </a>

          </th>
          <th style="background-color: #08b325d3;; color: white;">Action</th>
        </tr>
      </thead>

    </thead>
    <tbody>
    <tbody style="text-align: center;">
      @foreach($data as $item)
      <tr>
        <td>{{ $item['bill_no'] ?? '-' }}</td>
        <td>{{ $item['customer_name'] }}</td>
        <td> &#8377;{{ $item['total_amount'] }}</td>
        <td>&#8377;{{ $item['paid_amount'] }}</td>

        <td> &#8377;{{ $item['due_amount'] }}</td>
        <td>
          <div style="display: flex; gap: 5px; align-items: center;">
            <i class="bi bi-pencil-square text-success edit_sale"
              data-bs-toggle="modal" data-bs-target="#updatePaymentsModal"
              style="cursor:pointer; {{ $item['due_amount'] > 0 ? '' : 'visibility:hidden;' }}"
              data-sale_id="{{ $item['sale_id'] }}"
              data-customer_id="{{ $item['customer_id'] }}"
              data-timestamp="{{ $item['last_updated'] }}">
            </i>

            <i class="ms-4 bi bi-trash delete-sale-record text-danger"
              style="cursor:pointer;" value="{{ $item['id'] }}"></i>
          </div>
        </td>




      </tr>
      @endforeach
    </tbody>


    </tbody>
  </table>

  <!-- Pagination Links -->
  {{ $data->appends(request()->query())->links() }}
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





            <!-- <div class="col-md-4">
              <label for="trip_id" class="form-label">Trip</label>
              <div class="input-group"> 
                <input
                  id="trip_id"
                  type="text"
                  data-bs-target="#tripSearchModal"
                  data-bs-toggle="modal"
                  data-bs-dismiss="modal"
                  class="form-control"
                  placeholder="Select Trip"
                  autocomplete="off"
                  required />


              </div>


              <input type="hidden" name="trip_hidden" id="trip_hidden" />
              <span class="error-trip_id text-danger text-small"></span>
            </div> -->

            <div class="col-md-4">
              <label class="form-label">Select Shop</label>
              <select class="form-select" id="shop_id" aria-label="Select Shop">
                <option selected disabled>Choose shop</option>
                @foreach ($location as $locations)
                <option value="{{ $locations['id'] }}">{{ $locations['name'] }}</option>
                @endforeach
              </select>


            </div>


            <div class="col-md-4">
              <label class="form-label">Customer Name</label>
              <input type="text" class="form-control" id="customer_name" placeholder="Customer Name" required>
              <span class="error-customer_name text-danger text-small"></span>

              <ul
                id="drop-down"
                class="dropdown-menu">
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

          <div id="error_common" class="text-danger text-center"></div>




          <!--customer details-->

          <div id="newCustomerFields" class="mt-3" style="display: none;">


            <hr class="pt-1 pb-1">
            <!-- <h6>Customer Details</h6> -->
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">New Customer Name</label>
                <input type="text" class="form-control" id="new_customer_name" placeholder="Enter name">
                <span class="text-danger error-new_customer_name text-small"></span>
              </div>

              <div class="col-md-6">
                <label class="form-label">Business Name & Address</label>
                <input type="text" class="form-control" id="business_name" placeholder="Enter business  name and address">
                <span class="text-danger error-business_name text-small"></span>
              </div>

              <div class="col-md-6">
                <label class="form-label">Customer Contact</label>
                <input type="text" class="form-control" id="customer_contact" placeholder="Enter contact number">
                <span class="text-danger error-customer_contact text-small"></span>
              </div>

              <div class="col-md-6">
                <label class="form-label">Location Name</label>
                <input type="text" class="form-control" id="location_name" placeholder="Enter location">
                <span class="text-danger error-location_name text-small"></span>
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
              <!-- Dynamic rows will go here -->
            </tbody>


          </table>
          <div id="error-common" class="text-danger text-center" style="text-align: center;"></div>

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
              <input type="number" class="form-control" id="amount_paid" placeholder="Enter amount paid">
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
              <input type="text" class="form-control" id="notes" placeholder="Please enter notes if any">
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



<!-- Update Payment Modal -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1" aria-labelledby="updatePaymentModalLabel" aria-hidden="true">
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
            <input type="number" class="form-control" id="paymentAmount" placeholder="Enter remaining amount">
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
              <option disabled>Select Method</option>
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

<!-- Update Payments Modal -->
<div class="modal fade" id="updatePaymentsModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #f1f5f1ff;">
        <h5 class="modal-title" id="filterModalLabel" style="font-weight: 400;font-size:1em;"><i class="bi bi-pencil-square"></i> Update Sale Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Filter Form -->
        <form id="filterForm">
          <div class="row">


            <!-- Select Date -->
            <div class="mb-4 col-md-6">
              <label for="date" class="form-label">Customer Name</label>
              <input type="text" class="form-control" id="update_customer_name" aria-label="Select Date">
              <span class="error_update_customer_name text-danger"></span>
            </div>


            <!-- Select Date -->
            <div class="mb-4 col-md-6">
              <label for="date" class="form-label">Total Bill Amount</label>
              <input disabled type="text" class="form-control" id="update_total_bill" aria-label="Select Date">
              <span class="error_update_total_bill text-danger"></span>
            </div>


            <!-- Select Date -->
            <div class="mb-3 col-md-6">
              <label for="date" class="form-label">Pending Amount</label>
              <input disabled type="text" class="form-control" id="update_pending_amount" aria-label="Select Date">
              <span class="error-update_pending_amount text-danger"></span>
            </div>

            <div class="mb-3 col-md-6">
              <label for="date" class="form-label">New Amount</label>
              <input type="text" class="form-control" id="new_amount" aria-label="Select Date">
              <span class="error_new_amount text-danger"></span>
            </div>

            <div class=" col-md-12 mb-3">
              <label for="payment-method" class="form-label">Payment Method</label>
              <select id="payment-method" class="form-select">
                <option disabled>Select Method</option>
                <option value="cash">Cash</option>
                <option value="upi">UPI</option>
                <option value="bank">Bank Transfer</option>
              </select>
              <span class="error-payment-method text-danger"></span>
            </div>

          </div>






      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success " id="update_fetch">Apply Filters</button>
      </div>
      </form>
    </div>
  </div>
</div>
@include('stock_management::Components.Modals.batch_code', [
'locations' => $location,
'products' => $productList
])


@vite(['resources/js/shop/shop_sale.js' ,'resources/js/shop/customer_search.js'])

@endsection