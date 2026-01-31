<?php
include_once __DIR__ . '/../includes/nav.php';
/**
 * API: Check Authentication Status
 * Returns whether current user is authenticated
 * Used by JavaScript for gated content management
 */

session_start();

// Set JSON response header
header('Content-Type: application/json');

// Check if user is authenticated via session
$authenticated = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Return authentication status
http_response_code(200);
echo json_encode([
    'authenticated' => $authenticated,
    'user_id' => $authenticated ? $_SESSION['user_id'] : null,
    'email' => $authenticated ? $_SESSION['email'] : null,
    'role' => $authenticated ? $_SESSION['role'] : null,
    'status' => $authenticated ? $_SESSION['status'] : null
]);
?>
