document.addEventListener("DOMContentLoaded", async () => {
    try {
        const token = document.querySelector('meta[name="csrf-token"]').content;

        const response = await fetch('/fleet/', {
            headers: {
                "X-CSRF-TOKEN": token,
                "Accept": "application/json"
            }
        });

        if (!response.ok) throw new Error('Failed to load config');

        const appData = await response.json();

        // Now you have your sensitive data in JS safely
        console.log(appData.customers, appData.routes, appData.settings);

        // Example: populate select or form fields
        const customerSelect = document.getElementById("customer_id");
        appData.customers.forEach(c => {
            const option = document.createElement("option");
            option.value = c.id;
            option.textContent = c.name;
            customerSelect.appendChild(option);
        });

    } catch (err) {
        console.error(err);
    }
});
