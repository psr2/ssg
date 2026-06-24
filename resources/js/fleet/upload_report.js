document.addEventListener("DOMContentLoaded", function () {
    const uploadModalEl = document.getElementById("uploadReportModal");
    const uploadForm = document.getElementById("uploadReportForm");
    const tripSelect = document.getElementById("report_trip_id");
    const fileInput = document.getElementById("report_file");
    const uploadBtn = document.getElementById("btn-upload-report");
    const errorMsg = document.getElementById("upload-error-message");
    const successMsg = document.getElementById("upload-success-message");

    if (uploadModalEl) {
        // Load latest trips when modal is shown
        uploadModalEl.addEventListener("show.bs.modal", async function () {
            // Reset modal state
            if (uploadForm) uploadForm.reset();
            if (errorMsg) {
                errorMsg.style.display = "none";
                errorMsg.innerText = "";
            }
            if (successMsg) {
                successMsg.style.display = "none";
                successMsg.innerText = "";
            }
            if (uploadBtn) {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = "Upload";
            }

            if (tripSelect) {
                tripSelect.innerHTML = '<option value="" disabled selected>Loading trips...</option>';
                try {
                    const response = await fetch("/fleet/sale/latest-trips");
                    if (!response.ok) {
                        throw new Error("Failed to fetch trips");
                    }
                    const trips = await response.json();
                    tripSelect.innerHTML = '<option value="" disabled selected>Select a Trip</option>';
                    if (trips && trips.length > 0) {
                        trips.forEach(trip => {
                            const option = document.createElement("option");
                            option.value = trip.id;
                            option.innerText = trip.display;
                            tripSelect.appendChild(option);
                        });
                    } else {
                        tripSelect.innerHTML = '<option value="" disabled>No recent trips found</option>';
                    }
                } catch (error) {
                    console.error("Error loading trips:", error);
                    tripSelect.innerHTML = '<option value="" disabled>Error loading trips</option>';
                }
            }
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener("submit", async function (e) {
            e.preventDefault();

            // Clear previous messages
            if (errorMsg) {
                errorMsg.style.display = "none";
                errorMsg.innerText = "";
            }
            if (successMsg) {
                successMsg.style.display = "none";
                successMsg.innerText = "";
            }

            const tripId = tripSelect.value;
            const file = fileInput.files[0];

            if (!tripId) {
                showError("Please select a trip.");
                return;
            }

            if (!file) {
                showError("Please select an Excel file.");
                return;
            }

            // Simple client-side extension check
            const allowedExtensions = /(\.xlsx|\.xls)$/i;
            if (!allowedExtensions.exec(file.name)) {
                showError("Invalid file type. Only Excel files (.xlsx, .xls) are allowed.");
                return;
            }

            // Disable button and show spinner/loading
            if (uploadBtn) {
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Uploading...';
            }

            const formData = new FormData();
            formData.append("trip_id", tripId);
            formData.append("report_file", file);

            try {
                const response = await fetch("/fleet/sale/upload-report", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        "Accept": "application/json"
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    if (data.errors && data.errors.report_file) {
                        throw new Error(data.errors.report_file[0]);
                    }
                    throw new Error(data.message || "Failed to upload fleet sale report.");
                }

                // Show success
                if (successMsg) {
                    successMsg.innerText = data.message || "Fleet sale report uploaded and processed successfully!";
                    successMsg.style.display = "block";
                }

                setTimeout(() => {
                    // Close modal manually
                    const modalInstance = bootstrap.Modal.getInstance(uploadModalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }, 2000);

            } catch (error) {
                console.error("Upload error:", error);
                showError(error.message || "An unexpected error occurred during upload.");
                if (uploadBtn) {
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = "Upload";
                }
            }
        });
    }

    function showError(message) {
        if (errorMsg) {
            errorMsg.innerText = message;
            errorMsg.style.display = "block";
        }
    }
});
