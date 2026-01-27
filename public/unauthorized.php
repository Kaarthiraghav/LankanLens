<?php
/**
 * Unauthorized Access Page
 * 
 * Displayed when user tries to access a page they don't have permission for
 * Suggests login if user is not authenticated
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_helper.php';

$is_logged_in = isLoggedIn();
$user_name = $is_logged_in ? getCurrentUserName() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="bg-gradient-to-br from-red-50 to-orange-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white rounded-lg shadow-xl p-8 space-y-6">
            
            <!-- Lock Icon -->
            <div class="flex justify-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>

            <!-- Header -->
            <div class="text-center space-y-2">
                <h1 class="text-3xl font-bold text-gray-900">
                    Access Denied
                </h1>
                <p class="text-sm text-gray-600">
                    403 Forbidden
                </p>
            </div>

            <!-- Message -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 space-y-3">
                <p class="text-sm text-gray-700">
                    <span class="font-semibold text-gray-900">Sorry!</span> You don't have permission to access this page.
                </p>
                
                <?php if (!$is_logged_in): ?>
                    <div class="flex items-start space-x-2">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-gray-700">
                            You might need to <span class="font-semibold text-gray-900">log in</span> to access this page. Some features are only available to registered users.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="flex items-start space-x-2">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-gray-700">
                            Hello, <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($user_name); ?></span>! Your account doesn't have the required permissions for this page.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reasons List -->
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <h3 class="font-semibold text-gray-900 text-sm">Common reasons for this error:</h3>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-start space-x-2">
                        <span class="inline-block w-1.5 h-1.5 bg-gray-400 rounded-full mt-1.5 flex-shrink-0"></span>
                        <span>You're not logged in with the required account</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <span class="inline-block w-1.5 h-1.5 bg-gray-400 rounded-full mt-1.5 flex-shrink-0"></span>
                        <span>Your account doesn't have the necessary role or permissions</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <span class="inline-block w-1.5 h-1.5 bg-gray-400 rounded-full mt-1.5 flex-shrink-0"></span>
                        <span>Your account has been suspended</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <span class="inline-block w-1.5 h-1.5 bg-gray-400 rounded-full mt-1.5 flex-shrink-0"></span>
                        <span>The page you're looking for doesn't exist</span>
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3 pt-4">
                <a 
                    href="/public/index.php" 
                    class="block w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                >
                    Return to Home
                </a>

                <?php if (!$is_logged_in): ?>
                    <a 
                        href="/public/login.php" 
                        class="block w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                    >
                        Sign In
                    </a>

                    <a 
                        href="/public/register.php" 
                        class="block w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                    >
                        Create Account
                    </a>
                <?php else: ?>
                    <a 
                        href="/public/logout.php" 
                        class="block w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                    >
                        Logout
                    </a>
                <?php endif; ?>
            </div>

            <!-- Contact Support -->
            <div class="border-t pt-4 text-center text-xs text-gray-600">
                <p>
                    Still having trouble? <a href="/public/contact.php" class="font-medium text-blue-600 hover:text-blue-500">Contact our support team</a>
                </p>
            </div>

        </div>
    </div>
</body>
</html>
