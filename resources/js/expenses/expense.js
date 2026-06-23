document.addEventListener("DOMContentLoaded", function () {
    const expenseForm = document.getElementById("expenseForm");

    expenseForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        // Clear previous errors
        document.querySelectorAll("[id^='span_error_']").forEach(span => span.textContent = "");

        // Collect form data
        const formData = new FormData(expenseForm);
        const formDataObj = Object.fromEntries(formData.entries());

        try {
            const response = await fetch("/create-expense", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(formDataObj)
            });

            const data = await response.json();

            if (response.ok && data.status === "success") {
                // Success alert
                alert(data.message || "Expense added successfully!");

                // Reset the form
                expenseForm.reset();

                // Hide the modal
                const addModalEl = document.getElementById("addExpenseModal");
                const addModal = bootstrap.Modal.getInstance(addModalEl) || new bootstrap.Modal(addModalEl);
                addModal.hide();

                // Refresh the page to show the new expense in the table
                location.reload();

            } else if (data.errors) {
                // Validation errors
                Object.keys(data.errors).forEach(key => {
                    const span = document.getElementById(`span_error_${key}`);
                    if (span) span.textContent = data.errors[key][0];
                });
            } else {
                console.log("Unexpected response:", data);
            }

        } catch (error) {
            console.error("Error submitting expense:", error);
        }
    });
});


/**create new category */


document.getElementById("create_category").addEventListener("click", async function (e) {

  e.preventDefault();

  console.log("click logged");

  // Clear old error
  document.getElementById("error_new_category").textContent = "";



  let new_category_value = document.getElementById("new_category").value;

  console.log(new_category_value);

  try {
    let response = await fetch("/create-category", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
      },
      body: JSON.stringify({
        new_category: new_category_value
      })
    });


    if (response.status === 422) {
      let data = await response.json();
      console.log(data.errors.new_category);

      document.getElementById("error_new_category").textContent =
        data.errors.new_category ? data.errors.new_category[0] : "";
      return;
    } else {
      let data = await response.json();

      let select = document.getElementById("category_id");

      // Create new option
      let option = document.createElement("option");
      option.value = data.id;
      option.text = data.name;

      // Add it to the select
      select.appendChild(option);

      // Optionally, select the new option
      select.value = data.id;
      // Get the modals
      let createModalEl = document.getElementById('createCategoryeModal');
      let addExpenseModalEl = document.getElementById('addExpenseModal');

      // Get the existing Bootstrap modal instances
      let createModal = bootstrap.Modal.getInstance(createModalEl) || new bootstrap.Modal(createModalEl);
      let addExpenseModal = bootstrap.Modal.getInstance(addExpenseModalEl) || new bootstrap.Modal(addExpenseModalEl);

      // Add event listener: after the first modal is fully hidden, show the second
      createModalEl.addEventListener('hidden.bs.modal', function () {
        addExpenseModal.show();
      }, { once: true }); // { once: true } ensures this runs only once

      // Hide the first modal
      createModal.hide();



    }



  } catch (error) {
  }
});

/***Edit model */
document.addEventListener("DOMContentLoaded", function () {

    // ===== Edit Button Click - Fill Modal =====
    document.querySelectorAll(".editExpenseBtn").forEach(button => {
        button.addEventListener("click", function () {

            const expenseId = this.dataset.id;
            const form = document.getElementById('editExpenseForm');

            // Fill modal inputs
            document.getElementById("edit_expense_id").value    = expenseId;
            document.getElementById("edit_expense_date").value  = this.dataset.expense_date;
            document.getElementById("edit_category_id").value   = this.dataset.category_id;
            document.getElementById("edit_amount").value        = this.dataset.amount;
            document.getElementById("edit_payment_mode").value  = this.dataset.payment_mode;
            document.getElementById("edit_paid_to").value       = this.dataset.paid_to;
            document.getElementById("edit_description").value   = this.dataset.description ?? '';

        });
    });

    // ===== Edit Form Submit - Update Expense =====
    const editForm = document.getElementById("editExpenseForm");

    editForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const submitBtn = editForm.querySelector("button[type='submit']");
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="bi bi-arrow-repeat spin"></i> Updating...`;

        clearEditErrors();

        const formData = {
            id: document.getElementById("edit_expense_id").value,
            expense_date: document.getElementById("edit_expense_date").value,
            category_id: document.getElementById("edit_category_id").value,
            amount: document.getElementById("edit_amount").value,
            payment_mode: document.getElementById("edit_payment_mode").value,
            paid_to: document.getElementById("edit_paid_to").value,
            description: document.getElementById("edit_description").value,
        };

        try {
            const response = await fetch("/update-expense", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.status === 422) {
                showEditErrors(data.errors || {});
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<i class="bi bi-check-circle"></i> Update Expense`;
                return;
            }

            if (data.success) {
                // Close modal
                const modalEl = document.getElementById("editExpenseModal");
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.hide();

                // Update table row and edit button data
                updateRowInTable(data.expense);
                updateEditButtonData(data.expense);

                alert(data.message);

            } else {
                alert(data.message || "Something went wrong!");
            }

        } catch (error) {
            console.error(error);
            alert("Something went wrong!");
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = `<i class="bi bi-check-circle"></i> Update Expense`;
    });

    // ===== Delete Button Click - Delete Expense =====
    document.querySelector("#expenseTableBody").addEventListener("click", async function (e) {
        if (!e.target.closest(".deleteExpenseBtn")) return;

        const deleteBtn = e.target.closest(".deleteExpenseBtn");
        const expenseId = deleteBtn.dataset.id;

        if (!expenseId) return;

        const confirmed = confirm("Are you sure you want to delete this expense? This action cannot be undone.");
        if (!confirmed) return;

        try {
            const response = await fetch(`/delete-expense/${expenseId}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            const data = await response.json();

            if (data.success) {
                // Remove row
                const row = document.getElementById("row_" + expenseId);
                if (row) row.remove();

                // Reorder table numbers
                reorderTableNumbers();

                alert(data.message);

            } else {
                alert(data.message || "Could not delete.");
            }

        } catch (error) {
            console.error(error);
            alert("Something went wrong!");
        }
    });

});

// ===== Utility Functions =====

// Clear validation errors
function clearEditErrors() {
    const errorSpans = document.querySelectorAll('[id^="span_error_edit_"]');
    errorSpans.forEach(span => span.innerHTML = "");
}

// Show Laravel validation errors
function showEditErrors(errors) {
    for (let field in errors) {
        const span = document.getElementById("span_error_edit_" + field);
        if (span) span.innerHTML = errors[field][0];
    }
}

// Update table row after edit
function updateRowInTable(expense) {
    const row = document.getElementById("row_" + expense.id);
    if (!row) return;

    row.children[1].innerHTML = expense.paid_to;
    row.children[2].innerHTML = document.getElementById("edit_category_id")
        .selectedOptions[0].textContent;
    row.children[3].innerHTML = parseFloat(expense.amount).toFixed(2);
    row.children[4].innerHTML = expense.balance !== undefined ? parseFloat(expense.balance).toFixed(2) : row.children[4].innerHTML;
}

// Update edit button data attributes
function updateEditButtonData(expense) {
    const row = document.getElementById("row_" + expense.id);
    if (!row) return;

    const editBtn = row.querySelector(".editExpenseBtn");
    if (!editBtn) return;

    editBtn.dataset.expense_date = expense.expense_date;
    editBtn.dataset.category_id  = expense.category_id;
    editBtn.dataset.amount       = expense.amount;
    editBtn.dataset.payment_mode = expense.payment_mode;
    editBtn.dataset.paid_to      = expense.paid_to;
    editBtn.dataset.description  = expense.description ?? '';
}

// Reorder table numbers (first column) after deletion
function reorderTableNumbers() {
    const rows = document.querySelectorAll("#expenseTableBody tr");
    rows.forEach((row, index) => {
        row.children[0].innerHTML = index + 1;
    });
}
