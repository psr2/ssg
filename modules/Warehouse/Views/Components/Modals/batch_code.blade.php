<!-- Modal -->
<div class="modal fade" id="staticBackdropBatchCode" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content premium-modal-content">
      <div class="modal-header premium-modal-header text-white">
        <span class="modal-title premium-modal-title" id="staticBackdropLabel">
          <i class="bi bi-search"></i> Search Batch Code (Warehouse)
        </span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body premium-modal-body">
        <!-- Search Filters Card -->
        <div class="premium-card-filter mb-4">
          <form id="batchCodeSearchForm" class="row g-3">
            <div class="col-md-4">
              <label for="productName" class="form-label premium-form-label">Product Name</label>
              <select class="form-select premium-form-select" name="product_listing">
                <option value="" disabled selected>Select product</option>
                @foreach($productList as $product)
                <option value="{{ $product['id'] }}">{{ $product['name'] }}</option>
                @endforeach
              </select>
              <span class="error-product text-danger small"></span>
            </div>

            <div class="col-md-4">
              <label for="location" class="form-label premium-form-label">Location</label>
              <select id="location" class="form-select premium-form-select" name="location" required>
                <option selected disabled>Select location</option>
                @foreach ($locations as $loc)
                <option value="{{ $loc['id'] }}">{{ $loc['name'] }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4">
              <label for="dateFrom" class="form-label premium-form-label">Purchase Month and Year</label>
              <input type="month" class="form-control premium-form-control" id="purchase_date" name="dateFrom">
            </div>

            <div class="col-12 text-end mt-3">
              <button type="submit" class="btn btn-primary px-4" id="search_batch_code">
                <i class="bi bi-search"></i> Search Batches
              </button>
            </div>
          </form>
        </div>

        <!-- Quick Client-side Filter -->
        <div class="row mb-3 align-items-center d-none" id="modalQuickFilterContainer">
          <div class="col-md-6 ms-auto">
            <div class="quick-filter-wrapper">
              <i class="bi bi-funnel quick-filter-icon"></i>
              <input type="text" id="modalQuickFilter" class="form-control premium-form-control quick-filter-input" placeholder="Quick filter results...">
            </div>
          </div>
        </div>

        <!-- Search Results List -->
        <div id="batchCodeListResults" class="batch-list-results-container">
          <div class="premium-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
            <div class="premium-empty-state-title">No Search Performed Yet</div>
            <div class="premium-empty-state-text">Select product & location above, then click Search Batches.</div>
          </div>
        </div>
      </div>

      <div class="modal-footer bg-light border-top-0">
        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
