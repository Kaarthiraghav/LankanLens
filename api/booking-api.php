<?php
/**
 * Booking API Endpoint
 * Logs booking requests to database for analytics and shop owner follow-up
 * 
 * This is an optional endpoint that records booking attempts even though
 * the actual booking happens via WhatsApp. Useful for:
 * - Analytics on booking conversion rates
 * - Shop owner dashboard to see inquiries
 * - Follow-up on abandoned bookings
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/error-handler.php';

try {
    // Initialize database connection
    $db = new Database();
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // If JSON parsing failed, try form data
    if (!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    $required_fields = ['user_name', 'equipment_id', 'shop_id', 'rental_duration_days'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit;
    }
    
    // Sanitize and validate inputs
    $user_name = trim(htmlspecialchars($data['user_name']));
    $equipment_id = intval($data['equipment_id']);
    $shop_id = intval($data['shop_id']);
    $rental_duration_days = intval($data['rental_duration_days']);
    $additional_notes = isset($data['additional_notes']) ? trim(htmlspecialchars($data['additional_notes'])) : null;
    $user_contact = isset($data['user_contact']) ? trim($data['user_contact']) : null;
    $user_email = isset($data['user_email']) ? trim($data['user_email']) : null;
    $rental_start_date = isset($data['rental_start_date']) ? $data['rental_start_date'] : null;
    
    // Validate user name length
    if (strlen($user_name) < 2 || strlen($user_name) > 255) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'User name must be between 2 and 255 characters'
        ]);
        exit;
    }
    
    // Validate rental duration
    if ($rental_duration_days < 1 || $rental_duration_days > 365) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Rental duration must be between 1 and 365 days'
        ]);
        exit;
    }
    
    // Validate additional notes length if provided
    if ($additional_notes && strlen($additional_notes) > 1000) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Additional notes must not exceed 1000 characters'
        ]);
        exit;
    }
    
    // Fetch equipment and inventory details to calculate estimated total
    $query = "
        SELECT 
            e.equipment_name,
            e.brand,
            i.daily_rate_lkr,
            i.available_quantity,
            s.shop_name
        FROM equipment e
        INNER JOIN inventory i ON e.equipment_id = i.equipment_id
        INNER JOIN shops s ON e.shop_id = s.shop_id
        WHERE e.equipment_id = ? AND e.shop_id = ?
        LIMIT 1
    ";
    
    $equipment = $db->fetchOne($query, [$equipment_id, $shop_id]);
    
    if (!$equipment) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Equipment not found or does not belong to specified shop'
        ]);
        exit;
    }
    
    // Check availability
    if ($equipment['available_quantity'] <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Equipment is currently unavailable'
        ]);
        exit;
    }
    
    // Calculate estimated total (daily_rate × duration_days)
    $daily_rate = floatval($equipment['daily_rate_lkr']);
    $estimated_total_lkr = $daily_rate * $rental_duration_days;
    
    // Insert booking request into database
    $insert_query = "
        INSERT INTO booking_requests (
            user_name,
            user_contact,
            user_email,
            equipment_id,
            shop_id,
            rental_start_date,
            rental_duration_days,
            additional_notes,
            request_status,
            estimated_total_lkr,
            whatsapp_sent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, TRUE)
    ";
    
    $params = [
        $user_name,
        $user_contact,
        $user_email,
        $equipment_id,
        $shop_id,
        $rental_start_date,
        $rental_duration_days,
        $additional_notes,
        $estimated_total_lkr
    ];
    
    $request_id = $db->insert($insert_query, $params);
    
    if (!$request_id) {
        throw new Exception('Failed to insert booking request');
    }
    
    // Log successful booking request
    error_log(sprintf(
        "[Booking Request] ID: %d | User: %s | Equipment: %s | Shop: %s | Duration: %d days | Total: Rs %.2f LKR",
        $request_id,
        $user_name,
        $equipment['equipment_name'],
        $equipment['shop_name'],
        $rental_duration_days,
        $estimated_total_lkr
    ));
    
    // Return success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Booking request logged successfully',
        'data' => [
            'request_id' => $request_id,
            'equipment_name' => $equipment['equipment_name'],
            'brand' => $equipment['brand'],
            'shop_name' => $equipment['shop_name'],
            'rental_duration_days' => $rental_duration_days,
            'daily_rate_lkr' => $daily_rate,
            'estimated_total_lkr' => $estimated_total_lkr,
            'request_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    // Database error
    error_log("Booking API Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred. Please try again later.'
    ]);
    
} catch (Exception $e) {
    // General error
    error_log("Booking API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while processing your request.'
    ]);
}
