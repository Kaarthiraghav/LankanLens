<?php
include_once __DIR__ . '/../includes/nav.php';
/**
 * Vendor Pending Approval Page
 * 
 * Shown to vendors whose accounts are pending admin approval
 * Allows them to browse equipment while waiting
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Check if user is logged in and is a pending vendor
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'public/login.php?return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_role = getUserRole();
$user_status = getUserStatus();
$user_name = getCurrentUserName();
$user_email = $_SESSION['email'] ?? '';

// Only pending vendors can see this page
if ($user_role !== ROLES['VENDOR'] || $user_status !== USER_STATUS['PENDING']) {
    // If already active, redirect to vendor dashboard
    if ($user_role === ROLES['VENDOR'] && $user_status === USER_STATUS['ACTIVE']) {
        header('Location: ' . BASE_URL . 'vendor/dashboard.php');
        exit;
    }
    // Otherwise redirect to home
    header('Location: ' . BASE_URL . 'public/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approval - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white rounded-lg shadow-xl p-8 space-y-6">
            
            <!-- Hourglass Icon -->
            <div class="flex justify-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-amber-100 rounded-full">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Header -->
            <div class="text-center space-y-2">
                <h1 class="text-3xl font-bold text-gray-900">
                    Your Vendor Account is Pending Approval
                </h1>
                <p class="text-sm text-gray-600">
                    We're reviewing your application
                </p>
            </div>

            <!-- Message -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-3">
                <p class="text-sm text-gray-700">
                    <span class="font-semibold text-gray-900">Thank you for registering!</span> Our admin team will review your vendor application within 1-2 business days.
                </p>
                
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-gray-700">
                        You'll receive a confirmation email at <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($user_email); ?></span> once approved.
                    </p>
                </div>
            </div>

            <!-- What's Next Section -->
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <h3 class="font-semibold text-gray-900 text-sm">While you wait:</h3>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-center space-x-2">
                        <span class="inline-block w-2 h-2 bg-blue-600 rounded-full"></span>
                        <span>Browse available equipment and shop listings</span>
                    </li>
                    <li class="flex items-center space-x-2">
                        <span class="inline-block w-2 h-2 bg-blue-600 rounded-full"></span>
                        <span>Explore rental rates and popular gear</span>
                    </li>
                    <li class="flex items-center space-x-2">
                        <span class="inline-block w-2 h-2 bg-blue-600 rounded-full"></span>
                        <span>Get familiar with how <?php echo APP_NAME; ?> works</span>
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3 pt-4">
                <a 
                    href="<?php echo BASE_URL; ?>public/index.php" 
                    class="block w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                >
                    Browse Equipment
                </a>
                
                <a 
                    href="<?php echo BASE_URL; ?>public/logout.php" 
                    class="block w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                >
                    Logout
                </a>
            </div>

            <!-- Contact Support -->
            <div class="border-t pt-4 text-center text-xs text-gray-600">
                <p>
                    Have questions? <a href="<?php echo BASE_URL; ?>public/contact.php" class="font-medium text-blue-600 hover:text-blue-500">Contact our support team</a>
                </p>
            </div>

        </div>
    </div>
</body>
</html>
