// resources/js/fleet/fleet_vehicle.js
import axios from "axios";

// Attach CSRF token to all Axios requests
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) axios.defaults.headers.common["X-CSRF-TOKEN"] = csrf.getAttribute("content");

document.addEventListener("DOMContentLoaded", () => {
    const tableBody   = document.getElementById("vehiclesBody");
    const form        = document.getElementById("vehicleForm");
    const modalEl     = document.getElementById("vehicleModal");
    const modalTitle  = document.getElementById("vehicleModalLabel");
    const saveBtn     = document.getElementById("saveVehicle");

    // Delete confirmation modal
    const deleteModalEl  = document.getElementById("deleteVehicleModal");
    const confirmDeleteBtn = document.getElementById("confirmDeleteVehicle");

    // Bootstrap modal instances
    const vehicleModal = modalEl  ? bootstrap.Modal.getOrCreateInstance(modalEl)  : null;
    const deleteModal  = deleteModalEl ? bootstrap.Modal.getOrCreateInstance(deleteModalEl) : null;

    // Field references
    const regInput       = document.getElementById("registration_number");
    const modelInput     = document.getElementById("model");
    const typeInput      = document.getElementById("type");
    const capacityInput  = document.getElementById("capacity");
    const notesInput     = document.getElementById("notes");

    // Error spans
    const regErr      = document.querySelector(".registration_number_error");
    const modelErr    = document.querySelector(".model_error");
    const typeErr     = document.querySelector(".type_error");
    const capacityErr = document.querySelector(".capacity_error");
    const notesErr    = document.querySelector(".notes_error");

    // State
    let editingId   = null;
    let deletingId  = null;

    // ----------------------------------------------------------------
    // Reset to "Add" mode when the add button triggers the modal
    // ----------------------------------------------------------------
    document.querySelectorAll('[data-bs-target="#vehicleModal"]').forEach(btn => {
        btn.addEventListener("click", () => setCreateMode());
    });

    // Also reset when modal closes
    modalEl?.addEventListener("hidden.bs.modal", () => {
        setCreateMode();
        clearErrors();
    });

    // ----------------------------------------------------------------
    // Initial load
    // ----------------------------------------------------------------
    fetchVehicles();

    // ----------------------------------------------------------------
    // Save (create or update)
    // ----------------------------------------------------------------
    saveBtn.addEventListener("click", submitForm);

    form.addEventListener("submit", (e) => {
        e.preventDefault();
        submitForm();
    });

    // ----------------------------------------------------------------
    // Delete — confirm button inside delete modal
    // ----------------------------------------------------------------
    confirmDeleteBtn?.addEventListener("click", () => {
        if (!deletingId) return;
        axios.delete(`/fleet/vehicles/${deletingId}`)
            .then(() => {
                deleteModal?.hide();
                deletingId = null;
                fetchVehicles();
            })
            .catch(err => {
                console.error("Delete failed:", err);
                alert("Failed to delete vehicle. Please try again.");
            });
    });

    // Clean up deletingId when delete modal is hidden
    deleteModalEl?.addEventListener("hidden.bs.modal", () => {
        deletingId = null;
    });

    // ================================================================
    // Helpers
    // ================================================================

    function setCreateMode() {
        editingId = null;
        if (modalTitle) modalTitle.textContent = "Add Vehicle";
        form.reset();
    }

    function setEditMode(vehicle) {
        editingId                  = vehicle.id;
        if (modalTitle) modalTitle.textContent = "Edit Vehicle";
        regInput.value      = vehicle.registration_number ?? "";
        modelInput.value    = vehicle.model               ?? "";
        typeInput.value     = vehicle.type                ?? "";
        capacityInput.value = vehicle.capacity            ?? "";
        notesInput.value    = vehicle.notes               ?? "";
    }

    function submitForm() {
        clearErrors();

        const payload = {
            registration_number: regInput.value?.trim(),
            model:               modelInput.value?.trim()    || null,
            type:                typeInput.value?.trim()     || null,
            capacity:            capacityInput.value         || null,
            notes:               notesInput.value?.trim()    || null,
        };

        const req = editingId
            ? axios.put(`/fleet/vehicles/${editingId}`, payload)
            : axios.post("/fleet/vehicles", payload);

        req.then(() => {
            vehicleModal?.hide();
            form.reset();
            fetchVehicles();
        }).catch(handleValidation);
    }

    function handleValidation(err) {
        if (err.response?.status === 422) {
            const errors = err.response.data.errors || {};
            if (errors.registration_number?.[0]) regErr.textContent      = errors.registration_number[0];
            if (errors.model?.[0])               modelErr.textContent    = errors.model[0];
            if (errors.type?.[0])                typeErr.textContent     = errors.type[0];
            if (errors.capacity?.[0])            capacityErr.textContent = errors.capacity[0];
            if (errors.notes?.[0])               notesErr.textContent    = errors.notes[0];
        } else {
            console.error(err);
            alert("Something went wrong. Please try again.");
        }
    }

    function clearErrors() {
        regErr.textContent      = "";
        modelErr.textContent    = "";
        typeErr.textContent     = "";
        capacityErr.textContent = "";
        notesErr.textContent    = "";
    }

    function fetchVehicles() {
        axios.get("/fleet/vehicles").then(res => {
            const vehicles = Array.isArray(res.data) ? res.data : [];
            tableBody.innerHTML = vehicles.map((v, idx) => rowHtml(v, idx)).join("");
            attachRowEvents();
        }).catch(err => {
            console.error("Failed to load vehicles:", err);
        });
    }

    function rowHtml(v, index) {
        return `
            <tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(v.registration_number ?? "")}</td>
                <td>${escapeHtml(v.model ?? "—")}</td>
                <td>${escapeHtml(v.type ?? "—")}</td>
                <td>${escapeHtml(v.capacity !== null && v.capacity !== undefined ? String(v.capacity) + " kg" : "—")}</td>
                <td>${escapeHtml(v.notes ?? "—")}</td>
                <td class="text-nowrap">
                    <button
                        class="btn btn-sm btn-outline-secondary me-1 edit-btn"
                        data-id="${v.id}"
                        data-reg="${escapeAttr(v.registration_number ?? "")}"
                        data-model="${escapeAttr(v.model ?? "")}"
                        data-type="${escapeAttr(v.type ?? "")}"
                        data-capacity="${escapeAttr(v.capacity !== null && v.capacity !== undefined ? String(v.capacity) : "")}"
                        data-notes="${escapeAttr(v.notes ?? "")}">
                        Edit
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${v.id}">Delete</button>
                </td>
            </tr>
        `;
    }

    function attachRowEvents() {
        // Edit buttons
        document.querySelectorAll(".edit-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const vehicle = {
                    id:                   btn.getAttribute("data-id"),
                    registration_number:  btn.getAttribute("data-reg")      || "",
                    model:                btn.getAttribute("data-model")    || "",
                    type:                 btn.getAttribute("data-type")     || "",
                    capacity:             btn.getAttribute("data-capacity") || "",
                    notes:                btn.getAttribute("data-notes")    || "",
                };
                clearErrors();
                setEditMode(vehicle);
                vehicleModal?.show();
            });
        });

        // Delete buttons — open confirmation modal instead of browser confirm()
        document.querySelectorAll(".delete-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                deletingId = btn.getAttribute("data-id");
                if (deletingId) deleteModal?.show();
            });
        });
    }

    // ================================================================
    // HTML escaping utilities
    // ================================================================
    function escapeHtml(str) {
        return String(str)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }
    function escapeAttr(str) {
        return escapeHtml(str).replaceAll("\n", " ");
    }
});
