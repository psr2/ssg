@extends('dashboard::dashboard')

@section('product_catalog')

    {{-- Add Product Button --}}
    <button type="button" class="btn active mt-3 ms-2 btn-launch border-0"
        data-bs-toggle="modal" data-bs-target="#addProductModal">
        New Product <i class="bi bi-box-seam"></i>
    </button>

    <div class="container mt-2">

        <hr style="color:grey;">

        {{-- Title + Per-page + Reset --}}
        <div style="display:flex; justify-content:space-between; width:100%;">
            <div class="title" style="display:inline-flex;">
                <div>
                    <h5 class="mb-3 mt-1" style="font-weight:300;font-size:1em;">Product Records</h5>
                </div>
                <div class="ms-2" style="padding-top:2px;">
                    <form method="GET" id="perPageForm" style="margin-bottom:5px;margin-top:-0.38em;">
                        <select style="background-color:none;" class="form-select border-0"
                            name="per_page" id="perPageSelect"
                            onchange="document.getElementById('perPageForm').submit()">
                            @foreach ([15, 25, 50] as $size)
                                <option value="{{ $size }}"
                                    {{ request('per_page', 15) == $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
            <div class="controls">
                <a style="text-decoration:none;" href="{{ url()->current() }}" class="ms-2">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </div>

        {{-- Products Table --}}
        <table class="table table-bordered">
            <thead>
                <tr style="text-align:center;">
                    <th style="background-color:#08b325d3; color:white;">#</th>
                    <th style="background-color:#08b325d3; color:white;">Product Name</th>
                    <th style="background-color:#08b325d3; color:white;">Abbreviation</th>
                    <th style="background-color:#08b325d3; color:white;">Unit</th>
                    <th style="background-color:#08b325d3; color:white;">Category</th>
                    <th style="background-color:#08b325d3; color:white;">SKU</th>
                    <th style="background-color:#08b325d3; color:white;">Action</th>
                </tr>
            </thead>
            <tbody id="productTableBody" style="text-align:center;">
                {{-- Rows populated by product.js --}}
                <tr id="product-loading-row">
                    <td colspan="7" class="text-center text-muted py-3">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Loading products…
                    </td>
                </tr>
            </tbody>
        </table>

    </div>


    {{-- ================================================================ --}}
    {{-- ADD PRODUCT MODAL                                                 --}}
    {{-- ================================================================ --}}
    <div class="modal fade" id="addProductModal" tabindex="-1"
        aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg border-0">

                <div class="modal-header" style="background-color:#f1f5f1ff;">
                    <h5 class="modal-title" id="addProductModalLabel">
                        New Product <i class="bi bi-box-seam ms-1"></i>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="add_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="add_name" placeholder="e.g. Onion">
                            <span class="text-danger small" id="err_add_name"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="add_abbreviation" class="form-label">Abbreviation</label>
                            <input type="text" class="form-control" id="add_abbreviation" placeholder="e.g. ONI">
                            <span class="text-danger small" id="err_add_abbreviation"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="add_unit_id" class="form-label">Unit</label>
                            <select class="form-select" id="add_unit_id">
                                <option value="" selected disabled>Select Unit</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger small" id="err_add_unit_id"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="add_category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="add_category" placeholder="e.g. Vegetables">
                            <span class="text-danger small" id="err_add_category"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="add_sku" class="form-label">SKU <span class="text-muted small">(optional)</span></label>
                            <input type="text" class="form-control" id="add_sku" placeholder="e.g. VEG-001">
                            <span class="text-danger small" id="err_add_sku"></span>
                        </div>

                        <div class="col-md-12">
                            <label for="add_description" class="form-label">Description / Notes</label>
                            <textarea class="form-control" id="add_description" rows="2"
                                placeholder="Optional notes…"></textarea>
                            <span class="text-danger small" id="err_add_description"></span>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="btnSaveProduct">
                        <i class="bi bi-check-circle"></i> Save Product
                    </button>
                </div>

            </div>
        </div>
    </div>


    {{-- ================================================================ --}}
    {{-- EDIT PRODUCT MODAL                                                --}}
    {{-- ================================================================ --}}
    <div class="modal fade" id="editProductModal" tabindex="-1"
        aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg border-0">

                <div class="modal-header" style="background-color:#f1f5f1ff;">
                    <h5 class="modal-title" id="editProductModalLabel">
                        Edit Product <i class="bi bi-pencil-square ms-1"></i>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="edit_product_id">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="edit_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="edit_name">
                            <span class="text-danger small" id="err_edit_name"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_abbreviation" class="form-label">Abbreviation</label>
                            <input type="text" class="form-control" id="edit_abbreviation">
                            <span class="text-danger small" id="err_edit_abbreviation"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_unit_id" class="form-label">Unit</label>
                            <select class="form-select" id="edit_unit_id">
                                <option value="" disabled>Select Unit</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger small" id="err_edit_unit_id"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="edit_category">
                            <span class="text-danger small" id="err_edit_category"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_sku" class="form-label">SKU <span class="text-muted small">(optional)</span></label>
                            <input type="text" class="form-control" id="edit_sku">
                            <span class="text-danger small" id="err_edit_sku"></span>
                        </div>

                        <div class="col-md-12">
                            <label for="edit_description" class="form-label">Description / Notes</label>
                            <textarea class="form-control" id="edit_description" rows="2"></textarea>
                            <span class="text-danger small" id="err_edit_description"></span>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="btnUpdateProduct">
                        <i class="bi bi-check-circle"></i> Update Product
                    </button>
                </div>

            </div>
        </div>
    </div>


    {{-- ================================================================ --}}
    {{-- DELETE CONFIRMATION MODAL                                         --}}
    {{-- ================================================================ --}}
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content rounded-3 shadow border-0">
                <div class="modal-header" style="background-color:#f1f5f1ff;">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete <strong id="deleteProductName"></strong>?
                        This cannot be undone.</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDeleteProduct">
                        <i class="bi bi-trash3"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>


    @vite(['resources/js/inventory/product.js'])

@endsection
