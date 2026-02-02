<?php
/**
 * Search Results Page
 * Displays equipment search results in a grid layout
 */

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
include_once __DIR__ . '/../includes/auth_helper.php';

// Get query parameters from URL
$search_term = isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '';
$city = isset($_GET['city']) ? htmlspecialchars($_GET['city'], ENT_QUOTES, 'UTF-8') : '';
$rental_date = isset($_GET['date']) ? htmlspecialchars($_GET['date'], ENT_QUOTES, 'UTF-8') : '';

// Check if user is logged in for gated content
$is_logged_in = isLoggedIn();
?>

<main class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Search Summary Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Results for "<span id="search-term-display"><?php echo $search_term; ?></span>"
            </h1>
            <p class="text-gray-600 mb-4">
                Location: <span class="font-semibold text-gray-900" id="city-display"><?php echo $city; ?></span>
                <?php if ($rental_date): ?>
                    | Rental Date: <span class="font-semibold text-gray-900"><?php echo date('M d, Y', strtotime($rental_date)); ?></span>
                <?php endif; ?>
            </p>
            
            <!-- Results Count -->
            <div class="flex items-center space-x-2">
                <p class="text-lg text-gray-700">
                    <span id="results-count">0</span> items found
                </p>
                <div id="loading-spinner" class="hidden">
                    <div class="spinner w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>
        </div>

        <!-- Results Grid Container -->
        <div id="results-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Results will be populated here by JavaScript -->
            <div id="initial-loading" class="col-span-full flex justify-center items-center py-12">
                <div class="text-center">
                    <div class="spinner w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-gray-600">Loading results...</p>
                </div>
            </div>
        </div>

        <!-- Empty State Container (Hidden by default) -->
        <div id="empty-state" class="hidden">
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <!-- Camera with Question Mark SVG Icon -->
                <div class="flex justify-center mb-6">
                    <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <!-- Camera Body -->
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                        <!-- Camera Lens -->
                        <circle cx="12" cy="13" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></circle>
                        <!-- Question Mark -->
                        <g transform="translate(16, 2)">
                            <circle cx="3" cy="3" r="3" fill="currentColor" opacity="0.2"></circle>
                            <text x="3" y="4.5" text-anchor="middle" font-size="4" font-weight="bold" fill="currentColor">?</text>
                        </g>
                    </svg>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    No results found for '<span class="font-semibold"><?php echo $search_term; ?></span>' in <?php echo $city; ?>
                </h2>
                <p class="text-gray-600 mb-8">But don't worry! Here's what we recommend:</p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center flex-wrap">
                    <button data-action="search-all-cities" class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium shadow-sm hover:shadow-md">
                        🌍 Search in Nearby Cities
                    </button>
                    <button data-action="view-all-equipment" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium shadow-sm hover:shadow-md">
                        📷 View All Equipment
                    </button>
                    <button data-action="try-another-search" class="px-6 py-3 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-medium shadow-sm hover:shadow-md">
                        🔍 Try Another Search
                    </button>
                </div>
            </div>
        </div>

        <!-- Pagination Controls (Hidden by default) -->
        <div id="pagination-container" class="hidden flex justify-center items-center space-x-2 mt-12 mb-8">
            <button id="prev-page-btn" class="px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                ← Previous
            </button>
            
            <div id="page-info" class="text-gray-700 font-medium">
                Page <span id="current-page">1</span> of <span id="total-pages">1</span>
            </div>
            
            <button id="next-page-btn" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                Next →
            </button>
        </div>
    </div>
</main>

<!-- Equipment Card Template (used by JavaScript to render results) -->
<template id="equipment-card-template">
    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden card-hover">
        <!-- Equipment Image -->
        <div class="relative h-48 bg-gray-200 overflow-hidden group">
            <img 
                class="equipment-image w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" 
                alt="Equipment image"
                src=""
            >
            
            <!-- Condition Badge -->
            <div class="absolute top-3 right-3">
                <span class="condition-badge px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                    Excellent
                </span>
            </div>
            
            <!-- Availability Badge -->
            <div class="absolute bottom-3 left-3">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                    In Stock
                </span>
            </div>
        </div>

        <!-- Card Content -->
        <div class="p-4">
            <!-- Equipment Name & Brand -->
            <h3 class="equipment-name font-semibold text-gray-900 text-lg mb-1 truncate">
                Sony A7R IV
            </h3>
            <p class="equipment-brand text-sm text-gray-600 mb-3">
                Sony
            </p>

            <!-- Shop Info with Rating -->
            <div class="flex items-center gap-2 mb-3 pb-3 border-b">
                <span class="shop-rating text-yellow-500">⭐</span>
                <p class="shop-info text-sm text-gray-700">
                    <span class="shop-name font-medium">Pro Lens Rental</span>
                    <span class="shop-rating-value text-gray-600"> - 4.8/5</span>
                </p>
            </div>

            <!-- Pricing Section -->
            <div class="mb-4">
                <p class="text-xs text-gray-600 mb-1">Daily Rate</p>
                <p class="daily-rate text-2xl font-bold text-blue-600">
                    Rs 18,500
                </p>
                <p class="text-xs text-gray-500">LKR/day</p>
            </div>

            <!-- Check Availability Button -->
            <button 
                class="check-availability-btn w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200"
                data-action="check-availability"
            >
                Check Availability
            </button>
        </div>
    </div>
</template>

<!-- Hidden data attributes for JavaScript -->
<script>
    window.searchParams = {
        search_term: "<?php echo $search_term; ?>",
        city: "<?php echo $city; ?>",
        rental_date: "<?php echo $rental_date; ?>",
        is_logged_in: <?php echo json_encode($is_logged_in); ?>,
        base_url: "<?php echo BASE_URL; ?>"
    };
</script>

<!-- Results JavaScript (will be created next) -->
<script src="<?php echo BASE_URL; ?>assets/js/results.js"></script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="<?php echo BASE_URL; ?>public/index.php" class="text-xl font-bold text-blue-600">LankanLens</a>
                <div class="flex gap-4">
                    <?php if ($is_logged_in): ?>
                        <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <a href="<?php echo BASE_URL; ?>public/logout.php" class="text-blue-600 hover:text-blue-800">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>public/login.php" class="text-blue-600 hover:text-blue-800">Login</a>
                        <a href="<?php echo BASE_URL; ?>public/register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Sign Up</a>
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
                <a href="<?php echo BASE_URL; ?>public/index.php" class="inline-block bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-6 rounded-lg transition">
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
                    <a href="<?php echo BASE_URL; ?>public/index.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                        Try Another Search
                    </a>
                    <a href="<?php echo BASE_URL; ?>public/index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-900 font-semibold py-2 px-6 rounded-lg transition">
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
                        <a href="<?php echo BASE_URL; ?>public/product.php?id=<?php echo $item['equipment_id']; ?>" class="block relative h-48 bg-gray-200 overflow-hidden">
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
                            <a href="<?php echo BASE_URL; ?>public/product.php?id=<?php echo $item['equipment_id']; ?>" class="block hover:text-blue-600">
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
                                    href="<?php echo BASE_URL; ?>public/product.php?id=<?php echo $item['equipment_id']; ?>"
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
                            href="<?php echo BASE_URL; ?>public/results.php?q=<?php echo urlencode($search_term); ?>&city=<?php echo urlencode($city); ?>&date=<?php echo urlencode($rental_date); ?>&page=<?php echo $page - 1; ?>"
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
                                href="<?php echo BASE_URL; ?>public/results.php?q=<?php echo urlencode($search_term); ?>&city=<?php echo urlencode($city); ?>&date=<?php echo urlencode($rental_date); ?>&page=<?php echo $i; ?>"
                                class="px-4 py-2 rounded-lg bg-white border border-gray-300 hover:bg-gray-50 text-gray-900 font-semibold transition"
                            >
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a 
                            href="<?php echo BASE_URL; ?>public/results.php?q=<?php echo urlencode($search_term); ?>&city=<?php echo urlencode($city); ?>&date=<?php echo urlencode($rental_date); ?>&page=<?php echo $page + 1; ?>"
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
                        <li><a href="<?php echo BASE_URL; ?>public/index.php" class="hover:text-white">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>public/about.php" class="hover:text-white">About</a></li>
                        <li><a href="<?php echo BASE_URL; ?>public/contact.php" class="hover:text-white">Contact</a></li>
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
    <script src="<?php echo BASE_URL; ?>assets/js/auth.js"></script>
    <script>
        // Initialize gated content handlers on page load
        document.addEventListener('DOMContentLoaded', function() {
            AuthManager.initialize();
        });
    </script>
</body>
</html>
