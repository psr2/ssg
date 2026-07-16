@extends('dashboard::dashboard')

@section('content')
<div class="container py-4" id="billingAdjustmentPage">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h6 class="mb-1" style="font-weight:400; font-size: 1.25rem;">Billing Adjustment <i class="bi bi-wallet2"></i></h6>
            <p class="text-muted small mb-0">Adjust sale totals, apply corrections, and manage receivables across Warehouse, Shop, and Fleet modules.</p>
        </div>
        <div>
            <span class="badge bg-dark px-3 py-2 text-uppercase">Audit Trail Enabled</span>
        </div>
    </div>
    <hr style="color:grey;" class="mb-4">

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-4" id="billingTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="new-billing-tab" data-bs-toggle="tab" data-bs-target="#new-billing" type="button" role="tab" aria-controls="new-billing" aria-selected="true">
                <i class="bi bi-plus-circle me-1"></i> New Billing Adjustment
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="billing-history-tab" data-bs-toggle="tab" data-bs-target="#billing-history" type="button" role="tab" aria-controls="billing-history" aria-selected="false">
                <i class="bi bi-clock-history me-1"></i> Adjustment Logs
            </button>
        </li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content" id="billingTabContent">
        <!-- Tab 1: New Billing Adjustment Form -->
        <div class="tab-pane fade show active" id="new-billing" role="tabpanel" aria-labelledby="new-billing-tab">
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
                <form id="billingAdjustmentForm">
                    @csrf
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="sale_type" class="form-label fw-semibold">Sale Module/Source</label>
                            <select class="form-select" id="sale_type" name="sale_type" required>
                                <option value="" disabled selected>-- Select Module --</option>
                                <option value="warehouse">Warehouse Sale</option>
                                <option value="shop">Shop Sale</option>
                                <option value="fleet">Fleet Sale</option>
                            </select>
                            <span id="sale_type_error" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="sale_id" class="form-label fw-semibold">Select Invoice / Sale Record</label>
                            <select class="form-select" id="sale_id" name="sale_id" required disabled>
                                <option value="" disabled selected>-- Select Invoice --</option>
                            </select>
                            <div class="form-text text-muted small" id="sale_loading_text" style="display:none;">
                                <span class="spinner-border spinner-border-sm text-secondary me-1" role="status"></span> Loading sales...
                            </div>
                            <span id="sale_id_error" class="text-danger small"></span>
                        </div>
                    </div>

                    <!-- Financial breakdown of the selected sale -->
                    <div class="row g-3 mb-4 p-3 bg-light rounded border border-light-subtle" id="financial_breakdown_section" style="display:none;">
                        <div class="col-md-3">
                            <label class="form-label text-muted small mb-1">Original Invoice Total</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control bg-white" id="original_total" readonly value="0.00">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label text-muted small mb-1">Paid Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control bg-white" id="original_paid" readonly value="0.00">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label text-muted small mb-1">Current Due Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control bg-white text-danger" id="original_due" readonly value="0.00">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label text-muted small mb-1">Projected New Due</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control bg-white fw-bold" id="projected_due" readonly value="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Inputs for Adjustment -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="new_amount" class="form-label fw-semibold">New Invoice Total Amount</label>
                            <input type="number" step="0.01" class="form-control border-primary" id="new_amount" name="new_amount" placeholder="Enter new invoice total..." required disabled>
                            <span id="new_amount_error" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-2">Adjustment Delta</label>
                            <div class="d-flex align-items-center" style="height: 38px;">
                                <span id="adjustment_delta_badge" class="badge bg-secondary fs-6 px-3 py-2 w-100 text-center">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Reasons and Remarks -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="reason" class="form-label fw-semibold">Reason for Adjustment</label>
                            <select class="form-select" id="reason" name="reason" required disabled>
                                <option value="" disabled selected>-- Select Reason --</option>
                                <option value="price_correction">Price Correction (Wrong Unit Price)</option>
                                <option value="discretionary_discount">Discretionary Discount (Goodwill)</option>
                                <option value="billing_error">Billing Entry Error</option>
                                <option value="tax_adjustment">Tax / Fee Adjustment</option>
                                <option value="other">Other Correction</option>
                            </select>
                            <span id="reason_error" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label for="remarks" class="form-label fw-semibold">Audit Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Provide details / approval reference for this billing correction..." disabled></textarea>
                            <span id="remarks_error" class="text-danger small"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <button class="btn btn-outline-secondary px-4" type="reset" id="btn_reset_billing">Reset Form</button>
                        <button class="btn btn-primary px-4" type="submit" id="btn_submit_billing" disabled>
                            <i class="bi bi-check2-circle"></i> Save Billing Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab 2: Billing Adjustment Logs -->
        <div class="tab-pane fade" id="billing-history" role="tabpanel" aria-labelledby="billing-history-tab">
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle border border-light-subtle">
                        <thead>
                            <tr class="table-dark text-center">
                                <th>Date</th>
                                <th>Type</th>
                                <th>Sale Ref / ID</th>
                                <th>Original Amt</th>
                                <th>Adjustment</th>
                                <th>New Amt</th>
                                <th>Reason</th>
                                <th>Adjusted By</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $adj)
                                <tr class="text-center">
                                    <td>{{ $adj->created_at ? \Carbon\Carbon::parse($adj->created_at)->format('Y-m-d H:i') : '—' }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border text-uppercase">{{ $adj->sale_type }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">#{{ $adj->sale_id }}</span>
                                    </td>
                                    <td>${{ number_format($adj->original_amount, 2) }}</td>
                                    <td>
                                        @if($adj->adjusted_amount < 0)
                                            <span class="text-danger fw-bold"><i class="bi bi-dash-circle-fill text-danger me-1"></i> -${{ number_format(abs($adj->adjusted_amount), 2) }}</span>
                                        @else
                                            <span class="text-success fw-bold"><i class="bi bi-plus-circle-fill text-success me-1"></i> +${{ number_format($adj->adjusted_amount, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">${{ number_format($adj->new_amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-dark border border-secondary-subtle text-uppercase small">{{ str_replace('_', ' ', $adj->reason) }}</span>
                                    </td>
                                    <td>
                                        <small class="fw-semibold">{{ $adj->user->name ?? 'System' }}</small>
                                    </td>
                                    <td class="text-start">
                                        <span class="text-muted small">{{ $adj->remarks ?? '—' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-wallet2 fs-1 d-block mb-3 text-secondary"></i>
                                        <h6 class="fw-semibold">No billing adjustments recorded yet.</h6>
                                        <p class="small text-muted mb-0">Use the form above to record billing or invoice corrections.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $adjustments->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AJAX and Form Interactive Logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const saleTypeSelect = document.getElementById('sale_type');
    const saleIdSelect = document.getElementById('sale_id');
    const saleLoadingText = document.getElementById('sale_loading_text');
    const financialBreakdownSection = document.getElementById('financial_breakdown_section');
    
    const originalTotalInput = document.getElementById('original_total');
    const originalPaidInput = document.getElementById('original_paid');
    const originalDueInput = document.getElementById('original_due');
    const projectedDueInput = document.getElementById('projected_due');
    
    const newAmountInput = document.getElementById('new_amount');
    const deltaBadge = document.getElementById('adjustment_delta_badge');
    const reasonSelect = document.getElementById('reason');
    const remarksTextarea = document.getElementById('remarks');
    const btnSubmit = document.getElementById('btn_submit_billing');
    const form = document.getElementById('billingAdjustmentForm');

    let currentSalesList = [];
    let selectedSale = null;

    // 1. Listen for Sale Type Change
    saleTypeSelect.addEventListener('change', function() {
        const type = this.value;
        if (!type) return;

        // Reset elements
        saleIdSelect.disabled = true;
        saleIdSelect.innerHTML = '<option value="" disabled selected>-- Select Invoice --</option>';
        saleLoadingText.style.display = 'block';
        financialBreakdownSection.style.display = 'none';
        disableFields();

        fetch(`/billing-adjustments/sales?type=${type}`)
            .then(res => res.json())
            .then(data => {
                currentSalesList = data;
                saleLoadingText.style.display = 'none';
                
                if (data.length === 0) {
                    saleIdSelect.innerHTML = '<option value="" disabled selected>-- No Sales Records Found --</option>';
                } else {
                    saleIdSelect.disabled = false;
                    saleIdSelect.innerHTML = '<option value="" disabled selected>-- Select Invoice --</option>';
                    data.forEach(sale => {
                        const opt = document.createElement('option');
                        opt.value = sale.id;
                        opt.textContent = sale.label;
                        saleIdSelect.appendChild(opt);
                    });
                }
            })
            .catch(err => {
                console.error(err);
                saleLoadingText.style.display = 'none';
                alert('Error loading sales list. Please try again.');
            });
    });

    // 2. Listen for Sale Selection
    saleIdSelect.addEventListener('change', function() {
        const saleId = parseInt(this.value);
        selectedSale = currentSalesList.find(s => s.id === saleId);

        if (selectedSale) {
            // Show breakdown
            financialBreakdownSection.style.display = 'flex';
            originalTotalInput.value = parseFloat(selectedSale.amount).toFixed(2);
            originalPaidInput.value = parseFloat(selectedSale.paid).toFixed(2);
            originalDueInput.value = parseFloat(selectedSale.due).toFixed(2);
            
            // Enable inputs
            newAmountInput.disabled = false;
            reasonSelect.disabled = false;
            remarksTextarea.disabled = false;
            btnSubmit.disabled = false;
            
            // Populate new amount default
            newAmountInput.value = parseFloat(selectedSale.amount).toFixed(2);
            calculateDelta();
        } else {
            financialBreakdownSection.style.display = 'none';
            disableFields();
        }
    });

    // 3. Listen for New Amount Inputs
    newAmountInput.addEventListener('input', calculateDelta);

    function calculateDelta() {
        if (!selectedSale) return;

        const originalVal = parseFloat(selectedSale.amount);
        const newVal = parseFloat(newAmountInput.value);

        if (isNaN(newVal) || newVal < 0) {
            deltaBadge.className = 'badge bg-danger fs-6 px-3 py-2 w-100 text-center';
            deltaBadge.textContent = 'Invalid Amount';
            projectedDueInput.value = '—';
            btnSubmit.disabled = true;
            return;
        }

        btnSubmit.disabled = false;
        const delta = newVal - originalVal;

        // Calculate projected due
        const paidVal = parseFloat(selectedSale.paid);
        const projectedDue = newVal - paidVal;
        projectedDueInput.value = projectedDue.toFixed(2);
        if (projectedDue < 0) {
            projectedDueInput.className = 'form-control bg-white fw-bold text-success';
        } else if (projectedDue > 0) {
            projectedDueInput.className = 'form-control bg-white fw-bold text-danger';
        } else {
            projectedDueInput.className = 'form-control bg-white fw-bold';
        }

        // Format Badge
        if (delta === 0) {
            deltaBadge.className = 'badge bg-secondary fs-6 px-3 py-2 w-100 text-center';
            deltaBadge.textContent = '$0.00 (No Change)';
        } else if (delta > 0) {
            deltaBadge.className = 'badge bg-success fs-6 px-3 py-2 w-100 text-center';
            deltaBadge.textContent = `+$${delta.toFixed(2)} (Increase)`;
        } else {
            deltaBadge.className = 'badge bg-danger fs-6 px-3 py-2 w-100 text-center';
            deltaBadge.textContent = `-$${Math.abs(delta).toFixed(2)} (Decrease)`;
        }
    }

    function disableFields() {
        newAmountInput.disabled = true;
        newAmountInput.value = '';
        reasonSelect.disabled = true;
        reasonSelect.value = '';
        remarksTextarea.disabled = true;
        remarksTextarea.value = '';
        btnSubmit.disabled = true;
        deltaBadge.className = 'badge bg-secondary fs-6 px-3 py-2 w-100 text-center';
        deltaBadge.textContent = '-';
    }

    // 4. Form Submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Clear errors
        document.querySelectorAll('.text-danger.small').forEach(el => el.textContent = '');

        const formData = {
            sale_type: saleTypeSelect.value,
            sale_id: saleIdSelect.value,
            new_amount: newAmountInput.value,
            reason: reasonSelect.value,
            remarks: remarksTextarea.value,
            _token: document.querySelector('input[name="_token"]').value
        };

        fetch('/billing-adjustments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Billing adjustment applied successfully!');
                window.location.reload();
            } else if (data.errors) {
                // Show validation errors
                for (const key in data.errors) {
                    const errEl = document.getElementById(`${key}_error`);
                    if (errEl) {
                        errEl.textContent = data.errors[key][0];
                    }
                }
            } else {
                alert(data.message || 'An error occurred while saving the adjustment.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Failed to submit adjustment request.');
        });
    });

    // 5. Form Reset
    document.getElementById('btn_reset_billing').addEventListener('click', function() {
        form.reset();
        financialBreakdownSection.style.display = 'none';
        disableFields();
        saleIdSelect.disabled = true;
        saleIdSelect.innerHTML = '<option value="" disabled selected>-- Select Invoice --</option>';
    });
});
</script>
@endsection
