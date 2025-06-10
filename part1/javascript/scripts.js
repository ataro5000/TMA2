//handles the delete function
document.querySelectorAll('.delete-bookmark').forEach(button => {
    button.addEventListener('click', function (e) {
        e.preventDefault();
        const bookmarkId = this.getAttribute('data-id');

        // Add confirmation dialog
        if (confirm('Are you sure you want to delete this bookmark?')) {
            document.getElementById('delete-id').value = bookmarkId;
            document.getElementById('delete-bookmark-form').submit();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    // Handle the login form submission
    const loginForm = document.getElementById('login-form');
    const errorMessage = document.getElementById('error-message');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Prevent the default form submission
            const csrfToken = document.querySelector('[name="csrf_token"]').value;
            const formData = new FormData(loginForm);
            // Remove this line: formData.append('csrf_token', csrfToken); 

            try {
                const response = await fetch('../php/login.php', { // Updated path
                    method: 'POST',
                    body: formData,
                    credentials: 'include' // Crucial for session cookies
                });

                const result = await response.json();

                if (result.success) {
                    // Redirect to the main page if login is successful
                    window.location.href = '../index.php'; // Updated path
                } else {
                    // Show the error message if login fails
                    errorMessage.textContent = result.message;
                    errorMessage.style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                errorMessage.textContent = 'An unexpected error occurred. Please try again.';
                errorMessage.style.display = 'block';
            }
        });
    }

    // Registration form handling
    const regForm = document.querySelector('#register-form');
    if (regForm) {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm-password');

        regForm.addEventListener('submit', async (e) => {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match. Please try again.');
                return;
            }

            const formData = new FormData(regForm);

            try {
                const response = await fetch('../php/register.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'  // Crucial for session cookies
                });

                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    const result = await response.text();
                    // Handle errors if needed
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    }


    // handles the editbutton
    const editButtons = document.querySelectorAll('.edit-bookmark');
    const editForm = document.getElementById('edit-bookmark-form');
    const cancelEditButton = document.getElementById('cancel-edit');
    if (editForm) {
        editButtons.forEach(button => {
            button.addEventListener('click', () => {
                const bookmarkId = button.getAttribute('data-id');
                const bookmarkTitle = button.parentElement.querySelector('a').textContent;
                const bookmarkUrl = button.parentElement.querySelector('a').getAttribute('href');

                // Populate the edit form
                document.getElementById('edit-id').value = bookmarkId;
                document.getElementById('edit-title').value = bookmarkTitle;
                document.getElementById('edit-url').value = bookmarkUrl;

                // Show the edit form
                editForm.style.display = 'block';
            });
        });

        // Cancel edit
        cancelEditButton.addEventListener('click', () => {
            editForm.style.display = 'none';
        });
    }
    //handles the add-bookmark function
    const addBookmarkForm = document.getElementById('add-bookmark-form');
    if (addBookmarkForm) {
        addBookmarkForm.addEventListener('submit', function (event) {
            const urlInput = document.getElementById('url').value;
            try {
                new URL(urlInput); // Validate URL format
            } catch (e) {
                event.preventDefault(); // Prevent form submission
                alert('Please enter a valid URL.');
            }
        });
    };
});
