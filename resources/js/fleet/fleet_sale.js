/**
 * Handles click events on dynamically generated buttons with class 'btn-select-route',
 * setting the trip ID from the button's data-trip attribute into the trip_id input field.
 */
document.addEventListener("click", (e) => {
    // Check if the clicked element has the btn-select-route class
    if (e.target && e.target.classList.contains("btn-select-route")) {
        // Prevent default button behavior (e.g., form submission or modal toggle)
        e.preventDefault();

        // Log for debugging
        console.log("Clicked on submit button");

        // Retrieve trip ID from the button's data-trip attribute
        const tripTag = e.target.getAttribute("data-trip");

        // Log trip ID for debugging
        console.log("Trip ID:", tripTag);

        // Set the trip ID into the trip_id input field
        const tripInput = document.getElementById("trip_id");
        if (tripInput) {
            tripInput.value = tripTag;
        } else {
            console.error("Input element with ID 'trip_id' not found.");
        }
    }
});


/**
 * Sends a POST request to search for routes based on the
 *  provided payload and appends the results to a table.
 * 
 * @param {Object} payload - The search criteria (e.g., trip date, route name) to send in the request body.
 * @returns {void} - Does not return a value; updates the UI by appending results to a table or displaying errors.
 */
function searchRoutes(payload) {
    // Initiate a POST request to the routes search endpoint
    fetch("/fleet/sale/search/routes", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            // Include CSRF token for security
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            "Accept": "application/json"
        },
        body: JSON.stringify(payload) // Serialize payload to JSON
    })
        .then(async response => {
            // Parse JSON response
            let data = await response.json();

            // Clear previous validation error messages
            document.querySelector('.error_trip_date').innerText = "";
            document.querySelector('.error_routeName').innerText = "";

            // Handle non-OK responses (e.g., validation or server errors)
            if (!response.ok) {
                // Display validation errors if present
                if (data.errors) {
                    if (data.errors.trip_date) {
                        document.querySelector('.error_trip_date').innerText = data.errors.trip_date[0];
                    }
                    if (data.errors.routeName) {
                        document.querySelector('.error_routeName').innerText = data.errors.routeName[0];
                    }
                }
                return; // Exit early to avoid processing invalid data
            }

            // Append successful search results to the table
            console.log("Search result:", data);
            appendToTable(data);
        })
        .catch(err => {
            // Log fetch or network errors for debugging
            console.error("Error searching route:", err);
        });
}

/**
 * Populates a table with route data, creating rows with route details and a selectable button.
 * 
 * @param {Array<Object>} data - Array of route objects, each containing id, route_id, start_date, and tag.
 * @returns {void} - Updates the table body with new rows or a "no routes" message if data is empty.
 */
function appendToTable(data) {
    // Get the table body element to append rows
    let tbody = document.getElementById("trip-table");

    // Clear existing rows to prevent duplication
    tbody.innerHTML = "";

    // Handle empty or invalid data by displaying a "no routes" message
    if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center">No routes found.</td></tr>`;
        return;
    }

    // Build a map of route IDs to names from the route dropdown for display
    let routeSelect = document.getElementById('route-name');
    let routeMap = {};
    for (let option of routeSelect.options) {
        if (option.value) {
            routeMap[option.value] = option.textContent;
        }
    }

    // Iterate through route data to create and append table rows
    data.forEach(route => {
        let row = document.createElement('tr');

        // Format the trip start date to dd/mm/yyyy
        let tripDate = new Date(route.start_date).toLocaleDateString('en-GB');

        // Retrieve route name from the map, default to "Unknown Route" if not found
        let routeName = routeMap[route.route_id] || "Unknown Route";

        // Construct row HTML with route details and a button to open a modal
        row.innerHTML = `
            <td>${route.tag}</td>
            <td>${routeName}</td>
            <td>${tripDate}</td>
            <td>
                <button type="button"
                    class="btn btn-sm btn-primary btn-select-route"
                    data-bs-target="#saleModal"
                    data-bs-toggle="modal"
                    data-bs-dismiss="modal"
                    data-trip="${route.id}">
                    Select
                </button>
            </td>
        `;

        // Append the row to the table body
        tbody.appendChild(row);
    });
}



/**
 * Product name  , it and unit  with name , id is received and
 * saved inside the variables
 * 
 * @param unitName
 * @param unitId
 * @param productName
 * @param productId
 */

/****Dynamic row in sale item modal */



document.addEventListener("DOMContentLoaded", function () {
    const addItemBtn = document.getElementById("addItemBtn");
    const itemsTableBody = document.querySelector("#itemsTable tbody");
    const grandTotalInput = document.getElementById("grand_total");

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

        row.innerHTML = `
            <td>
                <input type="text" class="form-control item-product" name="items[product][]" required>
                <span class="error error-items-${rowIndex}-product text-danger text-small"></span>
            </td>
            <td>
                <input type="number" class="form-control item-qty" name="items[qty][]" min="0" step="any" required>
                <span class="error error-items-${rowIndex}-quantity text-danger text-small"></span>
            </td>
            <td style="width:15%;">
                <select class=" form-select item-unit" name="items[unit][]">
                    <option selected disabled>Select Unit</option>
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
    addRow();

    // Handle sale submission
    document.getElementById("btn-sale").addEventListener("click", async (e) => {
        e.preventDefault();

        console.log(grandTotalInput.value)



        // Clear previous errors
        document.querySelectorAll("span.error").forEach(el => el.innerText = "");

        let customer_details = [];

        // Collect payload
        let payload = {
            trip_id: document.getElementById("trip_id").value || "",
            payment_date: document.getElementById("payment_date").value || "",
            customer_name: document.getElementById("customer_name").value.trim(),
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
            const qty = row.querySelector(".item-qty")?.value || "";
            const unit = row.querySelector(".item-unit")?.value || "";
            const unit_price = row.querySelector(".item-price")?.value || "";
            const total_price = row.querySelector(".item-total")?.value || "";

            if (product && qty && unit_price && unit) {
                payload.items.push({
                    product: product,
                    quantity: qty,
                    unit: unit,
                    unit_price: unit_price,
                    total_price: total_price
                });
            }
        });

        const newCustomerName = document.getElementById("new_customer_name")?.value.trim();
        const newRouteName = document.getElementById("new_route_name")?.value.trim();
        const newCustomerContact = document.getElementById("new_customer_contact")?.value.trim();
        const newLocationName = document.getElementById("new_location_name")?.value.trim();

        // Apply overrides only if values exist
        payload.customer_name = newCustomerName || payload.customer_name;
        if (newRouteName) payload.route_name = newRouteName;
        if (newCustomerContact) payload.customer_contact = newCustomerContact;
        if (newLocationName) payload.location_name = newLocationName;

        let success = await storePayments(payload);
        if (success) {
            let modal = bootstrap.Modal.getInstance(document.getElementById("saleModal"));
            modal.hide();
            window.location.reload();
        }
    });

    // Store payment via API
    async function storePayments(payload) {
        clearErrorSpans()
        console.log(payload)

        try {
            let response = await fetch("/fleet/sale/store", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "Accept": "application/json"
                },
                body: JSON.stringify(payload)
            });

            let data = await response.json();

            console.log(data)

            // Clear all previous errors
            let itemsErrorSpan = document.querySelector(".error-items-general");

            // Clear previous errors
            if (itemsErrorSpan) itemsErrorSpan.innerText = "";
            document.querySelectorAll("span[class^='error-items-']").forEach(el => el.innerText = "");

            if (!response.ok && data.errors) {

                console.log(data.errors)

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

});


function clearErrorSpans() {
    // Select all spans where the class starts with "error-"
    const errorSpans = document.querySelectorAll('span[class^="error-"]');

    errorSpans.forEach(span => {
        span.textContent = ''; // Clear the text content
    });
}

function resetFormElementsExceptTripId() {
    // Select all input and select elements, excluding the one with id="trip_id"
    const elements = document.querySelectorAll('input:not(#trip_id), select:not(#trip_id)');

    elements.forEach(el => {
        if (el.tagName.toLowerCase() === 'input') {
            if (el.type === 'checkbox' || el.type === 'radio') {
                el.checked = false;
            } else {
                el.value = '';
            }
        } else if (el.tagName.toLowerCase() === 'select') {
            el.selectedIndex = 0; // reset to first option
        }
    });
}

// function resetItemRows() {
//     const itemsTableBody = document.querySelector("#itemsTable tbody");
//     itemsTableBody.innerHTML = ""; // Clear all rows

//     // Re-add one empty row
//     const addItemBtn = document.getElementById("addItemBtn");
//     if (addItemBtn) addItemBtn.click(); // Reuse existing logic to add a new row

//     // Reset grand total
//     const grandTotalInput = document.getElementById("grand_total");
//     if (grandTotalInput) grandTotalInput.value = "0.00";
// }

// Helpers
function normalizeVal(v) {
    return (v ?? '').toString().trim();
}
function isValidDateValue(v) {
    v = normalizeVal(v).toLowerCase();
    return v !== '' && v !== 'null';
}
function isValidRouteValue(v) {
    v = normalizeVal(v).toLowerCase();
    // treat common placeholders as invalid
    return v !== '' && v !== 'select' && v !== 'choose' && v !== 'null';
}

// Error UI helpers (Bootstrap-friendly)
function showDateError(message) {
    const tripDateInput = document.getElementById('trip-date');
    if (!tripDateInput) return;
    let err = document.getElementById('route-date-error');
    if (!err) {
        err = document.createElement('div');
        err.id = 'route-date-error';
        // use Bootstrap invalid-feedback styling; d-block to make it visible
        err.className = 'invalid-feedback d-block';
        tripDateInput.parentNode.insertBefore(err, tripDateInput.nextSibling);
    }
    err.textContent = message;
    tripDateInput.setAttribute('aria-invalid', 'true');
}
function clearDateError() {
    const tripDateInput = document.getElementById('trip-date');
    if (!tripDateInput) return;
    const err = document.getElementById('route-date-error');
    if (err) err.remove();
    tripDateInput.removeAttribute('aria-invalid');
}

// Main wiring
const routeSelect = document.getElementById('route-name');
const tripDateInput = document.getElementById('trip-date');

if (routeSelect) {
    routeSelect.addEventListener('change', function (e) {
        // if trip-date input not present, bail out early
        if (!tripDateInput) return;

        const routeName = normalizeVal(routeSelect.value);
        const tripDateRaw = normalizeVal(tripDateInput.value);

        // if route value looks like a placeholder, treat as not selected
        if (!isValidRouteValue(routeName)) {
            // optionally clear error and do nothing
            clearDateError();
            return;
        }

        // If date is not valid, block and show error (edge case: route selected first)
        if (!isValidDateValue(tripDateRaw)) {
            showDateError('Please select a trip date before choosing a route.');
            // focus on date so user can pick it quickly
            try { tripDateInput.focus(); } catch (err) { }
            return;
        }

        // Date is valid → clear any error and proceed
        clearDateError();
        const payload = { trip_date: tripDateRaw, routeName: routeName };
        // Call your search function
        console.log(routeName + "route name is")
        searchRoutes(payload);
        searchCustomerNames();
    });
}

// If user picks date *after* selecting route, auto-run search (if route valid)
if (tripDateInput) {
    tripDateInput.addEventListener('change', function (e) {
        clearDateError();

        const tripDateRaw = normalizeVal(tripDateInput.value);
        if (!isValidDateValue(tripDateRaw)) {
            // user cleared date — nothing to do
            return;
        }

        const routeName = routeSelect ? normalizeVal(routeSelect.value) : '';
        if (!isValidRouteValue(routeName)) {
            // route isn't chosen yet; optionally notify user to pick route first
            // showDateError('Select a route to run the search after picking date.');
            return;
        }


        const payload = { trip_date: tripDateRaw, routeName: routeName };

        searchCustomerNames(routeName)

        searchRoutes();



    });
}

let customerNames = []; // Will be array of { id, name }
let fuse;

// Fuse.js options
const fuseOptions = {
    includeScore: true,
    threshold: 0.3,
    keys: ['name'] // Search only by name
};

// Fetch and convert customer data from object format
async function searchCustomerNames() {
    const routeNameSelect = document.getElementById('route-name');
    if (!routeNameSelect) return;

    const payloadValue = routeNameSelect.value;

    try {
        const response = await fetch('/fleet/customers', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ route_id: payloadValue })
        });

        if (!response.ok) throw new Error('Network response was not ok.');

        const jsonResponse = await response.json();
        const data = jsonResponse.data;

        // Convert object to array of { id, name }
        customerNames = Object.entries(data).map(([id, name]) => ({ id, name }));

        // Initialize Fuse with updated array
        fuse = new Fuse(customerNames, fuseOptions);
        console.log("Fuse initialized with customer data:", customerNames);

    } catch (err) {
        console.error('Error fetching customer names:', err);
    }
}

// Suggest customer based on search pattern
function suggestCustomerName(searchPattern) {
    if (!fuse) {
        console.log("Fuse not initialized yet.");
        return [];
    }
    return fuse.search(searchPattern);
}

const dropDown = document.getElementById('drop-down');
const input = document.getElementById('customer_name');
const hiddenInput = document.getElementById('customer_id');
let currentFocus = -1;

input.addEventListener('input', function () {
    const searchPattern = this.value.trim();

    if (!searchPattern) {
        dropDown.classList.remove('show');
        dropDown.innerHTML = '';
        currentFocus = -1;
        return;
    }

    const results = suggestCustomerName(searchPattern);

    if (results.length === 0) {
        dropDown.innerHTML = `
      <li class="dropdown-item new-customer">
        No customer found <a href="#">Create new customer</a>
      </li>`;
        dropDown.classList.add('show');
        currentFocus = -1;
        return;
    }

    let html = '';
    results.forEach((result, index) => {
        const name = result.item.name;
        const id = result.item.id;
        html += `<li class="dropdown-item" data-id="${id}" data-name="${name}" role="option" tabindex="-1">${name}</li>`;
    });

    dropDown.innerHTML = html;
    dropDown.classList.add('show');
    currentFocus = -1;

    // Click selection
    dropDown.querySelectorAll('.dropdown-item').forEach((item) => {
        item.addEventListener('click', () => {
            input.value = item.dataset.name;
            hiddenInput.value = item.dataset.id;
            dropDown.classList.remove('show');
            dropDown.innerHTML = '';
        });
    });
});

// Keyboard navigation
input.addEventListener('keydown', function (e) {
    const items = dropDown.querySelectorAll('.dropdown-item:not(.disabled)');
    if (!items.length) return;

    if (e.key === 'ArrowDown') {
        currentFocus++;
        if (currentFocus >= items.length) currentFocus = 0;
        setActive(items, currentFocus);
        e.preventDefault();
    } else if (e.key === 'ArrowUp') {
        currentFocus--;
        if (currentFocus < 0) currentFocus = items.length - 1;
        setActive(items, currentFocus);
        e.preventDefault();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (currentFocus > -1) {
            items[currentFocus].click();
        }
    } else if (e.key === 'Escape') {
        dropDown.classList.remove('show');
        currentFocus = -1;
    }
});

function setActive(items, index) {
    items.forEach(item => item.classList.remove('active'));
    if (index >= 0 && items[index]) {
        items[index].classList.add('active');
        items[index].scrollIntoView({ block: 'nearest' });
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function (e) {
    if (!input.contains(e.target) && !dropDown.contains(e.target)) {
        dropDown.classList.remove('show');
        currentFocus = -1;
    }
    // Use event delegation for clicks inside the document or container
});


/**
 * Shows when add new customer link is clicked
 * Inputs are shown to enter details like customer name , contact , route and location
 * 
 */
document.body.addEventListener('click', function (e) {
    // Check if the clicked element (or its parent) contains the 'new-customer' class
    if (e.target.closest('.new-customer')) {


        const newCustomerFields = document.getElementById('newCustomerFields');
        const customerNameInput = document.getElementById('customer_name');
        const customerIdInput = document.getElementById('customer_id');

        // Log to confirm click is detected
        console.log("click on create new customer");

        // Show the new customer fields
        if (newCustomerFields) {
            newCustomerFields.style.display = 'block';
        }

        // Disable the existing customer name input and clear customer_id
        if (customerNameInput) customerNameInput.disabled = true;
        if (customerIdInput) customerIdInput.value = '';

        // Prevent the default action (e.g., following the link)
        e.preventDefault();
    }
});



// Get the checkbox element
const advancedOptionsCheckbox = document.getElementById('advancedOptions');

// Get the advanced options fields
const advancedOptionsFields = document.getElementById('advancedOptionsFields');

// Event listener for when the checkbox is toggled
advancedOptionsCheckbox.addEventListener('change', function () {
    if (this.checked) {
        advancedOptionsFields.style.display = 'block'; // Show the fields
    } else {
        advancedOptionsFields.style.display = 'none'; // Hide the fields
    }
});