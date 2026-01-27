<?php
/**
 * Authentication Helper Functions
 * Provides middleware and helper functions for authentication and authorization
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is currently logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user's role
 * @return string|null
 */
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

/**
 * Get current user's status
 * @return string|null
 */
function getUserStatus() {
    return isset($_SESSION['status']) ? $_SESSION['status'] : null;
}

/**
 * Check if current user is a customer
 * @return bool
 */
function isCustomer() {
    return getUserRole() === 'customer';
}

/**
 * Check if current user is a vendor
 * @return bool
 */
function isVendor() {
    return getUserRole() === 'vendor';
}

/**
 * Check if current user is an admin
 * @return bool
 */
function isAdmin() {
    return getUserRole() === 'admin';
}

/**
 * Get currently logged-in user's ID
 * @return int|null
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get currently logged-in user's full name
 * @return string|null
 */
function getCurrentUserName() {
    return isset($_SESSION['full_name']) ? $_SESSION['full_name'] : null;
}

/**
 * Get currently logged-in user's email
 * @return string|null
 */
function getCurrentUserEmail() {
    return isset($_SESSION['email']) ? $_SESSION['email'] : null;
}

/**
 * Require user to be logged in
 * If not authenticated, redirect to login page
 * @param string $returnUrl Optional URL to redirect to after login
 * @return void
 */
function requireLogin($returnUrl = null) {
    if (!isLoggedIn()) {
        if ($returnUrl) {
            $encoded = urlencode($returnUrl);
            header("Location: /public/login.php?return={$encoded}");
        } else {
            // Use current URL as return URL
            $current_url = $_SERVER['REQUEST_URI'];
            $encoded = urlencode($current_url);
            header("Location: /public/login.php?return={$encoded}");
        }
        exit;
    }
}

/**
 * Require user to have a specific role
 * If user lacks role, redirect to unauthorized page
 * @param string $role Role to check for (customer, vendor, admin)
 * @return void
 */
function requireRole($role) {
    requireLogin();
    
    if (getUserRole() !== $role) {
        header("Location: /public/unauthorized.php");
        exit;
    }
}

/**
 * Require user to be an admin
 * If not admin, redirect to unauthorized page
 * @return void
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        header("Location: /public/unauthorized.php");
        exit;
    }
}

/**
 * Require user to be an active vendor
 * Checks both role (vendor) and status (active)
 * If not active vendor, redirect appropriately
 * @return void
 */
function requireActiveVendor() {
    requireLogin();
    
    if (!isVendor()) {
        header("Location: /public/unauthorized.php");
        exit;
    }
    
    if (getUserStatus() === 'pending') {
        // Pending vendor - show pending page
        header("Location: /public/vendor-pending.php");
        exit;
    }
    
    if (getUserStatus() !== 'active') {
        // Suspended, rejected, or other status
        header("Location: /public/unauthorized.php");
        exit;
    }
}

/**
 * Require user to be a customer
 * If not customer, redirect to unauthorized page
 * @return void
 */
function requireCustomer() {
    requireLogin();
    
    if (!isCustomer()) {
        header("Location: /public/unauthorized.php");
        exit;
    }
}

/**
 * Check if user can access a specific resource
 * Useful for checking ownership of equipment, bookings, etc.
 * @param string $resourceType Type of resource (equipment, booking, etc.)
 * @param int $resourceOwnerId User ID of the resource owner
 * @return bool
 */
function canAccess($resourceType, $resourceOwnerId) {
    $user_id = getCurrentUserId();
    
    // Admins can access everything
    if (isAdmin()) {
        return true;
    }
    
    // Check if current user owns the resource
    if ($user_id === $resourceOwnerId) {
        return true;
    }
    
    return false;
}

/**
 * Check if user account is active
 * @return bool
 */
function isAccountActive() {
    return getUserStatus() === 'active';
}

/**
 * Check if user account is suspended
 * @return bool
 */
function isAccountSuspended() {
    return getUserStatus() === 'suspended';
}

/**
 * Require account to be active
 * If not active, show message about account status
 * @return void
 */
function requireActiveAccount() {
    requireLogin();
    
    if (!isAccountActive()) {
        $status = getUserStatus();
        
        if ($status === 'suspended') {
            die('Your account has been suspended. Please contact support.');
        } elseif ($status === 'rejected') {
            die('Your application has been rejected. Please contact support for more information.');
        } elseif ($status === 'pending') {
            header("Location: /public/vendor-pending.php");
            exit;
        }
    }
}

/**
 * Destroy user session and clear authentication
 * @return void
 */
function logout() {
    // Get user_id before destroying session
    $user_id = getCurrentUserId();
    
    // Clear remember_token from database if exists
    if ($user_id) {
        try {
            require_once '../config/database.php';
            $db = new Database();
            
            $query = "UPDATE users SET remember_token = NULL WHERE user_id = ?";
            $db->query($query, [$user_id]);
        } catch (Exception $e) {
            // Log error but continue with logout
            error_log("Error clearing remember token: " . $e->getMessage());
        }
    }
    
    // Clear remember_token cookie
    setcookie('remember_token', '', time() - 3600, '/');
    
    // Destroy session
    session_destroy();
}

/**
 * Redirect to logout page
 * Used after account actions to ensure clean logout
 * @param string $message Optional message to display
 * @return void
 */
function redirectToLogout($message = null) {
    logout();
    
    $redirect = '/public/login.php?logged_out=1';
    if ($message) {
        $redirect .= '&message=' . urlencode($message);
    }
    
    header("Location: {$redirect}");
    exit;
}

/**
 * Check if a role has permission for an action
 * @param string $role Role to check
 * @param string $action Action to check (view, edit, delete, etc.)
 * @return bool
 */
function roleHasPermission($role, $action) {
    $permissions = [
        'admin' => ['view', 'edit', 'delete', 'approve', 'reject', 'suspend', 'view_logs'],
        'vendor' => ['view', 'edit', 'delete', 'add_equipment', 'manage_listings'],
        'customer' => ['view', 'book', 'view_bookings']
    ];
    
    return isset($permissions[$role]) && in_array($action, $permissions[$role]);
}

/**
 * Get user's shop ID (for vendors)
 * @return int|null
 */
function getUserShopId() {
    if (!isVendor()) {
        return null;
    }
    
    // In a real implementation, this would query the database
    // For now, assume shop_id is stored in session or can be retrieved
    return isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : null;
}

/**
 * Verify CSRF token (placeholder for future implementation)
 * @param string $token Token to verify
 * @return bool
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Check if session has expired
 * Sessions expire after 2 hours of inactivity
 * @return bool
 */
function isSessionExpired() {
    $timeout = 2 * 60 * 60; // 2 hours
    
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            return true;
        }
    }
    
    $_SESSION['last_activity'] = time();
    return false;
}

/**
 * Check session validity
 * Validates session and returns expiration status
 * @return array ['valid' => bool, 'message' => string]
 */
function validateSession() {
    if (!isLoggedIn()) {
        return ['valid' => false, 'message' => 'Not logged in'];
    }
    
    if (isSessionExpired()) {
        logout();
        return ['valid' => false, 'message' => 'Session expired'];
    }
    
    return ['valid' => true, 'message' => 'Session valid'];
}

/**
 * Get user's full authorization info
 * Returns array with all auth details
 * @return array
 */
function getAuthInfo() {
    return [
        'logged_in' => isLoggedIn(),
        'user_id' => getCurrentUserId(),
        'full_name' => getCurrentUserName(),
        'email' => getCurrentUserEmail(),
        'role' => getUserRole(),
        'status' => getUserStatus(),
        'is_customer' => isCustomer(),
        'is_vendor' => isVendor(),
        'is_admin' => isAdmin(),
        'is_active' => isAccountActive(),
        'is_suspended' => isAccountSuspended(),
    ];
}

/**
 * Log authentication action to admin logs
 * @param string $action Action performed (approve, reject, suspend, delete, etc.)
 * @param int $target_user_id User affected by action
 * @param string $details Additional details about the action
 * @return bool
 */
function logAuthAction($action, $target_user_id, $details = '') {
    if (!isAdmin()) {
        return false;
    }
    
    try {
        require_once '../config/database.php';
        $db = new Database();
        
        $query = "
            INSERT INTO admin_logs (admin_user_id, action_type, target_user_id, details, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ";
        
        $db->query($query, [getCurrentUserId(), $action, $target_user_id, $details]);
        return true;
    } catch (Exception $e) {
        error_log("Error logging auth action: " . $e->getMessage());
        return false;
    }
}

?>
