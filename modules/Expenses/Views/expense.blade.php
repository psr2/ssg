@extends('dashboard::dashboard')

@section('expense')
    <button type="button" class="btn active mt-3 ms-2 btn-launch border-0" data-bs-toggle="modal"
        data-bs-target="#addExpenseModal">
        New Expense <i class="bi-credit-card"></i>

    </button>

    <div class="container mt-2">

        <hr style="color:grey;">

        <!-- Sales Table -->
        <div style="display: flex; justify-content: space-between; width: 100%;">
            <div class="title" style="display: inline-flex;">
                <div>
                    <h5 class="mb-3 mt-1" style=" font-weight:300;font-size:1em;">Expense Records </h5>
                </div>
                <div class="ms-2" style="padding-top:2px;">
                    <form method="GET" id="perPageForm" style="margin-bottom: 5px;margin-top:-0.38em;">

                        <select style="background-color:none;" class="form-select border-0" name="per_page"
                            id="perPageSelect" onchange="document.getElementById('perPageForm').submit()">
                            @foreach ([15, 25, 50] as $size)
                                <option value="{{ $size }}"
                                    {{ request('per_page', 10) == $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Preserve other query params -->
                        @foreach (request()->except('per_page') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                    </form>

                </div>

            </div>
            <div class="controls">
                <a style="text-decoration:none;" href="{{ url()->current() }}" class=" ms-2"> <i
                        class="bi bi-arrow-clockwise"></i> Reset</a>


            </div>

        </div>
        <table class="table table-bordered">
            <thead>
                <tr style="text-align: center;">
                    <th style="background-color: #08b325d3; color: white;">#</th>
                    <th style="background-color: #08b325d3; color: white;">Paid To</th>
                    <th style="background-color: #08b325d3; color: white;">Category</th>
                    <th style="background-color: #08b325d3; color: white;">Amount</th>
                    <th style="background-color: #08b325d3; color: white;">Balance</th>
                    <th style="background-color: #08b325d3; color: white;">Action</th>
                </tr>
            </thead>
            <tbody id="expenseTableBody">
                @foreach ($list as $index => $expense)
                    <tr style="text-align: center;" id="row_{{ $expense->id }}">
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $expense->paid_to }}</td>
                        <td>{{ $expense->category->name ?? '' }}</td>
                        <td>{{ number_format($expense->amount, 2) }}</td>
                        <td>{{ number_format($expense->balance ?? 0, 2) }}</td>

                        <td>

                            <!-- Edit Button -->
                            <button class="btn btn-sm btn-primary editExpenseBtn" data-id="{{ $expense->id }}"
                                data-expense_date="{{ \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d') }}"
                                data-category_id="{{ $expense->category_id }}" data-amount="{{ $expense->amount }}"
                                data-payment_mode="{{ $expense->payment_mode }}" data-paid_to="{{ $expense->paid_to }}"
                                data-description="{{ $expense->description }}"
                                data-reference_id="{{ $expense->reference_id }}"
                                data-created_by="{{ $expense->created_by }}"
                                data-approved_by="{{ $expense->approved_by }}" data-bs-toggle="modal"
                                data-bs-target="#editExpenseModal">
                                <i class="bi bi-pencil-square"></i>
                            </button>


                            <!-- Delete Button -->
                            <button class="btn btn-sm btn-danger deleteExpenseBtn" data-id="{{ $expense->id }}">
                                <i class="bi bi-trash3"></i>
                            </button>

                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>


        <!-- Pagination Links -->
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg border-0">

                <div class="modal-header text-white" style="background-color: #f1f5f1ff;">
                    <h5 class="modal-title" id="addExpenseModalLabel" style="color: black;">
                        Create category <i class="bi bi-wallet2 me-2"></i>
                    </h5>
                    <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <form id="expenseForm" method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">

                            <!-- Expense Date -->
                            <div class="col-md-6">
                                <label for="expense_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="expense_date" name="expense_date">
                                <span class="text-danger" id="span_error_expense_date"></span>
                            </div>

                            <!-- Expense Category -->
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Category</label>
                                <select id="category_id" name="category_id" class="form-select">
                                    <option value="" selected disabled>Select Category</option>
                                    @if (!empty($data))
                                        @foreach ($data as $name => $id)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>No categories available</option>
                                    @endif
                                </select>



                                <!-- Categories will be appended dynamically -->
                                </select>
                                <span class="text-danger" id="span_error_category_id"></span>
                                <span
                                    style="font-size: 0.8em; float:right; color: rgba(0, 0, 0, 0.822);
           background-color: rgba(255, 208, 0, 0.986); border-radius: 1px;"
                                    class="me-2 p-1 d-inline-flex align-items-center"
                                    data-bs-target="#createCategoryeModal" data-bs-toggle="modal">
                                    <!-- Icon before text -->
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Create New Category
                                </span>

                            </div>

                            <!-- Amount -->
                            <div class="col-md-6" style="margin-top:-0.2em;">
                                <label for="amount" class="form-label">Amount (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount"
                                    placeholder="0.00">
                                <span class="text-danger" id="span_error_amount"></span>
                            </div>

                            <!-- Payment Mode -->
                            <div class="col-md-6" style="margin-top:-0.2em;">
                                <label for="payment_mode" class="form-label">Payment Mode</label>
                                <select id="payment_mode" name="payment_mode" class="form-select">
                                    <option value="">Select</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="upi">UPI</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="credit">Credit</option>
                                </select>
                                <span class="text-danger" id="span_error_payment_mode"></span>
                            </div>

                            <!-- Paid To -->
                            <div class="col-md-12">
                                <label for="paid_to" class="form-label">Paid To</label>
                                <input type="text" class="form-control" id="paid_to" name="paid_to"
                                    placeholder="Person or vendor name">
                                <span class="text-danger" id="span_error_paid_to"></span>
                            </div>

                            <!-- Description -->
                            <div class="col-md-12">
                                <label for="description" class="form-label">Description / Notes</label>
                                <textarea class="form-control" id="description" name="description" rows="2" placeholder="Optional notes..."></textarea>
                                <span class="text-danger" id="span_error_description"></span>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Save Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Create expense Modal -->
    <div class="modal fade" id="createCategoryeModal" tabindex="-1" aria-labelledby="createCategoryeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg border-0">

                <div class="modal-header text-white" style="background-color: #f1f5f1ff;">
                    <h5 class="modal-title" id="createCategoryeModalLabel" style="color: black;">
                        Create Category <i class="bi bi-wallet2 me-2"></i>
                    </h5>
                    <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">




                        <!-- Amount -->
                        <div class="col-md-12">
                            <label for="amount" class="form-label">Category name</label>
                            <input type="text" class="form-control" id="new_category" name="new_category"
                                placeholder="Enter category name">
                            <span class="text-danger" id="error_new_category"></span>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    {{--  data-bs-toggle="modal" data-bs-target="#addExpenseModal" --}}
                    <button class="btn btn-success" id="create_category">
                        <i class="bi bi-check-circle"></i> Create Category
                    </button>

                </div>

            </div>
        </div>
    </div>

    <!-- Edit Expense Modal -->
    <div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg border-0">

                <div class="modal-header text-white" style="background-color: #f1f5f1ff;">
                    <h5 class="modal-title" id="editExpenseModalLabel" style="color: black;">
                        Edit Expense <i class="bi bi-pencil-square ms-2"></i>
                    </h5>
                    <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <form id="editExpenseForm" method="POST" action="">
                    @csrf
                    @method('PUT')

                    <!-- Hidden: Expense ID -->
                    <input type="hidden" id="edit_expense_id" name="expense_id">

                    <div class="modal-body">
                        <div class="row g-3">

                            <!-- Expense Date -->
                            <div class="col-md-6">
                                <label for="edit_expense_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="edit_expense_date" name="expense_date">
                                <span class="text-danger" id="span_error_edit_expense_date"></span>
                            </div>

                            <!-- Expense Category -->
                            <div class="col-md-6">
                                <label for="edit_category_id" class="form-label">Category</label>
                                <select id="edit_category_id" name="category_id" class="form-select">
                                    <option value="" disabled>Select Category</option>
                                    @if (!empty($data))
                                        @foreach ($data as $name => $id)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>No categories available</option>
                                    @endif
                                </select>

                                <span class="text-danger" id="span_error_edit_category_id"></span>

                                <!-- Create New Category Link -->
                                <span
                                    style="font-size: 0.8em; float:right; color: rgba(0, 0, 0, 0.822);
                                        background-color: rgba(255, 208, 0, 0.986); border-radius: 1px;"
                                    class="me-2 p-1 d-inline-flex align-items-center"
                                    data-bs-target="#createCategoryModal" data-bs-toggle="modal">
                                    <i class="bi bi-plus-circle me-1"></i> Create New Category
                                </span>
                            </div>

                            <!-- Amount -->
                            <div class="col-md-6">
                                <label for="edit_amount" class="form-label">Amount (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="edit_amount"
                                    name="amount">
                                <span class="text-danger" id="span_error_edit_amount"></span>
                            </div>

                            <!-- Payment Mode -->
                            <div class="col-md-6">
                                <label for="edit_payment_mode" class="form-label">Payment Mode</label>
                                <select id="edit_payment_mode" name="payment_mode" class="form-select">
                                    <option value="">Select</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="upi">UPI</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="credit">Credit</option>
                                </select>
                                <span class="text-danger" id="span_error_edit_payment_mode"></span>
                            </div>

                            <!-- Paid To -->
                            <div class="col-md-12">
                                <label for="edit_paid_to" class="form-label">Paid To</label>
                                <input type="text" class="form-control" id="edit_paid_to" name="paid_to">
                                <span class="text-danger" id="span_error_edit_paid_to"></span>
                            </div>

                            <!-- Description -->
                            <div class="col-md-12">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                                <span class="text-danger" id="span_error_edit_description"></span>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Update Expense
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>


    @vite(['resources/js/expenses/expense.js'])

@endsection
