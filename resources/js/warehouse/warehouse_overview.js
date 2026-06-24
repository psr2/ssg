/**
 * Warehouse Overview Inventory Explorer JS
 */

document.addEventListener('DOMContentLoaded', function () {
    const warehouseSelect = document.getElementById('explorer-warehouse-select');
    const searchInput = document.getElementById('explorer-search-input');
    const gradeSelect = document.getElementById('explorer-grade-select');
    const loader = document.getElementById('explorer-loader');
    const tableBody = document.querySelector('#explorer-inventory-table tbody');
    const resetButton = document.getElementById('btn-reset-filters');
    const showingCount = document.getElementById('explorer-showing-count');
    const sumContainer = document.getElementById('explorer-sum-container');
    const totalValueEl = document.getElementById('explorer-total-value');
    const sortableHeaders = document.querySelectorAll('.sortable-header');

    const container = document.getElementById('warehouseOverviewContainer');
    const inventoryBaseUrl = container ? container.getAttribute('data-inventory-url') : '/warehouse/overview/inventory';

    let debounceTimeout = null;
    let currentInventory = [];
    let currentSort = { column: null, direction: 'asc' };

    // Custom Grade Sorting priority
    const gradeWeights = {
        'a': 1,
        'grade a': 1,
        'b': 2,
        'grade b': 2,
        'c': 3,
        'grade c': 3,
        'waste': 4,
        'n/a': 5
    };

    // Helper: Debounce function for product search
    function debounce(callback, delay) {
        return function (...args) {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(() => callback.apply(null, args), delay);
        };
    }

    // Enable or disable filter controls
    function toggleControls(enabled) {
        if (enabled) {
            searchInput.removeAttribute('disabled');
            gradeSelect.removeAttribute('disabled');
        } else {
            searchInput.setAttribute('disabled', 'true');
            gradeSelect.setAttribute('disabled', 'true');
            searchInput.value = '';
            gradeSelect.value = '';
        }
    }

    // Get Grade Badge HTML
    function getGradeBadge(grade) {
        const normalized = (grade || '').trim().toLowerCase();
        let badgeClass = 'bg-secondary bg-opacity-10 text-secondary border-secondary';
        
        if (normalized.includes('grade a') || normalized === 'a') {
            badgeClass = 'bg-success bg-opacity-10 text-success border-success';
        } else if (normalized.includes('grade b') || normalized === 'b') {
            badgeClass = 'bg-info bg-opacity-10 text-info border-info';
        } else if (normalized.includes('grade c') || normalized === 'c') {
            badgeClass = 'bg-warning bg-opacity-10 text-warning border-warning';
        } else if (normalized.includes('waste')) {
            badgeClass = 'bg-danger bg-opacity-10 text-danger border-danger';
        }

        return `<span class="badge rounded-pill border px-3 py-1 ${badgeClass}" style="font-weight: 500; font-size: 0.72rem;">${grade}</span>`;
    }

    // Get Stock Quantity cell content
    function getQtySpan(qty, unit) {
        const numQty = parseFloat(qty);
        if (numQty <= 0) {
            return `<span class="text-danger fw-semibold small d-inline-flex align-items-center"><i class="bi bi-x-circle-fill me-1"></i>Out of Stock</span>`;
        } else if (numQty < 10) {
            return `<span class="text-warning fw-semibold small d-inline-flex align-items-center"><i class="bi bi-exclamation-circle-fill me-1"></i>${numQty.toLocaleString()} ${unit}</span>`;
        } else {
            return `<span class="text-success fw-medium small">${numQty.toLocaleString()} ${unit}</span>`;
        }
    }

    // Format money helper
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            minimumFractionDigits: 2
        }).format(amount);
    }

    // Main fetch function
    async function fetchInventory() {
        const warehouseId = warehouseSelect.value;
        if (!warehouseId) {
            renderEmptyState();
            return;
        }

        const search = searchInput.value.trim();
        const grade = gradeSelect.value;

        // Show Loader
        loader.classList.remove('d-none');

        try {
            const queryParams = new URLSearchParams({
                warehouse_id: warehouseId,
                search: search,
                grade: grade
            });

            const response = await fetch(`${inventoryBaseUrl}?${queryParams.toString()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const resData = await response.json();
            if (resData.success && Array.isArray(resData.data)) {
                currentInventory = resData.data;
                // If sort column is active, sort current fetched data
                if (currentSort.column) {
                    sortData(currentSort.column, currentSort.direction, false);
                }
                renderTableData(currentInventory);
            } else {
                renderErrorState('Failed to fetch valid inventory list.');
            }

        } catch (error) {
            console.error('Error fetching warehouse inventory:', error);
            renderErrorState('An error occurred while loading the warehouse stock.');
        } finally {
            // Hide Loader
            loader.classList.add('d-none');
        }
    }

    // Render list data to table rows
    function renderTableData(items) {
        tableBody.innerHTML = '';
        
        if (items.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="py-5 text-center text-muted">
                        <div class="py-3">
                            <i class="bi bi-search fs-2 mb-2 d-block text-muted opacity-50"></i>
                            <h6 class="fw-semibold text-secondary mb-1">No Matching Stock Items</h6>
                            <p class="small text-muted mb-0">Try adjusting your search criteria or filters.</p>
                        </div>
                    </td>
                </tr>
            `;
            showingCount.textContent = '0';
            sumContainer.classList.add('d-none');
            return;
        }

        let totalValue = 0;

        items.forEach((item, index) => {
            totalValue += item.total_value;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="py-3 px-4 text-muted small">${index + 1}</td>
                <td class="py-3 px-3 fw-semibold text-dark" style="font-size: 0.88rem;">${item.product_name}</td>
                <td class="py-3 px-3 text-secondary font-monospace small" style="font-size: 0.82rem;">${item.batch}</td>
                <td class="py-3 px-3 text-center">${getGradeBadge(item.grade)}</td>
                <td class="py-3 px-3 text-end">${getQtySpan(item.qty, item.unit)}</td>
                <td class="py-3 px-3 text-end text-secondary small">${formatCurrency(item.unit_cost)}</td>
                <td class="py-3 px-4 text-end fw-semibold text-dark" style="font-size: 0.88rem;">${formatCurrency(item.total_value)}</td>
            `;
            tableBody.appendChild(row);
        });

        showingCount.textContent = items.length.toString();
        totalValueEl.textContent = formatCurrency(totalValue);
        sumContainer.classList.remove('d-none');
    }

    // In-place sorting of currentInventory
    function sortData(column, direction, shouldRender = true) {
        currentInventory.sort((a, b) => {
            let valA, valB;

            if (column === 'grade') {
                const normA = (a[column] || '').toString().trim().toLowerCase();
                const normB = (b[column] || '').toString().trim().toLowerCase();
                valA = gradeWeights[normA] || 99;
                valB = gradeWeights[normB] || 99;
            } else if (column === 'product_name') {
                valA = (a[column] || '').toString().trim().toLowerCase();
                valB = (b[column] || '').toString().trim().toLowerCase();
            } else {
                valA = parseFloat(a[column]) || 0;
                valB = parseFloat(b[column]) || 0;
            }

            if (valA < valB) return direction === 'asc' ? -1 : 1;
            if (valA > valB) return direction === 'asc' ? 1 : -1;
            return 0;
        });

        updateSortIcons(column, direction);

        if (shouldRender) {
            renderTableData(currentInventory);
        }
    }

    // Update Sorting Icons inside Headers
    function updateSortIcons(activeColumn, direction) {
        sortableHeaders.forEach(header => {
            const column = header.getAttribute('data-sort');
            const icon = document.getElementById(`sort-icon-${column}`);
            if (!icon) return;

            // Reset icon
            icon.className = 'bi bi-arrow-down-up ms-1 text-muted';

            if (column === activeColumn) {
                if (direction === 'asc') {
                    icon.className = 'bi bi-arrow-up ms-1 text-primary fw-bold';
                } else {
                    icon.className = 'bi bi-arrow-down ms-1 text-primary fw-bold';
                }
            }
        });
    }

    // Reset all sorting state
    function resetSortState() {
        currentSort = { column: null, direction: 'asc' };
        sortableHeaders.forEach(header => {
            const column = header.getAttribute('data-sort');
            const icon = document.getElementById(`sort-icon-${column}`);
            if (icon) {
                icon.className = 'bi bi-arrow-down-up ms-1 text-muted';
            }
        });
    }

    // Default Empty State
    function renderEmptyState() {
        tableBody.innerHTML = `
            <tr id="explorer-empty-row">
                <td colspan="7" class="py-5 text-center text-muted">
                    <div class="py-4">
                        <i class="bi bi-building fs-1 text-muted opacity-50 mb-3 d-block"></i>
                        <h6 class="fw-semibold text-secondary mb-1">No Warehouse Selected</h6>
                        <p class="small text-muted mb-0">Please select a warehouse from the dropdown to list its products and grades.</p>
                    </div>
                </td>
            </tr>
        `;
        showingCount.textContent = '0';
        sumContainer.classList.add('d-none');
        toggleControls(false);
        resetSortState();
        currentInventory = [];
    }

    // Error State
    function renderErrorState(message) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="py-5 text-center text-danger">
                    <div class="py-3">
                        <i class="bi bi-exclamation-circle fs-2 mb-2 d-block"></i>
                        <h6 class="fw-semibold mb-1">Retrieval Failed</h6>
                        <p class="small mb-0 text-muted">${message}</p>
                    </div>
                </td>
            </tr>
        `;
        showingCount.textContent = '0';
        sumContainer.classList.add('d-none');
        resetSortState();
        currentInventory = [];
    }

    // Event Listeners
    warehouseSelect.addEventListener('change', function () {
        if (warehouseSelect.value) {
            toggleControls(true);
            fetchInventory();
        } else {
            renderEmptyState();
        }
    });

    searchInput.addEventListener('input', debounce(() => {
        fetchInventory();
    }, 300));

    gradeSelect.addEventListener('change', fetchInventory);

    resetButton.addEventListener('click', function () {
        warehouseSelect.value = '';
        toggleControls(false);
        renderEmptyState();
    });

    // Column Sorting Listeners
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function () {
            // Only allow sorting if we have loaded data
            if (currentInventory.length === 0) return;

            const column = this.getAttribute('data-sort');
            let direction = 'asc';

            if (currentSort.column === column) {
                direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            }

            currentSort.column = column;
            currentSort.direction = direction;

            sortData(column, direction, true);
        });
    });
});
