<div class="modal fade" id="add_new_unit" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(3, 3, 61);color:white;">
                <h5 class="modal-title modal-h1" id="staticBackdropLabel">Create New Unit <i
                        class="bi bi-house-add"></i></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    style="background-color:white;"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="/create-unit" id="unit_form">
                    @csrf
                    <div class="row">

                        <!-- Product Name -->
                        <div class="mb-3 col-md-12">
                            <label for="unit_name" class="form-label label-title">Unit</label>
                            <input type="text" class="form-control" id="unit" name="unit" 
                                placeholder="example :: Kilogram" value="{{ old('unit') }}">
                            @if ($errors->has('unit'))
                                <span class="text-danger" id="error-unit">{{ $errors->first('unit') }}</span>
                            @endif
                        </div>


                        <!-- Stock Quantity -->
                        <div class="mb-3 col-md-12">
                            <label for="unit_abbreviation"
                                class="form-label label-unit_abbreviation">Abbreviation</label>
                            <input type="text" class="form-control" id="abbreviation" name="abbreviation"
                                 placeholder="example :: Kg" value="{{ old('abbreviation') }}">
                            @if($errors->has('abbreviation'))

                            <span class="text-danger" id="error-abbreviation">{{ $errors->first('abbreviation') }} </span>

                            @endif
                        </div>




                    </div>



            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="button submit" class="btn btn-primary ">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
    <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                {{ session('success') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Hide the modal
            const modalEl = document.getElementById('add_new_unit');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
              const form = document.getElementById('unit_form');
            if (form) {
                form.reset();
            }
            if (modalInstance) {
                modalInstance.hide();
            }

            // Show and auto-dismiss the toast
            const toastEl = document.getElementById('successToast');
            const toast = new bootstrap.Toast(toastEl, {
                delay: 3000, // 3 seconds
                autohide: true
            });
            toast.show();
        });
    </script>
@endif

@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const unitModal = new bootstrap.Modal(document.getElementById('add_new_unit'));
            unitModal.show();
        });
    </script>
@endif
