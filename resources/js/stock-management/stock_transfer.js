document.addEventListener("DOMContentLoaded", function () {
    getLocations();
});


document.getElementById('batchCodeSearchForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const form = e.target;
    const data = {
        product_listing: form.product_listing.value,
        location: form.location.value,
        dateFrom: form.dateFrom.value
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
            const listContainer = document.getElementById('batchCodeListResults');
            if (!listContainer) return;
            listContainer.innerHTML = "";
            const filterContainer = document.getElementById('modalQuickFilterContainer');
            const filterInput = document.getElementById('modalQuickFilter');
            if (filterInput) filterInput.value = ""; // Reset filter input

            if (response.length === 0) {
                if (filterContainer) filterContainer.classList.add('d-none');
                listContainer.innerHTML = `
                <div class="premium-empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    <div class="premium-empty-state-title">No Batches Found</div>
                    <div class="premium-empty-state-text">No available batches match the selected product and location.</div>
                </div>`;
            } else {
                if (filterContainer) filterContainer.classList.remove('d-none');
                response.forEach((item) => {
                    // Determine Grade Badge Class
                    let gradeClass = 'badge-grade-unsorted';
                    let gradeLower = (item.grade || '').toLowerCase();
                    if (gradeLower === 'a' || gradeLower === 'big') {
                        gradeClass = 'badge-grade-a';
                    } else if (gradeLower === 'b' || gradeLower === 'small') {
                        gradeClass = 'badge-grade-b';
                    } else if (gradeLower === 'c') {
                        gradeClass = 'badge-grade-c';
                    } else if (gradeLower === 'waste' || gradeLower === 'reject') {
                        gradeClass = 'badge-grade-waste';
                    }

                    // Determine Qty Badge Class
                    let qtyVal = parseFloat(item.available_qty || 0);
                    let qtyClass = 'qty-low';
                    if (qtyVal > 50) {
                        qtyClass = 'qty-high';
                    } else if (qtyVal > 0) {
                        qtyClass = 'qty-medium';
                    }

                    listContainer.innerHTML += `
                    <div class="batch-item-card select-batch" data-batch-code="${item.batch_code}" data-grade="${item.grade || ''}" data-product-id="${item.product_id || ''}" data-unit="${item.unit || ''}">
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

            // Set up Quick Filter logic if not already configured
            if (filterInput && !filterInput.dataset.listenerAttached) {
                filterInput.dataset.listenerAttached = "true";
                filterInput.addEventListener('input', function() {
                    const val = this.value.toLowerCase().trim();
                    const cards = listContainer.querySelectorAll('.batch-item-card');
                    cards.forEach(card => {
                        const text = card.textContent.toLowerCase();
                        if (text.includes(val)) {
                            card.classList.remove('d-none');
                        } else {
                            card.classList.add('d-none');
                        }
                    });
                });
            }
        })
        .catch(error => console.error("Search failed:", error));
});

document.addEventListener('DOMContentLoaded', function () {
    const listContainer = document.getElementById('batchCodeListResults');
    if (!listContainer) return;

    listContainer.addEventListener('click', function (e) {
        const card = e.target.closest('.select-batch');
        if (card) {
            e.preventDefault();
            const batchCode = card.getAttribute('data-batch-code');
            const grade = card.getAttribute('data-grade');
            const productId = card.getAttribute('data-product-id');
            const unit = card.getAttribute('data-unit');
            const batchCodeInput = document.getElementById('t_batch_code');

            if (batchCodeInput) {
                batchCodeInput.value = batchCode;
                
                const gradeSelect = document.getElementById('t_grade');
                if (gradeSelect && grade) {
                    gradeSelect.value = grade;
                    if (!gradeSelect.value || gradeSelect.selectedIndex <= 0) {
                        const opt = Array.from(gradeSelect.options).find(o => 
                            o.text.trim().toLowerCase() === grade.toLowerCase() || 
                            o.value.trim().toLowerCase() === grade.toLowerCase()
                        );
                        if (opt) gradeSelect.value = opt.value;
                    }
                }
                
                const productSelect = document.getElementById('t_product_name');
                if (productSelect && productId) {
                    productSelect.value = productId;
                }
                
                const unitSelect = document.getElementById('t_unit');
                if (unitSelect) {
                    unitSelect.value = unit || '';
                }
            }

            const modalEl = document.getElementById('staticBackdropBatchCode');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        }
    });
});


function getLocations() {
    fetch("/api-locations")
        .then(response => response.json())
        .then(data => {
            // Get all three selects
            let fromSelect = document.getElementById("t_fromLocation");
            let toSelect = document.getElementById("t_toLocation");
            let locationSelect = document.getElementById("location");

            // Check existence before appending
            [fromSelect, toSelect, locationSelect].forEach(select => {
                if (!select) return;

                // Clear existing options except placeholder
                select.length = 1; // keeps "Select ..."

                // Append options
                data.forEach(loc => {
                    select.add(new Option(loc.name, loc.id));
                });
            });
        })
        .catch(error => console.error("Error fetching locations:", error));

}


document.addEventListener("DOMContentLoaded", () => {
    const submitBtn = document.getElementById("submit_stock_transfer");

    submitBtn.addEventListener("click", async (e) => {
        e.preventDefault();

        // clear old errors
        document.querySelectorAll(".text-danger.small").forEach(el => {
            el.innerText = "";
        });

        const payload = {
            t_transferDate: document.getElementById("t_transferDate").value,
            t_transferType: document.getElementById("t_transferType").value,
            t_fromLocation: document.getElementById("t_fromLocation").value,
            t_toLocation: document.getElementById("t_toLocation").value,
            t_product_name: document.getElementById("t_product_name").value,
            t_batch_code: document.getElementById("t_batch_code").value,
            t_grade: document.getElementById("t_grade").value,
            t_quantity: document.getElementById("t_quantity").value,
            t_unit: document.getElementById("t_unit").value,
            t_textarea: document.getElementById("t_textarea").value,
        };

        try {
            const response = await fetch("/stock-transfer", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (!response.ok) {
                if (result.errors) {
                    Object.keys(result.errors).forEach((field) => {
                        const errorSpan = document.getElementById(field + "_error");
                        if (errorSpan) {
                            errorSpan.innerText = result.errors[field][0];
                        }
                    });
                } else {
                    alert(result.message || "Something went wrong");
                }
                return;
            }

            alert("✅ Stock transfer saved successfully!");
            document.querySelector("form")?.reset();

        } catch (err) {
            console.error("Error submitting stock transfer:", err);
            alert("❌ Failed to save stock transfer.");
        }
    });
});
