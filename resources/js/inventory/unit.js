// resources/js/inventory/unit.js
import axios from 'axios';

// Attach CSRF token globally
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

document.addEventListener('DOMContentLoaded', () => {

    // ── DOM refs ──────────────────────────────────────────────────────────
    const tableBody       = document.getElementById('unitTableBody');

    // Add modal
    const addModalEl      = document.getElementById('addUnitModal');
    const addModal        = addModalEl ? bootstrap.Modal.getOrCreateInstance(addModalEl) : null;
    const btnSave         = document.getElementById('btnSaveUnit');

    // Edit modal
    const editModalEl     = document.getElementById('editUnitModal');
    const editModal       = editModalEl ? bootstrap.Modal.getOrCreateInstance(editModalEl) : null;
    const btnUpdate       = document.getElementById('btnUpdateUnit');
    const editIdInput     = document.getElementById('edit_unit_id');

    // Delete modal
    const deleteModalEl   = document.getElementById('deleteUnitModal');
    const deleteModal     = deleteModalEl ? bootstrap.Modal.getOrCreateInstance(deleteModalEl) : null;
    const btnConfirmDel   = document.getElementById('btnConfirmDeleteUnit');
    const deleteNameSpan  = document.getElementById('deleteUnitName');

    let deletingId = null;

    // ── Initial load ──────────────────────────────────────────────────────
    fetchUnits();

    // Reset add form when modal closes
    addModalEl?.addEventListener('hidden.bs.modal', () => {
        clearForm('add');
        clearErrors('add');
    });

    // Reset edit form when modal closes
    editModalEl?.addEventListener('hidden.bs.modal', () => {
        clearForm('edit');
        clearErrors('edit');
    });

    // ── Save (create) ─────────────────────────────────────────────────────
    btnSave?.addEventListener('click', () => {
        clearErrors('add');

        const payload = {
            name:         document.getElementById('add_unit_name').value.trim(),
            abbreviation: document.getElementById('add_unit_abbreviation').value.trim(),
        };

        axios.post('/api/units', payload)
            .then(() => {
                addModal?.hide();
                fetchUnits();
            })
            .catch(err => handleErrors(err, 'add'));
    });

    // ── Update ────────────────────────────────────────────────────────────
    btnUpdate?.addEventListener('click', () => {
        const id = editIdInput.value;
        if (!id) return;
        clearErrors('edit');

        const payload = {
            name:         document.getElementById('edit_unit_name').value.trim(),
            abbreviation: document.getElementById('edit_unit_abbreviation').value.trim(),
        };

        axios.put(`/api/units/${id}`, payload)
            .then(() => {
                editModal?.hide();
                fetchUnits();
            })
            .catch(err => handleErrors(err, 'edit'));
    });

    // ── Delete confirm ────────────────────────────────────────────────────
    btnConfirmDel?.addEventListener('click', () => {
        if (!deletingId) return;
        axios.delete(`/api/units/${deletingId}`)
            .then(() => {
                deleteModal?.hide();
                deletingId = null;
                fetchUnits();
            })
            .catch(() => alert('Failed to delete unit. Please try again.'));
    });

    deleteModalEl?.addEventListener('hidden.bs.modal', () => { deletingId = null; });

    // ================================================================
    // Core functions
    // ================================================================

    function fetchUnits() {
        tableBody.innerHTML = `
            <tr id="unit-loading-row">
                <td colspan="5" class="text-center text-muted py-3">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Loading units…
                </td>
            </tr>`;

        axios.get('/api/units')
            .then(res => {
                const units = Array.isArray(res.data) ? res.data : [];

                if (!units.length) {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">No units found.</td></tr>`;
                    return;
                }

                tableBody.innerHTML = units.map((u, i) => rowHtml(u, i)).join('');
                attachRowEvents();
            })
            .catch(() => {
                tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">Failed to load units.</td></tr>`;
            });
    }

    function rowHtml(u, index) {
        // Format the date if present, or show a simple placeholder/empty
        let formattedDate = '—';
        if (u.created_at) {
            const dateObj = new Date(u.created_at);
            if (!isNaN(dateObj)) {
                const day = String(dateObj.getDate()).padStart(2, '0');
                const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                const year = dateObj.getFullYear();
                formattedDate = `${day}/${month}/${year}`;
            }
        }

        return `
            <tr style="text-align:center;" id="unit_row_${u.id}">
                <td>${index + 1}</td>
                <td>${escHtml(u.name)}</td>
                <td>${escHtml(u.abbreviation)}</td>
                <td>${formattedDate}</td>
                <td>
                    <button class="btn btn-sm btn-primary editUnitBtn me-1"
                        data-id="${u.id}"
                        data-name="${escAttr(u.name)}"
                        data-abbreviation="${escAttr(u.abbreviation)}">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-danger deleteUnitBtn"
                        data-id="${u.id}"
                        data-name="${escAttr(u.name)}">
                        <i class="bi bi-trash3"></i>
                    </button>
                </td>
            </tr>`;
    }

    function attachRowEvents() {
        // Edit
        document.querySelectorAll('.editUnitBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                clearErrors('edit');

                editIdInput.value = btn.dataset.id;
                document.getElementById('edit_unit_name').value         = btn.dataset.name        ?? '';
                document.getElementById('edit_unit_abbreviation').value = btn.dataset.abbreviation ?? '';

                editModal?.show();
            });
        });

        // Delete
        document.querySelectorAll('.deleteUnitBtn').forEach(btn => {
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
        if (prefix === 'add') {
            document.getElementById('add_unit_name').value = '';
            document.getElementById('add_unit_abbreviation').value = '';
        } else {
            document.getElementById('edit_unit_name').value = '';
            document.getElementById('edit_unit_abbreviation').value = '';
            editIdInput.value = '';
        }
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
