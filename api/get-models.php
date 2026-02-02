<?php
/**
 * Get Models API
 * Returns equipment models based on category and brand
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Ensure only active vendors can access
requireActiveVendor();

header('Content-Type: application/json; charset=utf-8');

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';

if ($category_id <= 0 || $brand === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid category or brand.'
    ]);
    exit;
}

try {
    $db = new Database();

    $models = $db->fetchAll(
        "SELECT equipment_id, equipment_name, brand, model_number, equipment_type, specifications, image_url
         FROM equipment
         WHERE category_id = ? AND brand = ?
         ORDER BY equipment_name ASC",
        [$category_id, $brand]
    );

    echo json_encode([
        'success' => true,
        'models' => $models
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load models.'
    ]);
}
