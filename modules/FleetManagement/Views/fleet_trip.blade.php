@extends('dashboard::dashboard')

@section('fleet_trip')
    <div class="container mt-3">



        <div class="d-flex justify-content-between align-items-center mb-3 ">
            <div class="title" style="display: inline-flex;">
                <div>
                    <h5 class="mb-3 mt-3 " style=" font-weight:300;font-size:1em;">Sales Records </h5>
                </div>
                <div class="ms-2 mt-3">
                    <form method="GET" id="perPageForm" style="margin-bottom: 5px;margin-top:-0.38em;">

                        <select style="background-color:none;" class="form-select border-0" name="per_page"
                            id="perPageSelect" onchange="document.getElementById('perPageForm').submit()">
                            <option value="10" selected="">
                                10
                            </option>
                            <option value="25">
                                25
                            </option>
                            <option value="50">
                                50
                            </option>
                        </select>

                        <!-- Preserve other query params -->
                    </form>

                </div>


            </div>

            <button class="btn " data-bs-toggle="modal" data-bs-target="#tripModal">
                <i class="bi bi-plus-circle"></i> Create Trip
            </button>

            {{-- <button class="btn " data-bs-toggle="modal" data-bs-target="#tripModal">
                <i class="bi bi-plus-circle"></i> Filter Results
            </button> --}}

        </div>

        <table class="table table-sm table-striped ms-2">
            <thead style="text-align: center;">
                <tr>
                    <th style="background-color: #08b325d3; color: white;">#</th>
                    <th style="background-color: #08b325d3; color: white;">Route</th>
                    <!-- <th>Vehicle</th> -->
                    <th style="background-color: #08b325d3; color: white;">Tag</th>
                    <th style="background-color: #08b325d3; color: white;">Sent</th>
                    <th style="background-color: #08b325d3; color: white;">Billed</th>
                    <th style="background-color: #08b325d3; color: white;">Outstanding</th>
                    <th style="background-color: #08b325d3; color: white;">Date</th>
                    <th style="background-color: #08b325d3; color: white;">Action</th>
                </tr>
            </thead>
            <tbody style="text-align: center;">
                @forelse($trips as $trip)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $trip->route_name ?? 'N/A' }}</td>
                        <!-- <td>{{ $trip->vehicle_number ?? 'N/A' }}</td> -->
                        <td>{{ $trip->tag }}</td>
                        <td>
                            @if ($trip->total_sent > 1000)
                                {{ number_format($trip->total_sent / 1000, 2) }} tn
                            @else
                                {{ $trip->total_sent }} Kg
                            @endif
                        </td>
                        <td>
                            @if ($trip->total_billed > 1000)
                                {{ number_format($trip->total_billed / 1000, 2) }} tn
                            @else
                                {{ $trip->total_billed }} Kg
                            @endif
                        </td>
                        <td>₹{{ number_format($trip->outstanding_credit, 2) }}</td>

                        <td>{{ date('d-m-Y', strtotime($trip->start_date)) }}</td>
                        <td class="justify-center">
                            <span class="p-2 edit-trip" data-id="{{ $trip->id }}" style="cursor:pointer;">Edit</span>
                            <span class="badge bg-danger p-2 ms-1 delete-trip" data-id="{{ $trip->id }}"
                                style="cursor:pointer;">Delete</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No trips found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>


        <!-- Pagination -->
        <div class="">
            {!! $trips->links('pagination::bootstrap-5') !!}
        </div>





        <!-- Trip Modal -->
        <div class="modal fade" id="tripModal" tabindex="-1" aria-labelledby="tripModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #f1f5f1ff;">
                        <h5 class="modal-title" id="tripModalLabel">
                            <i class="bi bi-truck-front-fill me-2"></i>
                            Create Fleet Trip
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <form id="fleetTripForm" class="row g-3">
                            <!-- Trip Meta -->
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Route</label>
                                <select class="form-select" name="route_id" id="route_id">
                                    <option selected disabled value="">Loading routes…</option>
                                </select>
                                <span class="text-danger small error_route_id"></span>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Vehicle</label>
                                <select class="form-select" name="vehicle_id" id="vehicle_id">
                                    <option selected disabled value="">Loading vehicles…</option>
                                </select>
                                <span class="text-danger small error_vehicle_id"></span>
                            </div>

                            <div class="col-md-6">
                                <input type="date" name="start_date" class="form-control" id="start_date">
                                <span class="text-danger error_start_date"></span>
                            </div>

                            <div class="col-md-6">
                                <input type="text" name="tag" class="form-control" id="tag"
                                    placeholder="Please enter tag">
                                <span class="text-danger error_tag"></span>
                            </div>

                            <!-- ========================= -->
                            <!-- PRODUCTS SENT SECTION -->
                            <!-- ========================= -->
                            <div class="col-12">
                                <h6 class="mt-3">Products Sent</h6>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Batch</th>
                                            <th>Grade</th>
                                            <th>Qty</th>
                                            <th>Unit</th>
                                            <th>Location</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsSentContainer"></tbody>
                                </table>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addProductSentBtn"
                                    style="background-color:rgba(255, 196, 0, 0.692);
                                color:black;border:none;">
                                    + Add Item
                                </button>
                            </div>


                            <!-- ========================= -->
                            <!-- PRODUCTS RETURNED SECTION (Commented out) -->
                            <!-- ========================= -->
                            <!--
                            <div class="col-12 mt-4">
                                <h6>Products Returned</h6>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Batch</th>
                                            <th>Grade</th>
                                            <th>Qty</th>
                                            <th>Location</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsReturnedContainer"></tbody>
                                </table>
                                <button type="button"
                                    style="background-color:rgba(255, 196, 0, 0.692);
                                color:black;border:none;"
                                    class="btn btn-outline-primary btn-sm mt-2" id="addProductReturnedBtn">
                                    + Add Item
                                </button>
                            </div>
                            -->

                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>

                        <button type="submit" form="fleetTripForm" class="btn btn-success" id="create-trip">
                            <i class="bi bi-check-circle"></i> Create Trip
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- TEMPLATE -->
        <template id="productRowTemplate">
            <tr class="product-row">
                <td>
                    <select class="form-select" data-field="product_id">
                        <option selected disabled>Product</option>
                        @foreach ($productList as $product)
                            <option value="{{ $product['id'] }}">{{ $product['name'] }}</option>
                        @endforeach
                    </select>
                    <span class="text-danger error_product_id"></span>
                </td>

                <td>
                    <input type="text" class="form-control batch_code_dynamic" data-field="batch" placeholder="Batch"
                        readonly data-bs-toggle="modal" data-bs-target="#staticBackdropBatchCode">
                    <span class="text-danger error_batch"></span>
                </td>

                <td>
                    <select class="form-select" data-field="grade">
                        <option selected disabled value="">Grade</option>
                        @forelse ($grades ?? [] as $grade)
                            <option value="{{ $grade->code }}">{{ $grade->name }}</option>
                        @empty
                            <option value="A">Grade A</option>
                            <option value="B">Grade B</option>
                            <option value="C">Grade C</option>
                        @endforelse
                    </select>
                    <span class="text-danger error_grade"></span>
                </td>

                <td>
                    <input type="number" class="form-control" data-field="quantity" placeholder="Qty">
                    <span class="text-danger error_quantity"></span>
                </td>

                <td>
                    <select class="form-select" data-field="unit">
                        <option selected disabled value="">Unit</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->abbreviation }}">{{ $unit->abbreviation }}</option>
                        @endforeach
                    </select>
                    <span class="text-danger error_unit"></span>
                </td>

                <td>
                    <select class="form-select" data-field="location_id">
                        <option selected disabled>Location</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                        @endforeach
                    </select>
                    <span class="text-danger error_location_id"></span>
                </td>

                <td>
                    <button type="button" class="btn btn-danger btn-sm removeProductRow">&times;</button>
                </td>
            </tr>
        </template>




    </div>

    <!-- Stock Modal -->
    <!-- <div class="modal fade" id="stockModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="stockModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="stockModalLabel">Add Stock for Fleet Trip</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">

                                                    <form class="row g-3" id="stock-form">

                                                        <div class="col-md-4">
                                                            <select class="form-select" id="tripSelect" name="trip"   >
                                                                <option selected disabled>Choose Trip</option>
                                                                <option value="15">Trip #1 - City Center</option>
                                                            </select>
                                                            <span class="text-danger error-text trip-error"></span>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <select class="form-select" id="productSelect" name="product"   >
                                                                <option selected disabled>Choose Product</option>
                                                                <option value="1">Onion</option>
                                                                <option value="2">Potato</option>
                                                            </select>
                                                            <span class="text-danger error-text product-error"></span>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <input type="number" class="form-control" id="qtySent" name="qtySent" placeholder="Qty Sent"   >
                                                            <span class="text-danger error-text qtySent-error"></span>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <select class="form-select" id="locationSelect" name="location"   >
                                                                <option selected disabled>-- please select location --</option>
                                                                @foreach ($locations as $loction)
    <option value="{{ $loction['id'] }}">{{ $loction['name'] }}</option>
    @endforeach
                                                            </select>
                                                            <span class="text-danger error-text location-error"></span>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <input type="text"
                                                                id="batchCodeInput"
                                                                name="batch"
                                                                class="form-control"
                                                                placeholder="Batch"
                                                                readonly
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#staticBackdropBatchCode">
                                                            <span class="text-danger error-text batch-error"></span>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <input type="number" class="form-control" id="qtyReturned" name="qtyReturned" placeholder="Qty Returned">
                                                            <span class="text-danger error-text qtyReturned-error"></span>
                                                        </div>
                                                    </form>
                                                    <span id="error-common" class="ms-5 p-2 text-danger text-small" style="text-align: center;"></span>

                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary" id="add-stock">Add Stock</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div> -->


    @include('fleet_management::Components.Modals.batch_code', [
        'location' => $locations,
        'productList' => $productList
    ])



    @vite(['resources/js/fleet/fleet_trip.js'])
@endsection
