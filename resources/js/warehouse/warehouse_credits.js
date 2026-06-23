/**
 * Warehouse Credits Filter and Search JS
 */

document.addEventListener('DOMContentLoaded', function () {
    const runSearchBtn = document.getElementById('wh_run_credit_search');
    const startDateInput = document.getElementById('wh_credit_start_date');
    const endDateInput = document.getElementById('wh_credit_end_date');
    const tableBody = document.getElementById('creditsTableBody');
    const paginationContainer = document.getElementById('creditsPagination');

    if (!runSearchBtn) return;

    runSearchBtn.addEventListener('click', async function (e) {
        e.preventDefault();

        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        if (!startDate || !endDate) {
            alert('Please select both start and end dates.');
            return;
        }

        try {
            const response = await fetch('/warehouse/credits/search', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    start_date: startDate,
                    end_date: endDate
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Clear and rebuild table body
                tableBody.innerHTML = '';

                if (result.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-muted py-4">No credit records found between ${startDate} and ${endDate}.</td></tr>`;
                } else {
                    result.data.forEach(credit => {
                        const total = parseFloat(credit.total_amount).toFixed(2);
                        const paid = parseFloat(credit.paid_amount).toFixed(2);
                        const due = parseFloat(credit.due_amount).toFixed(2);

                        tableBody.innerHTML += `
                            <tr>
                                <td>${credit.customer_name}</td>
                                <td>${credit.sale_date}</td>
                                <td>&#8377;${total}</td>
                                <td>&#8377;${paid}</td>
                                <td class="text-danger fw-bold">&#8377;${due}</td>
                            </tr>
                        `;
                    });
                }

                // Hide pagination since we loaded all search results in one page
                if (paginationContainer) {
                    paginationContainer.style.display = 'none';
                }

                // Close the modal
                const modalEl = document.getElementById('creditFilterModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                }

            } else {
                alert(result.message || 'An error occurred during search.');
            }

        } catch (err) {
            console.error('Credits search request failed:', err);
            alert('An error occurred while fetching the credit records.');
        }
    });
});
