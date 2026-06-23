@extends('dashboard::dashboard') 

@section('locations')

<button type="button" class="btn active mt-3 ms-2 btn-launch border-0" data-bs-toggle="modal" data-bs-target="#add_new_location">
  New Location <i class="bi bi-geo-alt"></i>
</button>

<div class="container mt-2">

    <hr style="color:grey;">

    {{-- Title + Per-page + Reset --}}
    <div style="display:flex; justify-content:space-between; width:100%;">
        <div class="title" style="display:inline-flex;">
            <div>
                <h5 class="mb-3 mt-1" style="font-weight:300;font-size:1em;">Locations List</h5>
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
                    @foreach (request()->except('per_page') as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                </form>
            </div>
        </div>
        <div class="controls">
            <a style="text-decoration:none;" href="{{ url()->current() }}" class="ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </div>

    @include('locations::Components/Tables/locations_table')

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-3">
        {{ $data->appends(request()->query())->links() }}
    </div>

</div>

@include('locations::Components.Modals.new_location')
@include('locations::Components.Modals.edit_location')

@endsection