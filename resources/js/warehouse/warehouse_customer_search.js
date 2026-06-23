/**
 * Warehouse Customer Search JS
 * Mirrors shop/customer_search.js — uses wh_ prefixed element IDs
 * Powered by Fuse.js for fuzzy matching.
 */

document.getElementById('wh_shop_id').addEventListener('change', function (event) {
    const warehouseId = event.target.value;
    console.log('Warehouse selected:', warehouseId);
    whSearchCustomerNames(warehouseId);
});

let whCustomerNames = [];
let whFuse;

const whFuseOptions = {
    includeScore: true,
    threshold:    0.3,
    keys:         ['name'],
};

// Fetch customers for this warehouse
async function whSearchCustomerNames(warehouseId) {
    try {
        const response = await fetch('/warehouse/customer/search', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept':        'application/json',
            },
            body: JSON.stringify({ shop_id: warehouseId }),
        });

        if (!response.ok) {
            const err = await response.json();
            console.warn('Customer fetch returned non-OK:', err);
            // Not throwing — an empty warehouse just has no customers yet
            whCustomerNames = [];
            whFuse          = null;
            return;
        }

        const json = await response.json();
        const data = json.data || {};

        // Convert { id: name } object to array of { id, name }
        whCustomerNames = Object.entries(data).map(([id, name]) => ({ id, name }));
        whFuse          = new Fuse(whCustomerNames, whFuseOptions);

        console.log('Warehouse Fuse initialized with', whCustomerNames.length, 'customers');

    } catch (err) {
        console.error('Error fetching warehouse customers:', err);
    }
}

// ── Customer name autocomplete ────────────────────────────────────────────────
const whDropDown    = document.getElementById('wh_drop_down');
const whInput       = document.getElementById('wh_customer_name');
const whHiddenInput = document.getElementById('wh_customer_id');
let whCurrentFocus  = -1;

whInput.addEventListener('input', function () {
    const pattern = this.value.trim();

    if (!pattern) {
        whDropDown.classList.remove('show');
        whDropDown.innerHTML = '';
        whCurrentFocus = -1;
        return;
    }

    const results = whSuggestCustomer(pattern);

    if (results.length === 0) {
        whDropDown.innerHTML = `
            <li class="dropdown-item wh-new-customer">
                No customer found — <a href="#">Create new customer</a>
            </li>`;
        whDropDown.classList.add('show');
        whCurrentFocus = -1;
        return;
    }

    let html = '';
    results.forEach(result => {
        html += `<li class="dropdown-item" data-id="${result.item.id}" data-name="${result.item.name}" role="option" tabindex="-1">${result.item.name}</li>`;
    });

    whDropDown.innerHTML = html;
    whDropDown.classList.add('show');
    whCurrentFocus = -1;

    whDropDown.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', () => {
            whInput.value       = item.dataset.name;
            whHiddenInput.value = item.dataset.id;
            whDropDown.classList.remove('show');
            whDropDown.innerHTML = '';
        });
    });
});

function whSuggestCustomer(pattern) {
    if (!whFuse) {
        console.log('Fuse not initialized — select a warehouse first.');
        return [];
    }
    return whFuse.search(pattern);
}

// Keyboard navigation
whInput.addEventListener('keydown', function (e) {
    const items = whDropDown.querySelectorAll('.dropdown-item:not(.disabled)');
    if (!items.length) return;

    if (e.key === 'ArrowDown') {
        whCurrentFocus++;
        if (whCurrentFocus >= items.length) whCurrentFocus = 0;
        whSetActive(items, whCurrentFocus);
        e.preventDefault();
    } else if (e.key === 'ArrowUp') {
        whCurrentFocus--;
        if (whCurrentFocus < 0) whCurrentFocus = items.length - 1;
        whSetActive(items, whCurrentFocus);
        e.preventDefault();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (whCurrentFocus > -1) items[whCurrentFocus].click();
    } else if (e.key === 'Escape') {
        whDropDown.classList.remove('show');
        whCurrentFocus = -1;
    }
});

function whSetActive(items, index) {
    items.forEach(i => i.classList.remove('active'));
    if (index >= 0 && items[index]) {
        items[index].classList.add('active');
        items[index].scrollIntoView({ block: 'nearest' });
    }
}

document.addEventListener('click', function (e) {
    if (!whInput.contains(e.target) && !whDropDown.contains(e.target)) {
        whDropDown.classList.remove('show');
        whCurrentFocus = -1;
    }
});

// ── Create new customer flow ──────────────────────────────────────────────────
document.body.addEventListener('click', function (e) {
    if (e.target.closest('.wh-new-customer')) {
        e.preventDefault();

        const newFields = document.getElementById('wh_newCustomerFields');
        if (newFields) newFields.style.display = 'block';

        whInput.disabled       = true;
        whHiddenInput.value    = '';
    }
});
