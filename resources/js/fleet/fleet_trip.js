// Batch Logic
let currentBatchInput = null;

// Track clicked batch input and auto-populate search filters from the triggering row
document.addEventListener('click', function (e) {
    const input = e.target.closest('.batch_code_dynamic');
    if (input) {
        currentBatchInput = input;
        console.log('Clicked batch input:', currentBatchInput);

        const row = input.closest('.product-row');
        if (row) {
            const productSelect = row.querySelector('[data-field="product_id"]');
            const productId = productSelect ? productSelect.value : '';

            const locationSelect = row.querySelector('[data-field="location_id"]');
            const locationId = locationSelect ? locationSelect.value : '';

            const modalProductSelect = document.querySelector('#batchCodeSearchForm select[name="product_listing"]');
            const modalLocationSelect = document.querySelector('#batchCodeSearchForm select[name="location"]');

            if (modalProductSelect) {
                modalProductSelect.value = productId || '';
            }
            if (modalLocationSelect) {
                modalLocationSelect.value = locationId || '';
            }

            // Clear previous results
            const listContainer = document.getElementById('batchCodeListResults');
            if (listContainer) {
                listContainer.innerHTML = '';
            }

            // Auto-trigger search if both product and location are selected
            if (modalProductSelect && modalLocationSelect && productId && locationId) {
                setTimeout(() => {
                    const searchBtn = document.getElementById('search_batch_code');
                    if (searchBtn) {
                        searchBtn.click();
                    }
                }, 100);
            }
        }
    }
});

// Handle search form submission
document.addEventListener('submit', function (e) {
    if (e.target && e.target.id === 'batchCodeSearchForm') {
        e.preventDefault();

        const form = e.target;
        const data = {
            product_listing: form.product_listing.value,
            location:        form.location.value,
            dateFrom:        form.dateFrom.value,
        };

        const listContainer = document.getElementById('batchCodeListResults');
        if (!listContainer) return;

        const searchBtn = document.getElementById('search_batch_code');
        let searchBtnOriginalHTML = '';
        if (searchBtn) {
            searchBtnOriginalHTML = searchBtn.innerHTML;
            searchBtn.disabled = true;
            searchBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Searching...`;
        }

        // Show loading spinner in results container
        listContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem; border-width: 0.25em;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-3 text-muted">Searching available batches...</div>
        </div>`;

        fetch("/fleet/search-batch-code", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(results => {
            listContainer.innerHTML = "";

            const filterContainer = document.getElementById('modalQuickFilterContainer');
            const filterInput     = document.getElementById('modalQuickFilter');
            if (filterInput) filterInput.value = '';

            if (results.length === 0) {
                if (filterContainer) filterContainer.classList.add('d-none');
                listContainer.innerHTML = `
                <div class="premium-empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    <div class="premium-empty-state-title">No Batches Found</div>
                    <div class="premium-empty-state-text">No available stock matches the selected product and location.</div>
                </div>`;
            } else {
                if (filterContainer) filterContainer.classList.remove('d-none');

                results.forEach(item => {
                    let gradeClass = 'badge-grade-unsorted';
                    const gradeLower = (item.grade || '').toLowerCase();
                    if      (gradeLower === 'a' || gradeLower === 'big')         gradeClass = 'badge-grade-a';
                    else if (gradeLower === 'b' || gradeLower === 'small')       gradeClass = 'badge-grade-b';
                    else if (gradeLower === 'c')                                  gradeClass = 'badge-grade-c';
                    else if (gradeLower === 'waste' || gradeLower === 'reject')   gradeClass = 'badge-grade-waste';

                    const qtyVal = parseFloat(item.available_qty || 0);
                    let qtyClass = 'qty-low';
                    if      (qtyVal > 50) qtyClass = 'qty-high';
                    else if (qtyVal > 0)  qtyClass = 'qty-medium';

                    listContainer.innerHTML += `
                    <div class="batch-item-card select-batch"
                         data-batch-code="${item.batch_code}"
                         data-grade="${item.grade || ''}"
                         data-product-id="${item.product_id || ''}"
                         data-location-id="${item.location_id || ''}"
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
                if (filterInput && !filterInput.dataset.fleetListenerAttached) {
                    filterInput.dataset.fleetListenerAttached = 'true';
                    filterInput.addEventListener('input', function () {
                        const val = this.value.toLowerCase().trim();
                        listContainer.querySelectorAll('.batch-item-card').forEach(card => {
                            card.classList.toggle('d-none', !card.textContent.toLowerCase().includes(val));
                        });
                    });
                }
            }
        })
        .catch(error => console.error("Search failed:", error))
        .finally(() => {
            if (searchBtn) {
                searchBtn.disabled = false;
                searchBtn.innerHTML = searchBtnOriginalHTML;
            }
        });
    }
});

// Set selected batch code value back to input and auto-fill related details
document.addEventListener('DOMContentLoaded', function () {
    const listContainer = document.getElementById('batchCodeListResults');
    if (listContainer) {
        listContainer.addEventListener('click', function (e) {
            const card = e.target.closest('.select-batch');
            if (!card || !currentBatchInput) return;

            e.preventDefault();

            const batchCode = card.getAttribute('data-batch-code');
            const grade     = card.getAttribute('data-grade');
            const unit      = card.getAttribute('data-unit');
            const productId = card.getAttribute('data-product-id');
            const locationId = card.getAttribute('data-location-id');

            currentBatchInput.value = batchCode;
            console.log('Batch code set to:', batchCode);

            const row = currentBatchInput.closest('.product-row');
            if (row) {
                // Auto-fill product_id
                const productSelect = row.querySelector('[data-field="product_id"]');
                if (productSelect && productId) {
                    productSelect.value = productId;
                }

                // Auto-fill location_id
                const locationSelect = row.querySelector('[data-field="location_id"]');
                if (locationSelect && locationId) {
                    locationSelect.value = locationId;
                }

                // Auto-fill grade
                const gradeSelect = row.querySelector('[data-field="grade"]');
                if (gradeSelect && grade) {
                    let gradeOptionExists = Array.from(gradeSelect.options).some(o => o.value.toLowerCase() === grade.toLowerCase());
                    if (!gradeOptionExists) {
                        const newOpt = document.createElement('option');
                        newOpt.value = grade;
                        newOpt.textContent = grade;
                        gradeSelect.appendChild(newOpt);
                    }
                    gradeSelect.value = grade;
                }

                // Auto-fill unit
                const unitSelect = row.querySelector('[data-field="unit"]');
                if (unitSelect && unit) {
                    let unitOptionExists = Array.from(unitSelect.options).some(o => o.value.toLowerCase() === unit.toLowerCase());
                    if (!unitOptionExists) {
                        const newOpt = document.createElement('option');
                        newOpt.value = unit;
                        newOpt.textContent = unit;
                        unitSelect.appendChild(newOpt);
                    }
                    unitSelect.value = unit;
                }
            }

            // Close batch modal, returning to trip modal
            const modalEl = document.getElementById('staticBackdropBatchCode');
            const modal   = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        });
    }

    // Auto-reopen tripModal when staticBackdropBatchCode is closed
    const batchModalEl = document.getElementById('staticBackdropBatchCode');
    if (batchModalEl) {
        batchModalEl.addEventListener('hidden.bs.modal', function () {
            const parentModalEl = document.getElementById('tripModal');
            if (parentModalEl) {
                const parentModal = bootstrap.Modal.getOrCreateInstance(parentModalEl);
                parentModal.show();
            }
        });
    }
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
    // const returnedContainer = document.getElementById('productsReturnedContainer');

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

    // document.getElementById('addProductReturnedBtn').addEventListener('click', () => {
    //     addRow(returnedContainer, 'returned');
    // });

    // Remove row + reindex
    document.addEventListener('click', e => {
        if (e.target.classList.contains('removeProductRow')) {
            const row = e.target.closest('.product-row');
            const parent = row.parentElement;
            row.remove();
            if (parent.id.includes('Sent')) reindex(parent, 'sent');
            // else reindex(parent, 'returned');
        }
    });

    // Initialize with one default row each
    addRow(sentContainer, 'sent');
    // addRow(returnedContainer, 'returned');


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
        // const returned = collectRows(returnedContainer);

        console.log(sent)

        // console.log(sent)

        const data = {
            route_id,
            vehicle_id,
            start_date,
            tag,
            sent,
            // returned
        };

        createTrip(data);
    });

    // helper to collect all dynamic rows
    function collectRows(container) {
        if (!container) return [];
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
                } else if (result.success === false) {
                    alert(result.message || "Failed to create trip due to an error.");
                } else {
                    alert(result.message || "Trip created successfully.");
                    console.log("Trip created:", result);
                    // Reset form and close modal
                    document.getElementById('fleetTripForm').reset();
                    sentContainer.innerHTML = '';
                    addRow(sentContainer, 'sent');
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

    // ----------------------------------------------------------------
    // Adjust Trip Functionality
    // ----------------------------------------------------------------
    document.addEventListener('click', function (e) {
        const adjustBtn = e.target.closest('.adjust-trip-btn');
        if (!adjustBtn) return;

        const tripId = adjustBtn.getAttribute('data-id');
        const modalEl = document.getElementById('adjustTripModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Reset modal state
        const loader = document.getElementById('adjustModalLoader');
        const form = document.getElementById('adjustTripForm');
        const submitBtn = document.getElementById('submitAdjustTripBtn');
        const errorAlert = document.getElementById('adjustTripErrorAlert');
        
        loader.classList.remove('d-none');
        form.classList.add('d-none');
        submitBtn.disabled = true;
        errorAlert.classList.add('d-none');
        errorAlert.textContent = '';

        // Fetch routes, vehicles and trip details in parallel
        const promises = [
            fetch('/api/fleet-routes', { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
            fetch('/fleet/vehicles', { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
            fetch(`/fleet-trips/${tripId}/details`, { headers: { 'Accept': 'application/json' } }).then(r => r.json())
        ];

        Promise.all(promises)
            .then(([routes, vehicles, tripRes]) => {
                if (!tripRes.success) {
                    throw new Error(tripRes.message || 'Failed to fetch trip details.');
                }

                const tripData = tripRes.data.trip;
                const sentProducts = tripRes.data.products_sent;

                // Populate route select
                const routeSelect = document.getElementById('adjust_route');
                routeSelect.innerHTML = '';
                routes.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.name;
                    if (parseInt(r.id) === parseInt(tripData.route_id)) {
                        opt.selected = true;
                    }
                    routeSelect.appendChild(opt);
                });

                // Populate vehicle select
                const vehicleSelect = document.getElementById('adjust_vehicle');
                vehicleSelect.innerHTML = '';
                vehicles.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.id;
                    opt.textContent = v.registration_number;
                    if (parseInt(v.id) === parseInt(tripData.vehicle_id)) {
                        opt.selected = true;
                    }
                    vehicleSelect.appendChild(opt);
                });

                // Set metadata values
                document.getElementById('adjust_trip_id').value = tripId;
                document.getElementById('adjust_date').value = tripData.start_date.split(' ')[0];
                document.getElementById('adjust_tag').value = tripData.tag || '';

                // Populate items table
                const tbody = document.getElementById('adjustTripItemsTable');
                tbody.innerHTML = '';

                sentProducts.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <strong>${item.product_name}</strong><br>
                            <small class="text-muted">Batch: ${item.batch} | Grade: ${item.grade || 'N/A'}</small>
                        </td>
                        <td>${item.location_name}</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control adjust-item-qty" 
                                       data-stock-id="${item.id}" 
                                       value="${item.quantity}" 
                                       min="1" step="1" required>
                                <span class="input-group-text">${item.unit || ''}</span>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                // Hide loader and show form
                loader.classList.add('d-none');
                form.classList.remove('d-none');
                submitBtn.disabled = false;
            })
            .catch(err => {
                loader.classList.add('d-none');
                errorAlert.textContent = err.message || 'An error occurred while loading trip details.';
                errorAlert.classList.remove('d-none');
            });
    });

    // Handle submit adjustment
    const submitAdjustBtn = document.getElementById('submitAdjustTripBtn');
    if (submitAdjustBtn) {
        submitAdjustBtn.addEventListener('click', function () {
            const tripId = document.getElementById('adjust_trip_id').value;
            const submitSpinner = document.getElementById('adjustSubmitSpinner');
            const errorAlert = document.getElementById('adjustTripErrorAlert');

            submitAdjustBtn.disabled = true;
            submitSpinner.classList.remove('d-none');
            errorAlert.classList.add('d-none');
            errorAlert.textContent = '';

            const payload = {
                route_id: document.getElementById('adjust_route').value,
                vehicle_id: document.getElementById('adjust_vehicle').value,
                start_date: document.getElementById('adjust_date').value,
                tag: document.getElementById('adjust_tag').value,
                items: []
            };

            document.querySelectorAll('.adjust-item-qty').forEach(input => {
                payload.items.push({
                    id: input.getAttribute('data-stock-id'),
                    quantity: parseInt(input.value)
                });
            });

            fetch(`/fleet-trips/${tripId}/adjust`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    // Display validation errors or general exception
                    if (data.errors) {
                        let errorMsg = 'Validation failed:\n';
                        for (let k in data.errors) {
                            errorMsg += `- ${data.errors[k].join(', ')}\n`;
                        }
                        throw new Error(errorMsg);
                    } else {
                        throw new Error(data.message || 'Failed to adjust trip.');
                    }
                }
            })
            .catch(err => {
                submitAdjustBtn.disabled = false;
                submitSpinner.classList.add('d-none');
                errorAlert.innerHTML = err.message.replace(/\n/g, '<br>');
                errorAlert.classList.remove('d-none');
            });
        });
    }

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