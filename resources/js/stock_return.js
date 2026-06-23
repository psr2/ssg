import { helpers } from './common.js';

const movementType = document.getElementById('movementType');
const stockInFields = document.getElementById('stockInFields');
const stockOutFields = document.getElementById('stockOutFields');
const inType = document.getElementById('in_type');
const returnDetailsSection = document.getElementById('returnDetailsSection');
const returnReasonRow = document.getElementById('returnReasonRow');
const returnCustomerRow = document.getElementById('returnCustomerRow');

movementType.addEventListener('change', function () {
    if (this.value === 'in') {
        stockInFields.classList.remove('d-none');
        stockOutFields.classList.add('d-none');
    } else {
        stockOutFields.classList.remove('d-none');
        stockInFields.classList.add('d-none');
    }
});

inType.addEventListener('change', function () {
    if (this.value === 'return') {
        returnDetailsSection.classList.remove('d-none');
        returnReasonRow.classList.remove('d-none');
        returnCustomerRow.classList.remove('d-none');
    } else {
        returnDetailsSection.classList.add('d-none');
        returnReasonRow.classList.add('d-none');
        returnCustomerRow.classList.add('d-none');
    }
});

document.getElementById('addProductRowBtn').addEventListener('click', () => {
    const container = document.getElementById('productRowsContainer');
    const row = container.querySelector('.product-row');
    const clone = row.cloneNode(true);

    // Clear inputs in the cloned row
    // remove comments once test is written
    // clone.querySelectorAll('input, select, textarea').forEach(input => {
    //     input.value = '';
    // });

    container.appendChild(clone);
});

document.getElementById('productRowsContainer').addEventListener('click', function (e) {
    if (e.target.classList.contains('removeRowBtn')) {
        const rows = document.querySelectorAll('.product-row');
        if (rows.length > 1) {
            e.target.closest('.product-row').remove();
        }
    }
});

document.getElementById("stockMovementForm").addEventListener("submit", async function (e) {

    e.preventDefault();
    document.querySelectorAll('#stockMovementForm span').forEach(span => {
        span.textContent = "";
    });

    document.getElementById('error-movementType').textContent = "";



    const stockType = document.getElementById("movementType").value;
    const movementDate = document.getElementById("movement_date").value;
    const referenceNo = document.getElementById("referenceNo").value.trim();




    const payload = {
        stock_type: stockType,
        reference_no: referenceNo,
        movement_date: movementDate,
        items: []
    };


    if (stockType === "in") {
        // const source = document.getElementById("source").value.trim();
        const inTypeVal = document.getElementById("in_type").value;
        // payload.source = source;
        payload.in_type = inTypeVal;

        if (inTypeVal === "return") {
            payload.return_source = document.getElementById("returnSource").value;
            payload.return_reason = document.getElementById("return_reason").value.trim();
            payload.customer_name = document.getElementById("customer_name").value.trim();
            payload.customer_contact = document.getElementById("customer_contact").value.trim();
        }
    } else {
        const destination = document.getElementById("destination").value.trim();
        const outType = document.getElementById("outType").value;
        payload.destination = destination;
        payload.out_type = outType;
    }

    document.querySelectorAll(".product-row").forEach(row => {
        const product = row.querySelector("select[name='products[][product]']").value.trim();
        const batch_code = row.querySelector("input[name='products[][batch_code]']").value.trim();
        const grade = row.querySelector("select[name='products[][grade]']").value;
        const qty = row.querySelector("input[name='products[][quantity]']").value.trim();
        const unit = row.querySelector("input[name='products[][unit]']").value.trim();
        const unitCost = row.querySelector("input[name='products[][unit_cost]']").value.trim();
        const total = row.querySelector("input[name='products[][total]']").value.trim();
        const remarks = row.querySelector("textarea[name='products[][remarks]']").value.trim();

        const location_id = document.getElementById("location_id").value;

        const vendor = row.querySelector("input[name='products[][vendor]']").value.trim();
        const invoice_number = row.querySelector("input[name='products[][invoice_number]']").value.trim();
        const purchase_date = row.querySelector("input[name='products[][purchase_date]']").value;


        // Push to payload
        payload.items.push({
            product,
            location_id,
            batch_code,
            grade,
            quantity: qty,
            unit,
            unit_cost: unitCost,
            total,
            remarks,
            vendor,
            invoice_number,
            purchase_date
        });
    });


    if (stockType === "") {
        document.getElementById('error-movementType').textContent = 'Please select Stock movement type and submit again';
        return;
    }

    console.log(payload)
    try {
        const response = await fetch(`/stock-${stockType}-entry`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'Accept': 'application/json',
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        console.log(result)

        if (!response.ok && result.errors) {

            // 🔹 1. Static input errors (e.g. movement_date, source, etc.)
            for (const key in result.errors) {
                if (!key.startsWith('items')) {
                    const errorElement = document.getElementById(`error-${key}`);
                    if (errorElement) {
                        errorElement.textContent = result.errors[key][0];
                    }
                }
            }

            Object.entries(result.errors).forEach(([key, messages]) => {
                const match = key.match(/^items\.(\d+)\.(\w+)$/);
                if (match) {
                    const rowIndex = parseInt(match[1], 10);
                    const field = match[2];

                    const rows = document.querySelectorAll(".product-row");
                    const row = rows[rowIndex];

                    if (row) {

                        const errorSpan = row.querySelector(`.error-${field}`);

                        console.log(errorSpan)

                        if (errorSpan) {
                            errorSpan.textContent = messages[0]; // Show first error
                        }
                    }
                }
            });
        }
        else if (!response.ok && result.message) {

            alert(`Error: ${result.message}`);
        }
        else {
            alert(`Stock ${stockType.toUpperCase()} entry successful`);
            this.reset();
            stockInFields.classList.remove('d-none');
            stockOutFields.classList.add('d-none');
        }
    } catch (error) {
        console.error("Error submitting form:", error);
    }
});
