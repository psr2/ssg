import { helpers } from './common.js';

document.getElementById('submit_new_location').addEventListener('click', function (event) {

    event.preventDefault(); // Prevent default form submit

    console.log('click logged');

    const locationName = document.getElementById('location_name').value;
    const locationType = document.getElementById('location_type').value;
    const locationAddress = document.getElementById('location_address').value;
    const locationAbbreviation = document.getElementById('location_abbreviation').value;


    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const formData = {
        location_name: locationName,
        location_type: locationType,
        location_address: locationAddress,
        location_abbreviation: locationAbbreviation

    };

    fetch('/create-location', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json' // This tells Laravel to return JSON, not HTML
        },
        body: JSON.stringify(formData)
    })
        .then(async (response) => {
            const text = await response.text();

            const data = JSON.parse(text);
            if (data.success || response.ok) {
                alert('Location created successfully!');
                location.reload()
                // Optionally hide the modal or reset form
            } else {
                handleErrors(data.errors)
            }

        })
        .catch(error => {
            handleErrors(data.errors)

        });
});

document.querySelectorAll('.edit_location_btn').forEach(function (btn) {

    btn.addEventListener('click', function (e) {
        e.preventDefault();

        const locationId = this.getAttribute('data-id');

        console.log('location id is' + locationId)

        fetch('/edit-location', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({ id: locationId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const location = data.data;

                    console.log(location)

                    document.getElementById('edit_location_name').value = location.name || '';
                    document.getElementById('edit_location_address').value = location.address || '';
                    document.getElementById('edit_location_type').value = location.type || '';
                    document.getElementById('edit_location_id').value = location.id;

                    // Optional: Set an internal value to use during update
                    // document.getElementById('edit_location_modal').dataset.locationId = location.id;

                    // Update title if needed
                    // document.getElementById('staticBackdropLabel').innerHTML = 'Edit Location <i class="bi bi-pencil-square"></i>';

                    // Show modal

                    let modal = new bootstrap.Modal(document.getElementById('edit_location_modal'));

                    modal.show();
                } else {
                    alert('Failed to load location data.');
                }
            })
            .catch(error => {
                console.error('Error fetching location:', error);
            });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const updateBtn = document.getElementById('update_new_location');

    updateBtn.addEventListener('click', function (e) {
        e.preventDefault();

        const payload = {
            id: document.getElementById('edit_location_id').value,
            name: document.getElementById('edit_location_name').value,
            address: document.getElementById('edit_location_address').value,
            type: document.getElementById('edit_location_type').value,
        };

        console.log('payload for update is' + payload.id)

        fetch('/update-location', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify(payload)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Location updated successfully');

                    // Optionally: refresh the page or update the table row inline
                    window.location.reload();
                } else {
                    alert(data.message || 'Update failed.');
                }
            })
            .catch(error => {

                console.error('Update error:', error);

                alert('Something went wrong');
            });
    });
});

document.querySelectorAll('.edit_location_delete').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete this location?')) return;

        const locationId = this.getAttribute('data-id');

        fetch(`/delete-location/${locationId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            },
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Deleted successfully!');
                    // Optionally remove row from DOM or refresh
                    window.location.reload();
                } else {
                    alert('Delete failed.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Something went wrong.');
            });
    });
});



// Function to handle validation errors and display them in the error spans
function handleErrors(errors) {
    // Clear previous errors
    document.querySelectorAll('.text-danger').forEach(span => {
        span.textContent = '';
    });

    // Loop through the errors and append them to the appropriate span
    for (const [field, message] of Object.entries(errors)) {
        const errorSpan = document.getElementById(`error-${field}`);
        if (errorSpan) {
            errorSpan.textContent = message;
        }
    }
}