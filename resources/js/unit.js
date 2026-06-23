import { helpers } from './common.js';


document.addEventListener('DOMContentLoaded', () => {

    const editModal = new bootstrap.Modal(document.getElementById('edit_new_unit'));

    document.querySelectorAll('.edit-unit-btn').forEach(btn => {

        btn.addEventListener('click', async () => {

            console.log('click clogged')

            const unitId = btn.dataset.id;

            console.log('unit id is' + unitId)

            const response = await fetch(`/edit-unit/${unitId}`, {
                headers: {
                    'Accept': 'application/json'
                  }
            });

            const data = await response.json();

            console.log(data.name)

            document.getElementById('edit_unit_id').value = data.id;

            document.getElementById('edit_unit').value = data.name;

            document.getElementById('edit_abbreviation').value = data.abbreviation;

            editModal.show();

        });
    });

    document.getElementById('edit_new_unit').addEventListener('submit', async (e) => {

        e.preventDefault();

        console.log('submit fired')

        const unitId = document.getElementById('edit_unit_id').value;

        console.log(unitId)

        const formData = new FormData(e.target);
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const res = await fetch(`/update-units/${unitId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'X-HTTP-Method-Override': 'PUT'
            },
            body: formData
        });

        if (res.ok) {

            const data = await res.json();
            console.log(data)
            alert(data.message); // Replace with toast
            // helpers.resetForm('edit_new_unit');
            editModal.hide();
            location.reload(); // Or update the DOM without reload
        } else {
            const error = await res.json();
            console.error(error);
            alert('Update failed.');
        }
    });
});

document.querySelectorAll('.delete-unit-btn').forEach(button => {
    button.addEventListener('click', async () => {
        console.log('delete button fired');
        if (!confirm('Are you sure you want to delete this unit?')) return;

        const unitId = button.getAttribute('data-id');
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const res = await fetch(`/units/${unitId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token
            }
        });

        if (res.ok) {

            helpers.showToast('Deleted successfully!');

            setTimeout(() => {
                location.reload();
            }, 2000); // 2 seconds delay (adjust as needed)


        } else {
            alert('Failed to delete unit.');
        }
    });
});


