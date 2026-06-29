document.addEventListener("DOMContentLoaded", function() {

    const modal = new bootstrap.Modal(document.getElementById('updateStockModal'));
    const submitBtn = document.getElementById('submitAdjustment');

    let currentButton = null;
    let original = {};

    // Open modal + populate fields
    document.querySelectorAll('.update-btn').forEach(btn => {
        btn.addEventListener('click', function() {

            currentButton = this;

            original = {
                quantity: parseFloat(this.dataset.quantity),
                unit: this.dataset.unit || "",
                locationId: this.dataset.locationId || ""
            };

            // Fill modal fields
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_product').value = this.dataset.product;
            document.getElementById('edit_batch').value = this.dataset.batch;
            document.getElementById('edit_quantity').value = original.quantity;
            document.getElementById('edit_unit').value = original.unit;
            document.getElementById('edit_remarks').value = this.dataset.remarks || '';
            document.getElementById('current_location').value = this.dataset.locationName || '';
            document.getElementById('new_location').value = "";

            // Clear all previous field errors
            clearFieldErrors();

            // Disable submit by default
            submitBtn.disabled = true;

            modal.show();
        });
    });

    // Enable submit if something changed
    function checkSubmitEnabled() {
        const newQuantity = parseFloat(document.getElementById('edit_quantity').value);
        const newUnit = document.getElementById('edit_unit').value;
        const newLocation = document.getElementById('new_location').value;

        const quantityChanged = newQuantity !== original.quantity;
        const unitChanged = newUnit !== "" && newUnit !== original.unit;
        const locationChanged = newLocation !== "" && newLocation !== original.locationId;

        submitBtn.disabled = !(quantityChanged || unitChanged || locationChanged);
    }

    document.getElementById('edit_quantity').addEventListener('input', checkSubmitEnabled);
    document.getElementById('edit_unit').addEventListener('change', checkSubmitEnabled);
    document.getElementById('new_location').addEventListener('change', checkSubmitEnabled);

    // Handle modal submit
    submitBtn.addEventListener('click', function() {

        if (!currentButton) return;

        const payload = {};

        const newQuantity = parseFloat(document.getElementById('edit_quantity').value);
        const newUnit = document.getElementById('edit_unit').value;
        const newLocationId = document.getElementById('new_location').value;
        const remarks = document.getElementById('edit_remarks').value;
        const batch = document.getElementById('edit_batch').value;
        const id = currentButton.dataset.id;

        // Original values
        const originalQuantity = original.quantity;
        const originalUnit = original.unit;
        const originalLocationId = original.locationId;

        // Build payload only with meaningful updates
        payload.id = id;
        payload.batch = batch;
        payload.remarks = remarks;

        if (newQuantity !== originalQuantity) payload.quantity = newQuantity;
        if (newUnit && newUnit !== originalUnit) payload.unit = newUnit;
        if (newLocationId && newLocationId !== originalLocationId) payload.new_location_id = newLocationId;

        // Clear previous errors
        clearFieldErrors();

        // AJAX POST
        fetch(`/stock-adjustments/`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
        .then(async response => {

            if (response.status === 422) {
                const data = await response.json();
                showFieldErrors(data.errors);
                return;
            }

            if (!response.ok) {
                alert("Something went wrong.");
                return;
            }

            // Success: reload page
            location.reload();
        })
        .catch(err => {
            console.error("AJAX Error:", err);
        });
    });

    // Void Modal Handlers
    const voidModal = new bootstrap.Modal(document.getElementById('voidStockModal'));
    const submitVoidBtn = document.getElementById('submitVoid');

    document.querySelectorAll('.void-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('void_id').value = this.dataset.id;
            document.getElementById('void_product_display').textContent = this.dataset.product;
            document.getElementById('void_batch_display').textContent = this.dataset.batch;
            document.getElementById('void_qty_display').textContent = this.dataset.quantity;
            document.getElementById('void_remarks').value = '';
            document.getElementById('error_void_remarks').textContent = '';

            voidModal.show();
        });
    });

    submitVoidBtn.addEventListener('click', function() {
        const id = document.getElementById('void_id').value;
        const remarks = document.getElementById('void_remarks').value;
        document.getElementById('error_void_remarks').textContent = '';

        if (!remarks || remarks.trim().length < 10) {
            document.getElementById('error_void_remarks').textContent = 'Please provide a detailed remark of at least 10 characters.';
            return;
        }

        fetch(`/stock-adjustments/${id}/void`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ remarks: remarks })
        })
        .then(async response => {
            if (response.status === 422) {
                const data = await response.json();
                if (data.errors && data.errors.remarks) {
                    document.getElementById('error_void_remarks').textContent = data.errors.remarks.join(", ");
                } else if (data.message) {
                    document.getElementById('error_void_remarks').textContent = data.message;
                }
                return;
            }

            if (!response.ok) {
                alert("Void operation failed. Make sure there are no downstream movements for this batch.");
                return;
            }

            location.reload();
        })
        .catch(err => {
            console.error("AJAX Error:", err);
        });
    });

    // Clear all field error spans
    function clearFieldErrors() {
        const errorSpans = document.querySelectorAll('[id^="error_"]');
        errorSpans.forEach(span => span.textContent = "");
    }

    // Show backend validation errors under each input
    function showFieldErrors(errors) {
        Object.keys(errors).forEach(field => {
            const spanId = "error_" + field.replace(/\./g, "_"); // replace dots in nested keys
            const span = document.getElementById(spanId);
            if (span) {
                span.textContent = errors[field].join(", ");
            }
        });
    }

});
