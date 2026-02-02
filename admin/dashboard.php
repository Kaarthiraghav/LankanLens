<?php
/**
 * Admin Dashboard
 * System overview with key metrics and quick actions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/audit_logger.php';

requireAdmin();

$db = new Database();
$admin_id = getCurrentUserId();

// ============ FETCH SYSTEM METRICS ============

// User statistics
$user_stats = $db->fetchOne(
    "SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'customer') as total_customers,
        (SELECT COUNT(*) FROM users WHERE role = 'vendor') as total_vendors,
        (SELECT COUNT(*) FROM users WHERE role = 'admin') as total_admins,
        (SELECT COUNT(*) FROM users WHERE role = 'vendor' AND status = 'pending') as pending_vendors,
        COUNT(*) as total_users
     FROM users"
);

// Shop statistics
$shop_stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_shops,
        (SELECT COUNT(*) FROM shops WHERE is_active = TRUE) as active_shops,
        (SELECT COUNT(*) FROM shops WHERE shop_name = 'Master Catalog') as master_catalog_exists
     FROM shops"
);

// Equipment statistics
$equipment_stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_equipment,
        (SELECT COUNT(*) FROM equipment WHERE shop_id = (SELECT shop_id FROM shops WHERE shop_name = 'Master Catalog' LIMIT 1)) as master_equipment,
        (SELECT COUNT(DISTINCT shop_id) FROM equipment WHERE shop_id != (SELECT shop_id FROM shops WHERE shop_name = 'Master Catalog' LIMIT 1)) as vendor_shops_with_inventory
     FROM equipment"
);

// Inventory statistics
$inventory_stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_listings,
        SUM(available_quantity) as total_available,
        ROUND(AVG(daily_rate_lkr), 2) as avg_daily_rate
     FROM inventory"
);

// Booking statistics
$booking_stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_requests,
        (SELECT COUNT(*) FROM booking_requests WHERE request_status = 'pending') as pending_requests,
        (SELECT COUNT(*) FROM booking_requests WHERE request_status = 'confirmed') as confirmed_requests,
        (SELECT COUNT(*) FROM booking_requests WHERE request_status = 'completed') as completed_requests
     FROM booking_requests"
);

// Recent bookings
$recent_bookings = $db->fetchAll(
    "SELECT br.request_id, br.user_name, br.user_contact, e.equipment_name, e.brand, e.model_number, br.request_status, br.created_at, s.shop_name
     FROM booking_requests br
     LEFT JOIN equipment e ON e.equipment_id = br.equipment_id
     LEFT JOIN shops s ON s.shop_id = br.shop_id
     ORDER BY br.created_at DESC
     LIMIT 10"
);

// Recent catalog changes
$recent_audit_logs = getAuditLogs($db, 8);

// Categories breakdown
$category_stats = $db->fetchAll(
    "SELECT c.category_name, COUNT(e.equipment_id) as equipment_count
     FROM equipment_categories c
     LEFT JOIN equipment e ON e.category_id = c.category_id
     GROUP BY c.category_id, c.category_name
     ORDER BY equipment_count DESC
     LIMIT 5"
);

$page_title = 'Admin Dashboard - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
            <p class="mt-2 text-gray-600">System overview and quick actions</p>
        </div>

        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Users Card -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo (int)$user_stats['total_users']; ?></p>
                    </div>
                    <div class="text-4xl text-blue-100">👥</div>
                </div>
                <div class="mt-4 text-xs text-gray-600 space-y-1">
                    <p>Customers: <span class="font-semibold"><?php echo (int)$user_stats['total_customers']; ?></span></p>
                    <p>Vendors: <span class="font-semibold"><?php echo (int)$user_stats['total_vendors']; ?></span></p>
                    <p>Admins: <span class="font-semibold"><?php echo (int)$user_stats['total_admins']; ?></span></p>
                </div>
            </div>

            <!-- Pending Vendors Card -->
            <div class="bg-white rounded-xl border border-orange-200 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                        <p class="text-3xl font-bold text-orange-600 mt-2"><?php echo (int)$user_stats['pending_vendors']; ?></p>
                    </div>
                    <div class="text-4xl">⏳</div>
                </div>
                <a href="<?php echo BASE_URL; ?>admin/vendor-approvals.php" class="mt-4 inline-block text-sm text-orange-600 hover:text-orange-700 font-medium">
                    Review Vendors →
                </a>
            </div>

            <!-- Total Equipment Card -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Equipment Listings</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo (int)$equipment_stats['total_equipment']; ?></p>
                    </div>
                    <div class="text-4xl">📷</div>
                </div>
                <div class="mt-4 text-xs text-gray-600 space-y-1">
                    <p>Master: <span class="font-semibold"><?php echo (int)$equipment_stats['master_equipment']; ?></span></p>
                    <p>Vendors: <span class="font-semibold"><?php echo (int)$equipment_stats['vendor_shops_with_inventory']; ?></span> shops</p>
                </div>
            </div>

            <!-- Booking Requests Card -->
            <div class="bg-white rounded-xl border border-green-200 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Booking Requests</p>
                        <p class="text-3xl font-bold text-green-600 mt-2"><?php echo (int)$booking_stats['total_requests']; ?></p>
                    </div>
                    <div class="text-4xl">📅</div>
                </div>
                <div class="mt-4 text-xs text-gray-600 space-y-1">
                    <p>Pending: <span class="font-semibold"><?php echo (int)$booking_stats['pending_requests']; ?></span></p>
                    <p>Confirmed: <span class="font-semibold"><?php echo (int)$booking_stats['confirmed_requests']; ?></span></p>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Shop Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <a href="<?php echo BASE_URL; ?>admin/manage-master-gear.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-sm font-medium transition">
                        → Manage Master Catalog
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/vendor-approvals.php" class="block px-4 py-2 rounded-lg bg-orange-50 text-orange-700 hover:bg-orange-100 text-sm font-medium transition">
                        → Review Pending Vendors
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/users.php" class="block px-4 py-2 rounded-lg bg-purple-50 text-purple-700 hover:bg-purple-100 text-sm font-medium transition">
                        → Manage Users
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/listings.php" class="block px-4 py-2 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-sm font-medium transition">
                        → Review Listings
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/logs.php" class="block px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm font-medium transition">
                        → View Audit Logs
                    </a>
                </div>
            </div>

            <!-- Shop Overview -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Shop Overview</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Total Shops</span>
                        <span class="font-semibold text-gray-900"><?php echo (int)$shop_stats['total_shops']; ?></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Active Shops</span>
                        <span class="font-semibold text-green-600"><?php echo (int)$shop_stats['active_shops']; ?></span>
                    </div>
                    <div class="border-t pt-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600">Master Catalog</span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo (int)$shop_stats['master_catalog_exists'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo (int)$shop_stats['master_catalog_exists'] > 0 ? '✓ Active' : '✗ Missing'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Overview -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Inventory Overview</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Total Listings</span>
                        <span class="font-semibold text-gray-900"><?php echo (int)$inventory_stats['total_listings']; ?></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Available Units</span>
                        <span class="font-semibold text-blue-600"><?php echo (int)($inventory_stats['total_available'] ?? 0); ?></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Avg Daily Rate</span>
                        <span class="font-semibold text-gray-900">LKR <?php echo number_format((float)($inventory_stats['avg_daily_rate'] ?? 0), 0); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Categories & Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Top Equipment Categories -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Top Equipment Categories</h2>
                <div class="space-y-3">
                    <?php if (!empty($category_stats)): ?>
                        <?php foreach ($category_stats as $cat): ?>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($cat['equipment_count'] / max(array_map(fn($c) => $c['equipment_count'], $category_stats)) * 100); ?>%"></div>
                                    </div>
                                    <span class="font-semibold text-gray-900 w-6 text-right"><?php echo (int)$cat['equipment_count']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">No equipment categories yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Booking Activity -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Bookings</h2>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    <?php if (!empty($recent_bookings)): ?>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <div class="text-sm border-b pb-3 last:border-b-0">
                                <div class="flex justify-between items-start mb-1">
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['user_name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['equipment_name'] ?? 'Unknown Equipment'); ?></p>
                                    </div>
                                    <span class="px-2 py-1 rounded text-xs font-medium <?php 
                                        echo match($booking['request_status']) {
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'confirmed' => 'bg-green-100 text-green-800',
                                            'completed' => 'bg-blue-100 text-blue-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                    ?>">
                                        <?php echo ucfirst($booking['request_status']); ?>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600"><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">No booking requests yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Catalog Changes Audit Log -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Recent Catalog Changes</h2>
                <a href="<?php echo BASE_URL; ?>admin/logs.php" class="text-sm text-blue-600 hover:text-blue-700">View All →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-gray-600 border-b">
                        <tr>
                            <th class="text-left py-2">Admin</th>
                            <th class="text-left py-2">Action</th>
                            <th class="text-left py-2">Equipment</th>
                            <th class="text-left py-2">Details</th>
                            <th class="text-left py-2">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php if (!empty($recent_audit_logs)): ?>
                            <?php foreach ($recent_audit_logs as $log): ?>
                                <tr>
                                    <td class="py-3 text-gray-900 font-medium"><?php echo htmlspecialchars($log['admin_name'] ?? 'Unknown'); ?></td>
                                    <td class="py-3">
                                        <span class="px-2 py-1 rounded text-xs font-medium <?php 
                                            echo match($log['action_type']) {
                                                'CATALOG_CREATE' => 'bg-green-100 text-green-800',
                                                'CATALOG_UPDATE' => 'bg-blue-100 text-blue-800',
                                                'CATALOG_DELETE' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                        ?>">
                                            <?php echo str_replace('CATALOG_', '', $log['action_type']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 text-gray-700">
                                        <?php echo htmlspecialchars($log['brand'] ?? ''); ?>
                                        <?php echo $log['model_number'] ? ' ' . htmlspecialchars($log['model_number']) : ''; ?>
                                    </td>
                                    <td class="py-3 text-gray-600 text-xs">
                                        <?php 
                                        $details = json_decode($log['action_details'], true);
                                        if ($log['action_type'] === 'CATALOG_UPDATE' && isset($details['changes'])) {
                                            $changes = $details['changes'];
                                            $change_list = array_keys($changes);
                                            echo count($change_list) . ' field(s) changed';
                                        } else {
                                            echo $log['action_type'] === 'CATALOG_DELETE' ? 'Deleted' : 'Created';
                                        }
                                        ?>
                                    </td>
                                    <td class="py-3 text-gray-600 text-xs"><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">No catalog changes yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
