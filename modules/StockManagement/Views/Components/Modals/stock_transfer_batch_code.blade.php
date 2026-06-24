<!-- Modal -->
<div class="modal fade" id="staticBackdropBatchCode" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #0b0355ff;color:white;">
        <span class="modal-title" id="staticBackdropLabel" style="font-size:1em;">Search Batch Code</span>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <!-- Search Filters -->
        <form id="batchCodeSearchForm" class="row g-3 mb-3">
          <div class="col-md-3">
            <label for="productName" class="form-label">Product Name(make dyna)</label>

            <select class="form-select" name="product_listing">
              <option value="" disabled selected>Select product</option>
              <option value="onion" selected>Onion</option>
              <option value="shallots">Shallots</option>
              <option value="potato">Potato</option>
              <option value="tomato">Tomato</option>

            </select>
            <span class="error-product text-danger small"></span>
          </div>
         
          <div class="col-md-3">
            <label for="location" class="form-label">Location</label>
            <select id="location" class="form-select" name="location" required>
              <option selected disabled>Select location</option>
              <!-- You can dynamically populate this via backend ,as of now its hardcoded -->
              <option value=""></option>


            </select>
          </div>
          <div class="col-md-3">
            <label for="dateFrom" class="form-label">Purchase Month and Year</label>
            <input type="month" class="form-control" id="purchase_date" name="dateFrom">
          </div>

          <div class="col-12 text-end">
            <button type="submit" class="btn btn-sm btn-primary" id="search_batch_code">Search</button>
          </div>
        </form>

        <!-- Search Results Table -->
        <div class="table-responsive">
          <table class="table table-bordered table-sm table-hover" id="batchCodeResults">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Batch Code</th>
                <th>Product</th>
                <th>Grade</th>
                <th>Location</th>
                <th>Available Qty</th>
                <th>Select</th>
              </tr>
            </thead>
            <tbody>
              <!-- Results will be dynamically injected here -->
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="confirmBatchBtn" disabled>Confirm</button>
      </div>
    </div>
  </div>
</div>