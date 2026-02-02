<?php
/**
 * Admin Master Catalog - Manage Master Gear
 * CRUD for master equipment list (brand, model, image)
 * Includes comprehensive validation and audit logging
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/audit_logger.php';

requireAdmin();

$db = new Database();
$admin_id = getCurrentUserId();

$categories = $db->fetchAll("SELECT category_id, category_name FROM equipment_categories ORDER BY category_name ASC");

$master_shop = $db->fetchOne("SELECT shop_id, shop_name FROM shops WHERE shop_name = 'Master Catalog' LIMIT 1");
$master_shop_id = $master_shop ? (int)$master_shop['shop_id'] : 0;

$errors = [];
$success = '';
$editing_item = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'delete') {
        $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
        if ($equipment_id <= 0 || $master_shop_id <= 0) {
            $errors[] = 'Unable to delete this item.';
        } else {
            // Fetch equipment details before deletion for audit log
            $equipment = $db->fetchOne(
                "SELECT equipment_id, equipment_name, brand, model_number, category_id, image_url FROM equipment WHERE equipment_id = ? AND shop_id = ?",
                [$equipment_id, $master_shop_id]
            );

            if (!$equipment) {
                $errors[] = 'Equipment not found.';
            } else {
                try {
                    $db->query("DELETE FROM equipment WHERE equipment_id = ? AND shop_id = ?", [$equipment_id, $master_shop_id]);
                    
                    // Log the deletion
                    logAdminAction($db, $admin_id, 'CATALOG_DELETE', null, $equipment_id, [
                        'equipment_name' => $equipment['equipment_name'],
                        'brand' => $equipment['brand'],
                        'model_number' => $equipment['model_number'],
                        'category_id' => $equipment['category_id'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    
                    $success = 'Master gear removed successfully and logged.';
                } catch (Exception $e) {
                    $errors[] = 'Error deleting equipment: ' . $e->getMessage();
                }
            }
        }
    }

    if ($action === 'save') {
        $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $equipment_name = trim($_POST['equipment_name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $model_number = trim($_POST['model_number'] ?? '');
        $equipment_type = trim($_POST['equipment_type'] ?? '');
        $specifications = trim($_POST['specifications'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');

        // ============ COMPREHENSIVE VALIDATION ============
        
        // Validate master shop exists
        if ($master_shop_id <= 0) {
            $errors[] = "Master Catalog shop not found. Create a shop named 'Master Catalog' first.";
        }

        // Validate category
        if ($category_id <= 0) {
            $errors[] = 'Category is required.';
        } else {
            $cat_exists = $db->fetchOne("SELECT category_id FROM equipment_categories WHERE category_id = ?", [$category_id]);
            if (!$cat_exists) {
                $errors[] = 'Invalid category selection.';
            }
        }

        // Validate equipment name
        if ($equipment_name === '') {
            $errors[] = 'Equipment name is required.';
        } elseif (strlen($equipment_name) > 255) {
            $errors[] = 'Equipment name must not exceed 255 characters.';
        }

        // Validate brand
        if ($brand === '') {
            $errors[] = 'Brand is required.';
        } elseif (strlen($brand) > 100) {
            $errors[] = 'Brand must not exceed 100 characters.';
        }

        // Validate model number (optional but validate length)
        if (!empty($model_number) && strlen($model_number) > 100) {
            $errors[] = 'Model number must not exceed 100 characters.';
        }

        // Validate equipment type (optional but validate length)
        if (!empty($equipment_type) && strlen($equipment_type) > 100) {
            $errors[] = 'Equipment type must not exceed 100 characters.';
        }

        // Validate specifications (if provided, must be valid JSON or text)
        if (!empty($specifications)) {
            // Try to parse as JSON, but allow plain text too
            if ($specifications[0] === '{' || $specifications[0] === '[') {
                $decoded = json_decode($specifications, true);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = 'Specifications must be valid JSON or plain text.';
                }
            }
        }

        // Validate image URL
        $valid_image_path = false;
        if (empty($image_url)) {
            $errors[] = 'Image path is required.';
        } else {
            if (strlen($image_url) > 255) {
                $errors[] = 'Image path must not exceed 255 characters.';
            } else {
                // Validate image path exists in assets
                $image_file = __DIR__ . '/..' . parse_url($image_url, PHP_URL_PATH);
                $image_file = realpath($image_file);
                $assets_dir = realpath(__DIR__ . '/../assets/images');
                
                if (!$image_file || !$assets_dir || strpos($image_file, $assets_dir) !== 0 || !is_file($image_file)) {
                    $errors[] = 'Image path must point to a valid file in /assets/images/.';
                } else {
                    $valid_image_path = true;
                }
            }
        }

        // Check for duplicates: brand + model + category must be unique per shop
        if (empty($errors) && !empty($brand)) {
            $duplicate = $db->fetchOne(
                "SELECT COUNT(*) AS total FROM equipment 
                 WHERE category_id = ? AND brand = ? AND model_number = ? AND shop_id = ? AND equipment_id != ?",
                [$category_id, $brand, $model_number, $master_shop_id, $equipment_id]
            );
            if ($duplicate && (int)$duplicate['total'] > 0) {
                $errors[] = 'A master gear entry with this combination already exists. Brand, Model, and Category must be unique.';
            }
        }

        // ============ SAVE AND LOG IF VALID ============
        
        if (empty($errors)) {
            try {
                if ($equipment_id > 0) {
                    // UPDATE - Get old values for audit log
                    $old_equipment = $db->fetchOne(
                        "SELECT category_id, equipment_name, brand, model_number, equipment_type, specifications, image_url 
                         FROM equipment WHERE equipment_id = ? AND shop_id = ?",
                        [$equipment_id, $master_shop_id]
                    );

                    if (!$old_equipment) {
                        $errors[] = 'Equipment not found.';
                    } else {
                        $db->query(
                            "UPDATE equipment
                             SET category_id = ?, equipment_name = ?, brand = ?, model_number = ?, equipment_type = ?, specifications = ?, image_url = ?
                             WHERE equipment_id = ? AND shop_id = ?",
                            [$category_id, $equipment_name, $brand, $model_number, $equipment_type, $specifications, $image_url, $equipment_id, $master_shop_id]
                        );
                        
                        // Log the update with before/after values
                        $changes = [];
                        if ($old_equipment['category_id'] != $category_id) $changes['category_id'] = ['old' => $old_equipment['category_id'], 'new' => $category_id];
                        if ($old_equipment['equipment_name'] != $equipment_name) $changes['equipment_name'] = ['old' => $old_equipment['equipment_name'], 'new' => $equipment_name];
                        if ($old_equipment['brand'] != $brand) $changes['brand'] = ['old' => $old_equipment['brand'], 'new' => $brand];
                        if ($old_equipment['model_number'] != $model_number) $changes['model_number'] = ['old' => $old_equipment['model_number'], 'new' => $model_number];
                        if ($old_equipment['equipment_type'] != $equipment_type) $changes['equipment_type'] = ['old' => $old_equipment['equipment_type'], 'new' => $equipment_type];
                        if ($old_equipment['image_url'] != $image_url) $changes['image_url'] = ['old' => $old_equipment['image_url'], 'new' => $image_url];

                        logAdminAction($db, $admin_id, 'CATALOG_UPDATE', null, $equipment_id, [
                            'changes' => $changes,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                        
                        $success = 'Master gear updated successfully and logged.';
                    }
                } else {
                    // CREATE - New equipment
                    $db->query(
                        "INSERT INTO equipment (category_id, equipment_name, brand, model_number, equipment_type, specifications, image_url, shop_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$category_id, $equipment_name, $brand, $model_number, $equipment_type, $specifications, $image_url, $master_shop_id]
                    );
                    
                    $new_equipment_id = $db->fetchOne("SELECT LAST_INSERT_ID() as id");
                    $new_id = $new_equipment_id ? $new_equipment_id['id'] : 0;
                    
                    // Log the creation
                    logAdminAction($db, $admin_id, 'CATALOG_CREATE', null, $new_id, [
                        'equipment_name' => $equipment_name,
                        'brand' => $brand,
                        'model_number' => $model_number,
                        'equipment_type' => $equipment_type,
                        'category_id' => $category_id,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    
                    $success = 'Master gear added successfully and logged.';
                }
            } catch (Exception $e) {
                $errors[] = 'Error saving equipment: ' . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['edit']) && $master_shop_id > 0) {
    $edit_id = (int)$_GET['edit'];
    if ($edit_id > 0) {
        $editing_item = $db->fetchOne("SELECT * FROM equipment WHERE equipment_id = ? AND shop_id = ?", [$edit_id, $master_shop_id]);
    }
}

$master_gear = [];
if ($master_shop_id > 0) {
    $master_gear = $db->fetchAll(
        "SELECT e.equipment_id, e.equipment_name, e.brand, e.model_number, e.equipment_type, e.image_url, c.category_name
         FROM equipment e
         INNER JOIN equipment_categories c ON c.category_id = e.category_id
         WHERE e.shop_id = ?
         ORDER BY e.equipment_name ASC",
        [$master_shop_id]
    );
}

$page_title = 'Master Catalog - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Master Catalog</h1>
            <p class="mt-2 text-gray-600">Manage the master list of gear that vendors can select.</p>
        </div>

        <?php if (!$master_shop_id): ?>
            <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
                Create a shop named <strong>Master Catalog</strong> to store master gear entries.
            </div>
        <?php endif; ?>

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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo $editing_item ? 'Edit Master Gear' : 'Add Master Gear'; ?></h2>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="equipment_id" value="<?php echo $editing_item ? (int)$editing_item['equipment_id'] : 0; ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int)$category['category_id']; ?>" <?php echo $editing_item && (int)$editing_item['category_id'] === (int)$category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="equipment_name">Equipment Name *</label>
                        <input type="text" id="equipment_name" name="equipment_name" required
                               value="<?php echo $editing_item ? htmlspecialchars($editing_item['equipment_name']) : ''; ?>"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="brand">Brand *</label>
                        <input type="text" id="brand" name="brand" required
                               value="<?php echo $editing_item ? htmlspecialchars($editing_item['brand']) : ''; ?>"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="model_number">Model Number</label>
                        <input type="text" id="model_number" name="model_number"
                               value="<?php echo $editing_item ? htmlspecialchars($editing_item['model_number']) : ''; ?>"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="equipment_type">Equipment Type</label>
                        <input type="text" id="equipment_type" name="equipment_type"
                               value="<?php echo $editing_item ? htmlspecialchars($editing_item['equipment_type']) : ''; ?>"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="specifications">Specifications (JSON or text)</label>
                        <textarea id="specifications" name="specifications" rows="3"
                                  class="mt-1 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"><?php echo $editing_item ? htmlspecialchars($editing_item['specifications']) : ''; ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Image *</label>
                        <div class="flex gap-2">
                            <input type="text" id="image_url" name="image_url" readonly
                                   placeholder="/assets/images/Body/Sony/a7r4.jpg"
                                   value="<?php echo $editing_item ? htmlspecialchars($editing_item['image_url']) : ''; ?>"
                                   class="flex-1 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <button type="button" id="image_picker_btn" class="rounded-lg bg-gray-200 text-gray-800 font-semibold px-4 py-2 hover:bg-gray-300">
                                Browse
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Select from pre-stored asset paths.</p>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-blue-600 text-white font-semibold py-2 hover:bg-blue-700" <?php echo $master_shop_id ? '' : 'disabled'; ?>>
                        <?php echo $editing_item ? 'Update Master Gear' : 'Add to Master Catalog'; ?>
                    </button>

                    <?php if ($editing_item): ?>
                        <a href="<?php echo BASE_URL; ?>admin/manage-master-gear.php" class="block text-center text-sm text-gray-600 hover:text-gray-800">Cancel edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Master Gear List</h2>
                <?php if (empty($master_gear)): ?>
                    <p class="text-sm text-gray-600">No master gear entries yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2">Gear</th>
                                    <th class="py-2">Category</th>
                                    <th class="py-2">Image</th>
                                    <th class="py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach ($master_gear as $gear): ?>
                                    <tr>
                                        <td class="py-3">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($gear['equipment_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($gear['brand']); ?><?php echo $gear['model_number'] ? ' • ' . htmlspecialchars($gear['model_number']) : ''; ?></div>
                                        </td>
                                        <td class="py-3 text-gray-600"><?php echo htmlspecialchars($gear['category_name']); ?></td>
                                        <td class="py-3">
                                            <?php if (!empty($gear['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($gear['image_url']); ?>" alt="" class="h-12 w-16 object-cover rounded-md">
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3">
                                            <div class="flex gap-2">
                                                <a href="<?php echo BASE_URL; ?>admin/manage-master-gear.php?edit=<?php echo (int)$gear['equipment_id']; ?>" class="text-blue-600 hover:text-blue-800">Edit</a>
                                                <form method="post" onsubmit="return confirm('Delete this master gear item?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="equipment_id" value="<?php echo (int)$gear['equipment_id']; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Image Picker Modal -->
<div id="image_picker_modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl max-w-3xl w-full max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center p-5 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Select Image from Assets</h2>
            <button type="button" id="image_picker_close" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto p-5">
            <div id="images_loading" class="text-center py-8 text-gray-600">Loading available images...</div>
            <div id="images_grid" class="hidden grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"></div>
            <div id="images_error" class="hidden text-center py-8 text-red-600"></div>
        </div>

        <div class="flex justify-between items-center p-5 border-t border-gray-200">
            <div id="selected_info" class="text-sm text-gray-600">No image selected</div>
            <div class="flex gap-2">
                <button type="button" id="image_picker_cancel" class="rounded-lg bg-gray-200 text-gray-800 font-semibold px-4 py-2 hover:bg-gray-300">
                    Cancel
                </button>
                <button type="button" id="image_picker_confirm" class="rounded-lg bg-blue-600 text-white font-semibold px-4 py-2 hover:bg-blue-700" disabled>
                    Select
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const imagePickerBtn = document.getElementById('image_picker_btn');
const imagePickerModal = document.getElementById('image_picker_modal');
const imagePickerClose = document.getElementById('image_picker_close');
const imagePickerCancel = document.getElementById('image_picker_cancel');
const imagePickerConfirm = document.getElementById('image_picker_confirm');
const imageUrlInput = document.getElementById('image_url');
const imagesGrid = document.getElementById('images_grid');
const imagesLoading = document.getElementById('images_loading');
const imagesError = document.getElementById('images_error');
const selectedInfo = document.getElementById('selected_info');

let selectedImagePath = null;

imagePickerBtn.addEventListener('click', () => {
    imagePickerModal.classList.remove('hidden');
    loadImages();
});

imagePickerClose.addEventListener('click', closeModal);
imagePickerCancel.addEventListener('click', closeModal);

function closeModal() {
    imagePickerModal.classList.add('hidden');
    selectedImagePath = null;
    imagePickerConfirm.disabled = true;
    selectedInfo.textContent = 'No image selected';
}

imagePickerConfirm.addEventListener('click', () => {
    if (selectedImagePath) {
        imageUrlInput.value = selectedImagePath;
        closeModal();
    }
});

async function loadImages() {
    imagesGrid.innerHTML = '';
    imagesError.innerHTML = '';
    imagesLoading.classList.remove('hidden');
    imagesGrid.classList.add('hidden');
    imagesError.classList.add('hidden');

    try {
        const response = await fetch('<?php echo BASE_URL; ?>api/get-available-images.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to load images');
        }

        imagesLoading.classList.add('hidden');

        if (data.images.length === 0) {
            imagesError.textContent = 'No images found in /assets/images/';
            imagesError.classList.remove('hidden');
            return;
        }

        imagesGrid.innerHTML = '';
        data.images.forEach(img => {
            const div = document.createElement('div');
            div.className = 'relative cursor-pointer group';
            div.innerHTML = `
                <div class="relative overflow-hidden rounded-lg bg-gray-100 aspect-square border-2 border-transparent group-hover:border-blue-400">
                    <img src="${escapeHtml(img.path)}" alt="" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition"></div>
                </div>
                <div class="mt-2 text-xs text-gray-600 truncate" title="${escapeHtml(img.path)}">
                    ${escapeHtml(img.path)}
                </div>
            `;
            div.addEventListener('click', () => selectImage(img.path));
            imagesGrid.appendChild(div);
        });

        imagesGrid.classList.remove('hidden');
    } catch (error) {
        imagesLoading.classList.add('hidden');
        imagesError.textContent = 'Error loading images: ' + error.message;
        imagesError.classList.remove('hidden');
    }
}

function selectImage(path) {
    selectedImagePath = path;
    imagePickerConfirm.disabled = false;
    selectedInfo.textContent = 'Selected: ' + path;

    // Highlight selected image
    document.querySelectorAll('#images_grid > div').forEach(div => {
        div.classList.remove('ring-2', 'ring-blue-500');
    });
    event.currentTarget.classList.add('ring-2', 'ring-blue-500');
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
