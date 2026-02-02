<?php
/**
 * Search API Endpoint
 * Handles equipment search requests with validation, filtering, and analytics logging
 */

include_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/error-handler.php';

// Set response header to JSON
header('Content-Type: application/json');

/**
 * Log API error to /logs/errors.log with timestamp
 * @param string $errorType Type of error (e.g., 'Database Connection Error', 'Query Error')
 * @param string $errorMessage The error message
 * @param int $httpStatus HTTP status code
 */
function logApiError($errorType, $errorMessage, $httpStatus = 500) {
    $logFile = __DIR__ . '/../logs/errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$errorType}] [HTTP {$httpStatus}] {$errorMessage}\n";
    
    // Ensure logs directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Append to error log
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'results' => [],
    'count' => 0
];

try {
    // Initialize database connection with error handling
    try {
        $db = new Database();
    } catch (PDOException $e) {
        // Log the actual PDO error for debugging
        logApiError('Database Connection Error', $e->getMessage(), 503);
        
        http_response_code(503);
        $response['success'] = false;
        $response['message'] = 'Database connection failed. Please try again later.';
        
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        logApiError('Database Initialization Error', $e->getMessage(), 503);
        
        http_response_code(503);
        $response['success'] = false;
        $response['message'] = 'Database service unavailable. Please try again later.';
        
        echo json_encode($response);
        exit;
    }

    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        $response['message'] = 'Method not allowed. Use POST.';
        echo json_encode($response);
        exit;
    }

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate incoming parameters
    if (!$input) {
        http_response_code(400);
        $response['message'] = 'Invalid JSON input.';
        echo json_encode($response);
        exit;
    }

    // Extract and validate parameters
    $search_term = isset($input['search_term']) ? trim($input['search_term']) : '';
    $city = isset($input['city']) ? trim($input['city']) : '';
    $rental_date = isset($input['rental_date']) ? trim($input['rental_date']) : null;

    // At least one of search_term or city must be provided
    if (empty($search_term) && empty($city)) {
        http_response_code(400);
        $response['message'] = 'Please provide either a search term or select a city.';
        echo json_encode($response);
        exit;
    }

    // Validate search term length if provided
    if (!empty($search_term) && strlen($search_term) < 2) {
        http_response_code(400);
        $response['message'] = 'Search term must be at least 2 characters.';
        echo json_encode($response);
        exit;
    }

    // Validate date format if provided
    if (!empty($rental_date) && !strtotime($rental_date)) {
        http_response_code(400);
        $response['message'] = 'Invalid date format.';
        echo json_encode($response);
        exit;
    }

    // Sanitize inputs
    $search_term = htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8');
    $city = htmlspecialchars($city, ENT_QUOTES, 'UTF-8');

    // Build search query dynamically based on provided parameters
    $query = "
        SELECT 
            e.equipment_id,
            e.equipment_name,
            e.brand,
            e.model,
            e.description,
            e.condition,
            i.inventory_id,
            i.daily_rate_lkr,
            i.weekly_rate_lkr,
            i.monthly_rate_lkr,
            i.available_quantity,
            s.shop_id,
            s.shop_name,
            s.city,
            s.phone,
            s.whatsapp_number,
            COALESCE(AVG(sr.rating), 0) as average_rating,
            COUNT(sr.review_id) as review_count,
            GROUP_CONCAT(DISTINCT ec.category_name) as categories
        FROM equipment e
        INNER JOIN inventory i ON e.equipment_id = i.equipment_id
        INNER JOIN shops s ON e.shop_id = s.shop_id
        LEFT JOIN shop_reviews sr ON s.shop_id = sr.shop_id
        LEFT JOIN equipment_categories ec ON e.category_id = ec.category_id
        WHERE i.available_quantity > 0
    ";

    // Build WHERE conditions and parameters dynamically
    $conditions = [];
    $params = [];

    // Add search term condition if provided
    if (!empty($search_term)) {
        $conditions[] = "(
            e.equipment_name LIKE ? 
            OR e.brand LIKE ? 
            OR e.model LIKE ?
            OR e.description LIKE ?
        )";
        $searchPattern = '%' . $search_term . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }

    // Add city condition if provided
    if (!empty($city)) {
        $conditions[] = "s.city = ?";
        $params[] = $city;
    }

    // Append conditions to query
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }

    // Add GROUP BY and ORDER BY
    $query .= " GROUP BY e.equipment_id, i.inventory_id ";
    
    // Order by: exact brand match first (if search term provided), then price, then rating
    if (!empty($search_term)) {
        $query .= " ORDER BY 
            CASE WHEN e.brand = ? THEN 0 ELSE 1 END ASC,
            i.daily_rate_lkr ASC,
            average_rating DESC";
        $params[] = $search_term;
    } else {
        $query .= " ORDER BY 
            i.daily_rate_lkr ASC,
            average_rating DESC";
    }
    
    $query .= " LIMIT 50";

    // Execute query with prepared statements and error handling
    try {
        // Use Database class to execute query
        $stmt = $db->query($query, $params);

        // Fetch all results
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = [
                'equipment_id' => (int)$row['equipment_id'],
                'equipment_name' => $row['equipment_name'],
                'brand' => $row['brand'],
                'model' => $row['model'],
                'description' => $row['description'],
                'condition' => $row['condition'],
                'daily_rate_lkr' => (int)$row['daily_rate_lkr'],
                'weekly_rate_lkr' => $row['weekly_rate_lkr'] ? (int)$row['weekly_rate_lkr'] : null,
                'monthly_rate_lkr' => $row['monthly_rate_lkr'] ? (int)$row['monthly_rate_lkr'] : null,
                'available_quantity' => (int)$row['available_quantity'],
                'shop_id' => (int)$row['shop_id'],
                'shop_name' => $row['shop_name'],
                'shop_city' => $row['city'],
                'shop_phone' => $row['phone'],
                'shop_whatsapp' => $row['whatsapp_number'],
                'average_rating' => round((float)$row['average_rating'], 1),
                'review_count' => (int)$row['review_count'],
                'categories' => $row['categories'],
                'image_url' => null // Will be populated from equipment images if available
            ];
        }

    } catch (PDOException $pdoError) {
        // Log the PDO error with details
        logApiError('Query Execution Error', $pdoError->getMessage() . ' | Code: ' . $pdoError->getCode(), 500);
        
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Search failed. Please try again later.';
        $response['results'] = [];
        $response['count'] = 0;
        
        echo json_encode($response);
        exit;
    }

    // Log search to analytics
    logSearch($db, $search_term, $city, count($results));

    // Prepare success response
    $response['success'] = true;
    $response['message'] = count($results) > 0 
        ? 'Search completed successfully.' 
        : 'No results found for your search.';
    $response['results'] = $results;
    $response['count'] = count($results);

    http_response_code(200);

} catch (Exception $e) {
    // Log error
    logApiError('Search API Error', $e->getMessage(), 500);
    
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'An error occurred while processing your search. Please try again.';
    $response['results'] = [];
    $response['count'] = 0;
}

// Output JSON response
echo json_encode($response);

/**
 * Log search query to database for analytics
 * @param Database $db Database connection
 * @param string $search_term Search term used
 * @param string $city City searched in
 * @param int $result_count Number of results found
 */
function logSearch($db, $search_term, $city, $result_count) {
    try {
        $data = [
            'search_term' => $search_term,
            'search_city' => $city,
            'result_count' => $result_count,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('search_logs', $data);
        
    } catch (Exception $e) {
        // Log but don't fail the search if analytics logging fails
        logApiError('Analytics Logging Error', $e->getMessage(), 500);
    }
}
?>
