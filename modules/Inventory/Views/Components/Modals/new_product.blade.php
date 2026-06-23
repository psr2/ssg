<div class="modal fade" id="add_new_product" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f1f5f1ff;color:rgb(0, 0, 0);">
                <h5 class="modal-title modal-h1" id="staticBackdropLabel">Create New Product <i
                        class="bi bi-house-add"></i></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    style="background-color:white;"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    @csrf
                    <div class="row">
                        <!-- Product Name -->
                        <div class="mb-3 col-md-6">
                            <label for="product_name" class="form-label label-title">Product Name</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" required>
                            <span class="text-danger" id="error-product_name"></span>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label for="product_name" class="form-label label-title">Product Name Abbreviation</label>
                            <input type="text" class="form-control" id="product_name_abbreviation"
                                name="product_name" required>
                            <span class="text-danger" id="error-product_name_abbreviation"></span>
                        </div>
                        <!-- Stock Quantity -->
                        <div class="mb-3 col-md-6">
                            <label for="unit" class="form-label label-title">Unit</label>
                            <select class="form-select" aria-label="select units">
                                <option selected disabled>select unit</option>

                                @foreach ($units as $unit)
                                    <option data-id={{ $unit->id }}>{{ $unit->name }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger" id="error-unit"></span>
                        </div>



                        <!-- Stock In Date -->
                        <div class="mb-3 col-md-6">
                            <label for="category" class="form-label label-title">Category</label>
                            <input type="text" class="form-control" id="category" name="category">
                            <span class="text-danger" id="error-category"></span>
                        </div>

                        <div class="mb-3 col-md-12">
                            <label for="category" class="form-label label-title">Description</label>
                            <textarea class="form-control" id="description" name="description"></textarea>
                            <span class="text-danger" id="error-description"></span>
                        </div>
                    </div>
                </form>


            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" id="btn-cancel">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </button>

                <button type="button" class="btn btn-success" id="btn-save-product">
                    <i class="bi bi-check-circle me-1"></i> Save
                </button>
            </div>

        </div>
    </div>
</div>
