<?php
/**
 * Admin Listings Management
 * View and moderate all vendor equipment listings
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

// ============ HANDLE LISTING ACTIONS ============

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $inventory_id = isset($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : 0;

    if ($inventory_id <= 0) {
        $errors[] = 'Invalid listing ID.';
    } else {
        $inventory = $db->fetchOne(
            "SELECT i.inventory_id, i.equipment_id, i.shop_id, e.equipment_name, e.brand, e.model_number, s.shop_name, i.daily_rate_lkr, i.available_quantity
             FROM inventory i
             LEFT JOIN equipment e ON e.equipment_id = i.equipment_id
             LEFT JOIN shops s ON s.shop_id = i.shop_id
             WHERE i.inventory_id = ?",
            [$inventory_id]
        );

        if (!$inventory) {
            $errors[] = 'Listing not found.';
        } else {
            if ($action === 'disable') {
                try {
                    $db->query(
                        "UPDATE inventory SET available_quantity = 0 WHERE inventory_id = ?",
                        [$inventory_id]
                    );

                    logAdminAction($db, $admin_id, 'LISTING_DISABLE', null, $inventory['equipment_id'], [
                        'equipment_name' => $inventory['equipment_name'],
                        'brand' => $inventory['brand'],
                        'model_number' => $inventory['model_number'],
                        'shop_name' => $inventory['shop_name'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);

                    $success = "Listing has been disabled.";
                } catch (Exception $e) {
                    $errors[] = 'Error disabling listing: ' . $e->getMessage();
                }
            } else if ($action === 'enable') {
                // Get the original quantity before disabling
                $original = $db->fetchOne(
                    "SELECT total_quantity FROM inventory WHERE inventory_id = ?",
                    [$inventory_id]
                );

                if ($original && (int)$original['total_quantity'] > 0) {
                    try {
                        $db->query(
                            "UPDATE inventory SET available_quantity = total_quantity WHERE inventory_id = ?",
                            [$inventory_id]
                        );

                        logAdminAction($db, $admin_id, 'LISTING_ENABLE', null, $inventory['equipment_id'], [
                            'equipment_name' => $inventory['equipment_name'],
                            'brand' => $inventory['brand'],
                            'model_number' => $inventory['model_number'],
                            'shop_name' => $inventory['shop_name'],
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);

                        $success = "Listing has been enabled.";
                    } catch (Exception $e) {
                        $errors[] = 'Error enabling listing: ' . $e->getMessage();
                    }
                } else {
                    $errors[] = 'Cannot enable listing with zero quantity.';
                }
            } else if ($action === 'delete') {
                try {
                    $db->query("DELETE FROM inventory WHERE inventory_id = ?", [$inventory_id]);

                    logAdminAction($db, $admin_id, 'LISTING_DELETE', null, $inventory['equipment_id'], [
                        'equipment_name' => $inventory['equipment_name'],
                        'brand' => $inventory['brand'],
                        'model_number' => $inventory['model_number'],
                        'shop_name' => $inventory['shop_name'],
                        'daily_rate_lkr' => $inventory['daily_rate_lkr'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);

                    $success = "Listing has been removed.";
                } catch (Exception $e) {
                    $errors[] = 'Error deleting listing: ' . $e->getMessage();
                }
            }
        }
    }
}

// ============ FETCH LISTINGS ============

$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$status_filter = in_array($status_filter, ['available', 'unavailable', 'all']) ? $status_filter : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_clauses = [];
$params = [];

if ($category_filter > 0) {
    $where_clauses[] = "e.category_id = ?";
    $params[] = $category_filter;
}

if ($status_filter === 'available') {
    $where_clauses[] = "i.available_quantity > 0";
} else if ($status_filter === 'unavailable') {
    $where_clauses[] = "i.available_quantity = 0";
}

if (!empty($search)) {
    $where_clauses[] = "(e.equipment_name LIKE ? OR e.brand LIKE ? OR s.shop_name LIKE ?)";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param);
}

$where = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$listings = $db->fetchAll(
    "SELECT 
        i.inventory_id,
        i.equipment_id,
        i.shop_id,
        i.available_quantity,
        i.total_quantity,
        i.daily_rate_lkr,
        i.weekly_rate_lkr,
        i.monthly_rate_lkr,
        i.delivery_available,
        i.deposit_required_lkr,
        i.created_at,
        e.equipment_name,
        e.brand,
        e.model_number,
        e.equipment_type,
        e.image_url,
        e.specifications,
        c.category_name,
        s.shop_id,
        s.shop_name,
        s.primary_city,
        u.full_name as vendor_name
     FROM inventory i
     LEFT JOIN equipment e ON e.equipment_id = i.equipment_id
     LEFT JOIN equipment_categories c ON c.category_id = e.category_id
     LEFT JOIN shops s ON s.shop_id = i.shop_id
     LEFT JOIN users u ON u.shop_name = s.shop_name AND u.role = 'vendor'
     $where
     ORDER BY i.created_at DESC",
    $params
);

// Get categories for filter
$categories = $db->fetchAll("SELECT category_id, category_name FROM equipment_categories ORDER BY category_name ASC");

// Get listings statistics
$stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_listings,
        (SELECT COUNT(*) FROM inventory WHERE available_quantity > 0) as available,
        (SELECT COUNT(*) FROM inventory WHERE available_quantity = 0) as unavailable,
        ROUND(AVG(daily_rate_lkr), 0) as avg_daily_rate,
        SUM(available_quantity) as total_available_units
     FROM inventory"
);

$page_title = 'Listings Management - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Listings Management</h1>
            <p class="mt-2 text-gray-600">View and moderate all vendor equipment listings</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Total Listings</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo (int)$stats['total_listings']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-green-200 p-4">
                <p class="text-sm text-gray-600">Available</p>
                <p class="text-2xl font-bold text-green-600 mt-1"><?php echo (int)$stats['available']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-red-200 p-4">
                <p class="text-sm text-gray-600">Unavailable</p>
                <p class="text-2xl font-bold text-red-600 mt-1"><?php echo (int)$stats['unavailable']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Avg Daily Rate</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">LKR <?php echo number_format((int)$stats['avg_daily_rate']); ?></p>
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
                               placeholder="Equipment, brand, or shop..."
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="category" name="category" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="0" <?php echo $category_filter === 0 ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['category_id']; ?>" <?php echo $category_filter === (int)$cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Availability</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="unavailable" <?php echo $status_filter === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium text-sm hover:bg-blue-700">
                    Apply Filters
                </button>
                <a href="<?php echo BASE_URL; ?>admin/listings.php" class="inline-block px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium text-sm hover:bg-gray-50">
                    Clear Filters
                </a>
            </form>
        </div>

        <!-- Listings Grid -->
        <div class="space-y-4">
            <?php if (empty($listings)): ?>
                <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
                    <p class="text-gray-600">No listings found matching the selected filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($listings as $listing): ?>
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Image & Basic Info -->
                            <div>
                                <?php if ($listing['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($listing['image_url']); ?>" alt="" class="w-full h-40 object-cover rounded-lg mb-3">
                                <?php else: ?>
                                    <div class="w-full h-40 bg-gray-200 rounded-lg mb-3 flex items-center justify-center text-gray-400">No image</div>
                                <?php endif; ?>
                                <span class="inline-block px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    <?php echo htmlspecialchars($listing['category_name'] ?? 'Uncategorized'); ?>
                                </span>
                            </div>

                            <!-- Equipment Details -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($listing['equipment_name'] ?? 'Unknown'); ?></h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    <span class="font-medium"><?php echo htmlspecialchars($listing['brand'] ?? ''); ?></span>
                                    <?php if ($listing['model_number']): ?>
                                        <span class="text-gray-500"> • <?php echo htmlspecialchars($listing['model_number']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <?php if ($listing['equipment_type']): ?>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($listing['equipment_type']); ?></p>
                                <?php endif; ?>

                                <div class="mt-3 space-y-1 text-sm text-gray-600">
                                    <p>Shop: <span class="font-medium text-gray-900"><?php echo htmlspecialchars($listing['shop_name'] ?? 'Unknown'); ?></span></p>
                                    <p>Vendor: <span class="font-medium text-gray-900"><?php echo htmlspecialchars($listing['vendor_name'] ?? 'Unknown'); ?></span></p>
                                    <p>Location: <span class="font-medium text-gray-900"><?php echo htmlspecialchars($listing['primary_city'] ?? 'Unknown'); ?></span></p>
                                </div>
                            </div>

                            <!-- Pricing & Availability -->
                            <div class="flex flex-col justify-between">
                                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs text-gray-600">Daily Rate</p>
                                            <p class="text-lg font-bold text-gray-900">LKR <?php echo number_format((float)$listing['daily_rate_lkr'], 0); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-600">Available</p>
                                            <p class="text-lg font-bold <?php echo (int)$listing['available_quantity'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo (int)$listing['available_quantity']; ?>/<?php echo (int)$listing['total_quantity']; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if ((float)$listing['deposit_required_lkr'] > 0): ?>
                                        <p class="text-xs text-gray-600 mt-2">Deposit: <span class="font-medium">LKR <?php echo number_format((float)$listing['deposit_required_lkr'], 0); ?></span></p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-600 mt-1">
                                        Delivery: <span class="font-medium"><?php echo (int)$listing['delivery_available'] ? '✓ Yes' : '✗ No'; ?></span>
                                    </p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <?php if ((int)$listing['available_quantity'] > 0): ?>
                                        <button type="button" 
                                                onclick="openDisableModal(<?php echo (int)$listing['inventory_id']; ?>, '<?php echo htmlspecialchars(addslashes($listing['equipment_name'])); ?>')"
                                                class="flex-1 px-3 py-2 rounded-lg bg-orange-50 text-orange-700 font-medium text-xs hover:bg-orange-100 transition">
                                            Disable
                                        </button>
                                    <?php else: ?>
                                        <button type="button" 
                                                onclick="openEnableModal(<?php echo (int)$listing['inventory_id']; ?>, '<?php echo htmlspecialchars(addslashes($listing['equipment_name'])); ?>')"
                                                class="flex-1 px-3 py-2 rounded-lg bg-green-50 text-green-700 font-medium text-xs hover:bg-green-100 transition">
                                            Enable
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" 
                                            onclick="openDeleteModal(<?php echo (int)$listing['inventory_id']; ?>, '<?php echo htmlspecialchars(addslashes($listing['equipment_name'])); ?>')"
                                            class="flex-1 px-3 py-2 rounded-lg bg-red-50 text-red-700 font-medium text-xs hover:bg-red-100 transition">
                                        Delete
                                    </button>
                                </div>

                                <p class="text-xs text-gray-500 mt-2">Listed: <?php echo date('M d, Y', strtotime($listing['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="mt-4 text-sm text-gray-600">
            Showing <?php echo count($listings); ?> of <?php echo (int)$stats['total_listings']; ?> total listings
        </div>
    </div>
</div>

<!-- Disable Modal -->
<div id="disable-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Disable Listing?</h2>
        <p class="text-gray-600 mb-4">
            Are you sure you want to disable <span id="disable-name" class="font-semibold"></span>? It will no longer appear in search results.
        </p>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="disable">
            <input type="hidden" id="disable-inventory-id" name="inventory_id" value="">
            
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('disable-modal')" class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700">
                    Disable
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Enable Modal -->
<div id="enable-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Enable Listing?</h2>
        <p class="text-gray-600 mb-4">
            Are you sure you want to enable <span id="enable-name" class="font-semibold"></span>? It will appear in search results again.
        </p>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="enable">
            <input type="hidden" id="enable-inventory-id" name="inventory_id" value="">
            
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('enable-modal')" class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700">
                    Enable
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="delete-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Delete Listing?</h2>
        <p class="text-gray-600 mb-4">
            Are you sure you want to permanently remove <span id="delete-name" class="font-semibold"></span>? This action cannot be undone.
        </p>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" id="delete-inventory-id" name="inventory_id" value="">
            
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
function openDisableModal(inventoryId, equipmentName) {
    document.getElementById('disable-inventory-id').value = inventoryId;
    document.getElementById('disable-name').textContent = equipmentName;
    document.getElementById('disable-modal').classList.remove('hidden');
}

function openEnableModal(inventoryId, equipmentName) {
    document.getElementById('enable-inventory-id').value = inventoryId;
    document.getElementById('enable-name').textContent = equipmentName;
    document.getElementById('enable-modal').classList.remove('hidden');
}

function openDeleteModal(inventoryId, equipmentName) {
    document.getElementById('delete-inventory-id').value = inventoryId;
    document.getElementById('delete-name').textContent = equipmentName;
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
