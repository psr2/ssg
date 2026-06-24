@extends('dashboard::dashboard')

@section('content')

    {{-- Add Grade Button --}}
    <button type="button" class="btn btn-success active mt-3 ms-2 border-0"
        data-bs-toggle="modal" data-bs-target="#addGradeModal" style="background-color:#08b325ff;">
        New Grade <i class="bi bi-award ms-1"></i>
    </button>

    <div class="container mt-2">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <hr style="color:grey;">

        {{-- Title + Per-page + Reset --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0 fw-light">Product Grade Records</h5>
            </div>
            <div>
                <a style="text-decoration:none;" href="{{ url()->current() }}" class="text-muted">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </div>

        {{-- Grades Table --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr class="text-center">
                                <th style="background-color:#08b325d3; color:white; width: 80px;">#</th>
                                <th style="background-color:#08b325d3; color:white; width: 120px;">Code</th>
                                <th style="background-color:#08b325d3; color:white;">Grade Name</th>
                                <th style="background-color:#08b325d3; color:white;">Description</th>
                                <th style="background-color:#08b325d3; color:white; width: 120px;">Status</th>
                                <th style="background-color:#08b325d3; color:white; width: 150px;">Created On</th>
                                <th style="background-color:#08b325d3; color:white; width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody style="text-align:center;">
                            @forelse($grades as $index => $grade)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><span class="badge bg-secondary font-monospace">{{ $grade->code }}</span></td>
                                    <td class="fw-semibold">{{ $grade->name }}</td>
                                    <td class="text-muted text-start">{{ $grade->description ?? 'No description provided' }}</td>
                                    <td>
                                        @if($grade->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $grade->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <form action="/settings/grades/{{ $grade->id }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this grade?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                <i class="bi bi-trash3"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No product grades registered yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>


    {{-- ================================================================ --}}
    {{-- ADD GRADE MODAL                                                    --}}
    {{-- ================================================================ --}}
    <div class="modal fade" id="addGradeModal" tabindex="-1"
        aria-labelledby="addGradeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg border-0">

                <div class="modal-header" style="background-color:#f1f5f1ff;">
                    <h5 class="modal-title" id="addGradeModalLabel">
                        New Product Grade <i class="bi bi-award ms-1"></i>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="/settings/grades" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-md-12">
                                <label for="grade_name" class="form-label">Grade Name</label>
                                <input type="text" class="form-control" id="grade_name" name="name" placeholder="e.g. Grade A" required>
                                @error('name')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label for="grade_code" class="form-label">Code / Abbreviation</label>
                                <input type="text" class="form-control" id="grade_code" name="code" placeholder="e.g. A" required>
                                @error('code')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label for="grade_description" class="form-label">Description</label>
                                <textarea class="form-control" id="grade_description" name="description" rows="3" placeholder="Describe sorting guidelines for this grade..."></textarea>
                                @error('description')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success" style="background-color:#08b325ff;">
                            <i class="bi bi-check-circle"></i> Save Grade
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

@endsection
