document.addEventListener("DOMContentLoaded", function () {
    const addRowBtn = document.getElementById("add-grade-row");
    const tableBody = document.querySelector("#graded-outputs-table tbody");
    const emptyRowPlaceholder = document.querySelector(".empty-row-placeholder");
    const form = document.getElementById("stockSegregationForm");
    const submitBtn = document.getElementById("submit-segregation-btn");

    const batchCodeInput = document.getElementById("s_batch_code");
    const productNameInput = document.getElementById("s_product_name");
    const productIdInput = document.getElementById("s_product_id");
    const originalQtyInput = document.getElementById("s_original_qty");
    const availableQtyInput = document.getElementById("s_available_qty");
    const unitCostInput = document.getElementById("s_unit_cost");
    const unitInput = document.getElementById("s_unit");
    const locationSelect = document.getElementById("s_location_id");

    const totalOutputQtySpan = document.getElementById("total-output-qty");
    const remainingUnsortedQtySpan = document.getElementById("remaining-unsorted-qty");

    const container = document.getElementById("stockSegregationContainer");
    const gradesData = container && container.hasAttribute("data-grades") ? JSON.parse(container.getAttribute("data-grades") || "[]") : [];

    let activeUnit = "—";
    let activeCost = 0.00;

    // Handle search form submission in the batch code modal
    const searchForm = document.getElementById('batchCodeSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const form = e.target;
            const data = {
                product_listing: form.product_listing.value,
                location: form.location.value,
                dateFrom: form.dateFrom.value,
                unsegregated_only: true
            };

            fetch("/stock-segregation/search-batch-code", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(response => {
                    const tbody = document.querySelector('#batchCodeResults tbody');
                    tbody.innerHTML = "";

                    if (response.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" class="text-center">No results found.</td></tr>`;
                    } else {
                        response.forEach((item, index) => {
                            tbody.innerHTML += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${item.batch_code}</td>
                                <td>${item.product}</td>
                                <td>${item.grade}</td>
                                <td>${item.location}</td>
                                <td>${parseFloat(item.available_qty).toFixed(2)}</td>
                                <td><button class="btn btn-sm btn-success select-batch" data-batch-code="${item.batch_code}">Select</button></td>
                            </tr>`;
                        });
                    }
                })
                .catch(error => console.error("Search failed:", error));
        });
    }

    // Listen for select batch button clicks from the modal
    const batchCodeResultsBody = document.querySelector('#batchCodeResults tbody');
    if (batchCodeResultsBody) {
        batchCodeResultsBody.addEventListener('click', function (e) {
            const target = e.target;
            if (target && target.classList.contains('select-batch')) {
                e.preventDefault();
                const batchCode = target.getAttribute('data-batch-code');
                const selectedLocationId = document.querySelector('#location').value;

                if (batchCode) {
                    loadBatchDetails(batchCode, selectedLocationId);
                }
            }
        });
    }

    // Function to load details of the selected batch
    async function loadBatchDetails(batchCode, locationId) {
        try {
            // Retrieve details (product name, original qty, available qty, unit, cost)
            const response = await fetch(`/stock-segregation/batch-details?batch=${encodeURIComponent(batchCode)}&location_id=${encodeURIComponent(locationId)}`);
            if (!response.ok) {
                throw new Error("Failed to load batch details");
            }
            const data = await response.json();

            // Populate form fields
            batchCodeInput.value = batchCode;
            productNameInput.value = data.product_name || "—";
            productIdInput.value = data.product_id || "";
            originalQtyInput.value = data.original_qty || "0.00";
            availableQtyInput.value = data.available_qty || "0.00";
            unitCostInput.value = parseFloat(data.unit_cost || 0).toFixed(2);
            unitInput.value = data.unit || "—";
            
            // Set location dropdown value to match search
            if (locationSelect) {
                locationSelect.value = locationId;
            }

            activeUnit = data.unit || "—";
            activeCost = parseFloat(data.unit_cost || 0);

            // Update unit labels in table
            document.querySelectorAll(".s-unit-label").forEach(el => {
                el.innerText = activeUnit;
            });

            // Close modal
            const modalEl = document.getElementById('staticBackdropBatchCode');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }

            // Reset dynamic rows since parent changed
            tableBody.innerHTML = "";
            tableBody.appendChild(emptyRowPlaceholder);
            emptyRowPlaceholder.style.display = "table-row";

            calculateTotals();

        } catch (error) {
            console.error("Error loading batch details:", error);
            alert("❌ Error loading batch details. Please try again.");
        }
    }

    // Dynamic output rows handling
    if (addRowBtn) {
        addRowBtn.addEventListener("click", function () {
            if (!batchCodeInput.value) {
                alert("⚠️ Please select a parent batch first using the 'Search Parent Batch' button.");
                return;
            }

            // Remove placeholder if present
            if (emptyRowPlaceholder && emptyRowPlaceholder.parentNode === tableBody) {
                emptyRowPlaceholder.style.display = "none";
            }

            const defaultGrades = [
                { code: "A", name: "Grade A" },
                { code: "B", name: "Grade B" },
                { code: "C", name: "Grade C" },
                { code: "Waste", name: "Wastage / Reject" }
            ];
            const gradesList = gradesData.length > 0 ? gradesData : defaultGrades;
            let gradesOptionsHtml = '<option value="" disabled selected>Select grade</option>';
            gradesList.forEach(g => {
                gradesOptionsHtml += `<option value="${g.code}">${g.name}</option>`;
            });

            const rowId = 'row_' + Date.now();
            const tr = document.createElement("tr");
            tr.id = rowId;
            tr.className = "grade-output-row";

            tr.innerHTML = `
                <td>
                    <select class="form-select target-grade" required>
                        ${gradesOptionsHtml}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" class="form-control target-qty" placeholder="0.00" required>
                </td>
                <td>
                    <input type="text" class="form-control target-unit" readonly required value="${activeUnit}">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" class="form-control target-cost" required value="${activeCost.toFixed(2)}">
                </td>
                <td>
                    <input type="text" class="form-control target-remarks" placeholder="Optional remarks">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-row-btn"><i class="bi bi-trash"></i></button>
                </td>
            `;

            tableBody.appendChild(tr);

            // Add events
            tr.querySelector(".remove-row-btn").addEventListener("click", function () {
                tr.remove();
                checkEmptyState();
                calculateTotals();
            });

            tr.querySelector(".target-qty").addEventListener("input", function () {
                calculateTotals();
            });

            calculateTotals();
        });
    }

    function checkEmptyState() {
        const rows = tableBody.querySelectorAll(".grade-output-row");
        if (rows.length === 0 && emptyRowPlaceholder) {
            emptyRowPlaceholder.style.display = "table-row";
        }
    }

    function calculateTotals() {
        const rows = tableBody.querySelectorAll(".grade-output-row");
        let totalQty = 0;

        rows.forEach(row => {
            const qtyInput = row.querySelector(".target-qty");
            if (qtyInput && qtyInput.value) {
                totalQty += parseFloat(qtyInput.value);
            }
        });

        const availableQty = parseFloat(availableQtyInput.value || 0);
        const remainingQty = availableQty - totalQty;

        totalOutputQtySpan.innerText = totalQty.toFixed(2);
        remainingUnsortedQtySpan.innerText = remainingQty.toFixed(2);

        // Styling and Submit Button validation
        if (remainingQty < 0) {
            remainingUnsortedQtySpan.className = "text-danger fw-bold";
            submitBtn.disabled = true;
        } else if (rows.length === 0 || totalQty <= 0) {
            remainingUnsortedQtySpan.className = "text-muted";
            submitBtn.disabled = true;
        } else {
            remainingUnsortedQtySpan.className = "text-success fw-bold";
            submitBtn.disabled = false;
        }
    }

    // Submit handler
    if (form) {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            // Clear errors
            document.querySelectorAll(".text-danger.small").forEach(el => el.innerText = "");

            const rows = tableBody.querySelectorAll(".grade-output-row");
            const outputs = [];

            rows.forEach(row => {
                outputs.push({
                    grade: row.querySelector(".target-grade").value,
                    quantity: row.querySelector(".target-qty").value,
                    unit: row.querySelector(".target-unit").value,
                    unit_cost: row.querySelector(".target-cost").value,
                    remarks: row.querySelector(".target-remarks").value
                });
            });

            const payload = {
                segregation_date: document.getElementById("segregation_date").value,
                location_id: locationSelect.value,
                parent_batch_code: batchCodeInput.value,
                product_id: productIdInput.value,
                remarks: document.getElementById("remarks").value,
                outputs: outputs
            };

            try {
                const response = await fetch("/stock-segregation/store", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok) {
                    if (result.errors) {
                        Object.keys(result.errors).forEach(field => {
                            const errorSpan = document.getElementById("error-" + field);
                            if (errorSpan) {
                                errorSpan.innerText = result.errors[field][0];
                            }
                        });
                    } else {
                        alert(result.message || "Failed to save stock segregation.");
                    }
                    return;
                }

                alert("✅ Stock segregation saved successfully!");
                location.reload();

            } catch (error) {
                console.error("Submission failed:", error);
                alert("❌ Failed to save stock segregation due to an unexpected error.");
            }
        });
    }
});
