<?php
/**
 * Admin User Management
 * View, edit, and manage all users (customers, vendors, admins)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/audit_logger.php';

requireAdmin();

$db = new Database();
$admin_id = getCurrentUserId();

$errors = [];
$success = '';
$editing_user = null;

// ============ HANDLE USER ACTIONS ============

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if ($user_id <= 0 || $user_id === $admin_id) {
        $errors[] = 'Invalid user or cannot modify yourself.';
    } else {
        $user = $db->fetchOne("SELECT user_id, full_name, email, role, status FROM users WHERE user_id = ?", [$user_id]);

        if (!$user) {
            $errors[] = 'User not found.';
        } else {
            if ($action === 'suspend') {
                if ($user['status'] === 'suspended') {
                    $errors[] = 'User is already suspended.';
                } else {
                    try {
                        $db->query("UPDATE users SET status = 'suspended' WHERE user_id = ?", [$user_id]);
                        logAdminAction($db, $admin_id, 'USER_SUSPEND', $user_id, null, [
                            'user_name' => $user['full_name'],
                            'email' => $user['email'],
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                        $success = "User {$user['full_name']} has been suspended.";
                    } catch (Exception $e) {
                        $errors[] = 'Error suspending user: ' . $e->getMessage();
                    }
                }
            } else if ($action === 'activate') {
                if ($user['status'] === 'active') {
                    $errors[] = 'User is already active.';
                } else {
                    try {
                        $db->query("UPDATE users SET status = 'active' WHERE user_id = ?", [$user_id]);
                        logAdminAction($db, $admin_id, 'USER_ACTIVATE', $user_id, null, [
                            'user_name' => $user['full_name'],
                            'email' => $user['email'],
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                        $success = "User {$user['full_name']} has been activated.";
                    } catch (Exception $e) {
                        $errors[] = 'Error activating user: ' . $e->getMessage();
                    }
                }
            } else if ($action === 'delete') {
                try {
                    $db->query("DELETE FROM users WHERE user_id = ?", [$user_id]);
                    logAdminAction($db, $admin_id, 'USER_DELETE', $user_id, null, [
                        'user_name' => $user['full_name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    $success = "User {$user['full_name']} has been deleted.";
                } catch (Exception $e) {
                    $errors[] = 'Error deleting user: ' . $e->getMessage();
                }
            }
        }
    }
}

// ============ FETCH USERS ============

$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';
$role_filter = in_array($role_filter, ['customer', 'vendor', 'admin', 'all']) ? $role_filter : 'all';

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$status_filter = in_array($status_filter, ['active', 'pending', 'suspended', 'rejected', 'all']) ? $status_filter : 'all';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_clauses = [];
$params = [];

if ($role_filter !== 'all') {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== 'all') {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(full_name LIKE ? OR email LIKE ? OR shop_name LIKE ?)";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param);
}

$where = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$users = $db->fetchAll(
    "SELECT user_id, full_name, email, phone, role, status, shop_name, created_at, last_login_at 
     FROM users $where
     ORDER BY created_at DESC",
    $params
);

// Get user statistics
$user_stats = $db->fetchOne(
    "SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'customer') as customers,
        (SELECT COUNT(*) FROM users WHERE role = 'vendor') as vendors,
        (SELECT COUNT(*) FROM users WHERE role = 'admin') as admins,
        (SELECT COUNT(*) FROM users WHERE status = 'active') as active,
        (SELECT COUNT(*) FROM users WHERE status = 'suspended') as suspended,
        (SELECT COUNT(*) FROM users WHERE status = 'pending') as pending,
        COUNT(*) as total
     FROM users"
);

$page_title = 'User Management - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
            <p class="mt-2 text-gray-600">View and manage all system users</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Total Users</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo (int)$user_stats['total']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Customers</p>
                <p class="text-2xl font-bold text-blue-600 mt-1"><?php echo (int)$user_stats['customers']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Vendors</p>
                <p class="text-2xl font-bold text-green-600 mt-1"><?php echo (int)$user_stats['vendors']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Admins</p>
                <p class="text-2xl font-bold text-purple-600 mt-1"><?php echo (int)$user_stats['admins']; ?></p>
            </div>
        </div>

        <!-- Error & Success Messages -->
        <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Filters & Search -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-8">
            <form method="get" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Name, email, or shop..."
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <!-- Role Filter -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select id="role" name="role" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                            <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                            <option value="vendor" <?php echo $role_filter === 'vendor' ? 'selected' : ''; ?>>Vendor</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium text-sm hover:bg-blue-700">
                    Apply Filters
                </button>
                <a href="<?php echo BASE_URL; ?>admin/users.php" class="inline-block px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium text-sm hover:bg-gray-50">
                    Clear Filters
                </a>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <?php if (empty($users)): ?>
                <div class="p-8 text-center text-gray-600">
                    No users found matching the selected filters.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="text-left px-6 py-3 font-medium text-gray-700">Name</th>
                                <th class="text-left px-6 py-3 font-medium text-gray-700">Email</th>
                                <th class="text-left px-6 py-3 font-medium text-gray-700">Role</th>
                                <th class="text-left px-6 py-3 font-medium text-gray-700">Status</th>
                                <th class="text-left px-6 py-3 font-medium text-gray-700">Last Login</th>
                                <th class="text-left px-6 py-3 font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                            <?php if ($user['shop_name']): ?>
                                                <p class="text-xs text-gray-600"><?php echo htmlspecialchars($user['shop_name']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="text-blue-600 hover:text-blue-700">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium <?php 
                                            echo match($user['role']) {
                                                'admin' => 'bg-purple-100 text-purple-800',
                                                'vendor' => 'bg-green-100 text-green-800',
                                                'customer' => 'bg-blue-100 text-blue-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                        ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium <?php 
                                            echo match($user['status']) {
                                                'active' => 'bg-green-100 text-green-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'suspended' => 'bg-red-100 text-red-800',
                                                'rejected' => 'bg-orange-100 text-orange-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                        ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        <?php echo $user['last_login_at'] ? date('M d, H:i', strtotime($user['last_login_at'])) : 'Never'; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex gap-2">
                                            <?php if ($user['status'] === 'active'): ?>
                                                <button type="button" 
                                                        onclick="openSuspendModal(<?php echo (int)$user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>')"
                                                        class="text-orange-600 hover:text-orange-700 text-xs font-medium">
                                                    Suspend
                                                </button>
                                            <?php elseif ($user['status'] === 'suspended'): ?>
                                                <button type="button" 
                                                        onclick="openActivateModal(<?php echo (int)$user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>')"
                                                        class="text-green-600 hover:text-green-700 text-xs font-medium">
                                                    Activate
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" 
                                                    onclick="openDeleteModal(<?php echo (int)$user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>')"
                                                    class="text-red-600 hover:text-red-700 text-xs font-medium">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-4 text-sm text-gray-600">
            Showing <?php echo count($users); ?> of <?php echo (int)$user_stats['total']; ?> total users
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div id="suspend-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Suspend User?</h2>
        <p class="text-gray-600 mb-4">
            Are you sure you want to suspend <span id="suspend-name" class="font-semibold"></span>? They will lose access to their account.
        </p>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="suspend">
            <input type="hidden" id="suspend-user-id" name="user_id" value="">
            
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('suspend-modal')" class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700">
                    Suspend
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Activate Modal -->
<div id="activate-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Activate User?</h2>
        <p class="text-gray-600 mb-4">
            Are you sure you want to activate <span id="activate-name" class="font-semibold"></span>? They will regain access to their account.
        </p>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="activate">
            <input type="hidden" id="activate-user-id" name="user_id" value="">
            
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('activate-modal')" class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700">
                    Activate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="delete-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Delete User?</h2>
        <p class="text-gray-600 mb-4">
            Are you sure you want to permanently delete <span id="delete-name" class="font-semibold"></span>? This action cannot be undone.
        </p>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" id="delete-user-id" name="user_id" value="">
            
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('delete-modal')" class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700">
                    Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openSuspendModal(userId, userName) {
    document.getElementById('suspend-user-id').value = userId;
    document.getElementById('suspend-name').textContent = userName;
    document.getElementById('suspend-modal').classList.remove('hidden');
}

function openActivateModal(userId, userName) {
    document.getElementById('activate-user-id').value = userId;
    document.getElementById('activate-name').textContent = userName;
    document.getElementById('activate-modal').classList.remove('hidden');
}

function openDeleteModal(userId, userName) {
    document.getElementById('delete-user-id').value = userId;
    document.getElementById('delete-name').textContent = userName;
    document.getElementById('delete-modal').classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Close modals when clicking outside
document.querySelectorAll('[id$="-modal"]').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
