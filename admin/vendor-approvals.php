<?php
/**
 * Admin Vendor Approvals
 * Review and approve/reject pending vendor applications
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

// ============ HANDLE APPROVAL/REJECTION ============

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $reason = trim($_POST['reason'] ?? '');

    if ($user_id <= 0) {
        $errors[] = 'Invalid user ID.';
    } else {
        // Verify the user is a pending vendor
        $user = $db->fetchOne(
            "SELECT user_id, full_name, email, shop_name, role, status FROM users WHERE user_id = ? AND role = 'vendor'",
            [$user_id]
        );

        if (!$user) {
            $errors[] = 'Vendor not found.';
        } else {
            if ($action === 'approve') {
                if ($user['status'] === 'active') {
                    $errors[] = 'This vendor is already approved.';
                } else if ($user['status'] !== 'pending') {
                    $errors[] = 'Only pending vendors can be approved.';
                } else {
                    try {
                        // Approve vendor
                        $db->query(
                            "UPDATE users SET status = 'active', approved_by = ? WHERE user_id = ?",
                            [$admin_id, $user_id]
                        );

                        // Create shop if not exists
                        $shop = $db->fetchOne(
                            "SELECT shop_id FROM shops WHERE shop_name = ? LIMIT 1",
                            [$user['shop_name']]
                        );

                        if (!$shop) {
                            $db->query(
                                "INSERT INTO shops (shop_name, shop_description, primary_city, shop_phone, whatsapp_number, email, is_active)
                                 VALUES (?, ?, ?, ?, ?, ?, 1)",
                                [
                                    $user['shop_name'],
                                    'Vendor shop for ' . $user['full_name'],
                                    'Colombo', // Default city
                                    $user['phone'] ?? '0000000000',
                                    $user['whatsapp_number'] ?? $user['phone'] ?? '0000000000',
                                    $user['email']
                                ]
                            );
                        }

                        // Log the approval
                        logAdminAction($db, $admin_id, 'VENDOR_APPROVE', $user_id, null, [
                            'vendor_name' => $user['full_name'],
                            'shop_name' => $user['shop_name'],
                            'email' => $user['email'],
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);

                        $success = "Vendor {$user['full_name']} has been approved and activated.";
                    } catch (Exception $e) {
                        $errors[] = 'Error approving vendor: ' . $e->getMessage();
                    }
                }
            } else if ($action === 'reject') {
                if (!in_array($user['status'], ['pending', 'active'])) {
                    $errors[] = 'This vendor cannot be rejected.';
                } else {
                    try {
                        // Reject vendor
                        $db->query(
                            "UPDATE users SET status = 'rejected', approved_by = ? WHERE user_id = ?",
                            [$admin_id, $user_id]
                        );

                        // Log the rejection
                        logAdminAction($db, $admin_id, 'VENDOR_REJECT', $user_id, null, [
                            'vendor_name' => $user['full_name'],
                            'shop_name' => $user['shop_name'],
                            'email' => $user['email'],
                            'reason' => $reason ?: 'No reason provided',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);

                        $success = "Vendor {$user['full_name']} has been rejected.";
                    } catch (Exception $e) {
                        $errors[] = 'Error rejecting vendor: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// ============ FETCH VENDORS BY STATUS ============

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$status_filter = in_array($status_filter, ['pending', 'active', 'rejected', 'all']) ? $status_filter : 'pending';

$where_clause = "";
$params = [];

if ($status_filter !== 'all') {
    $where_clause = "AND u.status = ?";
    $params[] = $status_filter;
}

$vendors = $db->fetchAll(
    "SELECT 
        u.user_id, 
        u.full_name, 
        u.email, 
        u.phone, 
        u.whatsapp_number, 
        u.shop_name, 
        u.status, 
        u.created_at,
        u.approved_by,
        COALESCE(admin.full_name, 'N/A') as approved_by_name,
        (SELECT COUNT(*) FROM equipment e LEFT JOIN shops s ON s.shop_id = e.shop_id WHERE s.shop_name = u.shop_name) as equipment_count
     FROM users u
     LEFT JOIN users admin ON admin.user_id = u.approved_by
     WHERE u.role = 'vendor' $where_clause
     ORDER BY 
        CASE WHEN u.status = 'pending' THEN 0 ELSE 1 END,
        u.created_at DESC",
    $params
);

// Get status summary
$status_summary = $db->fetchOne(
    "SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'vendor' AND status = 'pending') as pending,
        (SELECT COUNT(*) FROM users WHERE role = 'vendor' AND status = 'active') as active,
        (SELECT COUNT(*) FROM users WHERE role = 'vendor' AND status = 'rejected') as rejected,
        (SELECT COUNT(*) FROM users WHERE role = 'vendor') as total
     FROM users LIMIT 1"
);

$page_title = 'Vendor Approvals - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Vendor Approvals</h1>
            <p class="mt-2 text-gray-600">Review and approve pending vendor applications</p>
        </div>

        <!-- Status Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <a href="<?php echo BASE_URL; ?>admin/vendor-approvals.php?status=pending" 
               class="block p-4 rounded-lg border-2 transition <?php echo $status_filter === 'pending' ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-gray-300'; ?>">
                <p class="text-sm font-medium text-gray-600">Pending Review</p>
                <p class="text-3xl font-bold text-orange-600 mt-1"><?php echo (int)$status_summary['pending']; ?></p>
            </a>

            <a href="<?php echo BASE_URL; ?>admin/vendor-approvals.php?status=active" 
               class="block p-4 rounded-lg border-2 transition <?php echo $status_filter === 'active' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'; ?>">
                <p class="text-sm font-medium text-gray-600">Approved</p>
                <p class="text-3xl font-bold text-green-600 mt-1"><?php echo (int)$status_summary['active']; ?></p>
            </a>

            <a href="<?php echo BASE_URL; ?>admin/vendor-approvals.php?status=rejected" 
               class="block p-4 rounded-lg border-2 transition <?php echo $status_filter === 'rejected' ? 'border-red-500 bg-red-50' : 'border-gray-200 hover:border-gray-300'; ?>">
                <p class="text-sm font-medium text-gray-600">Rejected</p>
                <p class="text-3xl font-bold text-red-600 mt-1"><?php echo (int)$status_summary['rejected']; ?></p>
            </a>

            <a href="<?php echo BASE_URL; ?>admin/vendor-approvals.php?status=all" 
               class="block p-4 rounded-lg border-2 transition <?php echo $status_filter === 'all' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'; ?>">
                <p class="text-sm font-medium text-gray-600">Total Vendors</p>
                <p class="text-3xl font-bold text-blue-600 mt-1"><?php echo (int)$status_summary['total']; ?></p>
            </a>
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

        <!-- Vendors List -->
        <div class="space-y-4">
            <?php if (empty($vendors)): ?>
                <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
                    <p class="text-gray-600">No vendors found for this status.</p>
                </div>
            <?php else: ?>
                <?php foreach ($vendors as $vendor): ?>
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Vendor Info -->
                            <div>
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($vendor['full_name']); ?></h3>
                                        <p class="text-sm text-gray-600 mt-1">Shop: <span class="font-medium"><?php echo htmlspecialchars($vendor['shop_name']); ?></span></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php 
                                        echo match($vendor['status']) {
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'active' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                    ?>">
                                        <?php echo ucfirst($vendor['status']); ?>
                                    </span>
                                </div>

                                <div class="space-y-2 text-sm text-gray-600">
                                    <p>
                                        <span class="text-gray-500">Email:</span> 
                                        <a href="mailto:<?php echo htmlspecialchars($vendor['email']); ?>" class="text-blue-600 hover:text-blue-700">
                                            <?php echo htmlspecialchars($vendor['email']); ?>
                                        </a>
                                    </p>
                                    <p>
                                        <span class="text-gray-500">Phone:</span> 
                                        <a href="tel:<?php echo htmlspecialchars($vendor['phone'] ?? ''); ?>" class="text-blue-600 hover:text-blue-700">
                                            <?php echo htmlspecialchars($vendor['phone'] ?? 'Not provided'); ?>
                                        </a>
                                    </p>
                                    <p>
                                        <span class="text-gray-500">WhatsApp:</span> 
                                        <?php echo htmlspecialchars($vendor['whatsapp_number'] ?? 'Not provided'); ?>
                                    </p>
                                    <p>
                                        <span class="text-gray-500">Applied:</span> 
                                        <?php echo date('M d, Y H:i', strtotime($vendor['created_at'])); ?>
                                    </p>
                                    <?php if ($vendor['status'] !== 'pending'): ?>
                                        <p>
                                            <span class="text-gray-500">Reviewed by:</span> 
                                            <?php echo htmlspecialchars($vendor['approved_by_name']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Actions & Status -->
                            <div class="flex flex-col justify-between">
                                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                    <p class="text-sm text-gray-600 mb-2">Equipment Listings</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo (int)$vendor['equipment_count']; ?></p>
                                </div>

                                <!-- Action Buttons -->
                                <?php if ($vendor['status'] === 'pending'): ?>
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <!-- Approve Modal Trigger -->
                                        <button type="button" 
                                                onclick="openApproveModal(<?php echo (int)$vendor['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($vendor['full_name'])); ?>')"
                                                class="flex-1 px-4 py-2 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700 transition">
                                            Approve
                                        </button>

                                        <!-- Reject Modal Trigger -->
                                        <button type="button" 
                                                onclick="openRejectModal(<?php echo (int)$vendor['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($vendor['full_name'])); ?>')"
                                                class="flex-1 px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition">
                                            Reject
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-2 text-sm text-gray-500">
                                        <?php echo ucfirst($vendor['status']) === 'Active' ? '✓ Approved' : '✗ ' . ucfirst($vendor['status']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div id="approve-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Approve Vendor?</h2>
        <p class="text-gray-600 mb-4">
            Are you sure you want to approve <span id="approve-name" class="font-semibold"></span>? They will be able to start listing equipment immediately.
        </p>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" id="approve-user-id" name="user_id" value="">
            
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('approve-modal')" class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700">
                    Approve
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Reject Vendor?</h2>
        <p class="text-gray-600 mb-4">
            Are you sure you want to reject <span id="reject-name" class="font-semibold"></span>?
        </p>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" id="reject-user-id" name="user_id" value="">
            
            <div>
                <label for="reject-reason" class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                <textarea id="reject-reason" name="reason" rows="3" 
                         placeholder="e.g., Incomplete application, missing documentation"
                         class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('reject-modal')" class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700">
                    Reject
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openApproveModal(userId, vendorName) {
    document.getElementById('approve-user-id').value = userId;
    document.getElementById('approve-name').textContent = vendorName;
    document.getElementById('approve-modal').classList.remove('hidden');
}

function openRejectModal(userId, vendorName) {
    document.getElementById('reject-user-id').value = userId;
    document.getElementById('reject-name').textContent = vendorName;
    document.getElementById('reject-reason').value = '';
    document.getElementById('reject-modal').classList.remove('hidden');
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
