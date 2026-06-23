

document.getElementById("shop_id").addEventListener("change", function(event) {
    // Your code here
    console.log("Shop ID changed to:", event.target.value);

    let shop_id=event.target.value;
   
   searchCustomerNames(shop_id)

});

let customerNames = []; // Will be array of { id, name }
let fuse;

// Fuse.js options
const fuseOptions = {
    includeScore: true,
    threshold: 0.3,
    keys: ['name'] // Search only by name
};


// Fetch and convert customer data from object format
async function searchCustomerNames(payloadValue) {

    try {
        const response = await fetch('/shop/customer/search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ shop_id: payloadValue })
        });

        if (!response.ok) throw new Error('Network response was not ok.');

        const jsonResponse = await response.json();
        const data = jsonResponse.data;

        console.log(data)

        // Convert object to array of { id, name }
        customerNames = Object.entries(data).map(([id, name]) => ({ id, name }));

        // Initialize Fuse with updated array
        console.log(customerNames)
        fuse = new Fuse(customerNames, fuseOptions);
        console.log("Fuse initialized with customer data:", customerNames);

    } catch (err) {
        console.error('Error fetching customer names:', err);
    }
}

/**
 * Perform customer name search on input
 */
const dropDown = document.getElementById('drop-down');
const input = document.getElementById('customer_name');
const hiddenInput = document.getElementById('customer_id');
let currentFocus = -1;

input.addEventListener('input', function () {

    
    const searchPattern = this.value.trim();

    if (!searchPattern) {
        dropDown.classList.remove('show');
        dropDown.innerHTML = '';
        currentFocus = -1;
        return;
    }

    const results = suggestCustomerName(searchPattern);

    if (results.length === 0) {
        dropDown.innerHTML = `
      <li class="dropdown-item new-customer">
        No customer found <a href="#">Create new customer</a>
      </li>`;
        dropDown.classList.add('show');
        currentFocus = -1;
        return;
    }

    let html = '';
    results.forEach((result, index) => {
        const name = result.item.name;
        const id = result.item.id;
        html += `<li class="dropdown-item" data-id="${id}" data-name="${name}" role="option" tabindex="-1">${name}</li>`;
    });

    dropDown.innerHTML = html;
    dropDown.classList.add('show');
    currentFocus = -1;

    // Click selection
    dropDown.querySelectorAll('.dropdown-item').forEach((item) => {
        item.addEventListener('click', () => {
            input.value = item.dataset.name;
            hiddenInput.value = item.dataset.id;
            dropDown.classList.remove('show');
            dropDown.innerHTML = '';
        });
    });

});



// Suggest customer based on search pattern
function suggestCustomerName(searchPattern) {

    console.log(searchPattern)
    if (!fuse) {
        console.log("Fuse not initialized yet.");
        return [];
    }
    return fuse.search(searchPattern);
}


// Keyboard navigation
input.addEventListener('keydown', function (e) {
    const items = dropDown.querySelectorAll('.dropdown-item:not(.disabled)');
    if (!items.length) return;

    if (e.key === 'ArrowDown') {
        currentFocus++;
        if (currentFocus >= items.length) currentFocus = 0;
        setActive(items, currentFocus);
        e.preventDefault();
    } else if (e.key === 'ArrowUp') {
        currentFocus--;
        if (currentFocus < 0) currentFocus = items.length - 1;
        setActive(items, currentFocus);
        e.preventDefault();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (currentFocus > -1) {
            items[currentFocus].click();
        }
    } else if (e.key === 'Escape') {
        dropDown.classList.remove('show');
        currentFocus = -1;
    }
});

function setActive(items, index) {
    items.forEach(item => item.classList.remove('active'));
    if (index >= 0 && items[index]) {
        items[index].classList.add('active');
        items[index].scrollIntoView({ block: 'nearest' });
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function (e) {
    if (!input.contains(e.target) && !dropDown.contains(e.target)) {
        dropDown.classList.remove('show');
        currentFocus = -1;
    }
// Use event delegation for clicks inside the document or container
});

/**
 * Shows when add new customer link is clicked
 * Inputs are shown to enter details like customer name , contact , route and location
 * 
 */
document.body.addEventListener('click', function (e) {
    // Check if the clicked element (or its parent) contains the 'new-customer' class
    if (e.target.closest('.new-customer')) {


        const newCustomerFields = document.getElementById('newCustomerFields');
        const customerNameInput = document.getElementById('customer_name');
        const customerIdInput = document.getElementById('customer_id');
        
        // Log to confirm click is detected
        console.log("click on create new customer");

        // Show the new customer fields
        if (newCustomerFields) {
            newCustomerFields.style.display = 'block';
        }

        // Disable the existing customer name input and clear customer_id
        if (customerNameInput) customerNameInput.disabled = true;
        if (customerIdInput) customerIdInput.value = '';

        // Prevent the default action (e.g., following the link)
        e.preventDefault();
    }
});

