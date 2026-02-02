<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth_helper.php';
require_once '../includes/audit_logger.php';

// Check admin access
requireAdmin();
$admin_id = getCurrentUserId();

// Database connection
$db = new Database();

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$action_filter = isset($_GET['action']) ? $_GET['action'] : '';
$admin_filter = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$where_clauses = [];
$params = [];

if ($action_filter) {
    $where_clauses[] = "al.action_type = ?";
    $params[] = $action_filter;
}

if ($admin_filter) {
    $where_clauses[] = "al.admin_user_id = ?";
    $params[] = $admin_filter;
}

if ($search) {
    $where_clauses[] = "(u_admin.full_name LIKE ? OR al.target_user_id LIKE ? OR al.target_equipment_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($date_from) {
    $where_clauses[] = "DATE(al.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_clauses[] = "DATE(al.created_at) <= ?";
    $params[] = $date_to;
}

$where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM admin_logs al LEFT JOIN users u_admin ON al.admin_user_id = u_admin.user_id" . $where_sql;
$result = $db->query($count_query, $params);
$total_logs = $result[0]['total'] ?? 0;
$total_pages = ceil($total_logs / $limit);

// Get logs with details
$logs_query = "
    SELECT 
        al.log_id,
        al.admin_user_id,
        al.action_type,
        al.target_user_id,
        al.target_equipment_id,
        al.details,
        al.ip_address,
        al.created_at,
        u_admin.full_name as admin_name,
        u_target.full_name as target_user_name,
        eq.brand,
        eq.model_number,
        eq.equipment_name
    FROM admin_logs al
    LEFT JOIN users u_admin ON al.admin_user_id = u_admin.user_id
    LEFT JOIN users u_target ON al.target_user_id = u_target.user_id
    LEFT JOIN equipment eq ON al.target_equipment_id = eq.equipment_id
    $where_sql
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;

$logs = $db->query($logs_query, $params);

// Get all admins for filter dropdown
$admins = $db->query("SELECT user_id, full_name FROM users WHERE role = 'admin' ORDER BY full_name");

// Get all action types for filter dropdown
$action_types = [
    'CATALOG_CREATE' => 'Catalog - Create',
    'CATALOG_UPDATE' => 'Catalog - Update',
    'CATALOG_DELETE' => 'Catalog - Delete',
    'VENDOR_APPROVE' => 'Vendor - Approve',
    'VENDOR_REJECT' => 'Vendor - Reject',
    'USER_SUSPEND' => 'User - Suspend',
    'USER_ACTIVATE' => 'User - Activate',
    'USER_DELETE' => 'User - Delete',
    'LISTING_DISABLE' => 'Listing - Disable',
    'LISTING_ENABLE' => 'Listing - Enable',
    'LISTING_DELETE' => 'Listing - Delete'
];

// Action type colors and icons
$action_colors = [
    'CATALOG_CREATE' => 'blue',
    'CATALOG_UPDATE' => 'purple',
    'CATALOG_DELETE' => 'red',
    'VENDOR_APPROVE' => 'green',
    'VENDOR_REJECT' => 'red',
    'USER_SUSPEND' => 'red',
    'USER_ACTIVATE' => 'green',
    'USER_DELETE' => 'red',
    'LISTING_DISABLE' => 'yellow',
    'LISTING_ENABLE' => 'green',
    'LISTING_DELETE' => 'red'
];

$error = '';
$success = '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../includes/nav.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Audit Logs</h1>
                    <p class="text-gray-600 mt-1">Track all admin actions and system events</p>
                </div>
                <a href="/admin/dashboard.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-gray-600 text-sm font-medium">Total Logs</div>
                <div class="text-3xl font-bold text-blue-600 mt-2"><?php echo number_format($total_logs); ?></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-gray-600 text-sm font-medium">Active Admins</div>
                <div class="text-3xl font-bold text-green-600 mt-2"><?php echo count($admins); ?></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-gray-600 text-sm font-medium">Action Types</div>
                <div class="text-3xl font-bold text-purple-600 mt-2"><?php echo count($action_types); ?></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-gray-600 text-sm font-medium">Current Page</div>
                <div class="text-3xl font-bold text-gray-600 mt-2"><?php echo $page; ?> / <?php echo $total_pages ?: 1; ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search Admin/User/Equipment</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Name, ID..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Action Type</label>
                        <select name="action" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Actions</option>
                            <?php foreach ($action_types as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $action_filter === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Admin</label>
                        <select name="admin_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Admins</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?php echo $admin['user_id']; ?>" <?php echo $admin_filter == $admin['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($admin['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <a href="/admin/logs.php" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <?php if (empty($logs)): ?>
            <div class="bg-white p-12 rounded-lg shadow text-center">
                <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">No logs found matching your filters</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 border-b border-gray-300">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Timestamp</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Admin</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Action</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Target</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Details</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($logs as $log): 
                                $color_class = $action_colors[$log['action_type']] ?? 'gray';
                                $color_map = [
                                    'blue' => 'bg-blue-100 text-blue-800',
                                    'green' => 'bg-green-100 text-green-800',
                                    'red' => 'bg-red-100 text-red-800',
                                    'yellow' => 'bg-yellow-100 text-yellow-800',
                                    'purple' => 'bg-purple-100 text-purple-800',
                                    'gray' => 'bg-gray-100 text-gray-800'
                                ];
                                $badge_class = $color_map[$color_class] ?? $color_map['gray'];
                                
                                $details = json_decode($log['details'], true) ?? [];
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                        <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        <div><?php echo htmlspecialchars($log['admin_name'] ?? 'Unknown'); ?></div>
                                        <div class="text-gray-500 text-xs">ID: <?php echo $log['admin_user_id']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $badge_class; ?>">
                                            <?php echo $action_types[$log['action_type']] ?? $log['action_type']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php if ($log['target_equipment_id']): ?>
                                            <div><strong>Equipment:</strong></div>
                                            <div><?php echo htmlspecialchars($log['brand'] . ' ' . $log['model_number']); ?></div>
                                        <?php elseif ($log['target_user_id']): ?>
                                            <div><strong>User:</strong></div>
                                            <div><?php echo htmlspecialchars($log['target_user_name'] ?? 'Unknown'); ?></div>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div class="max-w-xs">
                                            <?php if (!empty($details)): ?>
                                                <div class="text-xs bg-gray-50 p-2 rounded max-h-24 overflow-y-auto font-mono">
                                                    <?php 
                                                    foreach ($details as $key => $value) {
                                                        echo "<div><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . "</div>";
                                                    }
                                                    ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400">No details</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 font-mono whitespace-nowrap">
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-8 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $total_logs); ?> of <?php echo number_format($total_logs); ?> logs
                </div>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&action=<?php echo urlencode($action_filter); ?>&admin_id=<?php echo $admin_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-chevron-left mr-1"></i>Previous
                        </a>
                    <?php else: ?>
                        <button disabled class="px-4 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed">
                            <i class="fas fa-chevron-left mr-1"></i>Previous
                        </button>
                    <?php endif; ?>

                    <div class="flex items-center gap-2">
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <a href="?page=1&action=<?php echo urlencode($action_filter); ?>&admin_id=<?php echo $admin_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                               class="px-3 py-2 rounded-lg hover:bg-gray-100">1</a>
                            <span class="text-gray-400">...</span>
                        <?php endif; 
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="px-3 py-2 bg-blue-600 text-white rounded-lg"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&action=<?php echo urlencode($action_filter); ?>&admin_id=<?php echo $admin_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                                   class="px-3 py-2 rounded-lg hover:bg-gray-100"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; 
                        
                        if ($end_page < $total_pages): ?>
                            <span class="text-gray-400">...</span>
                            <a href="?page=<?php echo $total_pages; ?>&action=<?php echo urlencode($action_filter); ?>&admin_id=<?php echo $admin_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                               class="px-3 py-2 rounded-lg hover:bg-gray-100"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                    </div>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&action=<?php echo urlencode($action_filter); ?>&admin_id=<?php echo $admin_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Next<i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php else: ?>
                        <button disabled class="px-4 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed">
                            Next<i class="fas fa-chevron-right ml-1"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
