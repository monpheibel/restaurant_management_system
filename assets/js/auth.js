/**
 * Restaurant Management System - Authentication JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize auth forms
    initLoginForm();
});

/**
 * Resolve the application base URL from an explicit override or current page
 */
function getAppBaseUrl() {
    const configuredBaseUrl = document.querySelector('meta[name="app-base-url"]')?.content?.trim();
    if (configuredBaseUrl) {
        return configuredBaseUrl.endsWith('/') ? configuredBaseUrl : `${configuredBaseUrl}/`;
    }

    return new URL('.', window.location.href).toString();
}

/**
 * Initialize login form
 */
function initLoginForm() {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) return;
    const appBaseUrl = getAppBaseUrl();
    const loginApiUrl = new URL('api/auth/login.php', appBaseUrl).toString();

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const loginBtn = document.getElementById('loginBtn');
        const errorDiv = document.getElementById('errorMessage');

        // Clear previous errors
        clearErrors();

        // Basic validation
        if (!email || !password) {
            showError('Please fill in all fields');
            return;
        }

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showFieldError('emailError', 'Please enter a valid email address');
            return;
        }

        // Show loading state
        showLoading(loginBtn, 'Signing In...');

        try {
            const response = await fetch(loginApiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Redirect based on role
                redirectBasedOnRole(data.role, appBaseUrl);
            } else {
                showError(data.message || 'Login failed');
            }
        } catch (error) {
            console.error('Login error:', error);
            showError('Network error. Please try again.');
        } finally {
            hideLoading(loginBtn);
        }
    });

    // Password toggle
    initPasswordToggle();

    // Real-time validation
    initRealTimeValidation();
}

/**
 * Initialize password toggle
 */
function initPasswordToggle() {
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.textContent = 'Hide';
            } else {
                passwordInput.type = 'password';
                this.textContent = 'Show';
            }
        });
    }
}

/**
 * Initialize real-time validation
 */
function initRealTimeValidation() {
    const emailInput = document.getElementById('email');

    if (emailInput) {
        emailInput.addEventListener('input', debounce(function() {
            const email = this.value;
            const errorDiv = document.getElementById('emailError');

            if (email && !this.checkValidity()) {
                showFieldError('emailError', 'Please enter a valid email address');
            } else {
                clearFieldError('emailError');
            }
        }, 300));
    }
}

/**
 * Redirect based on user role
 */
function redirectBasedOnRole(role, appBaseUrl = getAppBaseUrl()) {
    switch (role) {
        case 'customer':
            window.location.href = new URL('pages/buyer/menu_gallery.php', appBaseUrl).toString();
            break;
        case 'vendor':
            window.location.href = new URL('pages/restaurant/dashboard.php', appBaseUrl).toString();
            break;
        case 'admin':
            window.location.href = new URL('pages/admin/dashboard.php', appBaseUrl).toString();
            break;
        default:
            window.location.href = new URL('login.php', appBaseUrl).toString();
            break;
    }
}

/**
 * Show global error message
 */
function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
}

/**
 * Show field-specific error
 */
function showFieldError(fieldId, message) {
    const errorDiv = document.getElementById(fieldId);
    if (errorDiv) {
        errorDiv.textContent = message;
    }
}

/**
 * Clear field error
 */
function clearFieldError(fieldId) {
    const errorDiv = document.getElementById(fieldId);
    if (errorDiv) {
        errorDiv.textContent = '';
    }
}

/**
 * Clear all errors
 */
function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.textContent = '';
    });

    const globalError = document.getElementById('errorMessage');
    if (globalError) {
        globalError.style.display = 'none';
    }
}

/**
 * Debounce utility
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
