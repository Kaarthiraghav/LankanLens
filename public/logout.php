<?php
include_once __DIR__ . '/../includes/nav.php';
/**
 * User Logout Handler
 * 
 * Destroys user session, clears remember token cookie, and updates database
 * Redirects to home page with success message
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Get user ID before destroying session
    $user_id = $_SESSION['user_id'] ?? null;

    // Clear remember_token cookie if it exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie(
            'remember_token',
            '',
            time() - 3600, // Set expiration to past time
            '/',
            '',
            false,
            true // httponly
        );
    }

    // Clear remember_token in database if user was logged in
    if ($user_id) {
        try {
            $db = new Database();
            $db->update(
                'users',
                ['remember_token' => null],
                ['user_id' => $user_id]
            );
        } catch (Exception $e) {
            error_log('Logout error clearing token: ' . $e->getMessage());
            // Continue with logout even if database update fails
        }
    }

    // Destroy the session
    session_destroy();

    // Redirect to home page with success message
    header('Location: ' . BASE_URL . 'public/index.php?logout=success');
    exit;

} catch (Exception $e) {
    error_log('Logout error: ' . $e->getMessage());
    
    // Even if there's an error, try to destroy session and redirect
    session_destroy();
    header('Location: ' . BASE_URL . 'public/index.php');
    exit;
}
?>
