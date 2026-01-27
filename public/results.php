<?php
/**
 * Search Results Page
 * Displays equipment search results with gated shop information for non-authenticated users
 */

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Get search parameters from URL
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$rental_date = isset($_GET['date']) ? trim($_GET['date']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Initialize database
$db = new Database();

// Validate search parameters
$results = [];
$total_results = 0;
$error_message = '';

if (empty($search_term) && empty($city)) {
    $error_message = 'Please enter a search term or select a city.';
} else {
    // Build dynamic WHERE clause
    $where_clauses = ['i.available_quantity > 0'];
    $params = [];

    // Add search term filter
    if (!empty($search_term)) {
        $where_clauses[] = "(e.equipment_name LIKE ? OR e.brand LIKE ? OR e.model_number LIKE ?)";
        $search_wildcard = "%{$search_term}%";
        $params[] = $search_wildcard;
        $params[] = $search_wildcard;
        $params[] = $search_wildcard;
    }

    // Add city filter
    if (!empty($city)) {
        $where_clauses[] = "sl.city_name = ?";
        $params[] = $city;
    }

    $where_clause = implode(' AND ', $where_clauses);

    // Count total results
    $count_query = "
        SELECT COUNT(*) as total
        FROM equipment e
        JOIN inventory i ON e.equipment_id = i.equipment_id
        JOIN shops s ON e.shop_id = s.shop_id
        LEFT JOIN shop_locations sl ON s.shop_id = sl.shop_id
        WHERE {$where_clause}
    ";
    
    $count_result = $db->fetchOne($count_query, $params);
    $total_results = $count_result['total'] ?? 0;

    // Fetch results with pagination
    if ($total_results > 0) {
        $query = "
            SELECT 
                e.equipment_id,
                e.equipment_name,
                e.brand,
                e.model_number,
                e.description,
                e.condition,
                e.image_url,
                i.daily_rate_lkr,
                i.available_quantity,
                s.shop_id,
                s.shop_name,
                s.whatsapp_number,
                sl.city_name
            FROM equipment e
            JOIN inventory i ON e.equipment_id = i.equipment_id
            JOIN shops s ON e.shop_id = s.shop_id
            LEFT JOIN shop_locations sl ON s.shop_id = sl.shop_id
            WHERE {$where_clause}
            ORDER BY e.brand, i.daily_rate_lkr ASC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $items_per_page;
        $params[] = $offset;
        
        $results = $db->fetchAll($query, $params);
    }
}

$total_pages = ceil($total_results / $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - LankanLens</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="/index.php" class="text-xl font-bold text-blue-600">LankanLens</a>
                <div class="flex gap-4">
                    <?php if ($is_logged_in): ?>
                        <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <a href="/public/logout.php" class="text-blue-600 hover:text-blue-800">Logout</a>
                    <?php else: ?>
                        <a href="/public/login.php" class="text-blue-600 hover:text-blue-800">Login</a>
                        <a href="/public/register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Search Summary Section -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Search Results</h1>
            <p class="text-gray-600">
                <?php if (!empty($search_term) || !empty($city)): ?>
                    <?php if (!empty($search_term)): ?>
                        <span>for <strong><?php echo htmlspecialchars($search_term); ?></strong></span>
                    <?php endif; ?>
                    <?php if (!empty($city)): ?>
                        <span> in <strong><?php echo htmlspecialchars($city); ?></strong></span>
                    <?php endif; ?>
                    <span> — <strong><?php echo $total_results; ?></strong> equipment<?php echo $total_results !== 1 ? 's' : ''; ?> found</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (!empty($error_message)): ?>
            <!-- Error State -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                <svg class="w-12 h-12 text-red-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="text-lg font-semibold text-red-900 mb-2">Search Error</h2>
                <p class="text-red-700 mb-4"><?php echo htmlspecialchars($error_message); ?></p>
                <a href="/index.php" class="inline-block bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                    Back to Home
                </a>
            </div>

        <?php elseif ($total_results === 0): ?>
            <!-- Empty State -->
            <div class="text-center py-12">
                <svg class="w-20 h-20 text-gray-400 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 015.646 5.646 9.001 9.001 0 0020.354 15.354z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h.01M15 15h.01M11 6h.01M17 12h.01M9 15h.01"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">No equipment found</h2>
                <p class="text-gray-600 mb-6">
                    We couldn't find any equipment matching your search. Try adjusting your search terms.
                </p>
                <div class="flex gap-3 justify-center flex-wrap">
                    <a href="/index.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                        Try Another Search
                    </a>
                    <a href="/index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-900 font-semibold py-2 px-6 rounded-lg transition">
                        Browse All Equipment
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Results Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php foreach ($results as $item): ?>
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden card-hover">
                        <!-- Equipment Image -->
                        <a href="/public/product.php?id=<?php echo $item['equipment_id']; ?>" class="block relative h-48 bg-gray-200 overflow-hidden">
                            <?php if ($item['image_url']): ?>
                                <img 
                                    src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                    alt="<?php echo htmlspecialchars($item['equipment_name']); ?>"
                                    class="w-full h-full object-cover hover:scale-105 transition-transform"
                                >
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <!-- Condition Badge -->
                            <div class="absolute top-3 right-3">
                                <?php
                                    $condition_colors = [
                                        'Excellent' => 'bg-green-100 text-green-800',
                                        'Good' => 'bg-blue-100 text-blue-800',
                                        'Fair' => 'bg-yellow-100 text-yellow-800'
                                    ];
                                    $badge_class = $condition_colors[$item['condition']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 py-1 rounded text-xs font-semibold <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($item['condition']); ?>
                                </span>
                            </div>
                        </a>

                        <!-- Equipment Info -->
                        <div class="p-4">
                            <a href="/public/product.php?id=<?php echo $item['equipment_id']; ?>" class="block hover:text-blue-600">
                                <h3 class="font-semibold text-gray-900 truncate mb-1">
                                    <?php echo htmlspecialchars($item['equipment_name']); ?>
                                </h3>
                                <p class="text-sm text-gray-600 mb-3">
                                    <?php echo htmlspecialchars($item['brand']); ?>
                                    <?php if ($item['model_number']): ?>
                                        - <?php echo htmlspecialchars($item['model_number']); ?>
                                    <?php endif; ?>
                                </p>
                            </a>

                            <!-- Pricing -->
                            <p class="text-lg font-bold text-blue-600 mb-3">
                                ₨ <?php echo number_format($item['daily_rate_lkr'], 0, '.', ','); ?> LKR/day
                            </p>

                            <!-- Gated Shop Info -->
                            <?php if ($is_logged_in): ?>
                                <!-- LOGGED IN: Show full shop details -->
                                <div class="mb-4 pb-4 border-t">
                                    <p class="text-sm text-gray-600 mb-1">From: <strong><?php echo htmlspecialchars($item['shop_name']); ?></strong></p>
                                    <?php if ($item['city_name']): ?>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($item['city_name']); ?></p>
                                    <?php endif; ?>
                                </div>

                                <!-- Rent Now Button -->
                                <a 
                                    href="/public/product.php?id=<?php echo $item['equipment_id']; ?>"
                                    class="w-full block text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition"
                                >
                                    Rent Now
                                </a>
                            <?php else: ?>
                                <!-- NOT LOGGED IN: Show gated shop info -->
                                <div class="gated-content mb-4">
                                    <!-- Blurred Content -->
                                    <div class="blur-filter border-t pt-4">
                                        <p class="text-sm text-gray-600 mb-1">From: <strong>████████ ████</strong></p>
                                        <p class="text-xs text-gray-500">██████████</p>
                                    </div>

                                    <!-- Login Overlay (smaller) -->
                                    <div class="login-overlay">
                                        <div class="text-center">
                                            <svg class="w-8 h-8 mx-auto mb-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                            <p class="text-xs text-gray-600 font-medium">Login to View Shop</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Login to Rent Button -->
                                <button 
                                    onclick="AuthManager.redirectToLogin('<?php echo urlencode($_SERVER['REQUEST_URI']); ?>')"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition"
                                >
                                    Login to Rent
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center gap-2 mt-8">
                    <?php if ($page > 1): ?>
                        <a 
                            href="?q=<?php echo urlencode($search_term); ?>&city=<?php echo urlencode($city); ?>&date=<?php echo urlencode($rental_date); ?>&page=<?php echo $page - 1; ?>"
                            class="px-4 py-2 rounded-lg bg-white border border-gray-300 hover:bg-gray-50 text-gray-900 font-semibold transition"
                        >
                            ← Previous
                        </a>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a 
                                href="?q=<?php echo urlencode($search_term); ?>&city=<?php echo urlencode($city); ?>&date=<?php echo urlencode($rental_date); ?>&page=<?php echo $i; ?>"
                                class="px-4 py-2 rounded-lg bg-white border border-gray-300 hover:bg-gray-50 text-gray-900 font-semibold transition"
                            >
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a 
                            href="?q=<?php echo urlencode($search_term); ?>&city=<?php echo urlencode($city); ?>&date=<?php echo urlencode($rental_date); ?>&page=<?php echo $page + 1; ?>"
                            class="px-4 py-2 rounded-lg bg-white border border-gray-300 hover:bg-gray-50 text-gray-900 font-semibold transition"
                        >
                            Next →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-bold mb-4">LankanLens</h3>
                    <p class="text-gray-400">Camera rental aggregator for Sri Lanka</p>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Quick Links</h3>
                    <ul class="text-gray-400 space-y-2">
                        <li><a href="/index.php" class="hover:text-white">Home</a></li>
                        <li><a href="/public/about.php" class="hover:text-white">About</a></li>
                        <li><a href="/public/contact.php" class="hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Legal</h3>
                    <ul class="text-gray-400 space-y-2">
                        <li><a href="#" class="hover:text-white">Terms & Conditions</a></li>
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2026 LankanLens. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Authentication Scripts -->
    <script src="/assets/js/auth.js"></script>
    <script>
        // Initialize gated content handlers on page load
        document.addEventListener('DOMContentLoaded', function() {
            AuthManager.initialize();
        });
    </script>
</body>
</html>
