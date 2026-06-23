@extends('dashboard::dashboard')

@section('fleet_vehicles')

<div class="container">

    {{-- Add Vehicle Button --}}
    <div class="mb-3">
        <button class="btn active mt-3 btn-launch border-0" data-bs-toggle="modal" data-bs-target="#vehicleModal">
            Add Vehicle <i class="bi bi-truck"></i>
        </button>
    </div>

    <hr class="ms-2" style="color: grey;">

    {{-- Title + Per-page --}}
    <div class="title ms-2" style="display: inline-flex;">
        <div>
            <h5 class="mb-3 mt-1" style="font-weight:300;font-size:1em;">Records</h5>
        </div>
        <div class="ms-2" style="padding-top:2px;">
            <form method="GET" id="perPageForm" style="margin-bottom:5px;margin-top:-0.38em;">
                <select style="background-color:none;" class="form-select border-0" name="per_page" id="perPageSelect"
                    onchange="document.getElementById('perPageForm').submit()">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </form>
        </div>
    </div>

    {{-- Vehicles Table --}}
    <table class="table table-sm table-striped ms-2" id="vehiclesTable">
        <thead>
            <tr style="text-align: center;">
                <th style="background-color: #08b325d3; color: white;">#</th>
                <th style="background-color: #08b325d3; color: white;">Registration No.</th>
                <th style="background-color: #08b325d3; color: white;">Model</th>
                <th style="background-color: #08b325d3; color: white;">Type</th>
                <th style="background-color: #08b325d3; color: white;">Capacity</th>
                <th style="background-color: #08b325d3; color: white;">Notes</th>
                <th style="background-color: #08b325d3; color: white;">Action</th>
            </tr>
        </thead>
        <tbody id="vehiclesBody" style="text-align: center;">
            {{-- Rows loaded dynamically --}}
        </tbody>
    </table>

</div>

{{-- Add / Edit Vehicle Modal --}}
<div class="modal fade" id="vehicleModal" tabindex="-1" aria-labelledby="vehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vehicleModalLabel">Add Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="vehicleForm" class="row g-3">

                    <div class="col-md-12">
                        <label for="registration_number" class="form-label">Registration Number</label>
                        <input type="text" id="registration_number" class="form-control" placeholder="KL-01-AB-1234">
                        <span class="text-danger small registration_number_error"></span>
                    </div>

                    <div class="col-md-12">
                        <label for="model" class="form-label">Model</label>
                        <input type="text" id="model" class="form-control" placeholder="Toyota Hilux">
                        <span class="text-danger small model_error"></span>
                    </div>

                    <div class="col-md-6">
                        <label for="type" class="form-label">Type</label>
                        <input type="text" id="type" class="form-control" placeholder="Truck / Van">
                        <span class="text-danger small type_error"></span>
                    </div>

                    <div class="col-md-6">
                        <label for="capacity" class="form-label">Capacity (kg)</label>
                        <input type="number" id="capacity" class="form-control" placeholder="1000" min="0">
                        <span class="text-danger small capacity_error"></span>
                    </div>

                    <div class="col-md-12">
                        <label for="notes" class="form-label">Notes</label>
                        <input type="text" id="notes" class="form-control" placeholder="Optional notes">
                        <span class="text-danger small notes_error"></span>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveVehicle" class="btn btn-primary">Save Vehicle</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this vehicle? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteVehicle" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>

@vite(['resources/js/fleet/fleet_vehicle.js'])

@endsection
