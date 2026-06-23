@extends('dashboard::dashboard')

@section('fleets')

{{-- @include('stock_management::Components.stock_in_out') --}}



<div class="container py-4">
  <h2 class="mb-4">Fleet Management System</h2>

  <!-- Nav Tabs -->
  <ul class="nav nav-tabs" id="fleetTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="routes-tab" data-bs-toggle="tab" data-bs-target="#routes" type="button" role="tab">Routes</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="trips-tab" data-bs-toggle="tab" data-bs-target="#trips" type="button" role="tab">Fleet Trips</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock" type="button" role="tab">Stock</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">Sales</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses" type="button" role="tab">Expenses</button>
    </li>

      <li class="nav-item" role="presentation">
      <button class="nav-link" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses" type="button" role="tab">Creddits</button>
    </li>
  </ul>

  <div class="tab-content border border-top-0 p-3 bg-white" id="fleetTabsContent">

    <!-- ROUTES -->
    <div class="tab-pane fade show active" id="routes" role="tabpanel">
      <h5>Create Route</h5>
      <form class="row g-3 mb-3">
        <div class="col-md-6">
          <input type="text" class="form-control" placeholder="Route Name" name="route_name" id="route_name">
        </div>
        <div class="col-md-6">
          <input type="text" class="form-control" placeholder="Description">
        </div>
        <div class="col-12">
          <button class="btn btn-primary">Save Route</button>
        </div>
      </form>

      <h6>Routes List</h6>
      <table class="table table-sm table-bordered">
        <thead><tr><th>#</th><th>Name</th><th>Description</th><th>Action</th></tr></thead>
        <tbody>
          <tr><td>1</td><td>City Center</td><td>Main Market</td><td><button class="btn btn-sm btn-outline-secondary">Edit</button></td></tr>
        </tbody>
      </table>
    </div>

    <!-- TRIPS -->
    <div class="tab-pane fade" id="trips" role="tabpanel">
      <h5>Create Fleet Trip</h5>
      <form class="row g-3 mb-3">
        <div class="col-md-4">
          <select class="form-select">
            <option selected>Choose Route</option>
            <option>City Center</option>
            <option>Suburb A</option>
          </select>
        </div>
        <div class="col-md-4">
          <select class="form-select">
            <option selected>Choose Vehicle</option>
            <option>KA-01-1234</option>
          </select>
        </div>
        <div class="col-md-4">
          <select class="form-select">
            <option selected>Choose Driver</option>
            <option>John Doe</option>
          </select>
        </div>
        <div class="col-md-6">
          <input type="date" class="form-control">
        </div>
        <div class="col-md-6">
          <input type="date" class="form-control">
        </div>
        <div class="col-12">
          <button class="btn btn-success">Create Trip</button>
        </div>
      </form>

      <h6>Fleet Trips List</h6>
      <table class="table table-sm table-bordered">
        <thead><tr><th>#</th><th>Route</th><th>Vehicle</th><th>Driver</th><th>Status</th></tr></thead>
        <tbody>
          <tr><td>1</td><td>City Center</td><td>KA-01-1234</td><td>John Doe</td><td><span class="badge bg-warning">Ongoing</span></td></tr>
        </tbody>
      </table>
    </div>

    <!-- STOCK -->
    <div class="tab-pane fade" id="stock" role="tabpanel">
      <h5>Stock for Fleet Trip</h5>
      <form class="row g-3 mb-3">
        <div class="col-md-3">
          <select class="form-select">
            <option selected>Choose Trip</option>
            <option>Trip #1 - City Center</option>
          </select>
        </div>
        <div class="col-md-3">
          <input type="text" class="form-control" placeholder="Product">
        </div>
        <div class="col-md-2">
          <input type="number" class="form-control" placeholder="Qty Sent">
        </div>
        <div class="col-md-2">
          <input type="number" class="form-control" placeholder="Location">
        </div>

          <div class="col-md-2">
          <input type="number" class="form-control" placeholder="Batch">
        </div>
        <div class="col-md-2">
          <input type="number" class="form-control" placeholder="Qty Returned">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary">Add</button>
        </div>
      </form>

      <h6>Stock Records</h6>
      <table class="table table-sm table-bordered">
        <thead><tr><th>Trip</th><th>Product</th><th>Sent</th><th>Returned</th><th>Sold</th></tr></thead>
        <tbody>
          <tr><td>#1</td><td>Onion</td><td>100kg</td><td>20kg</td><td>80kg</td></tr>
        </tbody>
      </table>
    </div>

    <!-- SALES -->
    <div class="tab-pane fade" id="sales" role="tabpanel">
      <h5>Record Sales</h5>
      <form class="row g-3 mb-3">
        <div class="col-md-3">
          <select class="form-select">
            <option selected>Choose Trip</option>
            <option>Trip #1</option>
          </select>
        </div>
        <div class="col-md-3">
          <input type="text" class="form-control" placeholder="Customer Name">
        </div>
        <div class="col-md-2">
          <input type="text" class="form-control" placeholder="Bill #">
        </div>
        <div class="col-md-2">
          <input type="text" class="form-control" placeholder="Product">
        </div>
        <div class="col-md-2">
          <input type="number" class="form-control" placeholder="Qty Sold">
        </div>
        <div class="col-12">
          <button class="btn btn-primary">Add Sale</button>
        </div>
      </form>

      <h6>Sales Records</h6>
      <table class="table table-sm table-bordered">
        <thead><tr><th>Trip</th><th>Customer</th><th>Bill #</th><th>Product</th><th>Qty</th></tr></thead>
        <tbody>
          <tr><td>#1</td><td>ABC Stores</td><td>1001</td><td>Onion</td><td>20kg</td></tr>
        </tbody>
      </table>
    </div>

    <!-- EXPENSES -->
    <div class="tab-pane fade" id="expenses" role="tabpanel">
      <h5>Record Trip Expenses</h5>
      <form class="row g-3 mb-3">
        <div class="col-md-3">
          <select class="form-select">
            <option selected>Choose Trip</option>
            <option>Trip #1</option>
          </select>
        </div>
        <div class="col-md-3">
          <select class="form-select">
            <option selected>Expense Type</option>
            <option>Fuel</option>
            <option>Toll</option>
            <option>Food</option>
            <option>Other</option>
          </select>
        </div>
        <div class="col-md-3">
          <input type="number" class="form-control" placeholder="Amount">
        </div>
        <div class="col-md-3">
          <input type="text" class="form-control" placeholder="Notes">
        </div>
        <div class="col-12">
          <button class="btn btn-primary">Add Expense</button>
        </div>
      </form>

      <h6>Expense Records</h6>
      <table class="table table-sm table-bordered">
        <thead><tr><th>Trip</th><th>Type</th><th>Amount</th><th>Notes</th></tr></thead>
        <tbody>
          <tr><td>#1</td><td>Fuel</td><td>1500</td><td>Diesel</td></tr>
        </tbody>
      </table>
    </div>

  </div>
</div>




@endsection
