<?php
/**
 * Vendor Dashboard
 * Main dashboard for active vendors to manage equipment, view stats, and handle inquiries
 */

// Include configuration and authentication
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Protect route - require active vendor
requireActiveVendor();

// Get current vendor information
$vendor_id = getCurrentUserId();
$vendor_name = getCurrentUserName();

// Initialize database connection
$db = new Database();

// Get vendor's shop_id
// Note: This assumes we'll link users.shop_name with shops.shop_name
// Alternative: Add shop_id column to users table in future schema update
$shop_query = "SELECT s.shop_id, s.shop_name, s.primary_city, s.shop_phone, s.average_rating, s.total_reviews
               FROM shops s
               INNER JOIN users u ON s.shop_name = u.shop_name
               WHERE u.user_id = ?
               LIMIT 1";

$shop_stmt = $db->query($shop_query, [$vendor_id]);
$shop = $shop_stmt->fetch(PDO::FETCH_ASSOC);

// If no shop found, redirect to setup page (future feature)
if (!$shop) {
    // For now, show error message
    $error_message = "No shop associated with your account. Please contact admin.";
    $shop_id = null;
} else {
    $shop_id = $shop['shop_id'];
}

// Initialize stats variables
$total_listings = 0;
$total_views = 0;
$total_inquiries = 0;
$active_listings = 0;
$unavailable_listings = 0;
$recent_inquiries = [];

// Get statistics if shop exists
if ($shop_id) {
    // 1. Total Listings - Count all equipment for this shop
    $listings_query = "SELECT COUNT(*) as total FROM equipment WHERE shop_id = ?";
    $listings_stmt = $db->query($listings_query, [$shop_id]);
    $total_listings = $listings_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 2. Total Views - Sum of view_count from equipment
    // Note: view_count column doesn't exist in current schema, using 0 as placeholder
    // TODO: Add view_count column to equipment table
    $total_views = 0; // Placeholder until view tracking is implemented
    
    // 3. Total Inquiries - Count booking requests for this shop
    $inquiries_query = "SELECT COUNT(*) as total FROM booking_requests WHERE shop_id = ?";
    $inquiries_stmt = $db->query($inquiries_query, [$shop_id]);
    $total_inquiries = $inquiries_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 4. Active vs Unavailable Listings
    $active_query = "SELECT 
                        SUM(CASE WHEN i.available_quantity > 0 THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN i.available_quantity = 0 THEN 1 ELSE 0 END) as unavailable
                     FROM equipment e
                     INNER JOIN inventory i ON e.equipment_id = i.equipment_id
                     WHERE e.shop_id = ?";
    $active_stmt = $db->query($active_query, [$shop_id]);
    $active_data = $active_stmt->fetch(PDO::FETCH_ASSOC);
    $active_listings = (int)$active_data['active'];
    $unavailable_listings = (int)$active_data['unavailable'];
    
    // 5. Recent Inquiries - Last 5 booking requests
    $recent_query = "SELECT 
                        br.request_id,
                        br.user_name,
                        br.user_contact,
                        br.rental_duration_days,
                        br.estimated_total_lkr,
                        br.request_status,
                        br.created_at,
                        e.equipment_name,
                        e.brand
                     FROM booking_requests br
                     INNER JOIN equipment e ON br.equipment_id = e.equipment_id
                     WHERE br.shop_id = ?
                     ORDER BY br.created_at DESC
                     LIMIT 5";
    $recent_stmt = $db->query($recent_query, [$shop_id]);
    $recent_inquiries = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Set page title
$page_title = "Vendor Dashboard - " . APP_NAME;

// Include header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Dashboard Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Welcome Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Welcome, <?php echo htmlspecialchars($vendor_name); ?>!</h1>
            <?php if ($shop): ?>
                <p class="mt-2 text-gray-600">
                    Managing: <span class="font-semibold"><?php echo htmlspecialchars($shop['shop_name']); ?></span>
                    <span class="mx-2">•</span>
                    <?php echo htmlspecialchars($shop['primary_city']); ?>
                    <?php if ($shop['average_rating'] > 0): ?>
                        <span class="mx-2">•</span>
                        <span class="text-yellow-600">⭐ <?php echo number_format($shop['average_rating'], 1); ?>/5</span>
                        <span class="text-gray-500">(<?php echo $shop['total_reviews']; ?> reviews)</span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if (isset($error_message)): ?>
            <!-- Error Message -->
            <div class="mb-8 bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            </div>
        <?php else: ?>

        <!-- Quick Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Total Listings Card -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Listings</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($total_listings); ?></p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-green-600 text-sm font-medium"><?php echo $active_listings; ?> Active</span>
                    <span class="text-gray-400 mx-2">•</span>
                    <span class="text-red-600 text-sm font-medium"><?php echo $unavailable_listings; ?> Unavailable</span>
                </div>
            </div>

            <!-- Total Views Card -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Views</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($total_views); ?></p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-gray-500 text-xs mt-4">Coming soon: View tracking</p>
            </div>

            <!-- Total Inquiries Card -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Inquiries</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($total_inquiries); ?></p>
                    </div>
                    <div class="bg-purple-100 rounded-full p-3">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                </div>
                <?php
                $pending_count = 0;
                foreach ($recent_inquiries as $inquiry) {
                    if ($inquiry['request_status'] === 'pending') $pending_count++;
                }
                ?>
                <?php if ($pending_count > 0): ?>
                    <p class="text-orange-600 text-sm font-medium mt-4"><?php echo $pending_count; ?> Pending Response</p>
                <?php else: ?>
                    <p class="text-gray-500 text-xs mt-4">All inquiries handled</p>
                <?php endif; ?>
            </div>

            <!-- Quick Action Card -->
            <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg shadow-md p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-lg">Quick Actions</h3>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <p class="text-blue-100 text-sm">Access vendor tools and features quickly</p>
            </div>

        </div>

        <!-- Action Buttons Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            
            <!-- Add New Equipment Button -->
            <a href="<?php echo BASE_URL; ?>/vendor/add-equipment.php" 
               class="bg-white hover:bg-blue-50 border-2 border-blue-600 text-blue-700 font-semibold py-4 px-6 rounded-lg shadow-md transition duration-200 flex items-center justify-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add New Equipment
            </a>

            <!-- Manage Listings Button -->
            <a href="<?php echo BASE_URL; ?>/vendor/my-listings.php" 
               class="bg-white hover:bg-gray-50 border-2 border-gray-300 text-gray-700 font-semibold py-4 px-6 rounded-lg shadow-md transition duration-200 flex items-center justify-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
                Manage Listings
            </a>

            <!-- View Inquiries Button -->
            <a href="<?php echo BASE_URL; ?>/vendor/inquiries.php" 
               class="bg-white hover:bg-gray-50 border-2 border-gray-300 text-gray-700 font-semibold py-4 px-6 rounded-lg shadow-md transition duration-200 flex items-center justify-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                View Inquiries
                <?php if ($pending_count > 0): ?>
                    <span class="bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>

            <!-- Analytics Button -->
            <a href="<?php echo BASE_URL; ?>/vendor/analytics.php" 
               class="bg-white hover:bg-gray-50 border-2 border-gray-300 text-gray-700 font-semibold py-4 px-6 rounded-lg shadow-md transition duration-200 flex items-center justify-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Analytics
            </a>

        </div>

        <!-- Recent Inquiries Section -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Recent Inquiries</h2>
                <p class="text-gray-600 text-sm mt-1">Latest booking requests from customers</p>
            </div>

            <?php if (empty($recent_inquiries)): ?>
                <!-- Empty State -->
                <div class="px-6 py-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No inquiries yet</h3>
                    <p class="text-gray-500">When customers request to rent your equipment, their inquiries will appear here.</p>
                </div>
            <?php else: ?>
                <!-- Inquiries Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Est. Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recent_inquiries as $inquiry): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inquiry['user_name']); ?></div>
                                        <?php if ($inquiry['user_contact']): ?>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inquiry['user_contact']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inquiry['equipment_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inquiry['brand']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $inquiry['rental_duration_days']; ?> day<?php echo $inquiry['rental_duration_days'] > 1 ? 's' : ''; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        Rs <?php echo number_format($inquiry['estimated_total_lkr'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_colors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'confirmed' => 'bg-blue-100 text-blue-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        $status_color = $status_colors[$inquiry['request_status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                            <?php echo ucfirst($inquiry['request_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($inquiry['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- View All Link -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-center">
                    <a href="<?php echo BASE_URL; ?>/vendor/inquiries.php" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                        View All Inquiries →
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php endif; // End if not error ?>

    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../includes/footer.php';
?>
