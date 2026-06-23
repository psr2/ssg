let payment_id = ""; // Updated on .btn-payments click

// Capture the ID when a payment button is clicked
document.addEventListener("click", (e) => {
    if (e.target && e.target.classList.contains("btn-payments")) {
        e.preventDefault();
        payment_id = e.target.dataset.id;
        console.log("Payment ID set:", payment_id);
    }
});

document.getElementById("btn-update-payments").addEventListener("click", async (e) => {
    e.preventDefault();
    await updatePayments(); // Call async function
});

async function updatePayments() {
    // Collect input values from the form
    const amount = document.getElementById("paymentAmount").value;
    const date = document.getElementById("payment-date").value;
    const method = document.getElementById("payment-method").value;

    const url = "/fleet/payment/update";

    // Clear previous error messages
    const errorFields = ['paymentAmount', 'payment-date', 'payment-method', 'id'];
    errorFields.forEach(field => {
        const errorSpan = document.querySelector(`.error-${field}`);
        if (errorSpan) errorSpan.textContent = '';
    });

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                "Accept": "application/json"
            },
            body: JSON.stringify({
                "paymentAmount": amount,
                "payment-date": date,
                "payment-method": method,
                "id": payment_id
            })
        });

        if (!response.ok) {
            if (response.status === 422) {
                const result = await response.json();
                
                // Console log full error response
                console.error("Validation failed:", result.errors);

                // Display errors under relevant input fields and log them individually
                if (result.errors) {
                    Object.entries(result.errors).forEach(([field, messages]) => {
                        const errorSpan = document.querySelector(`.error-${field}`);
                        if (errorSpan) {
                            errorSpan.textContent = messages.join(', ');
                        }

                    });
                }
            } else {
                throw new Error(`Response status: ${response.status}`);
            }
        } else {
            const result = await response.json();
            console.log("Payment update response:", result);

            // Optionally clear errors again on success
            errorFields.forEach(field => {
                const errorSpan = document.querySelector(`.error-${field}`);
                if (errorSpan) errorSpan.textContent = '';
            });
        }
    } catch (error) {
        console.error("Payment update failed:", error.message);
    }
}
