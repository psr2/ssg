/****Dynamic row in sale item modal */
const addItemBtn = document.getElementById("addItemBtn");
const itemsTableBody = document.querySelector("#itemsTable tbody");
const grandTotalInput = document.getElementById("grand_total");
let productList = null;
document.addEventListener("DOMContentLoaded", async function () {


    // Function to recalc grand total
    function recalcGrandTotal() {
        let grandTotal = 0;
        itemsTableBody.querySelectorAll("tr").forEach(row => {
            const totalField = row.querySelector(".item-total");
            if (totalField && totalField.value) {
                grandTotal += parseFloat(totalField.value) || 0;
            }
        });
        grandTotalInput.value = grandTotal.toFixed(2);
    }

    // Function to add new row
    function addRow() {
        let rowIndex = itemsTableBody.querySelectorAll("tr").length;
        const row = document.createElement("tr");
        row.setAttribute("data-index", rowIndex);

        let options = '<option selected disabled>select</option>';
        if (productList) {
            let list = productList;
            if (typeof list === 'string') {
                try { list = JSON.parse(list); } catch (e) { }
            }
            if (Array.isArray(list)) {
                list.forEach((product) => {
                    options += `<option value="${product.id}">${product.name}</option>`;
                });
            }
        }

        const productListTd = `<td style="width:15%;">
            <select class="form-select item-product" name="items[product][]" required>
                ${options}
            </select>
            <span class="error error-items-${rowIndex}-product text-danger text-small"></span>
        </td>`;

        let unitOpts = '<option selected disabled>select</option>';
        if (unitList && Array.isArray(unitList) && unitList.length > 0) {
            unitList.forEach(u => {
                unitOpts += `<option value="${u.abbreviation}">${u.abbreviation}</option>`;
            });
        } else {
            unitOpts += `<option value="kg">kg</option><option value="pcs">pcs</option>`;
        }

        row.innerHTML = `${productListTd}
            <td style="width:20%;">
                 <input type="text" readonly class="form-control item-batch-code" name="items[batch_code][]" data-bs-toggle="modal" data-bs-target="#staticBackdropBatchCode">
                <span class="error error-items-${rowIndex}-batch_code text-danger text-small"></span>
            </td>
            <td style="width:15%;">
                 <select class="form-select item-grade" name="items[grade][]">
                    <option selected disabled>select</option>
                    <option value="A">Grade A</option>
                    <option value="B">Grade B</option>
                    <option value="C">Grade C</option>
                    <option value="Waste">Waste</option>
                </select>
                <span class="error error-items-${rowIndex}-grade text-danger text-small"></span>
            </td>
            <td>
                <input type="number" class="form-control item-qty" name="items[qty][]" min="0" step="any" required>
                <span class="error error-items-${rowIndex}-quantity text-danger text-small"></span>
            </td>
            <td style="width:15%;">
                <select class="form-select item-unit" name="items[unit][]">
                    ${unitOpts}
                </select>
                <span class="error error-items-${rowIndex}-unit text-danger text-small"></span>
            </td>
            <td>
                <input type="number" class="form-control item-price" name="items[unit_price][]" min="0" step="any" required>
                <span class="error error-items-${rowIndex}-unit_price text-danger text-small"></span>
            </td>
            <td>
                <input type="number" class="form-control item-total" name="items[total][]" readonly>
                <span class="error error-items-${rowIndex}-total_price text-danger text-small"></span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger removeRowBtn"><i class="bi-trash"></i></button>
            </td>
        `;

        const qtyInput = row.querySelector(".item-qty");
        const priceInput = row.querySelector(".item-price");
        const totalInput = row.querySelector(".item-total");

        function recalcRowTotal() {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            totalInput.value = (qty * price).toFixed(2);
            recalcGrandTotal();
        }

        qtyInput.addEventListener("input", recalcRowTotal);
        priceInput.addEventListener("input", recalcRowTotal);

        row.querySelector(".removeRowBtn").addEventListener("click", function () {
            row.remove();
            recalcGrandTotal();
            reindexRows();
        });

        itemsTableBody.appendChild(row);
    }

    // Re-index rows after removal
    function reindexRows() {
        itemsTableBody.querySelectorAll("tr").forEach((row, newIndex) => {
            row.setAttribute("data-index", newIndex);
            row.querySelectorAll("span.error").forEach(span => {
                span.className = span.className.replace(/error-items-\d+-/, `error-items-${newIndex}-`);
            });
        });
    }

    // Add row when button clicked
    addItemBtn.addEventListener("click", addRow);

    await getProductlist();
    await getUnitlist();
    addRow();
});

/**
 * Sends an asynchronous fetch request to retrieve all customer names 
 * associated with a given shop ID. 
 * 
 * Primarily used to power the fuzzy customer search functionality with Fuse.js.
 */

document.addEventListener("click", (e) => {
    if (e.target && e.target.classList.contains("btn-launch")) {
        e.preventDefault();



    }
});


document.getElementById("btn-sale").addEventListener("click", async (e) => {
    e.preventDefault();
    let payload = {
        shop_id: document.getElementById("shop_id").value || "",
        payment_date: document.getElementById("payment_date").value || "",
        customer_name: document.getElementById("customer_name").value.trim(),
        customer_id: document.getElementById("customer_id").value.trim(),
        bill_no: document.getElementById("bill_no").value.trim(),
        items: [],
        payment_status: document.getElementById("payment_status").value,
        amount_paid: document.getElementById("amount_paid").value || "",
        grand_total: grandTotalInput.value || "",
        payment_mode: document.getElementById("payment_mode").value || "",
        notes: document.getElementById("notes").value || "",
    };

    itemsTableBody.querySelectorAll("tr").forEach(row => {
        const index = row.getAttribute("data-index");
        const product = row.querySelector(".item-product")?.value.trim() || "";
        const batch_code = row.querySelector(".item-batch-code")?.value.trim() || "";
        const qty = row.querySelector(".item-qty")?.value || "";
        const grade = row.querySelector(".item-grade")?.value || "";
        const unit = row.querySelector(".item-unit")?.value || "";
        const unit_price = row.querySelector(".item-price")?.value || "";
        const total_price = row.querySelector(".item-total")?.value || "";

        if (product && qty && unit_price && unit) {
            payload.items.push({
                product: product,
                batch_code: batch_code,
                quantity: qty,
                grade: grade,
                unit: unit,
                unit_price: unit_price,
                total_price: total_price
            });
        }
    });

    const newCustomerName = document.getElementById("new_customer_name")?.value.trim();
    const newBusinessName = document.getElementById("business_name")?.value.trim();
    const newCustomerContact = document.getElementById("customer_contact")?.value.trim();
    const newLocationName = document.getElementById("location_name")?.value.trim();

    if (newCustomerName) payload.customer_name = newCustomerName;
    if (newBusinessName) payload.business_name = newBusinessName;
    if (newCustomerContact) payload.customer_contact = newCustomerContact;
    if (newLocationName) payload.location_name = newLocationName;

    /**
     * If new customer name exists then remove the customer_name and customer id which is 
     * expected to be generated from the customer name search
     */
    if (newCustomerName) {
        payload.customer_name = newCustomerName;
        delete payload.customer_id;
    }


    console.log(payload)

    await storePayments(payload);

    // let success = await storePayments(payload);
    // if (success) {
    //     let modal = bootstrap.Modal.getInstance(document.getElementById("saleModal"));
    //     modal.hide();
    //     window.location.reload();
    // }


});

async function storePayments(payload) {
    clearErrorSpans()
    console.log(payload)

    try {
        let response = await fetch("/shop/sale/store/payments", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                "Accept": "application/json"
            },
            body: JSON.stringify(payload)
        });

        let data = await response.json();

        console.log(data.message)

        console.log(data)

        /**
         * Append exception messages
         */
        if (data.message) {
            const generalError = document.querySelector(".error-items-general");
            if (generalError) {
                generalError.textContent = data.message;
            }
            console.log(data.message);
        }


        // Clear all previous errors
        let itemsErrorSpan = document.querySelector(".error-items-general");

        // Clear previous errors
        if (itemsErrorSpan) itemsErrorSpan.innerText = "";
        document.querySelectorAll("span[class^='error-items-']").forEach(el => el.innerText = "");

        if (!response.ok && data.errors) {

            console.log(data.errors['common_error'])

            if (data.errors['common_error']) {
                const commonErrorDiv = document.getElementById('error_common');
                if (commonErrorDiv) {
                    commonErrorDiv.textContent = data.errors['common_error'].join(" ");
                }
            }




            Object.keys(data.errors).forEach(key => {
                // General items error
                if (key === "items" && Array.isArray(data.errors[key])) {
                    if (itemsErrorSpan) itemsErrorSpan.innerText = data.errors[key][0];
                }
                // Dynamic row errors
                else if (key.startsWith("items.")) {
                    // Example: items.0.product
                    let parts = key.split(".");
                    let rowIndex = parts[1];
                    let field = parts[2];
                    let errorSpan = document.querySelector(`.error-items-${rowIndex}-${field}`);
                    if (errorSpan) errorSpan.innerText = data.errors[key][0];
                }
                // Bill level errors
                else {
                    let safeKey = key.replace(/\./g, "-");
                    let errorSpan = document.querySelector(`.error-${safeKey}`);
                    if (errorSpan) errorSpan.innerText = data.errors[key][0];
                }
            });

            return false;
        }



    } catch (err) {
        console.error("Error creating sale:", err);
        return false;
    }
}

function clearErrorSpans() {
    // Select all spans where the class starts with "error-"
    const errorSpans = document.querySelectorAll('span[class^="error-"]');

    errorSpans.forEach(span => {
        span.textContent = ''; // Clear the text content
    });
}

async function getProductlist() {
    try {
        let response = await fetch("/shop/product/list", {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                "Accept": "application/json"
            },
        });

        let data = await response.json();
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (e) { }
        }

        if (!response.ok && data.errors) {
            console.log(data.errors);
            return false;
        } else {
            productList = data;
        }
    } catch (err) {
        console.error("Error loading product list:", err);
    }
}

let unitList = null;

async function getUnitlist() {
    try {
        let response = await fetch("/api/units", {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                "Accept": "application/json"
            },
        });
        if (response.ok) {
            let data = await response.json();
            unitList = data;
        }
    } catch (err) {
        console.error("Error loading unit list:", err);
    }
}

// ── Batch Code Selection Mechanism ──────────────────────────────────────────
let currentBatchInput = null;

document.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('item-batch-code')) {
        currentBatchInput = e.target;

        const row = e.target.closest('tr');
        if (row) {
            const productSelect = row.querySelector('.item-product');
            const productId = productSelect ? productSelect.value : '';

            const shopSelect = document.getElementById('shop_id');
            const shopId = shopSelect ? shopSelect.value : '';

            const modalProductSelect = document.querySelector('#batchCodeSearchForm select[name="product_listing"]');
            const modalLocationSelect = document.querySelector('#batchCodeSearchForm select[name="location"]');

            // Dynamically populate modal product options if empty
            if (modalProductSelect && productList && Array.isArray(productList) && modalProductSelect.options.length <= 1) {
                let opts = '<option value="" disabled selected>Select product</option>';
                productList.forEach(p => {
                    opts += `<option value="${p.id}">${p.name}</option>`;
                });
                modalProductSelect.innerHTML = opts;
            }

            if (modalProductSelect && productId && productId !== 'select') modalProductSelect.value = productId;
            if (modalLocationSelect && shopId && shopId !== 'select') modalLocationSelect.value = shopId;

            // Clear previous results
            const listContainer = document.getElementById('batchCodeListResults');
            if (listContainer) listContainer.innerHTML = '';

            // Auto-trigger search whenever shop location is set (shows all available batches for shop if no product selected)
            const currentLoc = modalLocationSelect ? modalLocationSelect.value : '';
            if (currentLoc && currentLoc !== 'select' && currentLoc !== 'Select location') {
                setTimeout(() => {
                    document.getElementById('search_batch_code')?.click();
                }, 100);
            }
        }
    }
});

document.getElementById('batchCodeSearchForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const prodVal = form.product_listing ? form.product_listing.value : '';
    const locVal = form.location ? form.location.value : '';
    const data = {
        product_listing: (prodVal && prodVal !== 'select') ? prodVal : '',
        location: (locVal && locVal !== 'select') ? locVal : '',
        dateFrom: form.dateFrom ? form.dateFrom.value : '',
    };

    const listContainer = document.getElementById('batchCodeListResults');
    if (!listContainer) return;

    fetch('/shop/search-batch-code', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify(data),
    })
        .then(res => res.json())
        .then(results => {
            listContainer.innerHTML = '';

            const filterContainer = document.getElementById('modalQuickFilterContainer');
            const filterInput = document.getElementById('modalQuickFilter');
            if (filterInput) filterInput.value = '';

            if (!results || !results.length) {
                if (filterContainer) filterContainer.classList.add('d-none');
                listContainer.innerHTML = `
            <div class="premium-empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <div class="premium-empty-state-title">No Batches Found</div>
                <div class="premium-empty-state-text">No available stock matches the selected product and shop location.</div>
            </div>`;
                return;
            }

            if (filterContainer) filterContainer.classList.remove('d-none');

            results.forEach(item => {
                let gradeClass = 'badge-grade-unsorted';
                const gradeLower = (item.grade || '').toLowerCase();
                if (gradeLower === 'a' || gradeLower === 'big' || gradeLower === '1') gradeClass = 'badge-grade-a';
                else if (gradeLower === 'b' || gradeLower === 'small' || gradeLower === '2') gradeClass = 'badge-grade-b';
                else if (gradeLower === 'c') gradeClass = 'badge-grade-c';
                else if (gradeLower === 'waste' || gradeLower === 'reject') gradeClass = 'badge-grade-waste';

                const qtyVal = parseFloat(item.available_qty || 0);
                let qtyClass = 'qty-low';
                if (qtyVal > 50) qtyClass = 'qty-high';
                else if (qtyVal > 0) qtyClass = 'qty-medium';

                listContainer.innerHTML += `
            <div class="batch-item-card select-batch"
                 data-batch-code="${item.batch_code}"
                 data-grade="${item.grade || ''}"
                 data-product-id="${item.product_id || ''}"
                 data-unit="${item.unit || ''}">
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
                    <span class="badge-premium-qty ${qtyClass}">${qtyVal.toFixed(2)} ${item.unit || ''}</span>
                </div>
            </div>`;
            });

            // Quick client-side filter
            if (filterInput && !filterInput.dataset.shopListenerAttached) {
                filterInput.dataset.shopListenerAttached = 'true';
                filterInput.addEventListener('input', function () {
                    const val = this.value.toLowerCase().trim();
                    listContainer.querySelectorAll('.batch-item-card').forEach(card => {
                        card.classList.toggle('d-none', !card.textContent.toLowerCase().includes(val));
                    });
                });
            }
        })
        .catch(err => console.error('Shop batch search failed:', err));
});

// Delegated click handler on the card list container
document.addEventListener('DOMContentLoaded', function () {
    const listContainer = document.getElementById('batchCodeListResults');
    if (listContainer) {
        listContainer.addEventListener('click', function (e) {
            const card = e.target.closest('.select-batch');
            if (!card || !currentBatchInput) return;

            e.preventDefault();

            const batchCode = card.getAttribute('data-batch-code');
            const grade = card.getAttribute('data-grade');
            const unit = card.getAttribute('data-unit');
            const productId = card.getAttribute('data-product-id');

            currentBatchInput.value = batchCode;

            const row = currentBatchInput.closest('tr');
            if (row) {
                // Auto-fill product if not set
                const productSelect = row.querySelector('.item-product');
                if (productSelect && productId) {
                    productSelect.value = productId;
                }

                // Auto-fill grade
                const gradeSelect = row.querySelector('.item-grade');
                if (gradeSelect && grade) {
                    let gradeOptionExists = Array.from(gradeSelect.options).some(o => o.value.toLowerCase() === grade.toLowerCase());
                    if (!gradeOptionExists) {
                        const newOpt = document.createElement('option');
                        newOpt.value = grade;
                        newOpt.textContent = grade;
                        gradeSelect.appendChild(newOpt);
                    }
                    gradeSelect.value = grade;
                    if (!gradeSelect.value || gradeSelect.selectedIndex <= 0) {
                        const opt = Array.from(gradeSelect.options).find(o =>
                            o.text.trim().toLowerCase() === grade.toLowerCase() ||
                            o.value.trim().toLowerCase() === grade.toLowerCase()
                        );
                        if (opt) gradeSelect.value = opt.value;
                    }
                }

                // Auto-fill unit
                const unitSelect = row.querySelector('.item-unit');
                if (unitSelect && unit) {
                    let unitOptionExists = Array.from(unitSelect.options).some(o => o.value.toLowerCase() === unit.toLowerCase());
                    if (!unitOptionExists) {
                        const newOpt = document.createElement('option');
                        newOpt.value = unit;
                        newOpt.textContent = unit;
                        unitSelect.appendChild(newOpt);
                    }
                    unitSelect.value = unit;
                    if (!unitSelect.value || unitSelect.selectedIndex <= 0) {
                        const opt = Array.from(unitSelect.options).find(o =>
                            o.text.trim().toLowerCase() === unit.toLowerCase() ||
                            o.value.trim().toLowerCase() === unit.toLowerCase()
                        );
                        if (opt) unitSelect.value = opt.value;
                    }
                }
            }

            // Close batch modal, return to sale modal
            const modalEl = document.getElementById('staticBackdropBatchCode');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        });
    }

    // Auto-reopen saleModal when staticBackdropBatchCode is closed
    const batchModalEl = document.getElementById('staticBackdropBatchCode');
    if (batchModalEl) {
        batchModalEl.addEventListener('hidden.bs.modal', function () {
            const parentModalEl = document.getElementById('saleModal');
            if (parentModalEl) {
                const parentModal = bootstrap.Modal.getOrCreateInstance(parentModalEl);
                parentModal.show();
            }
        });
    }
});

function resetContactName() {
    document.getElementById('customer_name').value = "";
}


//Edit sale record

document.addEventListener("click", (e) => {
    const updateBtn = e.target.closest(".edit_sale");
    if (updateBtn) {

        // Get sale_id and customer_id from the dataset
        const sale_id = updateBtn.dataset.sale_id;
        const customer_id = updateBtn.dataset.customer_id;
        const time_stamp = updateBtn.dataset.timestamp;

        console.log('Record ID:', sale_id); // Corrected the variable name

        // Get the table row that this button belongs to
        const row = updateBtn.closest("tr");

        if (!row) return;

        // Get cell values from the row
        const cells = row.querySelectorAll("td");

        const customerName = cells[1].textContent.trim();
        const totalBill = cells[2].textContent.trim().replace(/[^\d.]/g, '');
        const paidAmount = cells[3].textContent.trim().replace(/[^\d.]/g, '');
        const pendingAmount = cells[4].textContent.trim().replace(/[^\d.]/g, '');

        // Set modal form values
        document.getElementById("update_customer_name").value = customerName;
        document.getElementById("update_total_bill").value = totalBill;
        document.getElementById("update_pending_amount").value = pendingAmount;

        // Optionally reset new amount field
        document.getElementById("new_amount").value = '';

        console.log({ customerName, totalBill, paidAmount, pendingAmount });

        // Store these variables in the document object to access in other places
        document.sale_id_to_update = sale_id;
        document.customer_id_to_update = customer_id;
        document.timestamp = time_stamp
    }
});

document.getElementById("update_fetch").addEventListener("click", function (e) {
    e.preventDefault();

    // Log for debugging purposes
    console.log("sending fetch");

    const formData = {
        customer_name: document.getElementById("update_customer_name").value,
        total_bill: document.getElementById("update_total_bill").value,
        pending_amount: document.getElementById("update_pending_amount").value,
        new_amount: document.getElementById("new_amount").value,
        sale_id: document.sale_id_to_update,
        customer_id: document.customer_id_to_update,
        last_updated: document.timestamp, // Using the stored values
        payment_method: document.getElementById("payment-method").value
    };

    console.log(formData)

    submitPaymentUpdate(formData);
});


function submitPaymentUpdate(formData) {

    console.log("sending update")

    fetch('/shop/sale/payments/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(formData)
    })
        .then(async response => {
            if (!response.ok) {
                if (response.status === 422) {
                    const data = await response.json();
                    console.log(data)
                    showFormErrors(data.errors);
                } else {
                    console.error('Error:', response.statusText);
                }
            } else {
                const data = await response.json();
                console.log('Success:', data);

                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('updatePaymentsModal'));
                if (modal) modal.hide();

                // Optionally reset the form
                document.getElementById("filterForm").reset();

                // Optional: Refresh or update table here
            }
        })
        .catch(error => {
            console.error('Request failed:', error);
        });
}


function showFormErrors(errors) {
    for (const [field, messages] of Object.entries(errors)) {
        const span = document.querySelector(`.error_${field.replace(/\./g, '_')}`) ||
            document.querySelector(`.error-${field.replace(/\./g, '_')}`);
        if (span) {
            span.textContent = messages.join(', ');
        }
    }
}