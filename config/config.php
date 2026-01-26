<?php
/**
 * LankanLens Application Configuration
 * Loads environment variables from .env file and defines application constants
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables from .env file
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        die("Error: .env file not found at {$filePath}. Please create it from .env.example");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            // Set as environment variable and $_ENV
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

// Get project root directory
define('ROOT_PATH', dirname(__DIR__));

// Load .env file
loadEnv(ROOT_PATH . '/.env');

// Helper function to get environment variable with default fallback
function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// Database Configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'lankanlens'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN));
define('APP_URL', env('APP_URL', 'http://localhost/LankanLens'));
define('APP_NAME', 'LankanLens');

// Session Configuration
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 7200)); // 2 hours in seconds
define('SESSION_SECURE', filter_var(env('SESSION_SECURE', 'false'), FILTER_VALIDATE_BOOLEAN));
define('SESSION_HTTPONLY', filter_var(env('SESSION_HTTPONLY', 'true'), FILTER_VALIDATE_BOOLEAN));
define('SESSION_SAMESITE', env('SESSION_SAMESITE', 'strict'));

// WhatsApp Configuration
define('WHATSAPP_BASE_URL', env('WHATSAPP_BASE_URL', 'https://wa.me/'));

// File Upload Configuration
define('UPLOAD_DIR', ROOT_PATH . env('UPLOAD_DIR', '/uploads/'));
define('MAX_FILE_SIZE', (int)env('MAX_FILE_SIZE', 5242880)); // 5MB default
define('ALLOWED_IMAGE_TYPES', explode(',', env('ALLOWED_IMAGE_TYPES', 'jpg,jpeg,png,webp')));

// Security Settings
define('BCRYPT_ROUNDS', (int)env('BCRYPT_ROUNDS', 10));
define('MAX_LOGIN_ATTEMPTS', (int)env('MAX_LOGIN_ATTEMPTS', 5));
define('LOCKOUT_TIME', (int)env('LOCKOUT_TIME', 900)); // 15 minutes in seconds
define('REMEMBER_ME_DURATION', 30 * 24 * 60 * 60); // 30 days in seconds

// Email Configuration (Optional)
define('MAIL_HOST', env('MAIL_HOST', ''));
define('MAIL_PORT', env('MAIL_PORT', ''));
define('MAIL_USERNAME', env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', ''));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@lankanlens.lk'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'LankanLens'));

// Path Constants
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('API_PATH', ROOT_PATH . '/api');
define('VENDOR_PATH', ROOT_PATH . '/vendor');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('DATABASE_PATH', ROOT_PATH . '/database');
define('LOGS_PATH', ROOT_PATH . '/logs');

// Error Reporting Configuration
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Log errors to file
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . '/errors.log');

// Timezone Configuration
date_default_timezone_set('Asia/Colombo');

// // Session Cookie Parameters
// ini_set('session.cookie_httponly', SESSION_HTTPONLY ? 1 : 0);
// ini_set('session.cookie_secure', SESSION_SECURE ? 1 : 0);
// ini_set('session.cookie_samesite', SESSION_SAMESITE);
// ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Application Constants
define('ROLES', [
    'CUSTOMER' => 'customer',
    'VENDOR' => 'vendor',
    'ADMIN' => 'admin'
]);

define('USER_STATUS', [
    'ACTIVE' => 'active',
    'PENDING' => 'pending',
    'SUSPENDED' => 'suspended',
    'REJECTED' => 'rejected'
]);

define('EQUIPMENT_CONDITIONS', [
    'EXCELLENT' => 'Excellent',
    'GOOD' => 'Good',
    'FAIR' => 'Fair'
]);

// Currency
define('CURRENCY', 'LKR');
define('CURRENCY_SYMBOL', 'රු');

// Pagination
define('ITEMS_PER_PAGE', 12);

// Sri Lankan Cities (for dropdown)
define('SRI_LANKAN_CITIES', [
    'Colombo',
    'Kandy',
    'Galle',
    'Jaffna',
    'Negombo',
    'Matara',
    'Trincomalee',
    'Batticaloa',
    'Anuradhapura',
    'Kurunegala'
]);

// Success message - configuration loaded
if (APP_DEBUG) {
    // Optional: Log successful configuration load
    error_log('[' . date('Y-m-d H:i:s') . '] Config loaded successfully', 3, LOGS_PATH . '/app.log');
}
