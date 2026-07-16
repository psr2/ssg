document.addEventListener("DOMContentLoaded", function () {
    // Select elements
    const form = document.getElementById("stockAdjustmentForm");
    const selectLocation = document.getElementById("adj_location");
    const selectProduct = document.getElementById("adj_product");
    const inputBatchCode = document.getElementById("adj_batch_code");
    const selectGrade = document.getElementById("adj_grade");
    const inputAvailableQty = document.getElementById("adj_available_qty");
    const spanUnitLabel = document.getElementById("adj_unit_label");
    const inputNewQty = document.getElementById("adj_new_qty");
    const badgeDelta = document.getElementById("adj_delta_badge");
    const selectReason = document.getElementById("adj_reason");
    const textareaRemarks = document.getElementById("adj_remarks");
    const btnSubmit = document.getElementById("btn_submit_adjustment");

    const feedbackSection = document.getElementById("adj_validation_feedback");
    const alertBox = document.getElementById("adj_validation_alert");
    const alertIcon = document.getElementById("adj_validation_icon");
    const alertTitle = document.getElementById("adj_validation_title");
    const alertText = document.getElementById("adj_validation_text");

    let isFormValid = false;

    // Reset Form function
    function resetFormState() {
        form.reset();
        inputAvailableQty.value = "0.00";
        spanUnitLabel.textContent = "pcs";
        inputNewQty.disabled = true;
        selectReason.disabled = true;
        textareaRemarks.disabled = true;
        btnSubmit.disabled = true;
        feedbackSection.classList.add("d-none");
        badgeDelta.className = "badge bg-secondary fs-6 px-3 py-2 w-100 text-center";
        badgeDelta.textContent = "-";
        
        // Reset old errors
        document.querySelectorAll(".text-danger.small").forEach(el => {
            el.innerText = "";
        });
    }

    document.getElementById("btn_reset_adjustment")?.addEventListener("click", resetFormState);

    // Fetch locations from API to sync the batch search modal's location dropdown
    fetch("/api-locations")
        .then(response => response.json())
        .then(data => {
            const modalLocationSelect = document.querySelector('#batchCodeSearchForm select[name="location"]');
            if (modalLocationSelect) {
                modalLocationSelect.length = 1; // keep placeholder
                data.forEach(loc => {
                    modalLocationSelect.add(new Option(loc.name, loc.id));
                });
            }
        })
        .catch(err => console.error("Error fetching locations for modal:", err));

    // Handle batch selection modal integration
    let currentBatchInput = null;

    document.addEventListener("click", function (e) {
        if (e.target && e.target.closest("#adj_batch_code")) {
            currentBatchInput = inputBatchCode;

            const productId = selectProduct.value;
            const locationId = selectLocation.value;

            const modalProductSelect = document.querySelector('#batchCodeSearchForm select[name="product_listing"]');
            const modalLocationSelect = document.querySelector('#batchCodeSearchForm select[name="location"]');

            if (modalProductSelect && productId) modalProductSelect.value = productId;
            if (modalLocationSelect && locationId) modalLocationSelect.value = locationId;

            // Clear previous results
            const listContainer = document.getElementById('batchCodeListResults');
            if (listContainer) listContainer.innerHTML = '';

            // Auto-trigger search if both product and location are selected
            if (modalProductSelect && modalLocationSelect && productId && locationId) {
                setTimeout(() => {
                    document.getElementById('search_batch_code')?.click();
                }, 100);
            }
        }
    });

    // Handle batch selection click inside modal
    const listContainer = document.getElementById('batchCodeListResults');
    if (listContainer) {
        listContainer.addEventListener('click', function (e) {
            const card = e.target.closest('.select-batch');
            if (card && currentBatchInput === inputBatchCode) {
                e.preventDefault();
                const batchCode = card.getAttribute('data-batch-code');
                const grade = card.getAttribute('data-grade');

                inputBatchCode.value = batchCode;

                if (selectGrade && grade) {
                    selectGrade.value = grade;
                    if (!selectGrade.value || selectGrade.selectedIndex <= 0) {
                        const opt = Array.from(selectGrade.options).find(o => 
                            o.text.trim().toLowerCase() === grade.toLowerCase() || 
                            o.value.trim().toLowerCase() === grade.toLowerCase()
                        );
                        if (opt) selectGrade.value = opt.value;
                    }
                }

                const modalEl = document.getElementById('staticBackdropBatchCode');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                }

                // Trigger loading batch stock details
                fetchBatchStock();
            }
        });
    }

    // Search batch form in modal
    const searchForm = document.getElementById('batchCodeSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const data = {
                product_listing: searchForm.product_listing.value,
                location: searchForm.location.value,
                dateFrom: searchForm.dateFrom.value
            };

            fetch("/stock-transfer/search-batch-code", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(response => {
                const resultsBox = document.getElementById('batchCodeListResults');
                if (!resultsBox) return;
                resultsBox.innerHTML = "";
                const filterContainer = document.getElementById('modalQuickFilterContainer');
                const filterInput = document.getElementById('modalQuickFilter');
                if (filterInput) filterInput.value = "";

                if (response.length === 0) {
                    if (filterContainer) filterContainer.classList.add('d-none');
                    resultsBox.innerHTML = `
                    <div class="premium-empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                        <div class="premium-empty-state-title">No Batches Found</div>
                        <div class="premium-empty-state-text">No available batches match the selected product and location.</div>
                    </div>`;
                } else {
                    if (filterContainer) filterContainer.classList.remove('d-none');
                    response.forEach(item => {
                        let gradeClass = 'badge-grade-unsorted';
                        let gradeLower = (item.grade || '').toLowerCase();
                        if (gradeLower === 'a' || gradeLower === 'big') gradeClass = 'badge-grade-a';
                        else if (gradeLower === 'b' || gradeLower === 'small') gradeClass = 'badge-grade-b';
                        else if (gradeLower === 'c') gradeClass = 'badge-grade-c';
                        else if (gradeLower === 'waste' || gradeLower === 'reject') gradeClass = 'badge-grade-waste';

                        let qtyVal = parseFloat(item.available_qty || 0);
                        let qtyClass = qtyVal > 50 ? 'qty-high' : (qtyVal > 0 ? 'qty-medium' : 'qty-low');

                        resultsBox.innerHTML += `
                        <div class="batch-item-card select-batch" data-batch-code="${item.batch_code}" data-grade="${item.grade || ''}">
                            <div class="batch-item-left">
                                <div class="batch-item-title-row">
                                    <span class="batch-item-product">${item.product}</span>
                                    <span class="batch-item-code font-monospace">${item.batch_code}</span>
                                </div>
                                <div class="batch-item-subtitle-row">
                                    <span class="badge-premium-grade ${gradeClass}">${item.grade || 'N/A'}</span>
                                    <span class="batch-item-location text-muted"><i class="bi bi-geo-alt"></i> ${item.location}</span>
                                </div>
                            </div>
                            <div class="batch-item-right">
                                <div class="batch-item-qty-label">Available Qty</div>
                                <span class="badge-premium-qty ${qtyClass}">${qtyVal.toFixed(2)}</span>
                            </div>
                        </div>`;
                    });
                }
            })
            .catch(err => console.error("Error searching batch code in modal:", err));
        });
    }

    // Fetch batch stock details
    function fetchBatchStock() {
        const locationId = selectLocation.value;
        const productId = selectProduct.value;
        const batchCode = inputBatchCode.value;
        const grade = selectGrade.value;

        if (!locationId || !productId || !batchCode) return;

        inputNewQty.disabled = true;
        selectReason.disabled = true;
        textareaRemarks.disabled = true;
        btnSubmit.disabled = true;

        fetch(`/stock-adjustments/batch-stock?location_id=${locationId}&product_id=${productId}&batch_code=${batchCode}&grade=${grade}`)
            .then(res => res.json())
            .then(data => {
                inputAvailableQty.value = parseFloat(data.available_qty).toFixed(2);
                spanUnitLabel.textContent = data.unit || "pcs";
                
                // Reset inputs and activate
                inputNewQty.value = "";
                badgeDelta.className = "badge bg-secondary fs-6 px-3 py-2 w-100 text-center";
                badgeDelta.textContent = "0.00";
                
                inputNewQty.disabled = false;
                selectReason.disabled = false;
                textareaRemarks.disabled = false;
                inputNewQty.focus();
            })
            .catch(err => {
                console.error("Error fetching batch stock:", err);
                alert("Failed to fetch current stock for selected batch.");
            });
    }

    // Trigger batch stock fetch on manual changes of core attributes
    [selectLocation, selectProduct, selectGrade].forEach(el => {
        el.addEventListener("change", () => {
            if (inputBatchCode.value) {
                fetchBatchStock();
            }
        });
    });

    // Real-Time Calculations and Rules Validation
    inputNewQty.addEventListener("input", function () {
        const availableQty = parseFloat(inputAvailableQty.value) || 0;
        const newQtyVal = parseFloat(this.value);

        if (isNaN(newQtyVal)) {
            badgeDelta.className = "badge bg-secondary fs-6 px-3 py-2 w-100 text-center";
            badgeDelta.textContent = "-";
            feedbackSection.classList.add("d-none");
            btnSubmit.disabled = true;
            return;
        }

        const delta = newQtyVal - availableQty;
        const absDelta = Math.abs(delta);

        // Update Delta Badge
        if (delta < 0) {
            badgeDelta.className = "badge bg-danger fs-6 px-3 py-2 w-100 text-center";
            badgeDelta.innerHTML = `<i class="bi bi-arrow-down-short"></i> Deduction: ${delta.toFixed(2)}`;
        } else if (delta > 0) {
            badgeDelta.className = "badge bg-success fs-6 px-3 py-2 w-100 text-center";
            badgeDelta.innerHTML = `<i class="bi bi-arrow-up-short"></i> Addition: +${delta.toFixed(2)}`;
        } else {
            badgeDelta.className = "badge bg-secondary fs-6 px-3 py-2 w-100 text-center";
            badgeDelta.textContent = "No Change: 0.00";
        }

        feedbackSection.classList.remove("d-none");

        // 1. Safety Floor Check
        if (newQtyVal < 0) {
            isFormValid = false;
            alertBox.className = "alert alert-danger border-0 d-flex align-items-start gap-2 py-3 px-4 shadow-sm";
            alertIcon.className = "fs-4 bi bi-x-circle-fill text-danger";
            alertTitle.textContent = "Safety Floor Violation";
            alertText.textContent = "The reconciled quantity cannot be less than zero. Ledger balances cannot fall below physical availability.";
            btnSubmit.disabled = true;
            return;
        }

        // 2. Deviation Threshold Check
        // Calculate deviation percentage relative to available stock
        let deviation = 0;
        if (availableQty > 0) {
            deviation = (absDelta / availableQty) * 100;
        } else if (absDelta > 0) {
            // If starting from 0, any addition is a deviation
            deviation = 100;
        }

        const exceedsDeviationPercent = deviation > 5;
        const exceedsQuantityLimit = absDelta > 100;

        if (exceedsDeviationPercent || exceedsQuantityLimit) {
            isFormValid = true; // Still allow submission, but flag for approval
            alertBox.className = "alert alert-warning border-0 d-flex align-items-start gap-2 py-3 px-4 shadow-sm";
            alertIcon.className = "fs-4 bi bi-exclamation-triangle-fill text-warning";
            alertTitle.textContent = "Manager Approval Required";
            
            let ruleViolated = [];
            if (exceedsDeviationPercent) ruleViolated.push(`exceeds 5% deviation (${deviation.toFixed(1)}%)`);
            if (exceedsQuantityLimit) ruleViolated.push(`exceeds 100 units limit (${absDelta.toFixed(2)} ${spanUnitLabel.textContent})`);
            
            alertText.textContent = `This adjustment ${ruleViolated.join(" and ")}. It will be recorded as 'Pending Approval' and requires a manager's confirmation to hit the stock ledger.`;
        } else {
            isFormValid = true;
            alertBox.className = "alert alert-success border-0 d-flex align-items-start gap-2 py-3 px-4 shadow-sm";
            alertIcon.className = "fs-4 bi bi-check-circle-fill text-success";
            alertTitle.textContent = "Within Normal Tolerances";
            alertText.textContent = `The adjustment (${deviation.toFixed(1)}% deviation) is within standard limits. It will take effect immediately upon submission.`;
        }

        btnSubmit.disabled = !isFormValid;
    });

    // Form Submission
    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        // Clear old errors
        document.querySelectorAll(".text-danger.small").forEach(el => {
            el.innerText = "";
        });

        const availableQty = parseFloat(inputAvailableQty.value) || 0;
        const newQtyVal = parseFloat(inputNewQty.value);
        const delta = newQtyVal - availableQty;

        const payload = {
            location_id: selectLocation.value,
            product_id: selectProduct.value,
            batch_code: inputBatchCode.value,
            grade: selectGrade.value,
            original_qty: availableQty,
            adjusted_qty: delta,
            new_qty: newQtyVal,
            reason: selectReason.value,
            remarks: textareaRemarks.value
        };

        try {
            const response = await fetch("/stock-adjustments", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok) {
                if (result.errors) {
                    Object.keys(result.errors).forEach(field => {
                        const errorSpan = document.getElementById("adj_" + field + "_error");
                        if (errorSpan) {
                            errorSpan.innerText = result.errors[field][0];
                        }
                    });
                    
                    // Show general message if there is field error
                    alert("Validation Error: Please check the highlighted fields.");
                } else {
                    alert(result.message || "An error occurred while saving the stock adjustment.");
                }
                return;
            }

            alert(result.message || "✅ Stock adjustment recorded successfully!");
            
            // Reload page to reflect history and updated caches
            window.location.reload();

        } catch (err) {
            console.error("Error submitting stock adjustment:", err);
            alert("❌ Failed to save stock adjustment due to network or server error.");
        }
    });

    // Approval Button actions
    document.querySelectorAll(".btn-approve-adjustment").forEach(btn => {
        btn.addEventListener("click", async function (e) {
            e.preventDefault();
            const id = this.getAttribute("data-id");

            if (!confirm("Are you sure you want to approve this stock adjustment? This will post the adjustments directly to the immutable stock ledger.")) {
                return;
            }

            try {
                const response = await fetch(`/stock-adjustments/${id}/approve`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    alert(result.message || "Approval failed.");
                    return;
                }

                alert("✅ Adjustment approved and written to the ledger!");
                window.location.reload();

            } catch (err) {
                console.error("Error approving adjustment:", err);
                alert("❌ Failed to approve adjustment.");
            }
        });
    });
});
