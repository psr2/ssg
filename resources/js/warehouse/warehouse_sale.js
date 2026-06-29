/**
 * Warehouse Sale JS
 * Mirrors shop/shop_sale.js — uses wh_ prefixed element IDs
 */

const whAddItemBtn      = document.getElementById('wh_addItemBtn');
const whItemsTableBody  = document.querySelector('#wh_itemsTable tbody');
const whGrandTotalInput = document.getElementById('wh_grand_total');
let whProductList       = null;
document.addEventListener('DOMContentLoaded', async function () {
    const gradesDataEl = document.getElementById('grades-data');
    const dbGrades = gradesDataEl ? JSON.parse(gradesDataEl.getAttribute('data-grades')) : [];

    let gradeOpts = '<option selected disabled>select</option>';
    if (dbGrades && dbGrades.length > 0) {
        dbGrades.forEach(g => {
            gradeOpts += `<option value="${g.code}">${g.name}</option>`;
        });
    } else {
        const defaults = [
            { code: 'A', name: 'Grade A' },
            { code: 'B', name: 'Grade B' },
            { code: 'C', name: 'Grade C' },
            { code: 'Waste', name: 'Waste' }
        ];
        defaults.forEach(g => {
            gradeOpts += `<option value="${g.code}">${g.name}</option>`;
        });
    }

    // ── Grand total recalculation ─────────────────────────────────────────────
    function recalcGrandTotal() {
        let total = 0;
        whItemsTableBody.querySelectorAll('tr').forEach(row => {
            const tf = row.querySelector('.wh-item-total');
            if (tf && tf.value) total += parseFloat(tf.value) || 0;
        });
        whGrandTotalInput.value = total.toFixed(2);
    }

    // ── Add a new item row ────────────────────────────────────────────────────
    function addRow() {
        const rowIndex = whItemsTableBody.querySelectorAll('tr').length;
        const row      = document.createElement('tr');
        row.setAttribute('data-index', rowIndex);

        let productTd = '';
        if (whProductList) {
            let opts = '';
            whProductList.forEach(p => {
                opts += `<option value="${p.id}">${p.name}</option>`;
            });
            productTd = `<td style="width:15%;">
                <select class="form-select wh-item-product" name="items[product][]" required>
                    <option selected disabled>select</option>
                    ${opts}
                </select>
                <span class="error error-items-${rowIndex}-product text-danger text-small"></span>
            </td>`;
        }

        row.innerHTML = `${productTd}
            <td style="width:20%;">
                <input type="text" readonly class="form-control wh-item-batch-code"
                    name="items[batch_code][]"
                    data-bs-toggle="modal" data-bs-target="#staticBackdropBatchCode">
                <span class="error error-items-${rowIndex}-batch_code text-danger text-small"></span>
            </td>
            <td style="width:15%;">
                <select class="form-select wh-item-grade" name="items[grade][]">
                    ${gradeOpts}
                </select>
                <span class="error error-items-${rowIndex}-grade text-danger text-small"></span>
            </td>
            <td>
                <input type="number" class="form-control wh-item-qty" name="items[qty][]" min="0" step="any" required>
                <span class="error error-items-${rowIndex}-quantity text-danger text-small"></span>
            </td>
            <td style="width:15%;">
                <select class="form-select wh-item-unit" name="items[unit][]">
                    <option selected disabled>select</option>
                    <option value="kg">kg</option>
                    <option value="pcs">pcs</option>
                </select>
                <span class="error error-items-${rowIndex}-unit text-danger text-small"></span>
            </td>
            <td>
                <input type="number" class="form-control wh-item-price" name="items[unit_price][]" min="0" step="any" required>
                <span class="error error-items-${rowIndex}-unit_price text-danger text-small"></span>
            </td>
            <td>
                <input type="number" class="form-control wh-item-total" name="items[total][]" readonly>
                <span class="error error-items-${rowIndex}-total_price text-danger text-small"></span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger wh-removeRowBtn">
                    <i class="bi-trash"></i>
                </button>
            </td>`;

        const qtyInput   = row.querySelector('.wh-item-qty');
        const priceInput = row.querySelector('.wh-item-price');
        const totalInput = row.querySelector('.wh-item-total');

        function recalcRowTotal() {
            const qty   = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            totalInput.value = (qty * price).toFixed(2);
            recalcGrandTotal();
        }

        qtyInput.addEventListener('input', recalcRowTotal);
        priceInput.addEventListener('input', recalcRowTotal);

        row.querySelector('.wh-removeRowBtn').addEventListener('click', function () {
            row.remove();
            recalcGrandTotal();
            reindexRows();
        });

        whItemsTableBody.appendChild(row);
    }

    function reindexRows() {
        whItemsTableBody.querySelectorAll('tr').forEach((row, i) => {
            row.setAttribute('data-index', i);
            row.querySelectorAll('span.error').forEach(span => {
                span.className = span.className.replace(/error-items-\d+-/, `error-items-${i}-`);
            });
        });
    }

    whAddItemBtn.addEventListener('click', addRow);

    await whGetProductList();
    addRow();
});


// ── Save Sale ────────────────────────────────────────────────────────────────
document.getElementById('wh_btn_sale').addEventListener('click', async (e) => {
    e.preventDefault();

    const payload = {
        shop_id:         document.getElementById('wh_shop_id').value || '',
        payment_date:    document.getElementById('wh_payment_date').value || '',
        customer_name:   document.getElementById('wh_customer_name').value.trim(),
        customer_id:     document.getElementById('wh_customer_id').value.trim(),
        bill_no:         document.getElementById('wh_bill_no').value.trim(),
        items:           [],
        payment_status:  document.getElementById('wh_payment_status').value,
        amount_paid:     document.getElementById('wh_amount_paid').value || '',
        grand_total:     whGrandTotalInput.value || '',
        payment_mode:    document.getElementById('wh_payment_mode').value || '',
        notes:           document.getElementById('wh_notes').value || '',
    };

    whItemsTableBody.querySelectorAll('tr').forEach(row => {
        const product    = row.querySelector('.wh-item-product')?.value.trim() || '';
        const batch_code = row.querySelector('.wh-item-batch-code')?.value.trim() || '';
        const qty        = row.querySelector('.wh-item-qty')?.value || '';
        const grade      = row.querySelector('.wh-item-grade')?.value || '';
        const unit       = row.querySelector('.wh-item-unit')?.value || '';
        const unit_price = row.querySelector('.wh-item-price')?.value || '';
        const total_price = row.querySelector('.wh-item-total')?.value || '';

        if (product && qty && unit_price && unit) {
            payload.items.push({ product, batch_code, quantity: qty, grade, unit, unit_price, total_price });
        }
    });

    const newName    = document.getElementById('wh_new_customer_name')?.value.trim();
    const bizName    = document.getElementById('wh_business_name')?.value.trim();
    const contact    = document.getElementById('wh_customer_contact')?.value.trim();
    const locName    = document.getElementById('wh_location_name')?.value.trim();

    if (newName)  { payload.customer_name = newName; delete payload.customer_id; }
    if (bizName)  payload.business_name   = bizName;
    if (contact)  payload.customer_contact = contact;
    if (locName)  payload.location_name   = locName;

    console.log('Warehouse sale payload:', payload);
    await whStoreSale(payload);
});


// ── Submit Sale to API ────────────────────────────────────────────────────────
async function whStoreSale(payload) {
    whClearErrors();

    try {
        const response = await fetch('/warehouse/sale/store', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();
        console.log('Warehouse sale response:', data);

        if (data.message) {
            const span = document.querySelector('.error-items-general');
            if (span) span.textContent = data.message;
        }

        if (!response.ok && data.errors) {
            const commonDiv = document.getElementById('wh_error_common_items');

            if (data.errors['common_error'] && commonDiv) {
                commonDiv.textContent = Array.isArray(data.errors['common_error'])
                    ? data.errors['common_error'].join(' ')
                    : data.errors['common_error'];
            }

            Object.keys(data.errors).forEach(key => {
                if (key === 'items' && Array.isArray(data.errors[key])) {
                    const span = document.querySelector('.error-items-general');
                    if (span) span.innerText = data.errors[key][0];
                } else if (key.startsWith('items.')) {
                    const parts    = key.split('.');
                    const rowIndex = parts[1];
                    const field    = parts[2];
                    const span     = document.querySelector(`.error-items-${rowIndex}-${field}`);
                    if (span) span.innerText = data.errors[key][0];
                } else {
                    const safeKey = key.replace(/\./g, '-');
                    const span    = document.querySelector(`.error-${safeKey}`);
                    if (span) span.innerText = data.errors[key][0];
                }
            });

            return false;
        }

        if (response.ok) {
            // Close modal and reload on success
            const modal = bootstrap.Modal.getInstance(document.getElementById('warehouseSaleModal'));
            if (modal) modal.hide();
            window.location.reload();
        }

    } catch (err) {
        console.error('Warehouse sale error:', err);
        return false;
    }
}

function whClearErrors() {
    document.querySelectorAll('span[class*="error-"]').forEach(el => el.textContent = '');
    const common = document.getElementById('wh_error_common_items');
    if (common) common.textContent = '';
}


// ── Product list fetch ────────────────────────────────────────────────────────
async function whGetProductList() {
    try {
        const response = await fetch('/warehouse/product/list', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });

        const data = await response.json();
        if (response.ok) {
            whProductList = data;
        } else {
            console.warn('Could not load product list:', data);
        }
    } catch (err) {
        console.error('Error fetching warehouse product list:', err);
    }
}


// ── Batch code modal integration ──────────────────────────────────────────────
let whCurrentBatchInput = null;

document.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('wh-item-batch-code')) {
        whCurrentBatchInput = e.target;

        // Auto-populate product and location in the batch code search modal
        const row = e.target.closest('tr');
        if (row) {
            const productSelect = row.querySelector('.wh-item-product');
            const productId = productSelect ? productSelect.value : '';

            const warehouseSelect = document.getElementById('wh_shop_id');
            const warehouseId = warehouseSelect ? warehouseSelect.value : '';

            const modalProductSelect = document.querySelector('#batchCodeSearchForm select[name="product_listing"]');
            const modalLocationSelect = document.querySelector('#batchCodeSearchForm select[name="location"]');

            if (modalProductSelect && productId) {
                modalProductSelect.value = productId;
            }
            if (modalLocationSelect && warehouseId) {
                modalLocationSelect.value = warehouseId;
            }

            // Clear previous results in the modal table
            const tbody = document.querySelector('#batchCodeResults tbody');
            if (tbody) {
                tbody.innerHTML = '';
            }

            // Auto-trigger search if both product and location are set
            if (modalProductSelect && modalLocationSelect && productId && warehouseId) {
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
    const data = {
        product_listing: form.product_listing.value,
        location:        form.location.value,
        dateFrom:        form.dateFrom.value,
    };

    fetch('/warehouse/search-batch-code', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify(data),
    })
    .then(res => res.json())
    .then(results => {
        const tbody = document.querySelector('#batchCodeResults tbody');
        tbody.innerHTML = '';

        if (!results.length) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center">No results found.</td></tr>`;
            return;
        }

        results.forEach((item, i) => {
            tbody.innerHTML += `
            <tr>
                <td>${i + 1}</td>
                <td>${item.batch_code}</td>
                <td>${item.product}</td>
                <td>${item.grade}</td>
                <td>${item.location}</td>
                <td>${item.available_qty}</td>
                <td>
                    <button data-bs-target="#warehouseSaleModal" data-bs-toggle="modal"
                        class="btn btn-sm btn-success wh-select-batch"
                        data-batch-code="${item.batch_code}"
                        data-grade="${item.grade}">Select</button>
                </td>
            </tr>`;
        });
    })
    .catch(err => console.error('Batch search failed:', err));
});

document.querySelector('#batchCodeResults tbody')?.addEventListener('click', function (e) {
    const btn = e.target.closest('.wh-select-batch, .select-batch');
    if (btn && whCurrentBatchInput) {
        const batchCode = btn.getAttribute('data-batch-code');
        const grade = btn.getAttribute('data-grade');
        
        whCurrentBatchInput.value = batchCode;

        // Auto-select grade in the product row
        const row = whCurrentBatchInput.closest('tr');
        if (row) {
            const gradeSelect = row.querySelector('.wh-item-grade');
            if (gradeSelect && grade) {
                gradeSelect.value = grade;
            }
        }
    }
});


// ── Edit sale record → open update payments modal ─────────────────────────────
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.wh-edit-sale');
    if (!btn) return;

    const row = btn.closest('tr');
    if (!row) return;

    const cells = row.querySelectorAll('td');

    document.getElementById('wh_update_customer_name').value  = cells[1]?.textContent.trim() || '';
    document.getElementById('wh_update_total_bill').value     = cells[2]?.textContent.trim().replace(/[^\d.]/g, '') || '';
    document.getElementById('wh_update_pending_amount').value = cells[4]?.textContent.trim().replace(/[^\d.]/g, '') || '';
    document.getElementById('wh_new_amount').value            = '';

    document.whSaleIdToUpdate        = btn.dataset.sale_id;
    document.whCustomerIdToUpdate    = btn.dataset.customer_id;
    document.whTimestamp             = btn.dataset.timestamp;
});


// ── Submit payment update ─────────────────────────────────────────────────────
document.getElementById('wh_update_fetch').addEventListener('click', function (e) {
    e.preventDefault();

    const formData = {
        customer_name:   document.getElementById('wh_update_customer_name').value,
        total_bill:      document.getElementById('wh_update_total_bill').value,
        pending_amount:  document.getElementById('wh_update_pending_amount').value,
        new_amount:      document.getElementById('wh_new_amount').value,
        sale_id:         document.whSaleIdToUpdate,
        customer_id:     document.whCustomerIdToUpdate,
        last_updated:    document.whTimestamp,
        payment_method:  document.getElementById('wh_payment_method').value,
    };

    console.log('Warehouse update payload:', formData);
    whSubmitPaymentUpdate(formData);
});

function whSubmitPaymentUpdate(formData) {
    fetch('/warehouse/sale/payments/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify(formData),
    })
    .then(async response => {
        if (!response.ok) {
            const data = await response.json();
            whShowErrors(data.errors || {});
        } else {
            const data = await response.json();
            console.log('Payment updated:', data);

            const modal = bootstrap.Modal.getInstance(document.getElementById('warehouseUpdatePaymentsModal'));
            if (modal) modal.hide();
            document.getElementById('wh_filterForm').reset();

            window.location.reload();
        }
    })
    .catch(err => console.error('Payment update request failed:', err));
}

function whShowErrors(errors) {
    for (const [field, messages] of Object.entries(errors)) {
        const span = document.querySelector(`.error_${field.replace(/\./g, '_')}`) ||
                     document.querySelector(`.error-${field.replace(/\./g, '_')}`);
        if (span) span.textContent = Array.isArray(messages) ? messages.join(', ') : messages;
    }
}

// ── Delete sale record ────────────────────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.wh-delete-sale');
    if (!btn) return;

    const saleId = btn.getAttribute('data-sale_id');
    if (!saleId) return;

    if (confirm('Are you sure you want to delete this sale and restore its stock to the warehouse inventory?')) {
        fetch(`/warehouse/sale/${saleId}/delete`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(async response => {
            const data = await response.json();
            if (response.ok && data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Failed to delete sale.');
            }
        })
        .catch(err => {
            console.error('Delete sale request failed:', err);
            alert('An error occurred while deleting the sale.');
        });
    }
});

