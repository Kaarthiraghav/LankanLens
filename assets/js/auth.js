/**
 * Authentication JavaScript Module
 * Handles client-side validation for login and registration forms
 */

class AuthValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.errors = {};
        this.isValid = true;
    }

    /**
     * Validate full name (3-255 characters)
     */
    validateFullName(name) {
        const trimmed = name.trim();
        if (trimmed.length === 0) {
            return { valid: false, message: 'Full name is required' };
        }
        if (trimmed.length < 3) {
            return { valid: false, message: 'Full name must be at least 3 characters' };
        }
        if (trimmed.length > 255) {
            return { valid: false, message: 'Full name cannot exceed 255 characters' };
        }
        return { valid: true, message: '' };
    }

    /**
     * Validate email format
     */
    validateEmail(email) {
        const trimmed = email.trim();
        if (trimmed.length === 0) {
            return { valid: false, message: 'Email is required' };
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(trimmed)) {
            return { valid: false, message: 'Please enter a valid email address' };
        }
        return { valid: true, message: '' };
    }

    /**
     * Validate password strength
     * Returns strength level and validation status
     */
    validatePassword(password) {
        if (password.length === 0) {
            return { valid: false, message: 'Password is required', strength: 0, strengthText: '' };
        }
        if (password.length < 8) {
            return { valid: false, message: 'Password must be at least 8 characters', strength: 0, strengthText: '' };
        }

        // Calculate password strength
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;

        let strengthText = '';
        let strengthClass = '';
        if (strength <= 2) {
            strengthText = 'Weak password';
            strengthClass = 'weak';
        } else if (strength <= 3) {
            strengthText = 'Medium password';
            strengthClass = 'medium';
        } else {
            strengthText = 'Strong password';
            strengthClass = 'strong';
        }

        return { 
            valid: true, 
            message: '', 
            strength: strength, 
            strengthText: strengthText,
            strengthClass: strengthClass
        };
    }

    /**
     * Validate password confirmation
     */
    validatePasswordConfirmation(password, confirmPassword) {
        if (confirmPassword.length === 0) {
            return { valid: false, message: 'Please confirm your password' };
        }
        if (password !== confirmPassword) {
            return { valid: false, message: 'Passwords do not match' };
        }
        return { valid: true, message: '✓ Passwords match' };
    }

    /**
     * Show validation feedback for a field
     */
    showFieldFeedback(fieldId, isValid, message) {
        const field = document.getElementById(fieldId);
        const feedbackId = `${fieldId}Feedback`;
        let feedback = document.getElementById(feedbackId);

        // Create feedback element if it doesn't exist
        if (!feedback) {
            feedback = document.createElement('p');
            feedback.id = feedbackId;
            feedback.className = 'mt-1 text-xs';
            field.parentNode.appendChild(feedback);
        }

        // Update field styling
        if (isValid) {
            field.classList.remove('border-red-500');
            field.classList.add('border-green-500');
            feedback.className = 'mt-1 text-xs text-green-600';
        } else {
            field.classList.remove('border-green-500');
            field.classList.add('border-red-500');
            feedback.className = 'mt-1 text-xs text-red-600';
        }

        feedback.textContent = message;
        feedback.classList.remove('hidden');
    }

    /**
     * Clear field feedback
     */
    clearFieldFeedback(fieldId) {
        const field = document.getElementById(fieldId);
        const feedbackId = `${fieldId}Feedback`;
        const feedback = document.getElementById(feedbackId);

        field.classList.remove('border-red-500', 'border-green-500');
        if (feedback) {
            feedback.classList.add('hidden');
        }
    }

    /**
     * Check email uniqueness via AJAX
     */
    async checkEmailUniqueness(email) {
        try {
            const response = await fetch('/api/check-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            });

            const data = await response.json();
            return data.available; // Returns true if email is available
        } catch (error) {
            console.error('Email uniqueness check failed:', error);
            return true; // Assume available if check fails
        }
    }
}

/**
 * Registration Form Handler
 */
class RegistrationForm {
    constructor() {
        this.form = document.getElementById('registerForm');
        if (!this.form) return;

        this.validator = new AuthValidator('registerForm');
        this.initializeFields();
        this.attachEventListeners();
    }

    initializeFields() {
        this.fullNameField = document.getElementById('full_name');
        this.emailField = document.getElementById('email');
        this.passwordField = document.getElementById('password');
        this.confirmPasswordField = document.getElementById('confirm_password');
        this.roleCustomer = document.getElementById('roleCustomer');
        this.roleVendor = document.getElementById('roleVendor');
        this.shopNameField = document.getElementById('shopNameField');
        this.shopNameInput = document.getElementById('shop_name');
        this.termsCheckbox = document.getElementById('terms');
        this.submitBtn = document.getElementById('submitBtn');
        this.btnText = document.getElementById('btnText');
        this.btnSpinner = document.getElementById('btnSpinner');

        // Password strength elements
        this.strengthBar = document.getElementById('passwordStrength');
        this.strengthText = document.getElementById('strengthText');

        // Password match element
        this.passwordMatch = document.getElementById('passwordMatch');

        // Toggle password visibility
        this.togglePassword = document.getElementById('togglePassword');
    }

    attachEventListeners() {
        // Real-time validation
        this.fullNameField.addEventListener('blur', () => this.validateFullName());
        this.emailField.addEventListener('blur', () => this.validateEmail());
        this.passwordField.addEventListener('input', () => this.validatePassword());
        this.confirmPasswordField.addEventListener('input', () => this.validatePasswordConfirmation());

        // Role selection toggle
        this.roleCustomer.addEventListener('change', () => this.toggleShopNameField());
        this.roleVendor.addEventListener('change', () => this.toggleShopNameField());
        this.toggleShopNameField(); // Initialize on load

        // Password visibility toggle
        if (this.togglePassword) {
            this.togglePassword.addEventListener('click', () => this.togglePasswordVisibility());
        }

        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    validateFullName() {
        const result = this.validator.validateFullName(this.fullNameField.value);
        if (this.fullNameField.value.trim().length > 0) {
            this.validator.showFieldFeedback('full_name', result.valid, result.message);
        }
        return result.valid;
    }

    async validateEmail() {
        const result = this.validator.validateEmail(this.emailField.value);
        
        if (!result.valid) {
            this.validator.showFieldFeedback('email', false, result.message);
            return false;
        }

        // Optional: Check email uniqueness via AJAX
        const emailValue = this.emailField.value.trim();
        if (emailValue.length > 0) {
            const isAvailable = await this.validator.checkEmailUniqueness(emailValue);
            if (!isAvailable) {
                this.validator.showFieldFeedback('email', false, 'This email is already registered');
                return false;
            }
            this.validator.showFieldFeedback('email', true, '✓ Email is available');
        }
        
        return true;
    }

    validatePassword() {
        const result = this.validator.validatePassword(this.passwordField.value);
        
        // Update strength indicator
        if (this.strengthBar && this.strengthText) {
            if (result.strength === 0) {
                this.strengthBar.className = 'password-strength';
                this.strengthText.textContent = '';
            } else {
                this.strengthBar.className = 'password-strength ' + result.strengthClass;
                this.strengthText.textContent = result.strengthText;
                
                if (result.strengthClass === 'weak') {
                    this.strengthText.className = 'mt-1 text-xs text-red-500';
                } else if (result.strengthClass === 'medium') {
                    this.strengthText.className = 'mt-1 text-xs text-orange-500';
                } else {
                    this.strengthText.className = 'mt-1 text-xs text-green-500';
                }
            }
        }

        return result.valid;
    }

    validatePasswordConfirmation() {
        const password = this.passwordField.value;
        const confirmPassword = this.confirmPasswordField.value;
        const result = this.validator.validatePasswordConfirmation(password, confirmPassword);

        if (confirmPassword.length === 0) {
            this.passwordMatch.classList.add('hidden');
            return false;
        }

        this.passwordMatch.classList.remove('hidden');
        
        if (result.valid) {
            this.passwordMatch.textContent = result.message;
            this.passwordMatch.className = 'mt-1 text-xs text-green-600';
        } else {
            this.passwordMatch.textContent = '✗ ' + result.message;
            this.passwordMatch.className = 'mt-1 text-xs text-red-600';
        }

        return result.valid;
    }

    toggleShopNameField() {
        if (this.roleVendor.checked) {
            this.shopNameField.classList.remove('hidden');
            this.shopNameInput.setAttribute('required', 'required');
        } else {
            this.shopNameField.classList.add('hidden');
            this.shopNameInput.removeAttribute('required');
            this.shopNameInput.value = '';
        }
    }

    togglePasswordVisibility() {
        const eyeIcon = document.getElementById('eyeIcon');
        const type = this.passwordField.type === 'password' ? 'text' : 'password';
        this.passwordField.type = type;
        
        if (type === 'text') {
            eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
        } else {
            eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
        }
    }

    async handleSubmit(e) {
        e.preventDefault();

        // Validate all fields
        const isFullNameValid = this.validateFullName();
        const isEmailValid = await this.validateEmail();
        const isPasswordValid = this.validatePassword();
        const isConfirmPasswordValid = this.validatePasswordConfirmation();

        // Check terms acceptance
        if (!this.termsCheckbox.checked) {
            alert('Please accept the Terms & Conditions to continue.');
            return;
        }

        // Check vendor shop name
        if (this.roleVendor.checked && this.shopNameInput.value.trim().length === 0) {
            this.validator.showFieldFeedback('shop_name', false, 'Shop name is required for vendors');
            return;
        }

        // If all validations pass, submit the form
        if (isFullNameValid && isEmailValid && isPasswordValid && isConfirmPasswordValid) {
            this.showLoadingState();
            this.form.submit();
        } else {
            // Shake animation for error
            this.form.classList.add('shake');
            setTimeout(() => this.form.classList.remove('shake'), 500);
        }
    }

    showLoadingState() {
        this.submitBtn.disabled = true;
        this.btnText.textContent = 'Creating Account...';
        this.btnSpinner.classList.remove('hidden');
    }
}

/**
 * Login Form Handler
 */
class LoginForm {
    constructor() {
        this.form = document.getElementById('loginForm');
        if (!this.form) return;

        this.validator = new AuthValidator('loginForm');
        this.initializeFields();
        this.attachEventListeners();
    }

    initializeFields() {
        this.emailField = document.getElementById('email');
        this.passwordField = document.getElementById('password');
        this.rememberMeCheckbox = document.getElementById('remember_me');
        this.submitBtn = document.getElementById('submitBtn');
        this.btnText = document.getElementById('btnText');
        this.btnSpinner = document.getElementById('btnSpinner');
        this.togglePassword = document.getElementById('togglePassword');
    }

    attachEventListeners() {
        // Real-time validation
        this.emailField.addEventListener('blur', () => this.validateEmail());
        this.passwordField.addEventListener('blur', () => this.validatePassword());

        // Password visibility toggle
        if (this.togglePassword) {
            this.togglePassword.addEventListener('click', () => this.togglePasswordVisibility());
        }

        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    validateEmail() {
        const result = this.validator.validateEmail(this.emailField.value);
        if (this.emailField.value.trim().length > 0) {
            this.validator.showFieldFeedback('email', result.valid, result.message);
        }
        return result.valid;
    }

    validatePassword() {
        const password = this.passwordField.value;
        if (password.length === 0) {
            this.validator.showFieldFeedback('password', false, 'Password is required');
            return false;
        }
        this.validator.clearFieldFeedback('password');
        return true;
    }

    togglePasswordVisibility() {
        const eyeIcon = document.getElementById('eyeIcon');
        const type = this.passwordField.type === 'password' ? 'text' : 'password';
        this.passwordField.type = type;
        
        if (type === 'text') {
            eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
        } else {
            eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
        }
    }

    handleSubmit(e) {
        e.preventDefault();

        const isEmailValid = this.validateEmail();
        const isPasswordValid = this.validatePassword();

        if (isEmailValid && isPasswordValid) {
            this.showLoadingState();
            this.form.submit();
        } else {
            this.form.classList.add('shake');
            setTimeout(() => this.form.classList.remove('shake'), 500);
        }
    }

    showLoadingState() {
        this.submitBtn.disabled = true;
        this.btnText.textContent = 'Logging In...';
        this.btnSpinner.classList.remove('hidden');
    }
}

/**
 * Initialize authentication forms on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize registration form if present
    if (document.getElementById('registerForm')) {
        new RegistrationForm();
    }

    // Initialize login form if present
    if (document.getElementById('loginForm')) {
        new LoginForm();
    }
});

/**
 * Utility: Show toast notification
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white transition-opacity duration-300 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, type === 'success' ? 3000 : 5000);
}
