@extends('dashboard::dashboard')

@section('units')

    {{-- Add Unit Button --}}
    <button type="button" class="btn active mt-3 ms-2 btn-launch border-0"
        data-bs-toggle="modal" data-bs-target="#addUnitModal">
        New Unit <i class="bi bi-rulers"></i>
    </button>

    <div class="container mt-2">

        <hr style="color:grey;">

        {{-- Title + Per-page + Reset --}}
        <div style="display:flex; justify-content:space-between; width:100%;">
            <div class="title" style="display:inline-flex;">
                <div>
                    <h5 class="mb-3 mt-1" style="font-weight:300;font-size:1em;">Unit Records</h5>
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

        {{-- Units Table --}}
        <table class="table table-bordered">
            <thead>
                <tr style="text-align:center;">
                    <th style="background-color:#08b325d3; color:white;">#</th>
                    <th style="background-color:#08b325d3; color:white;">Unit Name</th>
                    <th style="background-color:#08b325d3; color:white;">Abbreviation</th>
                    <th style="background-color:#08b325d3; color:white;">Created On</th>
                    <th style="background-color:#08b325d3; color:white;">Action</th>
                </tr>
            </thead>
            <tbody id="unitTableBody" style="text-align:center;">
                <tr id="unit-loading-row">
                    <td colspan="5" class="text-center text-muted py-3">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Loading units…
                    </td>
                </tr>
            </tbody>
        </table>

    </div>


    {{-- ================================================================ --}}
    {{-- ADD UNIT MODAL                                                    --}}
    {{-- ================================================================ --}}
    <div class="modal fade" id="addUnitModal" tabindex="-1"
        aria-labelledby="addUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg border-0">

                <div class="modal-header" style="background-color:#f1f5f1ff;">
                    <h5 class="modal-title" id="addUnitModalLabel">
                        New Unit <i class="bi bi-rulers ms-1"></i>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-12">
                            <label for="add_unit_name" class="form-label">Unit Name</label>
                            <input type="text" class="form-control" id="add_unit_name" placeholder="e.g. Kilogram">
                            <span class="text-danger small" id="err_add_name"></span>
                        </div>

                        <div class="col-md-12">
                            <label for="add_unit_abbreviation" class="form-label">Abbreviation</label>
                            <input type="text" class="form-control" id="add_unit_abbreviation" placeholder="e.g. kg">
                            <span class="text-danger small" id="err_add_abbreviation"></span>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="btnSaveUnit">
                        <i class="bi bi-check-circle"></i> Save Unit
                    </button>
                </div>

            </div>
        </div>
    </div>


    {{-- ================================================================ --}}
    {{-- EDIT UNIT MODAL                                                   --}}
    {{-- ================================================================ --}}
    <div class="modal fade" id="editUnitModal" tabindex="-1"
        aria-labelledby="editUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg border-0">

                <div class="modal-header" style="background-color:#f1f5f1ff;">
                    <h5 class="modal-title" id="editUnitModalLabel">
                        Edit Unit <i class="bi bi-pencil-square ms-1"></i>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="edit_unit_id">
                    <div class="row g-3">

                        <div class="col-md-12">
                            <label for="edit_unit_name" class="form-label">Unit Name</label>
                            <input type="text" class="form-control" id="edit_unit_name">
                            <span class="text-danger small" id="err_edit_name"></span>
                        </div>

                        <div class="col-md-12">
                            <label for="edit_unit_abbreviation" class="form-label">Abbreviation</label>
                            <input type="text" class="form-control" id="edit_unit_abbreviation">
                            <span class="text-danger small" id="err_edit_abbreviation"></span>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="btnUpdateUnit">
                        <i class="bi bi-check-circle"></i> Update Unit
                    </button>
                </div>

            </div>
        </div>
    </div>


    {{-- ================================================================ --}}
    {{-- DELETE CONFIRMATION MODAL                                         --}}
    {{-- ================================================================ --}}
    <div class="modal fade" id="deleteUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content rounded-3 shadow border-0">
                <div class="modal-header" style="background-color:#f1f5f1ff;">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete unit <strong id="deleteUnitName"></strong>? This cannot be undone.</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDeleteUnit">
                        <i class="bi bi-trash3"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

  @vite(['resources/js/inventory/unit.js'])

@endsection