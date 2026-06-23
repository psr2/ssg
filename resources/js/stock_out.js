// export async function processStockOut(payload, form) {

//     try {
//         const response = await fetch(`/stock-out-entry`, {
//             method: "POST",
//             headers: {
//                 "Content-Type": "application/json",
//                 "Accept": "application/json",
//                 "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//             },
//             body: JSON.stringify(payload)
//         });

//         const result = await response.json();

//         // Clear previous errors
//         document.querySelectorAll('.text-danger').forEach(span => span.textContent = '');

//         if (!response.ok && result.errors) {
//             // Handle top-level (non-item-specific) errors
//             for (const key in result.errors) {
//                 if (!key.startsWith('items')) {
//                     const errorElement = document.getElementById(`error-${key}`);
//                     if (errorElement) {
//                         errorElement.textContent = result.errors[key][0];
//                     }
//                 }
//             }

//             // Handle item-specific field errors (e.g., items.0.product)
//             Object.entries(result.errors).forEach(([key, messages]) => {
//                 const match = key.match(/^items\.(\d+)\.(\w+)$/);
//                 if (match) {
//                     const [_, rowIndex, field] = match;
//                     const row = document.querySelectorAll(".product-row")[rowIndex];
//                     if (row) {
//                         const errorSpan = row.querySelector(`.error-${field}`);
//                         if (errorSpan) {
//                             errorSpan.textContent = messages[0];
//                         }
//                     }
//                 }
//             });
//         } else if (!response.ok && result.message) {
//             alert(`Error: ${result.message}`);
//         } else {
//             alert("Stock OUT entry successful");

//             // Reset form UI if passed as parameter
//             if (form) form.reset();

//             // Reset UI state (if needed, adapt based on your layout)
//             const stockInFields = document.getElementById('stockInFields');
//             const stockOutFields = document.getElementById('stockOutFields');
//             if (stockInFields && stockOutFields) {
//                 stockInFields.classList.remove('d-none');
//                 stockOutFields.classList.add('d-none');
//             }
//         }
//     } catch (error) {
//         console.error("Error submitting stock out:", error);
//         alert("An unexpected error occurred.");
//     }
// }


