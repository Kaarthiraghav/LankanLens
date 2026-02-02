<?php
/**
 * Add Equipment Page
 * Allows active vendors to add new equipment listings
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Protect route - require active vendor
requireActiveVendor();

// Initialize database connection
$db = new Database();

// Get vendor info and shop ID
$vendor_id = getCurrentUserId();
$shop = $db->fetchOne(
    "SELECT s.shop_id, s.shop_name, s.primary_city
     FROM shops s
     INNER JOIN users u ON s.shop_name = u.shop_name
     WHERE u.user_id = ?
     LIMIT 1",
    [$vendor_id]
);

if (!$shop) {
    die('Shop not found for this vendor.');
}

$shop_id = (int)$shop['shop_id'];
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
    $condition = trim($_POST['condition'] ?? '');
    $daily_rate_lkr = isset($_POST['daily_rate_lkr']) ? (float)$_POST['daily_rate_lkr'] : 0;
    $available_quantity = isset($_POST['available_quantity']) ? (int)$_POST['available_quantity'] : 0;
    $deposit_required_lkr = isset($_POST['deposit_required_lkr']) ? (float)$_POST['deposit_required_lkr'] : 0;
    $delivery_available = isset($_POST['delivery_available']) ? 1 : 0;

    // Validate inputs
    if ($equipment_id <= 0) {
        $errors[] = 'Please select a valid equipment model.';
    }
    if ($condition === '') {
        $errors[] = 'Condition is required.';
    }
    if ($daily_rate_lkr <= 0) {
        $errors[] = 'Daily rate must be greater than 0.';
    }
    if ($available_quantity < 0) {
        $errors[] = 'Available quantity cannot be negative.';
    }
    if ($deposit_required_lkr < 0) {
        $errors[] = 'Deposit cannot be negative.';
    }

    if (empty($errors)) {
        // Verify equipment exists and is from master catalog
        $equipment = $db->fetchOne(
            "SELECT equipment_id FROM equipment WHERE equipment_id = ? AND shop_id = (SELECT shop_id FROM shops WHERE shop_name = 'Master Catalog' LIMIT 1)",
            [$equipment_id]
        );

        if (!$equipment) {
            $errors[] = 'Invalid equipment selection. Equipment must be from the master catalog.';
        }

        // Check if vendor already has this equipment listed
        if (empty($errors)) {
            $existing = $db->fetchOne(
                "SELECT inventory_id FROM inventory WHERE equipment_id = ? AND shop_id = ?",
                [$equipment_id, $shop_id]
            );

            if ($existing) {
                $errors[] = 'You already have this equipment listed. Use "My Listings" to edit or remove it.';
            }
        }

        // Insert inventory row if no errors
        if (empty($errors)) {
            try {
                $db->query(
                    "INSERT INTO inventory (equipment_id, shop_id, available_quantity, total_quantity, daily_rate_lkr, deposit_required_lkr, delivery_available)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$equipment_id, $shop_id, $available_quantity, $available_quantity, $daily_rate_lkr, $deposit_required_lkr, $delivery_available]
                );
                $success = 'Equipment added successfully! Your listing is now live.';
                
                // Reset form after success
                $_POST = [];
            } catch (Exception $e) {
                $errors[] = 'Error saving equipment: ' . $e->getMessage();
            }
        }
    }
}

// Fetch categories and brands for dropdowns
$categories = [];
$brands = [];
try {
    $categories = $db->fetchAll("SELECT category_id, category_name FROM equipment_categories ORDER BY category_name ASC");
    $brands = $db->fetchAll("SELECT DISTINCT brand FROM equipment WHERE shop_id = (SELECT shop_id FROM shops WHERE shop_name = 'Master Catalog' LIMIT 1) ORDER BY brand ASC");
} catch (Exception $e) {
    $categories = [];
    $brands = [];
}

$page_title = "Add Equipment - " . APP_NAME;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Add New Equipment</h1>
            <p class="mt-2 text-gray-600">
                Create a new listing for your shop. Provide clear details and images to attract renters.
            </p>
            <?php if ($shop): ?>
                <p class="mt-1 text-sm text-gray-500">
                    Shop: <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($shop['shop_name']); ?></span>
                    <?php if (!empty($shop['primary_city'])): ?>
                        <span class="mx-2">•</span><?php echo htmlspecialchars($shop['primary_city']); ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

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

        <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-6">
            <form action="<?php echo BASE_URL; ?>vendor/add-equipment.php" method="post" class="space-y-6" id="add-equipment-form">
                <input type="hidden" id="equipment_id" name="equipment_id" value="">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700">Category *</label>
                                <select id="category_id" name="category_id" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo (int)$category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="brand" class="block text-sm font-medium text-gray-700">Brand *</label>
                                <select id="brand" name="brand" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select a brand</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo htmlspecialchars($brand['brand']); ?>">
                                            <?php echo htmlspecialchars($brand['brand']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label for="model_id" class="block text-sm font-medium text-gray-700">Model *</label>
                                <select id="model_id" name="model_id" required disabled class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select category and brand first</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Models are loaded from the master equipment list.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label for="condition" class="block text-sm font-medium text-gray-700">Condition *</label>
                                <select id="condition" name="condition" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select condition</option>
                                    <option value="excellent">Excellent</option>
                                    <option value="good">Good</option>
                                    <option value="fair">Fair</option>
                                </select>
                            </div>

                            <div>
                                <label for="daily_rate_lkr" class="block text-sm font-medium text-gray-700">Daily Rate (LKR) *</label>
                                <input type="number" id="daily_rate_lkr" name="daily_rate_lkr" required min="0" step="0.01"
                                       placeholder="e.g., 15000"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="available_quantity" class="block text-sm font-medium text-gray-700">Available Quantity *</label>
                                <input type="number" id="available_quantity" name="available_quantity" required min="0" step="1"
                                       placeholder="e.g., 2"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="deposit_required_lkr" class="block text-sm font-medium text-gray-700">Deposit Required (LKR)</label>
                                <input type="number" id="deposit_required_lkr" name="deposit_required_lkr" min="0" step="0.01"
                                       placeholder="Optional"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div class="flex items-center mt-6">
                                <input type="checkbox" id="delivery_available" name="delivery_available" value="1"
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label for="delivery_available" class="ml-2 block text-sm text-gray-700">Delivery Available</label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Gear Preview</h3>
                        <div class="rounded-lg bg-white border border-gray-200 p-3">
                            <div class="h-48 bg-gray-100 rounded-md overflow-hidden flex items-center justify-center">
                                <img id="preview-image" src="" alt="Gear preview" class="hidden w-full h-full object-cover" />
                                <div id="preview-placeholder" class="text-sm text-gray-400">Select a model to preview</div>
                            </div>
                            <div class="mt-3">
                                <p id="preview-title" class="font-semibold text-gray-900">No model selected</p>
                                <p id="preview-subtitle" class="text-sm text-gray-500">Brand and model details will appear here.</p>
                            </div>
                            <div class="mt-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Specifications</p>
                                <ul id="preview-specs" class="mt-2 text-sm text-gray-700 space-y-1">
                                    <li>Select a model to view specs.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="submit" id="submit-btn"
                            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-3 text-white font-semibold shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Add Equipment
                    </button>
                    <a href="<?php echo BASE_URL; ?>vendor/dashboard.php"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-6 py-3 text-gray-700 font-semibold hover:bg-gray-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div><script>
    const baseUrl = "<?php echo BASE_URL; ?>";
    const categorySelect = document.getElementById('category_id');
    const brandSelect = document.getElementById('brand');
    const modelSelect = document.getElementById('model_id');
    const equipmentIdInput = document.getElementById('equipment_id');

    const previewImage = document.getElementById('preview-image');
    const previewPlaceholder = document.getElementById('preview-placeholder');
    const previewTitle = document.getElementById('preview-title');
    const previewSubtitle = document.getElementById('preview-subtitle');
    const previewSpecs = document.getElementById('preview-specs');

    let modelData = [];

    function resetModelSelect(message) {
        modelSelect.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = message;
        modelSelect.appendChild(option);
        modelSelect.disabled = true;
        equipmentIdInput.value = '';
        updatePreview(null);
    }

    function updatePreview(model) {
        if (!model) {
            previewImage.classList.add('hidden');
            previewImage.src = '';
            previewPlaceholder.classList.remove('hidden');
            previewTitle.textContent = 'No model selected';
            previewSubtitle.textContent = 'Brand and model details will appear here.';
            previewSpecs.innerHTML = '<li>Select a model to view specs.</li>';
            return;
        }

        if (model.image_url) {
            previewImage.src = model.image_url;
            previewImage.classList.remove('hidden');
            previewPlaceholder.classList.add('hidden');
        } else {
            previewImage.classList.add('hidden');
            previewImage.src = '';
            previewPlaceholder.classList.remove('hidden');
            previewPlaceholder.textContent = 'No image available';
        }

        const modelLabel = model.model_number ? `${model.equipment_name} (${model.model_number})` : model.equipment_name;
        previewTitle.textContent = modelLabel;
        previewSubtitle.textContent = `${model.brand || ''} ${model.equipment_type ? '• ' + model.equipment_type : ''}`.trim();

        let specsHtml = '';
        if (model.specifications) {
            try {
                const specsObj = JSON.parse(model.specifications);
                const entries = Object.entries(specsObj);
                if (entries.length) {
                    specsHtml = entries.map(([key, value]) => `<li><span class="font-medium">${key}:</span> ${value}</li>`).join('');
                }
            } catch (error) {
                specsHtml = `<li>${model.specifications}</li>`;
            }
        }

        if (!specsHtml) {
            specsHtml = '<li>No specifications listed.</li>';
        }

        previewSpecs.innerHTML = specsHtml;
    }

    async function fetchModels() {
        const categoryId = categorySelect.value;
        const brand = brandSelect.value;

        if (!categoryId || !brand) {
            resetModelSelect('Select category and brand first');
            return;
        }

        resetModelSelect('Loading models...');

        try {
            const url = `${baseUrl}api/get-models.php?category_id=${encodeURIComponent(categoryId)}&brand=${encodeURIComponent(brand)}`;
            const response = await fetch(url);
            const data = await response.json();

            if (!data.success || !Array.isArray(data.models)) {
                resetModelSelect('No models found');
                return;
            }

            modelData = data.models;
            modelSelect.innerHTML = '<option value="">Select a model</option>';
            modelData.forEach((model) => {
                const option = document.createElement('option');
                option.value = model.equipment_id;
                const label = model.model_number ? `${model.equipment_name} (${model.model_number})` : model.equipment_name;
                option.textContent = label;
                modelSelect.appendChild(option);
            });
            modelSelect.disabled = modelData.length === 0;
            if (modelData.length === 0) {
                resetModelSelect('No models found');
            }
        } catch (error) {
            resetModelSelect('Error loading models');
        }
    }

    categorySelect.addEventListener('change', fetchModels);
    brandSelect.addEventListener('change', fetchModels);

    modelSelect.addEventListener('change', () => {
        const selectedId = modelSelect.value;
        const selectedModel = modelData.find((model) => String(model.equipment_id) === String(selectedId));
        equipmentIdInput.value = selectedModel ? selectedModel.equipment_id : '';
        updatePreview(selectedModel || null);
    });

    // Form validation on submit
    document.getElementById('add-equipment-form').addEventListener('submit', (e) => {
        if (!equipmentIdInput.value || equipmentIdInput.value === '') {
            e.preventDefault();
            alert('Please select a model before submitting.');
            return false;
        }
    });

    resetModelSelect('Select category and brand first');
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
