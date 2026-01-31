<?php
include_once __DIR__ . '/../includes/nav.php';
/**
 * Email Uniqueness Check API
 * 
 * Returns JSON response indicating if email is available for registration
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

// Validate email format
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['available' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    $db = new Database();
    
    // Check if email exists in database
    $existing_user = $db->fetchOne(
        "SELECT user_id FROM users WHERE email = :email",
        ['email' => $email]
    );
    
    if ($existing_user) {
        echo json_encode(['available' => false, 'message' => 'Email already registered']);
    } else {
        echo json_encode(['available' => true, 'message' => 'Email is available']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'available' => true]); // Fail open
    error_log('Email check error: ' . $e->getMessage());
}
