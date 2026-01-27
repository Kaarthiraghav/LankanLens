<?php
/**
 * Navigation Bar Component
 *
 * Includes:
 * - Brand logo link to home
 * - Search shortcut link
 * - Browse by Category dropdown
 * - Conditional Authentication menu (guest, customer, vendor, admin)
 * - Mobile hamburger toggle
 */

// Ensure session exists for auth checks
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load auth helpers if available
$auth_helper_path = __DIR__ . '/auth_helper.php';
if (file_exists($auth_helper_path)) {
    require_once $auth_helper_path;
}

// Resolve auth state safely
$is_logged_in = function_exists('isLoggedIn') ? isLoggedIn() : (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']));
$user_name = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'User';
$user_role = function_exists('getUserRole') ? getUserRole() : (isset($_SESSION['role']) ? $_SESSION['role'] : null);
$is_customer = function_exists('isCustomer') ? isCustomer() : ($user_role === 'customer');
$is_vendor   = function_exists('isVendor') ? isVendor()   : ($user_role === 'vendor');
$is_admin    = function_exists('isAdmin')  ? isAdmin()    : ($user_role === 'admin');
?>

<nav class="bg-white border-b border-gray-200 shadow-sm">
  <div class="container mx-auto px-4">
    <div class="flex items-center justify-between py-3">
      <!-- Left: Brand -->
      <div class="flex items-center gap-3">
        <a href="/public/index.php" class="flex items-center gap-2">
          <span class="inline-flex items-center justify-center w-9 h-9 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
          </span>
          <span class="text-lg font-semibold text-gray-900">LankanLens</span>
        </a>
      </div>

      <!-- Center: Primary nav links -->
      <div class="hidden md:flex items-center gap-6">
        <a href="/public/index.php#search" class="text-gray-700 hover:text-blue-600 transition-colors">Search</a>
        <!-- Categories dropdown -->
        <div class="relative group">
          <button class="inline-flex items-center gap-2 text-gray-700 hover:text-blue-600 transition-colors">
            <span>Browse by Category</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div class="absolute left-0 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition">
            <a href="/public/results.php?category=camera-bodies" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Camera Bodies</a>
            <a href="/public/results.php?category=lenses" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Lenses</a>
            <a href="/public/results.php?category=lighting-gear" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Lighting Gear</a>
            <a href="/public/results.php?category=accessories" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Accessories</a>
          </div>
        </div>
      </div>

      <!-- Right: Auth menu -->
      <div class="hidden md:flex items-center gap-3">
        <?php if (!$is_logged_in): ?>
          <a href="/public/login.php" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">Login</a>
          <a href="/public/register.php" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">Sign Up</a>
        <?php else: ?>
          <?php if ($is_admin): ?>
            <a href="/admin/dashboard.php" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">Admin Panel</a>
          <?php elseif ($is_vendor): ?>
            <a href="/vendor/dashboard.php" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">Vendor Dashboard</a>
          <?php endif; ?>

          <!-- User dropdown -->
          <div class="relative">
            <button id="user-menu-btn" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">
              <span>Welcome, <?php echo $user_name; ?></span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div id="user-menu" class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg hidden">
              <?php if ($is_customer): ?>
                <a href="/public/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                <a href="/public/bookings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Booking History</a>
              <?php else: ?>
                <a href="/public/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
              <?php endif; ?>
              <div class="border-t border-gray-200"></div>
              <a href="/public/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Logout</a>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Mobile: Hamburger -->
      <button id="mobile-menu-btn" class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100" aria-label="Toggle navigation">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>

    <!-- Mobile menu panel -->
    <div id="mobile-menu" class="md:hidden hidden border-t border-gray-200 py-3">
      <div class="flex flex-col gap-2">
        <a href="/public/index.php#search" class="px-2 py-2 text-gray-700 hover:text-blue-600">Search</a>
        <div class="border-t border-gray-200"></div>
        <span class="px-2 py-1 text-xs font-semibold text-gray-500">Browse by Category</span>
        <a href="/public/results.php?category=camera-bodies" class="px-2 py-2 text-gray-700 hover:text-blue-600">Camera Bodies</a>
        <a href="/public/results.php?category=lenses" class="px-2 py-2 text-gray-700 hover:text-blue-600">Lenses</a>
        <a href="/public/results.php?category=lighting-gear" class="px-2 py-2 text-gray-700 hover:text-blue-600">Lighting Gear</a>
        <a href="/public/results.php?category=accessories" class="px-2 py-2 text-gray-700 hover:text-blue-600">Accessories</a>
        <div class="border-t border-gray-200"></div>
        <?php if (!$is_logged_in): ?>
          <div class="flex gap-2 px-2">
            <a href="/public/login.php" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 border border-gray-300 rounded-md text-center">Login</a>
            <a href="/public/register.php" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md text-center">Sign Up</a>
          </div>
        <?php else: ?>
          <?php if ($is_admin): ?>
            <a href="/admin/dashboard.php" class="px-2 py-2 text-gray-700 hover:text-blue-600">Admin Panel</a>
          <?php elseif ($is_vendor): ?>
            <a href="/vendor/dashboard.php" class="px-2 py-2 text-gray-700 hover:text-blue-600">Vendor Dashboard</a>
          <?php endif; ?>
          <a href="/public/profile.php" class="px-2 py-2 text-gray-700 hover:text-blue-600">Profile</a>
          <?php if ($is_customer): ?>
            <a href="/public/bookings.php" class="px-2 py-2 text-gray-700 hover:text-blue-600">Booking History</a>
          <?php endif; ?>
          <a href="/public/logout.php" class="px-2 py-2 text-gray-700 hover:text-blue-600">Logout</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- Minimal interactivity for dropdowns and mobile toggle -->
<script>
  (function() {
    const userBtn = document.getElementById('user-menu-btn');
    const userMenu = document.getElementById('user-menu');
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (userBtn && userMenu) {
      userBtn.addEventListener('click', function(e) {
        e.preventDefault();
        userMenu.classList.toggle('hidden');
      });
      document.addEventListener('click', function(e) {
        if (!userBtn.contains(e.target) && !userMenu.contains(e.target)) {
          userMenu.classList.add('hidden');
        }
      });
    }

    if (mobileBtn && mobileMenu) {
      mobileBtn.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
      });
    }
  })();
</script>
