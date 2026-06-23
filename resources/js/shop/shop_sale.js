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
        let productListTd = "";

        if (productList) {
            let options = '';

            // Generate options for the select element
            productList.forEach((product) => {
                options += `<option value="${product.id}">${product.name}</option>`;
            });

            productListTd = `<td style="width:15%;">
                <select class="form-select item-product" name="items[product][]" required>
                <option selected disabled>select</option>
                    ${options}
                    </select>
                    <span class="error error-items-${rowIndex}-product text-danger text-small"></span>
                </td>`;
        }


        row.innerHTML = `${productListTd}

            <td style="width:20%;">
                 <input type="text" readonly class="form-control item-batch-code " name="items[batch_code][]" data-bs-toggle="modal" data-bs-target="#staticBackdropBatchCode">
                <span class="error error-items-${rowIndex}-batch_code text-danger text-small"></span>
            </td>

            <td style="width:15%;">
                 <select class=" form-select item-grade" name="items[grade][]">
                    <option selected disabled>select </option>
                    <option value=1>Big</option>
                    <option value=2>Small</option>
                </select>

                <span class="error error-items-${rowIndex}-grade text-danger text-small"></span>
            </td>
           

            <td >
                <input type="number" class="form-control item-qty" name="items[qty][]" min="0" step="any" required>
                <span class="error error-items-${rowIndex}-quantity text-danger text-small"></span>
            </td>
            <td style="width:15%;">
                <select class=" form-select item-unit" name="items[unit][]">
                    <option selected disabled>select</option>
                    <option value="kg">kg</option>
                    <option value="pcs">pcs</option>
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
            // Re-index rows and error spans
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

    // Start with one empty row

    await getProductlist();

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

        if (!response.ok && data.errors) {

            console.log(data.errors)

            return false;
        }
        else {

            productList = data;

        }



    } catch (err) {
        console.error("Error creating sale:", err);
        return false;
    }


}




//Batch Logic 

/**
 * Batch code is currently working only on single product stock out
 * will not work on dynamically generated product items
 * Result of the fetch call is append to input with id batchCodeInput
 */


document.getElementById('batchCodeSearchForm').addEventListener('submit', function (e) {
    e.preventDefault();

    console.log('fired')

    const form = e.target;
    const data = {
        product_listing: form.product_listing.value,
        location: form.location.value,
        dateFrom: form.dateFrom.value
    };

    fetch("/search-batch-code", {
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
                tbody.innerHTML = `<tr><td colspan="6" class="text-center">No results found.</td></tr>`;
            } else {
                response.forEach((item, index) => {
                    tbody.innerHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.batch_code}</td>
                        <td>${item.product}</td>
                        <td>${item.location}</td>
                        <td><button data-bs-target="#saleModal" data-bs-toggle="modal" class="btn btn-sm btn-success select-batch" data-batch-code="${item.batch_code}" data-id="${item.id}">Select</button></td>
                    </tr>`;
                });
            }
        })
        .catch(error => console.error("Search failed:", error));
});

// Global variable to track which input was clicked
let currentBatchInput = null;

// Track clicked batch input
document.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('item-batch-code')) {
        currentBatchInput = e.target;
        console.log('Clicked input:', currentBatchInput);

        // Auto-populate product and location in the batch code search modal
        const row = e.target.closest('tr');
        if (row) {
            const productSelect = row.querySelector('.item-product');
            const productId = productSelect ? productSelect.value : '';

            const shopSelect = document.getElementById('shop_id');
            const shopId = shopSelect ? shopSelect.value : '';

            const modalProductSelect = document.querySelector('#batchCodeSearchForm select[name="product_listing"]');
            const modalLocationSelect = document.querySelector('#batchCodeSearchForm select[name="location"]');

            if (modalProductSelect && productId) {
                modalProductSelect.value = productId;
            }
            if (modalLocationSelect && shopId) {
                modalLocationSelect.value = shopId;
            }

            // Clear previous results in the modal table
            const tbody = document.querySelector('#batchCodeResults tbody');
            if (tbody) {
                tbody.innerHTML = '';
            }

            // Auto-trigger search if both product and location are set
            if (modalProductSelect && modalLocationSelect && productId && shopId) {
                setTimeout(() => {
                    document.getElementById('search_batch_code')?.click();
                }, 100);
            }
        }
    }
});

// Attach listener once, delegate clicks on .select-batch buttons inside tbody
document.querySelector('#batchCodeResults tbody').addEventListener('click', function (e) {
    const target = e.target;

    if (target && (target.classList.contains('select-batch') || target.classList.contains('wh-select-batch'))) {
        e.preventDefault();

        const batchCode = target.getAttribute('data-batch-code');

        if (currentBatchInput) {
            currentBatchInput.value = batchCode;
            console.log('Batch code set:', batchCode);
        } else {
            console.warn('No batch input was selected before selecting batch code');
        }
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