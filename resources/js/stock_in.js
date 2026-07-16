import { helpers } from './common.js'; // <-- Use this or remove it if not 
import './stock_out.js'; // <-- Use this or remove it if not 




const movementType = document.getElementById('movementType');
const stockInFields = document.getElementById('stockInFields');
const stockOutFields = document.getElementById('stockOutFields');
const inType = document.getElementById('in_type');
const returnDetailsSection = document.getElementById('returnDetailsSection');
const returnReasonRow = document.getElementById('returnReasonRow');
const returnCustomerRow = document.getElementById('returnCustomerRow');
const returnFleetRow = document.getElementById('returnFleetRow');

function updateFormDisplay() {
    const moveType = movementType.value;
    const inTypeValue = inType.value;
    const referenceNo = document.getElementById("referenceNo");

    if (moveType === "in") {
        stockInFields.classList.remove("d-none");
        stockOutFields.classList.add("d-none");
        document.getElementById('stock_in_type').classList.remove("d-none");
        inType.disabled = false;

        document.querySelectorAll(".product-row").forEach(row => {
            const batchCol = row.querySelector("#batch_code_wrapper");
            const vendorCol = row.querySelector("#vendor_col");
            const invoiceCol = row.querySelector("#invoice_number_col");
            const purchaseDateCol = row.querySelector("#purchase_date_col");
            const locationCol = row.querySelector("#location_col");
            const totalCol = row.querySelector("#total_col");
            const unitCostCol = row.querySelector("#unit_cost_col");

            if (batchCol) batchCol.classList.add("d-none");
            if (vendorCol) vendorCol.classList.remove("d-none");
            if (invoiceCol) invoiceCol.classList.remove("d-none");
            if (purchaseDateCol) purchaseDateCol.classList.remove("d-none");

            if (locationCol) locationCol.className = "col-md-4";
            if (totalCol) totalCol.className = "col-md-6";
            if (purchaseDateCol) purchaseDateCol.className = "col-md-6";
            if (unitCostCol) unitCostCol.className = "col-md-3";

            // Auto-select "Unsorted" grade for Stock In if available
            const gradeSelect = row.querySelector("select[name='products[][grade]']");
            if (gradeSelect) {
                const unsortedOpt = Array.from(gradeSelect.options).find(opt => 
                    opt.value.toLowerCase() === 'unsorted' || opt.text.toLowerCase() === 'unsorted'
                );
                if (unsortedOpt) {
                    gradeSelect.value = unsortedOpt.value;
                }
            }
        });

        if (referenceNo) {
            referenceNo.closest(".col-md-6").classList.remove("d-none");
        }

    } else if (moveType === "out") {
        stockOutFields.classList.remove("d-none");
        stockInFields.classList.add("d-none");
        document.getElementById('stock_in_type').classList.add("d-none");
        inType.disabled = true;

        document.querySelectorAll(".product-row").forEach(row => {
            const batchCol = row.querySelector("#batch_code_wrapper");
            const vendorCol = row.querySelector("#vendor_col");
            const invoiceCol = row.querySelector("#invoice_number_col");
            const purchaseDateCol = row.querySelector("#purchase_date_col");
            const locationCol = row.querySelector("#location_col");
            const totalCol = row.querySelector("#total_col");
            const unitCostCol = row.querySelector("#unit_cost_col");

            if (batchCol) {
                batchCol.classList.remove("d-none");
                batchCol.className = "col-md-3";
            }
            if (vendorCol) vendorCol.classList.add("d-none");
            if (invoiceCol) invoiceCol.classList.add("d-none");
            if (purchaseDateCol) purchaseDateCol.classList.add("d-none");

            if (locationCol) locationCol.className = "col-md-3";
            if (totalCol) totalCol.className = "col-md-3";
            if (unitCostCol) unitCostCol.className = "col-md-3";
        });

        if (referenceNo) {
            referenceNo.closest(".col-md-6").classList.add("d-none");
        }
    } else {
        stockInFields.classList.add("d-none");
        stockOutFields.classList.add("d-none");

        if (referenceNo) {
            referenceNo.closest(".col-md-6").classList.remove("d-none");
        }

        document.querySelectorAll(".product-row").forEach(row => {
            const batchCol = row.querySelector("#batch_code_wrapper");
            const vendorCol = row.querySelector("#vendor_col");
            const invoiceCol = row.querySelector("#invoice_number_col");
            const purchaseDateCol = row.querySelector("#purchase_date_col");
            const locationCol = row.querySelector("#location_col");
            const totalCol = row.querySelector("#total_col");
            const unitCostCol = row.querySelector("#unit_cost_col");

            if (batchCol) batchCol.classList.add("d-none");
            if (vendorCol) vendorCol.classList.remove("d-none");
            if (invoiceCol) invoiceCol.classList.remove("d-none");
            if (purchaseDateCol) purchaseDateCol.classList.remove("d-none");

            if (locationCol) locationCol.className = "col-md-4";
            if (totalCol) totalCol.className = "col-md-6";
            if (purchaseDateCol) purchaseDateCol.className = "col-md-6";
            if (unitCostCol) unitCostCol.className = "col-md-3";
        });
    }
}



inType.addEventListener('change', handleInTypeChange);

function handleInTypeChange() {
    const selectedValue = this.value;

    if (selectedValue === "return") {
        returnDetailsSection.classList.remove('d-none');
        // returnReasonRow.classList.remove('d-none');
        // returnCustomerRow.classList.remove('d-none');
    } else {
        returnDetailsSection.classList.add('d-none');
        // returnReasonRow.classList.add('d-none');
        // returnCustomerRow.classList.add('d-none');
    }

    // Fetch new reference number
    let url = selectedValue === "return"
        ? '/stock-return-reference-id'
        : selectedValue === "purchase"
            ? '/stock-purchase-reference-id'
            : null;

    if (url) {
        fetch(url)
            .then(response => response.ok ? response.json() : Promise.reject(response))
            .then(data => {
                document.getElementById("referenceNo").value = data.reference_no;
            })
            .catch(error => console.error("Fetch error:", error));
    }
}

const returnSourceType = document.getElementById('returnSource');


returnSourceType.addEventListener('change', handleReturnTypeChange);

function handleReturnTypeChange() {
    const selectedValue = this.value;

    if (selectedValue === "fleet") {
        console.log('fleet chosen')
        //show fleet and hide customer
        returnFleetRow.classList.remove('d-none');

    } else if (selectedValue === "customer") {

        console.log('customer chosen')
        //show customer and hide fleet


        // returnFleetRow.classList.add('d-none');   
    }

    // Fetch new reference number
    let url = selectedValue === "return"
        ? '/stock-return-reference-id'
        : selectedValue === "purchase"
            ? '/stock-purchase-reference-id'
            : null;

    if (url) {
        fetch(url)
            .then(response => response.ok ? response.json() : Promise.reject(response))
            .then(data => {
                document.getElementById("referenceNo").value = data.reference_no;
            })
            .catch(error => console.error("Fetch error:", error));
    }
}

// document.getElementById('addStockOutProductRowBtn').addEventListener('click', () => {
//     const container = document.getElementById('stockOutRowsContainer');
//     const row = container.querySelector('.product-row');
//     const clone = row.cloneNode(true);

//     // Optional: Clear inputs
//     clone.querySelectorAll('input, select, textarea').forEach(input => {
//         if (input.tagName === 'SELECT') input.selectedIndex = 0;
//         else input.value = '';
//     });  

//     container.appendChild(clone);
// });


document.getElementById('addProductRowBtn').addEventListener('click', () => {
    const container = document.getElementById('productRowsContainer');
    const row = container.querySelector('.product-row');
    const clone = row.cloneNode(true);

    // Optional: Clear inputs
    clone.querySelectorAll('input, select, textarea').forEach(input => {
        if (input.tagName === 'SELECT') input.selectedIndex = 0;
        else input.value = '';
    });

    container.appendChild(clone);
    updateFormDisplay();
});

document.getElementById('productRowsContainer').addEventListener('click', function (e) {
    const btn = e.target.closest('.removeRowBtn');
    if (btn) {
        const rows = document.querySelectorAll('.product-row');
        if (rows.length > 1) {
            btn.closest('.product-row').remove();
        }
    }
});

document.getElementById("stockMovementForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    const form = this;

    // Clear previous error messages
    form.querySelectorAll('span').forEach(span => span.textContent = "");
    document.getElementById('error-movementType').textContent = "";

    const stockType = document.getElementById("movementType").value;
    const movementDate = document.getElementById("movement_date").value;
    const referenceNo = document.getElementById("referenceNo").value.trim();

    if (!stockType) {
        document.getElementById('error-movementType').textContent = 'Please select Stock movement type and submit again';
        return;
    }

    const payload = {
        stock_type: stockType,
        reference_no: referenceNo,
        movement_date: movementDate,
        items: []
    };

    if (stockType === "in") {
        const inTypeVal = document.getElementById("in_type").value;
        payload.in_type = inTypeVal;

        if (inTypeVal === "return") {
            payload.return_source = document.getElementById("returnSource").value;
            payload.return_reason = document.getElementById("return_reason").value.trim();
            payload.customer_name = document.getElementById("customer_name").value.trim();
            payload.customer_contact = document.getElementById("customer_contact").value.trim();
        }
    } else {
        payload.destination = document.getElementById("destination").value.trim();
        payload.out_type = document.getElementById("outType").value;
    }

    document.querySelectorAll(".product-row").forEach(row => {
        payload.items.push({
            product_id: row.querySelector("select[name='products[][product]']").value.trim(),
            /**
             * Todo - 1.Modify product_name later and collect product name
             *          from data or query in backend
             */
            product_name: row.querySelector("select[name='products[][product]']").options[
                row.querySelector("select[name='products[][product]']").selectedIndex
            ].text.trim().toLowerCase(),

            location_id: row.querySelector("select[id='location_id']")?.value || row.querySelector("#location_id")?.value || '',
            grade: row.querySelector("select[name='products[][grade]']").value,
            quantity: row.querySelector("input[name='products[][quantity]']").value.trim(),
            unit: row.querySelector("[name='products[][unit]']").value.trim(),
            unit_cost: row.querySelector("input[name='products[][unit_cost]']").value.trim(),
            total: row.querySelector("input[name='products[][total]']").value.trim(),
            remarks: row.querySelector("textarea[name='products[][remarks]']").value.trim(),
            vendor: row.querySelector("input[name='products[][vendor]']").value.trim(),
            invoice_number: row.querySelector("input[name='products[][invoice_number]']").value.trim(),
            purchase_date: row.querySelector("input[name='products[][purchase_date]']").value
        });
    });

    console.log(payload)
    console.log(movementType + "Movement type is")
    let movement = document.getElementById('movementType').value;

    if (movement == "out") {
        await processStockOut(form);
        console.log("phase 1 okay")
    }
    else {
        console.log('else block executed')
        await stockPurchase(payload, stockType, form);

    }
});

async function stockPurchase(payload, stockType, form) {

    console.log("stock in method executed")

    try {
        const response = await fetch(`/stock-in-entry`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'Accept': 'application/json',
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (!response.ok && result.errors) {
            // Field-level errors
            for (const key in result.errors) {
                if (!key.startsWith('items')) {
                    let elementId = key;
                    if (key === 'stock_type') elementId = 'movementType';
                    else if (key === 'reference_no') elementId = 'referenceNo';
                    else if (key === 'in_type') elementId = 'in_type';
                    
                    const errorElement = document.getElementById(`error-${elementId}`);
                    if (errorElement) {
                        errorElement.textContent = result.errors[key][0];
                    }
                }
            }

            Object.entries(result.errors).forEach(([key, messages]) => {
                const match = key.match(/^items\.(\d+)\.(\w+)$/);
                if (match) {
                    let [_, rowIndex, field] = match;
                    if (field === 'product_id' || field === 'product_name') {
                        field = 'product';
                    }
                    const row = document.querySelectorAll(".product-row")[rowIndex];
                    if (row) {
                        const errorSpan = row.querySelector(`.error-${field}`);
                        if (errorSpan) {
                            errorSpan.textContent = messages[0];
                        }
                    }
                }
            });
        } else if (!response.ok && result.message) {
            alert(`Error: ${result.message}`);
        } else {
            alert(`Stock ${stockType.toUpperCase()} entry successful`);
            form.reset();

            // Reset UI state
            stockInFields.classList.remove('d-none');
            stockOutFields.classList.add('d-none');
        }
    } catch (error) {
        console.error("Error submitting form:", error);
        alert("An unexpected error occurred.");
    }
}


async function processStockOut(form) {
    const movementTypeVal = document.getElementById('movementType').value;
    const movementDate = document.getElementById('movement_date').value;
    const outTypeVal = document.getElementById('outType').value;
    const destinationVal = document.getElementById('destination').value;
    const referenceNoVal = document.getElementById('referenceNo').value;

    let out_payload = {
        stock_type: movementTypeVal,
        movement_date: movementDate,
        out_type: outTypeVal,
        destination: destinationVal,
        reference_no: referenceNoVal,
        items: [] // initialize the array to hold product rows
    };

    document.querySelectorAll(".product-row").forEach(row => {
        const selectEl = row.querySelector("select[name='products[][product]']");
        const productName = selectEl.options[selectEl.selectedIndex]?.text.trim().toLowerCase() || '';
        const locationSelect = row.querySelector("select[id='location_id']") || row.querySelector("#location_id");
        const locationIdVal = locationSelect ? locationSelect.value : '';

        const item = {
            product_id: selectEl.value.trim(),
            product_name: productName,
            batch_code: row.querySelector("input[name='products[][batch_code]']").value,
            location_id: locationIdVal,
            grade: row.querySelector("select[name='products[][grade]']").value,
            quantity: row.querySelector("input[name='products[][quantity]']").value.trim(),
            unit: row.querySelector("[name='products[][unit]']").value.trim(),
            unit_cost: row.querySelector("input[name='products[][unit_cost]']").value.trim(),
            total: row.querySelector("input[name='products[][total]']").value.trim(),
            remarks: row.querySelector("textarea[name='products[][remarks]']").value.trim(),
        };

        out_payload.items.push(item); // push the item into the payload
    });

    console.log(out_payload)

    try {
        const response = await fetch(`/stock-out-entry`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(out_payload)
        });

        const result = await response.json();

        // Clear previous errors
        document.querySelectorAll('.text-danger').forEach(span => span.textContent = '');

        if (!response.ok && result.errors) {
            // Handle top-level (non-item-specific) errors
            for (const key in result.errors) {
                if (!key.startsWith('items')) {
                    let elementId = key;
                    if (key === 'stock_type') elementId = 'movementType';
                    else if (key === 'reference_no') elementId = 'referenceNo';
                    else if (key === 'out_type') elementId = 'outType';
                    
                    const errorElement = document.getElementById(`error-${elementId}`);
                    if (errorElement) {
                        errorElement.textContent = result.errors[key][0];
                    }
                }
            }

            // Handle item-specific field errors (e.g., items.0.product)
            Object.entries(result.errors).forEach(([key, messages]) => {
                const match = key.match(/^items\.(\d+)\.(\w+)$/);
                if (match) {
                    let [_, rowIndex, field] = match;
                    if (field === 'product_id' || field === 'product_name') {
                        field = 'product';
                    }
                    const row = document.querySelectorAll(".product-row")[rowIndex];
                    if (row) {
                        const errorSpan = row.querySelector(`.error-${field}`);
                        if (errorSpan) {
                            errorSpan.textContent = messages[0];
                        }
                    }
                }
            });
        } else if (!response.ok && result.message) {
            alert(`Error: ${result.message}`);
        } else {
            alert("Stock OUT entry successful");

            // Reset form UI if passed as parameter
            if (form) form.reset();

            // Reset UI state (if needed, adapt based on your layout)
            const stockInFields = document.getElementById('stockInFields');
            const stockOutFields = document.getElementById('stockOutFields');
            if (stockInFields && stockOutFields) {
                stockInFields.classList.remove('d-none');
                stockOutFields.classList.add('d-none');
            }
        }
    } catch (error) {
        console.error("Error submitting stock out:", error);
        alert("An unexpected error occurred.");
    }
}



/**
 * Batch code is currently working only on single product stock out
 * will not work on dynamically generated product items
 * Result of the fetch call is append to input with id batch_code 
 */








document.getElementById('batchCodeSearchForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const form = e.target;
    const data = {
        product_listing: form.product_listing.value,
        location: form.location.value,
        dateFrom: form.dateFrom.value
    };

    fetch("/stock-out/search-batch-code", {
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

// Track which batch code input triggered the modal
let currentBatchInput = null;

document.addEventListener('click', function (e) {
    if (e.target && e.target.closest('#batch_code_wrapper')) {
        const wrapper = e.target.closest('#batch_code_wrapper');
        const input = wrapper.querySelector('input');
        if (input) {
            currentBatchInput = input;

            const row = wrapper.closest('.product-row');
            if (row) {
                const productSelect = row.querySelector("select[name='products[][product]']");
                const productId = productSelect ? productSelect.value : '';

                const locationSelect = row.querySelector("select[id='location_id']") || row.querySelector("#location_id");
                const locationId = locationSelect ? locationSelect.value : '';

                const modalProductSelect = document.querySelector('#batchCodeSearchForm select[name="product_listing"]');
                const modalLocationSelect = document.querySelector('#batchCodeSearchForm select[name="location"]');

                if (modalProductSelect && productId) modalProductSelect.value = productId;
                if (modalLocationSelect && locationId) modalLocationSelect.value = locationId;

                // Clear previous results
                const listContainer = document.getElementById('batchCodeListResults');
                if (listContainer) listContainer.innerHTML = '';

                // Auto-trigger search if both product and location are ready
                if (modalProductSelect && modalLocationSelect && productId && locationId) {
                    setTimeout(() => {
                        document.getElementById('search_batch_code')?.click();
                    }, 100);
                }
            }
        }
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const listContainer = document.getElementById('batchCodeListResults');
    if (!listContainer) return;

    listContainer.addEventListener('click', function (e) {
        const card = e.target.closest('.select-batch');
        if (card && currentBatchInput) {
            e.preventDefault();
            const batchCode = card.getAttribute('data-batch-code');
            const grade = card.getAttribute('data-grade');

            currentBatchInput.value = batchCode;
            const row = currentBatchInput.closest('.product-row');
            if (row) {
                const gradeSelect = row.querySelector("select[name='products[][grade]']");
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
            }

            const modalEl = document.getElementById('staticBackdropBatchCode');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        }
    });
});

document.addEventListener("DOMContentLoaded", () => {
    // Event listeners
    const movementTypeEl = document.getElementById("movementType");
    const inTypeEl = document.getElementById("in_type");

    if (movementTypeEl) {
        movementTypeEl.addEventListener("change", updateFormDisplay);
    }
    if (inTypeEl) {
        inTypeEl.addEventListener("change", updateFormDisplay);
    }

    // Initial call (handles pre-filled data)
    updateFormDisplay();
});


// show input type date on click anywhere on input of type date
document.querySelectorAll('input[type="date"]').forEach(input => {
    input.addEventListener('focus', () => {
        // Trigger the date picker
        input.showPicker?.(); // Modern browsers support showPicker()
    });

    // Optional: also open on click, for older behavior
    input.addEventListener('click', () => {
        input.showPicker?.();
    });
});
