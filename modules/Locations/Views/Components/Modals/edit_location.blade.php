<div class="modal fade" id="edit_location_modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="editStaticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 shadow-lg border-0">

            <div class="modal-header" style="background-color:#f1f5f1ff;">
                <h5 class="modal-title" id="editStaticBackdropLabel">
                    Edit Location <i class="bi bi-pencil-square ms-1"></i>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form method="POST" action="">
                    @csrf
                    <div class="row g-3">

                        <!-- Location Name -->
                        <div class="col-md-12">
                            <label for="edit_location_name" class="form-label label-title">Location Name</label>
                            <input type="text" class="form-control" id="edit_location_name" name="location_name" required>
                            <span class="text-danger small" id="error-edit_location_name"></span>
                        </div>

                        <!-- Location Address -->
                        <div class="col-md-12">
                            <label for="edit_location_address" class="form-label label-title">Location Address</label>
                            <textarea rows="4" class="form-control" id="edit_location_address" name="location_address" required></textarea>
                            <span class="text-danger small" id="error-edit_location_address"></span>
                        </div>

                        <!-- Location Type -->
                        <div class="col-md-12">
                            <label for="edit_location_type" class="form-label label-title">Location Type</label>
                            <select class="form-select" id="edit_location_type" name="location_type" required>
                                <option value="" disabled selected>Select Location Type</option>
                                <option value="warehouse">Warehouse</option>
                                <option value="shop">Shop</option>
                            </select>
                            <span class="text-danger small" id="error-edit_location_type"></span>
                        </div>

                        <!-- Hidden ID for update reference -->
                        <input type="hidden" id="edit_location_id" name="location_id">

                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="submit" class="btn btn-success" id="update_new_location">
                    <i class="bi bi-check-circle"></i> Update
                </button>
            </div>

        </div>
    </div>
</div>
