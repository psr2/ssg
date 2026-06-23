@extends('dashboard::dashboard')


@section('shop_overview')
    <div class="row g-3 ms-2 ">


        <!-- Pending Money -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 d-flex flex-column" style="background-color: #d0ebff;">
                <div class="card-body d-flex flex-column justify-content-center align-items-center">


                    <h3 class="fw-bold p-2">
                        <i class="bi bi-currency-rupee me-1"></i>145,000/-
                    </h3>

                    <small class="text-muted">Total Receivables</small>
                </div>
            </div>
        </div>

        <!-- Low Stock -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 d-flex flex-column" style="background-color: #5fc3c770;">
                <div class="card-body d-flex flex-column justify-content-center align-items-center">


                    <h3 class="fw-bold p-2">
                        <i class="bi bi-currency-rupee me-1"></i>145,000/-
                    </h3>

                    <small class="text-muted">Todays' sale</small>
                </div>
            </div>
        </div>


        <!-- Low Stock -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 d-flex flex-column" style="background-color: #c968688c;">
                <div class="card-body d-flex flex-column justify-content-center align-items-center">


                    <h3 class="fw-bold p-2 ">
                        <i class="bi bi-exclamation-triangle-fill me-1"
                            style="animation: blinkWarning 1.4s infinite ease-in-out;"></i>
                        2 Items
                    </h3>

                    <small class="text-muted">Low Stock</small>
                </div>
            </div>
        </div>
    </div>
@endsection
