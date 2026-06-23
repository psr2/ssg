// resources/js/inventory/product.js
import axios from 'axios';

// Attach CSRF token globally
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

document.addEventListener('DOMContentLoaded', () => {

    // ── DOM refs ──────────────────────────────────────────────────────────
    const tableBody       = document.getElementById('productTableBody');

    // Add modal
    const addModalEl      = document.getElementById('addProductModal');
    const addModal        = addModalEl ? bootstrap.Modal.getOrCreateInstance(addModalEl) : null;
    const btnSave         = document.getElementById('btnSaveProduct');

    // Edit modal
    const editModalEl     = document.getElementById('editProductModal');
    const editModal       = editModalEl ? bootstrap.Modal.getOrCreateInstance(editModalEl) : null;
    const btnUpdate       = document.getElementById('btnUpdateProduct');
    const editIdInput     = document.getElementById('edit_product_id');

    // Delete modal
    const deleteModalEl   = document.getElementById('deleteProductModal');
    const deleteModal     = deleteModalEl ? bootstrap.Modal.getOrCreateInstance(deleteModalEl) : null;
    const btnConfirmDel   = document.getElementById('btnConfirmDeleteProduct');
    const deleteNameSpan  = document.getElementById('deleteProductName');

    let deletingId = null;

    // ── Initial load ──────────────────────────────────────────────────────
    fetchProducts();

    // Reset add form when modal closes
    addModalEl?.addEventListener('hidden.bs.modal', () => {
        clearForm('add');
        clearErrors('add');
    });

    // ── Save (create) ─────────────────────────────────────────────────────
    btnSave?.addEventListener('click', () => {
        clearErrors('add');

        const payload = {
            name:         document.getElementById('add_name').value.trim(),
            abbreviation: document.getElementById('add_abbreviation').value.trim(),
            unit_id:      document.getElementById('add_unit_id').value,
            category:     document.getElementById('add_category').value.trim() || null,
            sku:          document.getElementById('add_sku').value.trim()      || null,
            description:  document.getElementById('add_description').value.trim() || null,
        };

        axios.post('/api/products', payload)
            .then(() => {
                addModal?.hide();
                fetchProducts();
            })
            .catch(err => handleErrors(err, 'add'));
    });

    // ── Update ────────────────────────────────────────────────────────────
    btnUpdate?.addEventListener('click', () => {
        const id = editIdInput.value;
        if (!id) return;
        clearErrors('edit');

        const payload = {
            name:         document.getElementById('edit_name').value.trim(),
            abbreviation: document.getElementById('edit_abbreviation').value.trim(),
            unit_id:      document.getElementById('edit_unit_id').value,
            category:     document.getElementById('edit_category').value.trim() || null,
            sku:          document.getElementById('edit_sku').value.trim()      || null,
            description:  document.getElementById('edit_description').value.trim() || null,
        };

        axios.put(`/api/products/${id}`, payload)
            .then(() => {
                editModal?.hide();
                fetchProducts();
            })
            .catch(err => handleErrors(err, 'edit'));
    });

    // ── Delete confirm ────────────────────────────────────────────────────
    btnConfirmDel?.addEventListener('click', () => {
        if (!deletingId) return;
        axios.delete(`/api/products/${deletingId}`)
            .then(() => {
                deleteModal?.hide();
                deletingId = null;
                fetchProducts();
            })
            .catch(() => alert('Failed to delete product. Please try again.'));
    });

    deleteModalEl?.addEventListener('hidden.bs.modal', () => { deletingId = null; });

    // ================================================================
    // Core functions
    // ================================================================

    function fetchProducts() {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-3">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Loading products…
                </td>
            </tr>`;

        axios.get('/api/products')
            .then(res => {
                const products = Array.isArray(res.data) ? res.data : [];

                if (!products.length) {
                    tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">No products found.</td></tr>`;
                    return;
                }

                tableBody.innerHTML = products.map((p, i) => rowHtml(p, i)).join('');
                attachRowEvents();
            })
            .catch(() => {
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-3">Failed to load products.</td></tr>`;
            });
    }

    function rowHtml(p, index) {
        return `
            <tr style="text-align:center;" id="product_row_${p.id}">
                <td>${index + 1}</td>
                <td>${escHtml(p.name)}</td>
                <td>${escHtml(p.abbreviation)}</td>
                <td>${escHtml(p.unit_name)}</td>
                <td>${escHtml(p.category ?? '—')}</td>
                <td>${escHtml(p.sku ?? '—')}</td>
                <td>
                    <button class="btn btn-sm btn-primary editProductBtn me-1"
                        data-id="${p.id}"
                        data-name="${escAttr(p.name)}"
                        data-abbreviation="${escAttr(p.abbreviation)}"
                        data-unit_id="${p.unit_id ?? ''}"
                        data-category="${escAttr(p.category ?? '')}"
                        data-sku="${escAttr(p.sku ?? '')}"
                        data-description="${escAttr(p.description ?? '')}">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-danger deleteProductBtn"
                        data-id="${p.id}"
                        data-name="${escAttr(p.name)}">
                        <i class="bi bi-trash3"></i>
                    </button>
                </td>
            </tr>`;
    }

    function attachRowEvents() {
        // Edit
        document.querySelectorAll('.editProductBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                clearErrors('edit');

                editIdInput.value = btn.dataset.id;
                document.getElementById('edit_name').value         = btn.dataset.name        ?? '';
                document.getElementById('edit_abbreviation').value = btn.dataset.abbreviation ?? '';
                document.getElementById('edit_unit_id').value      = btn.dataset.unit_id     ?? '';
                document.getElementById('edit_category').value     = btn.dataset.category    ?? '';
                document.getElementById('edit_sku').value          = btn.dataset.sku         ?? '';
                document.getElementById('edit_description').value  = btn.dataset.description ?? '';

                editModal?.show();
            });
        });

        // Delete
        document.querySelectorAll('.deleteProductBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                deletingId = btn.dataset.id;
                deleteNameSpan.textContent = btn.dataset.name;
                deleteModal?.show();
            });
        });
    }

    // ── Error helpers ─────────────────────────────────────────────────────
    function handleErrors(err, prefix) {
        if (err.response?.status === 422) {
            const errors = err.response.data.errors ?? {};
            Object.keys(errors).forEach(field => {
                const span = document.getElementById(`err_${prefix}_${field}`);
                if (span) span.textContent = errors[field][0];
            });
        } else {
            console.error(err);
            alert('Something went wrong. Please try again.');
        }
    }

    function clearErrors(prefix) {
        document.querySelectorAll(`[id^="err_${prefix}_"]`).forEach(el => el.textContent = '');
    }

    function clearForm(prefix) {
        ['name', 'abbreviation', 'unit_id', 'category', 'sku', 'description'].forEach(field => {
            const el = document.getElementById(`${prefix}_${field}`);
            if (el) el.value = el.tagName === 'SELECT' ? '' : '';
        });
    }

    // ── Escape helpers ────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
    function escAttr(str) {
        return escHtml(str).replaceAll('\n', ' ');
    }
});
