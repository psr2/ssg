<div class="container pt-2" style="font-size:0.9em ;">
    <h6 class="mb-4" style="font-weight:400;">Stock Movements <i class="bi bi-box-seam"></i></h6>
    <hr>
    <form id="stockMovementForm">

        <!-- Stock IN/OUT Toggle -->
        <div class="mb-2 row">
            <div class="col-md-6">
                <label for="movementType" class="form-label">Movement Type</label>
                <select class="form-select" id="movementType">
                    <option value="" disabled selected>Select movement type</option>
                    <option value="in">Stock In</option>
                    <option value="out">Stock Out</option>
                </select>
                <span id="error-movementType" class="text-danger"></span>
            </div>

            <div class="col-md-6">
                <label for="movement_date" class="form-label">Stock In Date</label>
                <input type="date" id="movement_date" class="form-control" >
                <span id="error-movement_date" class="text-danger"></span>
            </div>
        </div>

        <!-- Common Fields -->
        <div class="row g-3 mb-3">
            <div class="col-md-6" id="stock_in_type">
                <label for="in_type" class="form-label">Stock In Type</label>
                <select class="form-select" id="in_type" disabled>
                    <option value="" disabled selected>Select type</option>
                    <option value="purchase">Purchase</option>
                    <!-- <option value="return">Return</option>
                    <option value="opening">Opening Balance</option>
                    <option value="manual">Manual</option> -->
                </select>
                <span id="error-in_type" class="text-danger"></span>
            </div>



            <div class="col-md-6">
                <label for="referenceNo" class="form-label">Reference No.</label>
                <input disabled value="" type="text" id="referenceNo" class="form-control"
                    placeholder="xxxxxxxxxxx">
                <span id="error-referenceNo" class="text-danger"></span>
            </div>
        </div>

        <!-- Stock IN Fields -->
        <div id="stockInFields" class="mb-3">
            <div class="row g-3">
                <div class="col-md-12  d-none" id="returnDetailsSection">
                    <label for="returnSource" class="form-label">Return Source</label>
                    <select class="form-select" id="returnSource">
                        <option value="" disabled selected>Select return source</option>
                        <option value="fleet">Fleet (unsold stock)</option>
                        <option value="customer">Customer Return</option>
                    </select>
                    <span id="error-returnSource" class="text-danger"></span>
                </div>
            </div>

            <div class="row g-3 mt-1 d-none" id="returnFleetRow">

                <div class="col-md-6">
                    <label for="return_reason" class="form-label">Return Count</label>
                    <input class="form-control" id="bill_number" name="bill_number">
                    <span id="error-bill_number" class="text-danger"></span>
                </div>
                <div class="col-md-6">
                    <label for="return_reason" class="form-label">Return Quantity</label>
                    <input class="form-control" id="bill_number" name="bill_number">
                    <span id="error-bill_number" class="text-danger"></span>
                </div>

            </div>

            <!-- Return Reason -->
            <div class="row g-3 mt-1 d-none" id="returnReasonRow">
                <div class="col-md-12">
                    <label for="return_reason" class="form-label">Return Reason</label>
                    <textarea class="form-control" id="return_reason" rows="2"
                        placeholder="e.g., Low quality, expired, damaged, etc."></textarea>
                    <span id="error-return_reason" class="text-danger"></span>
                </div>
            </div>

            <!-- Customer Return Info -->
            <div class="row g-3 mt-2 d-none mb-2" id="returnCustomerRow">
                <div class="col-md-6">
                    <label for="customer_name" class="form-label">Customer Name</label>
                    <input class="form-control" id="customer_name" name="customer_name">
                    <span id="error-customer_name" class="text-danger"></span>
                </div>

                <div class="col-md-6">
                    <label for="bill_number" class="form-label">Bill Number</label>
                    <input class="form-control" id="bill_number" name="bill_number">
                    <span id="error-bill_number" class="text-danger"></span>
                </div>
            </div>
        </div>

        <!-- Stock OUT Fields -->
        <div id="stockOutFields" class="mb-3 d-none">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="destination" class="form-label">Destination / Reason</label>
                    <input type="text" id="destination" class="form-control" name="destination">
                    <span id="error-destination" class="text-danger"></span>
                </div>

                <div class="col-md-6">
                    <label for="outType" class="form-label">Stock Out Type</label>
                    <select class="form-select" id="outType">
                        <option value="" disabled selected>Select type</option>
                        <option value="damage">Damage</option>
                        <option value="wastage">Wastage</option>
                        <option value="sample">Sample</option>
                        <option value="theft">Theft</option>
                        <option value="expired">Expired</option>
                        <option value="other">Other</option>
                    </select>

                    <span id="error-outType" class="text-danger"></span>
                </div>
            </div>
        </div>


        <!-- Products Table -->
        <div id="productRowsContainer" class="mb-3">
            <div class="row g-1 align-items-end product-row border rounded p-2 mb-2 bg-light mt-2">

                <!-- Product Name -->
                <div class="col-md-3">
                    <label class="form-label">Product Name</label>
                    <select class="form-select" name="products[][product]">
                        <option value="" disabled selected>Select product</option>
                        @foreach ($productList as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <span class="error-product text-danger small"></span>
                </div>

                <!-- Grade -->
                <div class="col-md-2">
                    <label class="form-label">Grade</label>
                    <select class="form-select" name="products[][grade]">
                        <option value="" disabled selected>Select grade</option>
                        @forelse ($grades ?? [] as $grade)
                            <option value="{{ $grade->code }}">{{ $grade->name }}</option>
                        @empty
                            <option value="A">Grade A</option>
                            <option value="B">Grade B</option>
                            <option value="C">Grade C</option>
                        @endforelse
                    </select>
                    <span class="error-grade text-danger small"></span>
                </div>

                <!-- Quantity -->
                <div class="col-md-3">
                    <label class="form-label">Qty</label>
                    <input value="" type="number" class="form-control" placeholder="Qty"
                        name="products[][quantity]">
                    <span class="error-quantity text-danger small"></span>
                </div>

                <!-- Unit -->
                <div class="col-md-4">
                    <label class="form-label">Unit</label>
                    <select class="form-select" name="products[][unit]">
                        <option value="" disabled selected>Select unit</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->abbreviation }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                        @endforeach
                    </select>
                    <span class="error-unit text-danger small"></span>
                </div>

                <!-- Unit Cost -->
                <div class="col-md-3" id="unit_cost_col">
                    <label class="form-label">Unit Cost</label>
                    <input value="" type="number" class="form-control" id="unit_cost" placeholder="Cost"
                        name="products[][unit_cost]">
                    <span class="error-unit_cost text-danger small"></span>
                </div>

                <!-- Vendor Name -->
                <div class="col-md-3" id="vendor_col">
                    <label class="form-label">Vendor</label>
                    <input value="" type="text" class="form-control" placeholder="Vendor name"
                        name="products[][vendor]">
                    <span class="error-vendor text-danger small"></span>
                </div>

                <!-- Location -->
                <div class="col-md-4" id="location_col">
                    <label for="location_id" class="form-label">Location</label>
                    <select class="form-select" id="location_id">
                        <option value="" disabled selected>Select Location</option>

                        @foreach ($location as $loc)
                        <option value="{{ $loc['id'] }}">{{ $loc['name'] }}</option>
                        @endforeach

                    </select>

                    <span class="error-location_id text-danger"></span>
                </div>

                <!-- Invoice Number -->
                <div class="col-md-2" id="invoice_number_col">
                    <label class="form-label">Invoice Number</label>
                    <input type="text" class="form-control" placeholder="Invoice No."
                        name="products[][invoice_number]" value="">
                    <span class="error-invoice_number text-danger small"></span>
                </div>

                <!-- Purchase Date -->
                <div class="col-md-4" id="purchase_date_col">
                    <label class="form-label">Purchase Date</label>
                    <input type="date" class="form-control" name="products[][purchase_date]" value="">
                    <span class="error-purchase_date text-danger small"></span>
                </div>

                <!-- Batch Code -->
                <div class="col-md-3" data-bs-toggle="modal" data-bs-target="#staticBackdropBatchCode" 
                id="batch_code_wrapper">
                    <label class="form-label" for="batch_code">Batch Code</label>
                    <input type="text" class="form-control" placeholder=""
                        name="products[][batch_code]" value="" id="batch_code" >
                    <span class="error-batch_code text-danger small"></span>
                </div>

                <!-- Total -->
                <div class="col-md-6" id="total_col">
                    <label class="form-label">Total</label>
                    <input type="number" value="" class="form-control" placeholder="Total"
                        name="products[][total]" >
                    <span class="error-total text-danger small"></span>
                </div>

                <!-- Remarks -->
                <div class="col-md-10 mt-2">
                    <textarea class="form-control w-100" rows="2" placeholder="Remarks..." name="products[][remarks]"></textarea>
                    <span class="error-remarks text-danger small"></span>
                </div>

                <!-- Action Button (Remove) -->
                <div class="col-md-2 mt-2 d-flex">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 removeRowBtn align-self-start mt-2">
                        <i class="bi bi-trash3"></i> Remove Row
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Product Row -->
        <div class="mb-3">
            <div id="addProductRowBtn" class="btn btn-outline-primary btn-sm">➕ Add Row</div>
        </div>

        <!-- Final Controls -->
        <div class="d-flex justify-content-between">
            <div>
                <button class="btn btn-secondary" type="reset">Reset</button>
                <button class="btn btn-success" type="submit" id="btn_submit_stock_in">✅ Submit Stock</button>
            </div>
        </div>
    </form>
</div>



@include('stock_management::Components.Modals.batch_code', ['locations' => $location])


@vite(['resources/js/stock_in.js'])
