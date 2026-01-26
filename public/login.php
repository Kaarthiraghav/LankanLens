<?php
/**
 * User Login Page
 * 
 * Handles user authentication for customers, vendors, and admins
 * Supports return URL redirection and remember me functionality
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Initialize variables
$errors = [];
$form_data = [
    'email' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    // Store email for repopulation
    $form_data['email'] = htmlspecialchars($email);

    // Basic validation
    if (empty($email)) {
        $errors[] = 'Email is required.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    // If basic validation passes, proceed with authentication
    if (empty($errors)) {
        try {
            $db = new Database();
            
            // Query user by email
            $user = $db->fetchOne(
                "SELECT * FROM users WHERE email = :email",
                ['email' => $email]
            );

            if (!$user) {
                // Generic error for security
                $errors[] = 'Invalid email or password.';
            } else {
                // Check failed login attempts (account lockout)
                $max_attempts = MAX_LOGIN_ATTEMPTS;
                $lockout_time = LOCKOUT_TIME; // seconds
                
                if ($user['failed_login_attempts'] >= $max_attempts && $user['last_failed_login']) {
                    $time_since_last_attempt = time() - strtotime($user['last_failed_login']);
                    
                    if ($time_since_last_attempt < $lockout_time) {
                        $minutes_remaining = ceil(($lockout_time - $time_since_last_attempt) / 60);
                        $errors[] = "Account locked due to multiple failed login attempts. Please try again in {$minutes_remaining} minute(s).";
                    }
                }

                // Verify password if not locked
                if (empty($errors)) {
                    if (!password_verify($password, $user['password_hash'])) {
                        // Increment failed login attempts
                        $db->update(
                            'users',
                            [
                                'failed_login_attempts' => $user['failed_login_attempts'] + 1,
                                'last_failed_login' => date('Y-m-d H:i:s')
                            ],
                            ['user_id' => $user['user_id']]
                        );
                        
                        $errors[] = 'Invalid email or password.';
                    } else {
                        // Password correct - check account status
                        if ($user['status'] === USER_STATUS['SUSPENDED']) {
                            $errors[] = 'Your account has been suspended. Please contact admin.';
                        } elseif ($user['status'] === USER_STATUS['REJECTED']) {
                            $errors[] = 'Your vendor application was not approved.';
                        } elseif ($user['status'] === USER_STATUS['PENDING'] && $user['role'] === ROLES['VENDOR']) {
                            // Vendor pending approval - create session and redirect
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['status'] = $user['status'];
                            $_SESSION['full_name'] = $user['full_name'];
                            
                            header('Location: /public/vendor-pending.php');
                            exit;
                        } elseif ($user['status'] === USER_STATUS['ACTIVE']) {
                            // Account is active - proceed with login
                            
                            // Reset failed login attempts
                            $db->update(
                                'users',
                                [
                                    'failed_login_attempts' => 0,
                                    'last_failed_login' => null,
                                    'last_login_at' => date('Y-m-d H:i:s')
                                ],
                                ['user_id' => $user['user_id']]
                            );
                            
                            // Create session
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['status'] = $user['status'];
                            $_SESSION['full_name'] = $user['full_name'];
                            
                            // Handle Remember Me
                            if ($remember_me) {
                                $token = bin2hex(random_bytes(32));
                                
                                // Store token in database
                                $db->update(
                                    'users',
                                    ['remember_token' => $token],
                                    ['user_id' => $user['user_id']]
                                );
                                
                                // Set cookie for 30 days
                                $cookie_duration = REMEMBER_ME_DURATION;
                                setcookie(
                                    'remember_token',
                                    $token,
                                    time() + $cookie_duration,
                                    '/',
                                    '',
                                    false, // Set to true in production with HTTPS
                                    true // httponly
                                );
                            }
                            
                            // Redirect based on role
                            if ($user['role'] === ROLES['ADMIN']) {
                                header('Location: /admin/dashboard.php');
                                exit;
                            } elseif ($user['role'] === ROLES['VENDOR']) {
                                header('Location: /vendor/dashboard.php');
                                exit;
                            } else {
                                // Customer - check for return URL
                                $return_url = $_GET['return'] ?? '/public/index.php';
                                header('Location: ' . $return_url);
                                exit;
                            }
                        } else {
                            $errors[] = 'Your account is not yet activated. Please contact support.';
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            $errors[] = 'An error occurred during login. Please try again later.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script src="/assets/js/auth.js" defer></script>
    <style>
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
                <h2 class="mt-6 text-2xl font-semibold text-gray-900">Sign in to your account</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Don't have an account? 
                    <a href="/public/register.php<?php echo isset($_GET['return']) ? '?return=' . urlencode($_GET['return']) : ''; ?>" class="font-medium text-blue-600 hover:text-blue-500">
                        Sign Up
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
                            <h3 class="text-sm font-medium text-red-800">Login failed:</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form class="mt-8 space-y-6 bg-white p-8 rounded-lg shadow" method="POST" action="" id="loginForm">
                <!-- Preserve return URL -->
                <?php if (isset($_GET['return'])): ?>
                    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_GET['return']); ?>">
                <?php endif; ?>

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
                        autofocus
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
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter your password"
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
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="remember_me" 
                            id="remember_me" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                            Remember me for 30 days
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500" title="Coming soon">
                            Forgot password?
                        </a>
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                    >
                        <span id="btnText">Sign In</span>
                        <svg id="btnSpinner" class="hidden ml-3 h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>

                <!-- Registration Link -->
                <div class="text-center text-sm text-gray-600">
                    New to LankanLens? 
                    <a href="/public/register.php<?php echo isset($_GET['return']) ? '?return=' . urlencode($_GET['return']) : ''; ?>" class="font-medium text-blue-600 hover:text-blue-500">
                        Create an account
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript handled by /assets/js/auth.js -->
</body>
</html>
