// resources/js/fleet/fleet_route.js
import axios from "axios";

// Ensure Laravel CSRF header is set for Axios
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) axios.defaults.headers.common["X-CSRF-TOKEN"] = csrf.getAttribute("content");

document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.getElementById("routesBody");
  const form = document.getElementById("routeForm");
  const nameInput = document.getElementById("name");
  const descInput = document.getElementById("description");
  const nameErr = document.querySelector(".name_error");
  const descErr = document.querySelector(".description_error");
  const saveBtn = document.getElementById("saveRoute");
  const modalEl = document.getElementById("routeModal");
  const modalTitle = document.getElementById("routeModalLabel");
  // bootstrap must be globally available (from your app.js/bootstrap.js)
  const routeModal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

  // Track mode (create/update)
  let editingId = null;

  // If user clicks the "Add Route" trigger (data-bs-target="#routeModal"), prep for create mode
  document.querySelectorAll('[data-bs-target="#routeModal"]').forEach(btn => {
    btn.addEventListener("click", () => {
      setCreateMode();
    });
  });

  // Also reset form when modal is hidden
  modalEl?.addEventListener("hidden.bs.modal", () => {
    setCreateMode();
    clearErrors();
  });

  // Load routes on start
  fetchRoutes();

  // Save button handler (works for both create & update)
  saveBtn.addEventListener("click", submitForm);

  // Also allow Enter/submit inside the form
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    submitForm();
  });

  function setCreateMode() {
    editingId = null;
    modalTitle && (modalTitle.textContent = "Add New Route");
    form.reset();
  }

  function setEditMode(route) {
    editingId = route.id;
    modalTitle && (modalTitle.textContent = "Edit Route");
    nameInput.value = route.name ?? "";
    descInput.value = route.description ?? "";
  }

  function submitForm() {
    clearErrors();

    const payload = {
      name: nameInput.value?.trim(),
      description: descInput.value?.trim() || null,
    };

    const req = editingId
      ? axios.put(`/api/fleet-routes/${editingId}`, payload)
      : axios.post("/api/fleet-routes", payload);

    req.then(() => {
      routeModal?.hide();
      form.reset();
      fetchRoutes();
    }).catch(handleValidation);
  }

  function handleValidation(err) {
    if (err.response?.status === 422) {
      const errors = err.response.data.errors || {};
      if (errors.name?.[0]) nameErr.textContent = errors.name[0];
      if (errors.description?.[0]) descErr.textContent = errors.description[0];
    } else {
      // Optional: show a toast/alert
      console.error(err);
      alert("Something went wrong. Please try again.");
    }
  }

  function clearErrors() {
    nameErr.textContent = "";
    descErr.textContent = "";
  }

  function fetchRoutes() {
    axios.get("/api/fleet-routes").then(res => {
      const routes = Array.isArray(res.data) ? res.data : [];
      tableBody.innerHTML = routes.map((route, idx) => rowHtml(route, idx)).join("");
      attachRowEvents();
    });
  }

  function rowHtml(route, index) {
    return `
      <tr>
        <td>${index + 1}</td>
        <td>${escapeHtml(route.name ?? "")}</td>
        <td>${escapeHtml(route.description ?? "")}</td>
        <td class="text-nowrap">
          <button 
            class="btn btn-sm btn-outline-secondary me-1 edit-btn" 
            data-id="${route.id}" 
            data-name="${escapeAttr(route.name ?? "")}" 
            data-description="${escapeAttr(route.description ?? "")}">
            Edit
          </button>
          <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${route.id}">Delete</button>
        </td>
      </tr>
    `;
  }

  function attachRowEvents() {
    // Edit
    document.querySelectorAll(".edit-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        const route = {
          id: btn.getAttribute("data-id"),
          name: btn.getAttribute("data-name") || "",
          description: btn.getAttribute("data-description") || "",
        };
        clearErrors();
        setEditMode(route);
        routeModal?.show();
      });
    });

    // Delete
    document.querySelectorAll(".delete-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        const id = btn.getAttribute("data-id");
        if (!id) return;
        if (confirm("Delete this route?")) {
          axios.delete(`/api/fleet-routes/${id}`).then(() => fetchRoutes());
        }
      });
    });
  }

  // Basic HTML escaping helpers to avoid accidental injection in table
  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }
  function escapeAttr(str) {
    // attributes cannot contain unescaped quotes
    return escapeHtml(str).replaceAll("\n", " ");
  }
});
