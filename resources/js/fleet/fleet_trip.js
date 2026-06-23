
//Batch Logic 

/**
 * Batch code is currently working only on single product stock out
 * will not work on dynamically generated product items
 * Result of the fetch call is append to input with id batchCodeInput
 */


document.getElementById('search_batch_code').addEventListener('click', function (e) {

    e.preventDefault();

    console.log('fired batch code submi button')

    const product = document.querySelector('select[name="product_listing"]').value;
    const location = document.querySelector('select[name="location"]').value;
    const purchaseDate = document.querySelector('input[name="dateFrom"]').value;

    // Store them in variables or an object
    const data = {
        product: product,
        location: location,
        purchaseDate: purchaseDate
    };

    fetch("search-batch-code", {
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
                        <td><button class="btn btn-sm btn-success select-batch" data-batch-code="${item.batch_code}" data-id="${item.id}">Select</button></td>
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
    if (e.target && e.target.classList.contains('batch_code_dynamic')) {
        currentBatchInput = e.target;
        console.log('Clicked input:', currentBatchInput);
    }
});

// Attach listener once, delegate clicks on .select-batch buttons inside tbody
document.querySelector('#batchCodeResults tbody').addEventListener('click', function (e) {
    const target = e.target;

    console.log("table click logged")

    if (target && target.classList.contains('select-batch')) {
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


document.addEventListener('DOMContentLoaded', function () {
    // Get reference to <tbody> that holds the dynamic rows
    const tbody = document.getElementById('batchCodeResults'); // <-- Replace with actual ID

    if (!tbody) {
        console.error('Tbody element not found');
        return;
    }

    // Use event delegation to listen for clicks on .select-batch buttons

    tbody.addEventListener('click', function (e) {
        const target = e.target;

        if (target && target.classList.contains('select-batch')) {
            e.preventDefault();

            console.log('Click detected on dynamically created button');

            const batchCode = target.getAttribute('data-batch-code');
            const batchCodeInput = document.getElementById('batchCodeInput');

            if (batchCodeInput) {
                batchCodeInput.value = batchCode;
                console.log('Batch code set:', batchCode);
            } else {
                console.warn('Input with id "batch_code" not found');
            }

            // const modalEl = document.getElementById('staticBackdropBatchCode');
            // const modal = bootstrap.Modal.getInstance(modalEl);
            // if (modal) {
            //     modal.hide();
            // } else {
            //     console.warn('Bootstrap modal instance not found');
            // }
        }
    });
});







document.addEventListener('DOMContentLoaded', function () {

    // ----------------------------------------------------------------
    // Populate Route and Vehicle selects dynamically
    // ----------------------------------------------------------------

    const routeSelect   = document.getElementById('route_id');
    const vehicleSelect = document.getElementById('vehicle_id');
    const tripModalEl   = document.getElementById('tripModal');

    function populateSelect(select, items, valueKey, labelKey, placeholder) {
        select.innerHTML = `<option selected disabled value="">${placeholder}</option>`;
        if (!items.length) {
            select.innerHTML = `<option disabled value="">No records found</option>`;
            return;
        }
        items.forEach(item => {
            const opt = document.createElement('option');
            opt.value       = item[valueKey];
            opt.textContent = item[labelKey];
            select.appendChild(opt);
        });
    }

    function fetchRoutes() {
        fetch('/api/fleet-routes', {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            const routes = Array.isArray(data) ? data : [];
            populateSelect(routeSelect, routes, 'id', 'name', 'Choose Route');
        })
        .catch(() => {
            routeSelect.innerHTML = '<option disabled value="">Failed to load routes</option>';
        });
    }

    function fetchVehicles() {
        fetch('/fleet/vehicles', {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            const vehicles = Array.isArray(data) ? data : [];
            populateSelect(vehicleSelect, vehicles, 'id', 'registration_number', 'Choose Vehicle');
        })
        .catch(() => {
            vehicleSelect.innerHTML = '<option disabled value="">Failed to load vehicles</option>';
        });
    }

    // Load on page ready
    fetchRoutes();
    fetchVehicles();

    // Reload every time the modal is opened so data is always fresh
    tripModalEl?.addEventListener('show.bs.modal', () => {
        fetchRoutes();
        fetchVehicles();
    });

    // ----------------------------------------------------------------

    console.log("fired");

    const productTemplate = document.getElementById('productRowTemplate');
    const sentContainer = document.getElementById('productsSentContainer');
    const returnedContainer = document.getElementById('productsReturnedContainer');

    const addRow = (container, section) => {
        const clone = productTemplate.content.cloneNode(true);
        container.appendChild(clone);
        reindex(container, section);
    };

    const reindex = (container, section) => {
        [...container.querySelectorAll('.product-row')].forEach((row, index) => {
            row.querySelectorAll('[data-field]').forEach(field => {
                const fieldName = field.dataset.field;
                field.name = `${section}[${index}][${fieldName}]`;
                field.id = `${section}_${index}_${fieldName}`;
            });

            // Fix: Add matching error span class (like .error_sent_0_product_id)
            row.querySelectorAll('.text-danger').forEach(span => {
                const match = span.className.match(/error_([a-z_]+)/);
                if (match) {
                    const fieldName = match[1];
                    span.classList.add(`error_${section}_${index}_${fieldName}`);
                }
            });
        });
    };


    document.getElementById('addProductSentBtn').addEventListener('click', () => {
        addRow(sentContainer, 'sent');
    });

    document.getElementById('addProductReturnedBtn').addEventListener('click', () => {
        addRow(returnedContainer, 'returned');
    });

    // Remove row + reindex
    document.addEventListener('click', e => {
        if (e.target.classList.contains('removeProductRow')) {
            const row = e.target.closest('.product-row');
            const parent = row.parentElement;
            row.remove();
            if (parent.id.includes('Sent')) reindex(parent, 'sent');
            else reindex(parent, 'returned');
        }
    });

    // Initialize with one default row each
    addRow(sentContainer, 'sent');
    addRow(returnedContainer, 'returned');


    // Handle form submit
    document.getElementById('fleetTripForm').addEventListener('submit', (e) => {
        e.preventDefault();

        // main trip fields
        const route_id = document.getElementById('route_id').value;
        const vehicle_id = document.getElementById('vehicle_id').value;
        const start_date = document.getElementById('start_date').value;
        const tag = document.getElementById('tag').value;

        // collect dynamic rows
        const sent = collectRows(sentContainer);
        const returned = collectRows(returnedContainer);

        console.log(sent)

        // console.log(sent)

        const data = {
            route_id,
            vehicle_id,
            start_date,
            tag,
            sent,
            returned
        };

        createTrip(data);
    });

    // helper to collect all dynamic rows
    function collectRows(container) {
        const rows = [];
        container.querySelectorAll('.product-row').forEach(row => {
            const rowData = {};
            row.querySelectorAll('[data-field]').forEach(input => {
                rowData[input.dataset.field] = input.value;
            });
            rows.push(rowData);
        });
        return rows;
    }

    // send data to Laravel
    function createTrip(data) {
        console.log("Sending data:", data);
        fetch('/create-trip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(result => {
                if (result.errors) {
                    showErrors(result.errors);
                } else {
                    console.log("Trip created:", result);
                    // Optionally reset form or close modal
                    document.getElementById('fleetTripForm').reset();
                    sentContainer.innerHTML = '';
                    returnedContainer.innerHTML = '';
                    addRow(sentContainer, 'sent');
                    addRow(returnedContainer, 'returned');
                    bootstrap.Modal.getInstance(document.getElementById('tripModal')).hide();
                }
            })
            .catch(error => console.error("Error:", error));
    }

    // display Laravel validation errors
    function showErrors(errors) {
        // Clear old errors
        document.querySelectorAll('[class*="error_"]').forEach(el => {
            el.textContent = '';
        });



        for (let key in errors) {
            // Laravel keys like 'sent.0.product_id' → convert to valid CSS class format
            const safeKey = key.replace(/\./g, '_');
            const span = document.querySelector(`.error_${safeKey}`);

            if (span) {
                span.innerText = errors[key][0]; // show first error message
                span.style.color = "red";
            } else {
                console.warn("No matching span found for:", key);
            }
        }
    }

});




/**Edit fleet trip */

document.querySelectorAll('.edit-trip').forEach(btn => {
    btn.addEventListener('click', function () {
        const tripId = this.dataset.id;

        // Fetch and populate the modal
        loadTripForEdit(tripId);
    });
});



/**** Check and remove if needed later */

// document.getElementById('add-stock').addEventListener('click', (e) => {
//     e.preventDefault();
//     dispatchStock();
// });

// async function dispatchStock() {
//     // Clear previous errors
//     document.querySelectorAll('.error-text').forEach(el => el.textContent = '');

//     const data = {
//         trip: document.getElementById('tripSelect').value,
//         product: document.getElementById('productSelect').value,
//         qtySent: document.getElementById('qtySent').value,
//         location: document.getElementById('locationSelect').value,
//         batch: document.getElementById('batchCodeInput').value,
//         qtyReturned: document.getElementById('qtyReturned').value,
//     };

//     try {
//         const response = await fetch('/stock-dispatch', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/json',
//                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//             },
//             body: JSON.stringify(data)
//         });

//         const result = await response.json();
//         console.log(result)

//         if (response.status === 422) {
//             document.getElementById("error-common").innerText = "";

//             // Loop through each validation error
//             Object.keys(result.errors).forEach(key => {
//                 const errorElement = document.querySelector(`.${key}-error`);
//                 if (errorElement) {
//                     errorElement.textContent = result.errors[key][0];
//                 }
//                 else if (key === 'common') {
//                     // If no specific element for 'common', show alert or in a generic error div
//                     document.getElementById("error-common").innerText = result.errors[key][0];
//                 }
//             });
//         } else if (response.ok) {
//             alert('Stock dispatched successfully!');
//             document.getElementById('stock-form').reset();
//             // Optionally close modal here
//         } else {
//             alert('Unexpected server error.');
//         }

//     } catch (error) {
//         console.error('Fetch error:', error);
//         alert('Network error occurred.');
//     }
// }