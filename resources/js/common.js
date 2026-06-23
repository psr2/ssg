export const helpers = {
    fireModel(model_id) {
        const myModal = new bootstrap.Modal(document.getElementById(model_id));
        myModal.show();
    },

    resetForm(form_id) {
        document.getElementById(form_id).reset();
    },

    resetErrorFields() {
        document.querySelectorAll('div[class^="error-"], span[class^="error-"]').forEach(el => {
            el.textContent = '';
        });
    },

    showToast(message, type = 'success') {
        // Create toast container if not exists
        if (!document.getElementById('toast-container')) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
        }

        // Create a unique ID for the toast
        const toastId = `toast-${Date.now()}`;

        // Create toast HTML
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast align-items-center text-bg-${type} border-0 show mb-2`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        // Append toast to container
        document.getElementById('toast-container').appendChild(toast);

        // Initialize and show the toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        // Optional: remove toast from DOM after hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
};
