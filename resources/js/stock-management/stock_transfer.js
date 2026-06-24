document.addEventListener("DOMContentLoaded", function () {
    getLocations();
});


document.getElementById('batchCodeSearchForm').addEventListener('submit', function (e) {
    e.preventDefault();

    console.log('fired')

    const form = e.target;
    const data = {
        product_listing: form.product_listing.value,
        location: form.location.value,
        dateFrom: form.dateFrom.value
    };

    fetch("/stock-transfer/search-batch-code", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
        .then(res => res.json())
        .then(response => {
            const tbody = document.querySelector('#batchCodeResults tbody');
            tbody.innerHTML = "";

            if (response.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center">No results found.</td></tr>`;
            } else {
                response.forEach((item, index) => {
                    tbody.innerHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.batch_code}</td>
                        <td>${item.product}</td>
                        <td>${item.grade}</td>
                        <td>${item.location}</td>
                        <td>${parseFloat(item.available_qty).toFixed(2)}</td>
                        <td><button class="btn btn-sm btn-success select-batch" data-batch-code="${item.batch_code}" data-grade="${item.grade}">Select</button></td>
                    </tr>`;
                });
            }
        })
        .catch(error => console.error("Search failed:", error));
});

document.addEventListener('DOMContentLoaded', function () {
    // Get reference to <tbody> that holds the dynamic rows
    const tbody = document.getElementById('batchCodeResults'); // <-- Replace with actual ID

    if (!tbody) {
        console.error('Tbody element not found');
        return;
    }

    // Use event delegation to listen for clicks on .select-batch buttons
    tbody.addEventListener('click', function (e) {
        const target = e.target;

        if (target && target.classList.contains('select-batch')) {
            e.preventDefault();

            console.log('Click detected on dynamically created button');

            const batchCode = target.getAttribute('data-batch-code');
            const grade = target.getAttribute('data-grade');
            const batchCodeInput = document.getElementById('t_batch_code');

            if (batchCodeInput) {
                batchCodeInput.value = batchCode;
                console.log('Batch code set:', batchCode);

                const gradeSelect = document.getElementById('t_grade');
                if (gradeSelect && grade) {
                    gradeSelect.value = grade;
                }
            } else {
                console.warn('Input with id "batch_code" not found');
            }

            const modalEl = document.getElementById('staticBackdropBatchCode');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            } else {
                console.warn('Bootstrap modal instance not found');
            }
        }
    });
});


function getLocations() {
    fetch("/api-locations")
        .then(response => response.json())
        .then(data => {
            // Get all three selects
            let fromSelect = document.getElementById("t_fromLocation");
            let toSelect = document.getElementById("t_toLocation");
            let locationSelect = document.getElementById("location");

            // Check existence before appending
            [fromSelect, toSelect, locationSelect].forEach(select => {
                if (!select) return;

                // Clear existing options except placeholder
                select.length = 1; // keeps "Select ..."

                // Append options
                data.forEach(loc => {
                    select.add(new Option(loc.name, loc.id));
                });
            });
        })
        .catch(error => console.error("Error fetching locations:", error));

}


document.addEventListener("DOMContentLoaded", () => {
    const submitBtn = document.getElementById("submit_stock_transfer");

    submitBtn.addEventListener("click", async (e) => {
        e.preventDefault();

        // clear old errors
        document.querySelectorAll(".text-danger.small").forEach(el => {
            el.innerText = "";
        });

        const payload = {
            t_transferDate: document.getElementById("t_transferDate").value,
            t_transferType: document.getElementById("t_transferType").value,
            t_fromLocation: document.getElementById("t_fromLocation").value,
            t_toLocation: document.getElementById("t_toLocation").value,
            t_product_name: document.getElementById("t_product_name").value,
            t_batch_code: document.getElementById("t_batch_code").value,
            t_grade: document.getElementById("t_grade").value,
            t_quantity: document.getElementById("t_quantity").value,
            t_unit: document.getElementById("t_unit").value,
            t_textarea: document.getElementById("t_textarea").value,
        };

        try {
            const response = await fetch("/stock-transfer", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (!response.ok) {
                if (result.errors) {
                    Object.keys(result.errors).forEach((field) => {
                        const errorSpan = document.getElementById(field + "_error");
                        if (errorSpan) {
                            errorSpan.innerText = result.errors[field][0];
                        }
                    });
                } else {
                    alert(result.message || "Something went wrong");
                }
                return;
            }

            alert("✅ Stock transfer saved successfully!");
            document.querySelector("form")?.reset();

        } catch (err) {
            console.error("Error submitting stock transfer:", err);
            alert("❌ Failed to save stock transfer.");
        }
    });
});
