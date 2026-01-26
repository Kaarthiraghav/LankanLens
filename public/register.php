<?php
/**
 * User Registration Page
 * 
 * Handles user registration for both customers and vendors
 * Customers get instant activation, vendors require admin approval
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Initialize variables
$errors = [];
$success = '';
$form_data = [
    'full_name' => '',
    'email' => '',
    'role' => 'customer',
    'shop_name' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $shop_name = trim($_POST['shop_name'] ?? '');
    $terms = isset($_POST['terms']);

    // Store form data for repopulation
    $form_data = [
        'full_name' => htmlspecialchars($full_name),
        'email' => htmlspecialchars($email),
        'role' => $role,
        'shop_name' => htmlspecialchars($shop_name)
    ];

    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    } elseif (strlen($full_name) < 3 || strlen($full_name) > 255) {
        $errors[] = 'Full name must be between 3 and 255 characters.';
    }

    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // Check email uniqueness
        try {
            $db = new Database();
            $existing_user = $db->fetchOne(
                "SELECT user_id FROM users WHERE email = :email",
                ['email' => $email]
            );
            if ($existing_user) {
                $errors[] = 'This email is already registered. Please login or use a different email.';
            }
        } catch (Exception $e) {
            $errors[] = 'Database error. Please try again later.';
        }
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (!in_array($role, ['customer', 'vendor'])) {
        $errors[] = 'Invalid role selected.';
    }

    if ($role === 'vendor' && empty($shop_name)) {
        $errors[] = 'Shop name is required for vendor registration.';
    }

    if (!$terms) {
        $errors[] = 'You must accept the Terms & Conditions.';
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $db = new Database();
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_ROUNDS]);
            
            // Set status based on role
            $status = ($role === 'customer') ? USER_STATUS['ACTIVE'] : USER_STATUS['PENDING'];
            
            // Prepare user data
            $user_data = [
                'full_name' => $full_name,
                'email' => $email,
                'password_hash' => $password_hash,
                'role' => $role,
                'status' => $status,
                'shop_name' => ($role === 'vendor') ? $shop_name : null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert user
            $user_id = $db->insert('users', $user_data);
            
            if ($user_id) {
                // Create session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                $_SESSION['status'] = $status;
                $_SESSION['full_name'] = $full_name;
                
                // Redirect based on role
                if ($role === 'customer') {
                    // Check for return URL
                    $return_url = $_GET['return'] ?? '/public/index.php';
                    header('Location: ' . $return_url);
                    exit;
                } else {
                    // Vendor - redirect to pending approval page
                    header('Location: /public/vendor-pending.php');
                    exit;
                }
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
            
        } catch (Exception $e) {
            $errors[] = 'An error occurred during registration. Please try again later.';
            error_log('Registration error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script src="/assets/js/auth.js" defer></script>
    <style>
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .password-strength.weak { width: 33%; background-color: #ef4444; }
        .password-strength.medium { width: 66%; background-color: #f59e0b; }
        .password-strength.strong { width: 100%; background-color: #10b981; }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .shake { animation: shake 0.5s; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900"><?php echo APP_NAME; ?></h1>
                <h2 class="mt-6 text-2xl font-semibold text-gray-900">Create your account</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Already have an account? 
                    <a href="/public/login.php<?php echo isset($_GET['return']) ? '?return=' . urlencode($_GET['return']) : ''; ?>" class="font-medium text-blue-600 hover:text-blue-500">
                        Login
                    </a>
                </p>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form class="mt-8 space-y-6 bg-white p-8 rounded-lg shadow" method="POST" action="" id="registerForm">
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="full_name" 
                        id="full_name" 
                        required
                        minlength="3"
                        maxlength="255"
                        value="<?php echo $form_data['full_name']; ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter your full name"
                    >
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        required
                        value="<?php echo $form_data['email']; ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="you@example.com"
                    >
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            required
                            minlength="8"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Min. 8 characters"
                        >
                        <button 
                            type="button" 
                            id="togglePassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                        >
                            <svg id="eyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Password Strength Indicator -->
                    <div class="mt-2 bg-gray-200 rounded-full h-1">
                        <div id="passwordStrength" class="password-strength"></div>
                    </div>
                    <p id="strengthText" class="mt-1 text-xs text-gray-500"></p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                        Confirm Password <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        id="confirm_password" 
                        required
                        minlength="8"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Re-enter your password"
                    >
                    <p id="passwordMatch" class="mt-1 text-xs hidden"></p>
                </div>

                <!-- Role Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Account Type <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-3">
                        <label class="flex items-start p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input 
                                type="radio" 
                                name="role" 
                                value="customer" 
                                id="roleCustomer"
                                <?php echo ($form_data['role'] === 'customer') ? 'checked' : ''; ?>
                                class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                            >
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-900">Customer</span>
                                <span class="block text-xs text-gray-500">Browse and rent equipment (Instant activation)</span>
                            </div>
                        </label>
                        <label class="flex items-start p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input 
                                type="radio" 
                                name="role" 
                                value="vendor" 
                                id="roleVendor"
                                <?php echo ($form_data['role'] === 'vendor') ? 'checked' : ''; ?>
                                class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                            >
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-900">Vendor</span>
                                <span class="block text-xs text-gray-500">List your equipment for rent (Requires admin approval)</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Shop Name (for Vendors only) -->
                <div id="shopNameField" class="hidden">
                    <label for="shop_name" class="block text-sm font-medium text-gray-700">
                        Shop Name <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="shop_name" 
                        id="shop_name" 
                        maxlength="255"
                        value="<?php echo $form_data['shop_name']; ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g., Pro Lens Rental"
                    >
                </div>

                <!-- Terms & Conditions -->
                <div class="flex items-start">
                    <input 
                        type="checkbox" 
                        name="terms" 
                        id="terms" 
                        required
                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="terms" class="ml-2 block text-sm text-gray-700">
                        I agree to the <a href="/public/terms.php" target="_blank" class="text-blue-600 hover:text-blue-500">Terms & Conditions</a> and <a href="/public/privacy.php" target="_blank" class="text-blue-600 hover:text-blue-500">Privacy Policy</a> <span class="text-red-500">*</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div>
                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                    >
                        <span id="btnText">Create Account</span>
                        <svg id="btnSpinner" class="hidden ml-3 h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
     !-- JavaScript handled by /assets/js/auth.js --t btnSpinner = document.getElementById('btnSpinner');

        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            btnText.textContent = 'Creating Account...';
            btnSpinner.classList.remove('hidden');
        });
    </script>
</body>
</html>
