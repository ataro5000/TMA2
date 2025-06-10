document.addEventListener('DOMContentLoaded', function () {
  // Handle the login form submission
  const loginForm = document.getElementById('login-form');
  const errorMessage = document.getElementById('error-message');

  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(loginForm);
        
        try {
            const response = await fetch('../php/login.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
    
            // Check for HTTP errors
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
    
            const result = await response.json();
            
            if (result.success) {
                window.location.href = '../index.php';
            } else {
                errorMessage.textContent = result.message;
                errorMessage.style.display = 'block';
            }
        } catch (error) {
            console.error('Login error:', error);
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
});