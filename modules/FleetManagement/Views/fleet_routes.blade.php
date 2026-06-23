@extends('dashboard::dashboard')

@section('fleets_routes')

<!-- <h5 class="ms-3 mb-3"><i class="bi bi-signpost-split"></i> Route Management</h5> -->

<div class="container">
  <!-- Trigger Modal -->
  <div class="mb-3">
    <button class="btn active mt-3 btn-launch border-0" data-bs-toggle="modal" data-bs-target="#routeModal">
      Add Route <i class="bi bi-cloud-plus"></i>
    </button>
  </div>

  <hr class="ms-2" style="color: grey;">



  <div class="title ms-2" style="display: inline-flex;">
    <div>
      <h5 class="mb-3 mt-1" style=" font-weight:300;font-size:1em;"> Records </h5>
    </div>
    <div class="ms-2" style="padding-top:2px;">
      <form method="GET" id="perPageForm" style="margin-bottom: 5px;margin-top:-0.38em;">

        <select style="background-color:none;" class="form-select border-0" name="per_page" id="perPageSelect" onchange="document.getElementById('perPageForm').submit()">
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

  <!-- Routes List -->
  <table class="table table-sm table-striped ms-2" id="routesTable">
    <thead>
      <tr style="text-align: center;">
        <th style="background-color: #08b325d3; color: white;">#</th>
        <th style="background-color: #08b325d3; color: white;">Name</th>
        <th style="background-color: #08b325d3; color: white;">Description</th>
        <th style="background-color: #08b325d3; color: white;">Action</th>
      </tr>
    </thead>
    <tbody id="routesBody" style="text-align: center;">
      <tr>
        <td>1</td>
        <td>City Center</td>
        <td>Main Market</td>
        <td>
          <button class="btn btn-sm btn-outline-secondary">Edit</button>
        </td>
      </tr>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div class="modal fade" id="routeModal" tabindex="-1" aria-labelledby="routeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="routeModalLabel">Add New Route</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="routeForm" class="row g-3">
          <div class="col-md-12">
            <label for="name" class="form-label">Route Name</label>
            <input type="text" id="name" class="form-control" placeholder="Route Name">
            <span class="text-danger small name_error"></span>
          </div>
          <div class="col-md-12">
            <label for="description" class="form-label">Description</label>
            <input type="text" id="description" class="form-control" placeholder="Description">
            <span class="text-danger small description_error"></span>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="saveRoute" class="btn btn-primary">Save Route</button>
      </div>
    </div>
  </div>
</div>

@vite(['resources/js/fleet/fleet_route.js'])

@endsection