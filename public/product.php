<?php
include_once __DIR__ . '/../includes/nav.php';
/**
 * Product Detail Page
 * Displays equipment details with gated shop information for non-authenticated users
 */

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: ' . BASE_URL . 'public/index.php');
    exit;
}

// Initialize database
$db = new Database();

// Fetch equipment and shop details
$query = "
    SELECT 
        e.equipment_id,
        e.equipment_name,
        e.brand,
        e.model_number,
        e.description,
        e.specifications,
        e.condition,
        e.image_url,
        i.daily_rate_lkr,
        i.weekly_rate_lkr,
        i.monthly_rate_lkr,
        i.available_quantity,
        i.deposit_required_lkr,
        i.delivery_available,
        s.shop_id,
        s.shop_name,
        s.whatsapp_number,
        s.phone,
        sl.address as shop_address,
        sl.city_name
    FROM equipment e
    JOIN inventory i ON e.equipment_id = i.equipment_id
    JOIN shops s ON e.shop_id = s.shop_id
    LEFT JOIN shop_locations sl ON s.shop_id = sl.shop_id
    WHERE e.equipment_id = ?
    LIMIT 1
";

$equipment = $db->fetchOne($query, [$product_id]);

if (!$equipment) {
    http_response_code(404);
    header('Location: ' . BASE_URL . 'public/index.php');
    exit;
}

// Parse specifications JSON if available
$specs = [];
if ($equipment['specifications']) {
    $specs = json_decode($equipment['specifications'], true) ?? [];
}

// Format daily rate with comma separator
$daily_rate_formatted = number_format($equipment['daily_rate_lkr'], 0, '.', ',');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($equipment['equipment_name']); ?> - LankanLens</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm">
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

    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <a href="<?php echo BASE_URL; ?>public/index.php" class="text-blue-600 hover:text-blue-800">Home</a>
            <span class="text-gray-500 mx-2">/</span>
            <span class="text-gray-700"><?php echo htmlspecialchars($equipment['equipment_name']); ?></span>
        </div>
    </div>

    <!-- Product Detail Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Equipment Details Section (ALWAYS VISIBLE) -->
            <div>
                <!-- Equipment Image -->
                <div class="bg-gray-200 rounded-lg overflow-hidden mb-6 h-96 flex items-center justify-center">
                    <?php if ($equipment['image_url']): ?>
                        <img 
                            src="<?php echo htmlspecialchars($equipment['image_url']); ?>" 
                            alt="<?php echo htmlspecialchars($equipment['equipment_name']); ?>"
                            class="w-full h-full object-cover"
                        >
                    <?php else: ?>
                        <div class="text-gray-400 text-center">
                            <svg class="w-24 h-24 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p>No image available</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Equipment Info -->
                <div class="bg-white rounded-lg p-6 shadow-sm mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <?php echo htmlspecialchars($equipment['equipment_name']); ?>
                    </h1>
                    <p class="text-lg text-gray-600 mb-4">
                        <span class="font-semibold"><?php echo htmlspecialchars($equipment['brand']); ?></span>
                        <?php if ($equipment['model_number']): ?>
                            - <?php echo htmlspecialchars($equipment['model_number']); ?>
                        <?php endif; ?>
                    </p>

                    <!-- Condition Badge -->
                    <div class="mb-4">
                        <?php
                            $condition_colors = [
                                'Excellent' => 'bg-green-100 text-green-800',
                                'Good' => 'bg-blue-100 text-blue-800',
                                'Fair' => 'bg-yellow-100 text-yellow-800'
                            ];
                            $badge_class = $condition_colors[$equipment['condition']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold <?php echo $badge_class; ?>">
                            Condition: <?php echo htmlspecialchars($equipment['condition']); ?>
                        </span>
                    </div>

                    <!-- Availability -->
                    <div class="mb-4">
                        <?php if ($equipment['available_quantity'] > 0): ?>
                            <span class="text-green-600 font-semibold">✓ In Stock (<?php echo $equipment['available_quantity']; ?> available)</span>
                        <?php else: ?>
                            <span class="text-red-600 font-semibold">✗ Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
                        <p class="text-gray-700 leading-relaxed">
                            <?php echo htmlspecialchars($equipment['description']); ?>
                        </p>
                    </div>

                    <!-- Specifications -->
                    <?php if (!empty($specs)): ?>
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Specifications</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <?php foreach ($specs as $key => $value): ?>
                                    <div class="border-l-4 border-blue-600 pl-3">
                                        <p class="text-sm text-gray-600 font-medium"><?php echo htmlspecialchars($key); ?></p>
                                        <p class="text-gray-900"><?php echo htmlspecialchars($value); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Pricing -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Pricing</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-700">Daily Rate:</span>
                                <span class="font-semibold text-gray-900">₨ <?php echo $daily_rate_formatted; ?> LKR</span>
                            </div>
                            <?php if ($equipment['weekly_rate_lkr']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Weekly Rate:</span>
                                    <span class="font-semibold text-gray-900">₨ <?php echo number_format($equipment['weekly_rate_lkr'], 0, '.', ','); ?> LKR</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($equipment['monthly_rate_lkr']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Monthly Rate:</span>
                                    <span class="font-semibold text-gray-900">₨ <?php echo number_format($equipment['monthly_rate_lkr'], 0, '.', ','); ?> LKR</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($equipment['deposit_required_lkr']): ?>
                                <div class="flex justify-between pt-2 border-t">
                                    <span class="text-gray-700">Deposit Required:</span>
                                    <span class="font-semibold text-gray-900">₨ <?php echo number_format($equipment['deposit_required_lkr'], 0, '.', ','); ?> LKR</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($equipment['delivery_available']): ?>
                                <div class="flex items-center gap-2 pt-2 border-t">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-gray-700">Delivery Available</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shop Details Section (GATED FOR GUESTS) -->
            <div>
                <?php if ($is_logged_in): ?>
                    <!-- LOGGED IN: Show full shop details -->
                    <div class="bg-white rounded-lg p-6 shadow-sm mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Rental Shop</h2>
                        
                        <div class="mb-4 pb-4 border-b">
                            <p class="text-sm text-gray-600">Shop Name</p>
                            <p class="text-xl font-semibold text-gray-900">
                                <?php echo htmlspecialchars($equipment['shop_name']); ?>
                            </p>
                        </div>

                        <?php if ($equipment['shop_address']): ?>
                            <div class="mb-4 pb-4 border-b">
                                <p class="text-sm text-gray-600">Address</p>
                                <p class="text-gray-900">
                                    <?php echo htmlspecialchars($equipment['shop_address']); ?>
                                    <?php if ($equipment['city_name']): ?>
                                        , <?php echo htmlspecialchars($equipment['city_name']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4 pb-4 border-b">
                            <p class="text-sm text-gray-600">Contact</p>
                            <p class="text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773c.058.3.102.605.102.924 0 1.898.555 3.664 1.52 5.192l1.539-1.539a1 1 0 011.414 0l3.14 3.14a1 1 0 010 1.414l-.79.79c-1.585 1.585-4.587 1.945-7.456 0-2.573-1.763-4.32-5.268-4.32-8.86 0-.576.028-1.148.084-1.711.05-.573-.04-1.159-.41-1.487l-.868-.868a1 1 0 01-.11-1.213l.51-.868A1 1 0 012 3z"></path>
                                </svg>
                                <?php echo htmlspecialchars($equipment['phone']); ?>
                            </p>
                        </div>

                        <div class="mb-6">
                            <p class="text-sm text-gray-600">WhatsApp</p>
                            <a 
                                href="https://wa.me/<?php echo preg_replace('/\D/', '', $equipment['whatsapp_number']); ?>" 
                                target="_blank"
                                class="inline-flex items-center gap-2 text-green-600 hover:text-green-800 font-semibold"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.272-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-4.928 1.227l-.353-.192-3.66-1.207.738 2.692.46.364a9.864 9.864 0 00-1.203 4.817c0 5.432 4.424 9.857 9.857 9.857 2.64 0 5.12-1.053 6.979-2.911 1.859-1.859 2.88-4.339 2.88-6.979 0-5.431-4.424-9.857-9.857-9.857z"/>
                                </svg>
                                Chat on WhatsApp
                            </a>
                        </div>

                        <!-- Rent Now Button -->
                        <button 
                            onclick="AuthManager.redirectToLogin('<?php echo urlencode($_SERVER['REQUEST_URI']); ?>')"
                            data-action="login-to-rent"
                            data-product-id="<?php echo $product_id; ?>"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1h7.586a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM5 16a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Rent Now
                        </button>
                    </div>
                <?php else: ?>
                    <!-- NOT LOGGED IN: Show gated shop details -->
                    <div class="gated-content mb-6">
                        <!-- Blurred Content -->
                        <div class="blur-filter bg-white rounded-lg p-6 shadow-sm">
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Rental Shop</h2>
                            
                            <div class="mb-4 pb-4 border-b">
                                <p class="text-sm text-gray-600">Shop Name</p>
                                <p class="text-xl font-semibold text-gray-900">████████ ███████</p>
                            </div>

                            <div class="mb-4 pb-4 border-b">
                                <p class="text-sm text-gray-600">Address</p>
                                <p class="text-gray-900">████████ ███, ███████</p>
                            </div>

                            <div class="mb-4 pb-4 border-b">
                                <p class="text-sm text-gray-600">Contact</p>
                                <p class="text-gray-900">+94 ██ ███ ████</p>
                            </div>

                            <button 
                                class="w-full bg-gray-400 text-gray-200 font-bold py-3 px-6 rounded-lg cursor-not-allowed"
                                disabled
                            >
                                Rent Now
                            </button>
                        </div>

                        <!-- Login Overlay -->
                        <div class="login-overlay">
                            <div class="text-center">
                                <svg class="lock-icon w-16 h-16 mx-auto mb-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Login to View Shop Details</h3>
                                <p class="text-gray-600 mb-4">Sign in to contact the shop and start your rental</p>
                                <button 
                                    onclick="AuthManager.redirectToLogin('<?php echo urlencode($_SERVER['REQUEST_URI']); ?>')"
                                    class="login-overlay-button bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition"
                                >
                                    Login to Rent
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
