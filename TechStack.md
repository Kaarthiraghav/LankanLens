# LankanLens - Technical Stack & Architecture

**Version:** 1.0  
**Date:** January 2026  
**PHP Version:** 8.x  
**Database:** MySQL 5.7+  
**Frontend:** Vanilla JS + Tailwind CSS (CDN)  
**Server:** Apache (XAMPP/WAMP)

---

## Table of Contents

1. [Project Folder Structure](#project-folder-structure)
2. [Database Schema](#database-schema)
3. [Coding Standards](#coding-standards)
4. [Environment Setup](#environment-setup)
5. [Quick Start Guide](#quick-start-guide)

---

## Project Folder Structure

### Directory Layout

```
LankanLens/
├── config/
│   ├── database.php          # PDO database connection class
│   ├── config.php            # Application-wide constants
│   └── .env.example          # Environment variables template
│
├── includes/
│   ├── header.php            # Global header component
│   ├── footer.php            # Global footer component
│   ├── navbar.php            # Navigation bar component
│   ├── auth_helper.php       # Authentication middleware & helper functions
│   └── error-handler.php     # Global error handling
│
├── public/
│   ├── index.php             # Landing page / Home
│   ├── search.php            # Search results page
│   ├── product.php           # Product detail page (with gated content)
│   ├── login.php             # User login page
│   ├── register.php          # User registration page
│   ├── logout.php            # Logout handler
│   ├── vendor-pending.php    # Pending vendor approval page
│   ├── unauthorized.php      # Access denied page
│   ├── api/
│   │   ├── search-api.php    # Search endpoint (JSON response)
│   │   ├── booking-api.php   # Booking request endpoint
│   │   ├── login-api.php     # Login authentication endpoint
│   │   ├── register-api.php  # User registration endpoint
│   │   ├── shop-list-api.php # Shop data endpoint
│   │   └── equipment-api.php # Equipment data endpoint
│   └── thank-you.php         # Post-booking confirmation page
│
├── vendor/
│   ├── dashboard.php         # Vendor dashboard (requires auth + active status)
│   ├── add-equipment.php     # Add new equipment listing
│   ├── edit-equipment.php    # Edit existing equipment
│   ├── my-listings.php       # View all vendor listings
│   └── inquiries.php         # View booking inquiries
│
├── admin/
│   ├── dashboard.php         # Admin panel (God Mode)
│   ├── vendor-approvals.php  # Approve/reject vendor accounts
│   ├── users.php             # User management (view, edit, suspend, delete)
│   ├── listings.php          # Moderate all equipment listings
│   ├── reviews.php           # Moderate shop reviews
│   └── analytics.php         # System-wide analytics
│
├── assets/
│   ├── css/
│   │   ├── styles.css        # Global custom CSS (overrides for Tailwind)
│   │   └── tailwind.config.js # Tailwind configuration (optional)
│   │
│   ├── js/
│   │   ├── search.js         # Search form logic & autocomplete
│   │   ├── booking.js        # Booking modal & WhatsApp generator
│   │   ├── auth.js           # Login/register form validation & gated content UI
│   │   ├── empty-state.js    # Empty state interaction handlers
│   │   ├── utils.js          # Utility functions (validation, formatting)
│   │   ├── modal.js          # Reusable modal component
│   │   └── toast.js          # Toast notification system
│   │
│   └── images/
│       ├── logo.svg          # LankanLens logo
│       ├── hero.jpg          # Hero section background
│       ├── empty-state.svg   # Empty state illustration
│       └── icons/
│           ├── camera.svg    # Equipment type icons
│           ├── lens.svg
│           ├── lights.svg
│           └── location.svg
│
├── database/
│   ├── schema.sql            # Complete database schema (SQL dump)
│   ├── seeds/
│   │   ├── shops-seed.sql    # Sample shop data
│   │   ├── equipment-seed.sql# Sample equipment data
│   │   ├── inventory-seed.sql# Sample inventory data
│   │   └── users-seed.sql    # Sample user data (including admin)
│   └── migrations/
│       ├── 001-initial-schema.sql
│       ├── 002-add-ratings-table.sql
│       └── 003-add-users-table.sql
│
├── uploads/
│   ├── equipment/            # Uploaded equipment images
│   └── profiles/             # User profile images
│
├── logs/
│   ├── errors.log            # PHP error logs
│   ├── auth.log              # Authentication logs (login attempts, failures)
│   └── database.log          # Database query logs
│
├── README.md                 # Project documentation
├── Requirements.md           # Project requirements
├── AppFlow.md               # User journey flows
├── TechStack.md             # This file
└── .env                      # Environment variables (local, gitignored)
```

### Folder Descriptions

| Folder | Purpose |
|--------|---------|
| **config/** | Database connections, constants, and app configuration. PDO setup lives here. |
| **includes/** | Reusable HTML components (header, footer, navbar) included via PHP `require_once()`. |
| **public/** | Public-facing PHP pages and API endpoints. Entry point for user requests. |
| **public/api/** | JSON API endpoints that handle AJAX requests from JavaScript. |
| **assets/css/** | Stylesheets. Tailwind CSS loaded via CDN in HTML; custom overrides here. |
| **assets/js/** | Vanilla JavaScript modules for client-side logic (no frameworks). |
| **assets/images/** | SVG icons, logos, and background images. |
| **database/** | SQL schema, seed data, and migration scripts. |
| **logs/** | Application and database logs for debugging. |

---

## Database Schema

### MySQL Database Creation

```sql
-- Create the LankanLens database
CREATE DATABASE IF NOT EXISTS lankanlens CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lankanlens;
```

### Table 1: `shops`

Stores information about camera rental shops across Sri Lanka.

```sql
CREATE TABLE shops (
    shop_id INT AUTO_INCREMENT PRIMARY KEY,
    shop_name VARCHAR(255) NOT NULL,
    shop_description TEXT,
    primary_city VARCHAR(100) NOT NULL,
    shop_address VARCHAR(255),
    shop_phone VARCHAR(20) NOT NULL UNIQUE,
    whatsapp_number VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    website_url VARCHAR(255),
    established_year INT,
    average_rating DECIMAL(3, 2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_city (primary_city),
    INDEX idx_phone (shop_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table 2: `equipment_categories`

Categories for organizing equipment types (lenses, bodies, lighting, etc.).

```sql
CREATE TABLE equipment_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_slug VARCHAR(100) NOT NULL UNIQUE,
    category_description TEXT,
    icon_url VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (category_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table 3: `equipment`

Detailed equipment/gear items (camera bodies, lenses, lighting gear, accessories).

```sql
CREATE TABLE equipment (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    equipment_name VARCHAR(255) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    model_number VARCHAR(100),
    equipment_type VARCHAR(100),
    description TEXT,
    specifications JSON,
    condition_status ENUM('excellent', 'good', 'fair') DEFAULT 'good',
    image_url VARCHAR(255),
    shop_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES equipment_categories(category_id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(shop_id) ON DELETE CASCADE,
    INDEX idx_brand (brand),
    INDEX idx_category (category_id),
    INDEX idx_shop (shop_id),
    FULLTEXT INDEX ft_search (equipment_name, brand, model_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table 4: `inventory`

Tracks equipment availability and pricing for each shop.

```sql
CREATE TABLE inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    shop_id INT NOT NULL,
    available_quantity INT DEFAULT 0,
    total_quantity INT DEFAULT 0,
    daily_rate_lkr DECIMAL(10, 2) NOT NULL,
    weekly_rate_lkr DECIMAL(10, 2),
    monthly_rate_lkr DECIMAL(10, 2),
    delivery_available BOOLEAN DEFAULT FALSE,
    insurance_included BOOLEAN DEFAULT FALSE,
    deposit_required_lkr DECIMAL(10, 2) DEFAULT 0.00,
    min_rental_days INT DEFAULT 1,
    max_rental_days INT DEFAULT 365,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(shop_id) ON DELETE CASCADE,
    UNIQUE KEY unique_equipment_shop (equipment_id, shop_id),
    INDEX idx_available (available_quantity),
    INDEX idx_shop_id (shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table 5: `booking_requests`

Logs all user booking requests (for analytics and follow-up).

```sql
CREATE TABLE booking_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(255) NOT NULL,
    user_contact VARCHAR(20),
    user_email VARCHAR(255),
    equipment_id INT NOT NULL,
    shop_id INT NOT NULL,
    rental_start_date DATE,
    rental_duration_days INT NOT NULL,
    additional_notes TEXT,
    request_status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    estimated_total_lkr DECIMAL(10, 2),
    whatsapp_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(shop_id) ON DELETE CASCADE,
    INDEX idx_status (request_status),
    INDEX idx_created (created_at),
    INDEX idx_shop (shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table 6: `shop_locations`

Extended location data for shops (supports multi-city shops in the future).

```sql
CREATE TABLE shop_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    city_name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(shop_id) ON DELETE CASCADE,
    INDEX idx_city (city_name),
    INDEX idx_shop (shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table 7: `shop_reviews`

User reviews and ratings for shops.

```sql
CREATE TABLE shop_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    equipment_rented VARCHAR(255),
    verified_rental BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(shop_id) ON DELETE CASCADE,
    INDEX idx_shop (shop_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table 8: `search_logs`

Analytics: Logs user searches for insights.

```sql
CREATE TABLE search_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    search_term VARCHAR(255),
    search_city VARCHAR(100),
    search_date DATE,
    result_count INT,
    user_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_term (search_term),
    INDEX idx_date (search_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table 9: `users`

Authentication and user management for customers, vendors, and admins.

```sql
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'vendor', 'admin') DEFAULT 'customer' NOT NULL,
    status ENUM('pending', 'active', 'suspended', 'rejected') DEFAULT 'active' NOT NULL,
    whatsapp_number VARCHAR(20),
    phone VARCHAR(20),
    shop_name VARCHAR(255),
    profile_image_url VARCHAR(255),
    preferred_cities JSON,
    preferred_equipment_types JSON,
    email_verified BOOLEAN DEFAULT FALSE,
    failed_login_attempts INT DEFAULT 0,
    last_failed_login TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    last_login_at TIMESTAMP NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_role_status (role, status),
    INDEX idx_remember_token (remember_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Schema Notes:**
- `password_hash`: Use `password_hash($password, PASSWORD_BCRYPT)` in PHP
- `role`: Determines user permissions (customer, vendor, admin)
- `status`: Controls account activation state
  - Customers: Default to 'active' upon registration
  - Vendors: Default to 'pending' until admin approves
  - Admins: Always 'active'
- `failed_login_attempts`: Track failed logins for security (lock after 5 attempts)
- `remember_token`: For \"Remember Me\" functionality (30-day sessions)
- `approved_by`: Foreign key to admin user who approved vendor account

### Table 10: `sessions` (Optional - Database Session Storage)

Optional table for storing PHP sessions in database instead of file system.

```sql
CREATE TABLE sessions (
    session_id VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT,
    last_activity INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table 11: `admin_logs`

Audit trail for admin actions (vendor approvals, content moderation, user management).

```sql
CREATE TABLE admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    target_user_id INT NULL,
    target_equipment_id INT NULL,
    action_details JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (target_equipment_id) REFERENCES equipment(equipment_id) ON DELETE SET NULL,
    INDEX idx_admin (admin_user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Sample Data Insert Statements

```sql
-- Insert sample categories
INSERT INTO equipment_categories (category_name, category_slug, category_description) VALUES
('Camera Bodies', 'camera-bodies', 'DSLR and Mirrorless cameras'),
('Lenses', 'lenses', 'Various focal length lenses'),
('Lighting Gear', 'lighting-gear', 'Studio and continuous lighting'),
('Accessories', 'accessories', 'Tripods, stands, bags, and more');

-- Insert sample shop
INSERT INTO shops (shop_name, primary_city, shop_phone, whatsapp_number, email) VALUES
('Pro Lens Rental', 'Colombo', '+94701234567', '+94701234567', 'info@prolensrental.lk');

-- Insert sample admin user
INSERT INTO users (full_name, email, password_hash, role, status) VALUES
('Admin User', 'admin@lankanlens.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
-- Password: 'password' (for testing only - CHANGE IN PRODUCTION)

-- Insert sample customer user
INSERT INTO users (full_name, email, password_hash, role, status) VALUES
('John Doe', 'customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active');

-- Insert sample vendor user (pending approval)
INSERT INTO users (full_name, email, password_hash, role, status, shop_name) VALUES
('Jane Smith', 'vendor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendor', 'pending', 'Epic Camera Rentals');

-- Insert sample equipment
INSERT INTO equipment (category_id, equipment_name, brand, model_number, equipment_type, description, shop_id, condition_status) VALUES
(2, 'Sony A7R IV', 'Sony', 'ILCE-7RM4', 'Full-Frame Mirrorless', '61MP Full-Frame Mirrorless Camera', 1, 'excellent');

-- Insert sample inventory
INSERT INTO inventory (equipment_id, shop_id, available_quantity, total_quantity, daily_rate_lkr, weekly_rate_lkr, monthly_rate_lkr) VALUES
(1, 1, 2, 2, 15500, 100000, 350000);
```

---

## Authentication Middleware & Gated Content Logic

### Authentication Helper Functions

**File:** `includes/auth_helper.php`

This file provides middleware functions for checking authentication state and protecting routes.

```php
<?php
/**
 * Authentication Helper Functions
 * Provides middleware for route protection and role-based access control
 * 
 * @package LankanLens
 * @version 1.0
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user role
 * @return string|null ('customer', 'vendor', 'admin', or null if not logged in)
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user status
 * @return string|null ('active', 'pending', 'suspended', 'rejected', or null)
 */
function getUserStatus() {
    return $_SESSION['status'] ?? null;
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's full name
 * @return string|null
 */
function getCurrentUserName() {
    return $_SESSION['full_name'] ?? null;
}

/**
 * Check if user is a customer
 * @return bool
 */
function isCustomer() {
    return isLoggedIn() && getUserRole() === 'customer';
}

/**
 * Check if user is an active vendor
 * @return bool
 */
function isVendor() {
    return isLoggedIn() && getUserRole() === 'vendor' && getUserStatus() === 'active';
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && getUserRole() === 'admin';
}

/**
 * Require login - redirect to login page if not authenticated
 * @param string|null $returnUrl URL to return to after login
 */
function requireLogin($returnUrl = null) {
    if (!isLoggedIn()) {
        $returnUrl = $returnUrl ?? $_SERVER['REQUEST_URI'];
        $_SESSION['return_url'] = $returnUrl;
        header('Location: /public/login.php');
        exit;
    }
}

/**
 * Require specific role - redirect if user doesn't have required role
 * @param string $requiredRole ('customer', 'vendor', 'admin')
 */
function requireRole($requiredRole) {
    requireLogin();
    
    if (getUserRole() !== $requiredRole) {
        header('Location: /public/unauthorized.php');
        exit;
    }
    
    // Additional check for vendors - must be active
    if ($requiredRole === 'vendor' && getUserStatus() !== 'active') {
        header('Location: /public/vendor-pending.php');
        exit;
    }
}

/**
 * Require admin access
 */
function requireAdmin() {
    requireRole('admin');
}

/**
 * Require active vendor access
 */
function requireActiveVendor() {
    requireRole('vendor');
}

/**
 * Logout user - destroy session and redirect
 */
function logout() {
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/');
    header('Location: /public/index.php');
    exit;
}

/**
 * Check if current user can access a resource
 * @param string $resourceType ('equipment', 'shop', 'user')
 * @param int $resourceOwnerId Owner of the resource
 * @return bool
 */
function canAccess($resourceType, $resourceOwnerId) {
    if (isAdmin()) {
        return true; // Admin has access to everything
    }
    
    return getCurrentUserId() === $resourceOwnerId;
}
?>
```

### Gated Content Implementation

**File:** `public/product.php` (example usage)

This shows how to conditionally display shop details based on authentication state.

```php
<?php
/**
 * Product Detail Page
 * Displays equipment details with gated shop information
 */

require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../config/database.php';

$is_logged_in = isLoggedIn();
$equipment_id = $_GET['id'] ?? null;

// Fetch equipment details
$db = new Database();
$equipment = $db->fetchOne(
    \"SELECT e.*, i.daily_rate_lkr, i.weekly_rate_lkr, i.available_quantity,
            s.shop_name, s.whatsapp_number, s.shop_phone, s.primary_city, s.shop_address
     FROM equipment e 
     JOIN inventory i ON e.equipment_id = i.equipment_id
     JOIN shops s ON e.shop_id = s.shop_id 
     WHERE e.equipment_id = ?\",
    [$equipment_id]
);

if (!$equipment) {
    header('Location: /public/404.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang=\"en\">
<head>
    <title><?php echo htmlspecialchars($equipment['equipment_name']); ?> - LankanLens</title>
    <script src=\"https://cdn.tailwindcss.com\"></script>
</head>
<body>

<!-- Equipment Details (ALWAYS VISIBLE TO EVERYONE) -->
<div class=\"equipment-info\">
    <h1 class=\"text-3xl font-bold\"><?php echo htmlspecialchars($equipment['equipment_name']); ?></h1>
    <p class=\"text-xl\">Brand: <?php echo htmlspecialchars($equipment['brand']); ?></p>
    <p class=\"text-lg\">Daily Rate: ₨<?php echo number_format($equipment['daily_rate_lkr'], 2); ?></p>
    <p>Condition: <span class=\"badge\"><?php echo htmlspecialchars($equipment['condition_status']); ?></span></p>
    <p>Availability: <?php echo $equipment['available_quantity'] > 0 ? 'In Stock' : 'Unavailable'; ?></p>
</div>

<!-- Shop Details (GATED CONTENT - BLURRED FOR GUESTS) -->
<div class=\"shop-details-container mt-6 <?php echo $is_logged_in ? '' : 'relative'; ?>\">
    <?php if ($is_logged_in): ?>
        <!-- AUTHENTICATED USER: Show full shop details -->
        <div class=\"shop-info bg-gray-800 p-6 rounded-lg\">
            <h2 class=\"text-2xl font-semibold mb-4\">Shop Information</h2>
            <p><strong>Shop Name:</strong> <?php echo htmlspecialchars($equipment['shop_name']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($equipment['primary_city']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($equipment['shop_address']); ?></p>
            <p><strong>WhatsApp:</strong> 
                <a href=\"https://wa.me/<?php echo $equipment['whatsapp_number']; ?>\" 
                   class=\"text-blue-400 hover:underline\">
                    <?php echo htmlspecialchars($equipment['whatsapp_number']); ?>
                </a>
            </p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($equipment['shop_phone']); ?></p>
            
            <button class=\"rent-now-btn bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg mt-4\"
                    onclick=\"openBookingModal(<?php echo $equipment['equipment_id']; ?>)\">
                Rent Now via WhatsApp
            </button>
        </div>
    <?php else: ?>
        <!-- GUEST USER: Blurred content with login prompt -->
        <div class=\"shop-info bg-gray-800 p-6 rounded-lg blur-filter\">
            <h2 class=\"text-2xl font-semibold mb-4\">Shop Information</h2>
            <p><strong>Shop Name:</strong> ████████ ███████ ███████</p>
            <p><strong>Location:</strong> ████████</p>
            <p><strong>Address:</strong> ████████████████████</p>
            <p><strong>WhatsApp:</strong> ███████████</p>
            <p><strong>Phone:</strong> ███████████</p>
        </div>
        
        <!-- Login Overlay -->
        <div class=\"login-overlay absolute inset-0 bg-black bg-opacity-70 flex flex-col items-center justify-center rounded-lg\">
            <div class=\"text-center\">
                <span class=\"lock-icon text-6xl mb-4\">🔒</span>
                <h3 class=\"text-2xl font-bold text-white mb-2\">Login to View Shop Details</h3>
                <p class=\"text-gray-300 mb-6\">Create a free account to contact shop owners and rent equipment</p>
                <a href=\"/public/login.php?return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>\" 
                   class=\"btn-primary bg-orange-500 hover:bg-orange-600 text-white px-8 py-3 rounded-lg inline-block\">
                    Login to Rent
                </a>
                <p class=\"mt-4\">
                    <a href=\"/public/register.php?return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>\" 
                       class=\"text-blue-400 hover:underline\">
                        Don't have an account? Sign Up
                    </a>
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.blur-filter {
    filter: blur(8px);
    pointer-events: none;
    user-select: none;
}

.login-overlay {
    pointer-events: all;
}
</style>

</body>
</html>
```

### Login Page Implementation

**File:** `public/login.php`

```php
<?php
/**
 * User Login Page
 * Handles authentication for customers, vendors, and admins
 */

require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../config/database.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $role = getUserRole();
    if ($role === 'admin') {
        header('Location: /admin/dashboard.php');
    } elseif ($role === 'vendor' && getUserStatus() === 'active') {
        header('Location: /vendor/dashboard.php');
    } else {
        header('Location: /public/index.php');
    }
    exit;
}

$error_message = '';
$return_url = $_GET['return'] ?? $_SESSION['return_url'] ?? '/public/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } else {
        $db = new Database();
        
        // Fetch user by email
        $user = $db->fetchOne(
            \"SELECT user_id, full_name, email, password_hash, role, status, 
                    failed_login_attempts, last_failed_login
             FROM users 
             WHERE email = ?\",
            [$email]
        );
        
        if (!$user) {
            $error_message = 'Invalid email or password.';
        } else {
            // Check if account is locked (5 failed attempts in 15 minutes)
            if ($user['failed_login_attempts'] >= 5) {
                $lockout_time = strtotime($user['last_failed_login']) + (15 * 60);
                if (time() < $lockout_time) {
                    $minutes_left = ceil(($lockout_time - time()) / 60);
                    $error_message = \"Account temporarily locked. Try again in {$minutes_left} minutes.\";
                } else {
                    // Reset failed attempts after lockout period
                    $db->query(\"UPDATE users SET failed_login_attempts = 0 WHERE user_id = ?\", [$user['user_id']]);
                }
            }
            
            // Verify password
            if (empty($error_message) && password_verify($password, $user['password_hash'])) {
                // Check account status
                if ($user['status'] === 'suspended') {
                    $error_message = 'Your account has been suspended. Please contact admin.';
                } elseif ($user['status'] === 'rejected') {
                    $error_message = 'Your vendor application was not approved. Contact admin for details.';
                } else {
                    // Successful login
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['status'] = $user['status'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // Reset failed login attempts
                    $db->query(\"UPDATE users SET failed_login_attempts = 0, last_login_at = NOW() WHERE user_id = ?\", 
                              [$user['user_id']]);
                    
                    // Handle \"Remember Me\"
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                        $db->query(\"UPDATE users SET remember_token = ? WHERE user_id = ?\", [$token, $user['user_id']]);
                    }
                    
                    // Redirect based on role and status
                    if ($user['role'] === 'admin') {
                        header('Location: /admin/dashboard.php');
                    } elseif ($user['role'] === 'vendor' && $user['status'] === 'pending') {
                        header('Location: /public/vendor-pending.php');
                    } elseif ($user['role'] === 'vendor' && $user['status'] === 'active') {
                        header('Location: /vendor/dashboard.php');
                    } else {
                        // Customer or default
                        header('Location: ' . $return_url);
                    }
                    exit;
                }
            } else {
                // Invalid password - increment failed attempts
                if (empty($error_message)) {
                    $db->query(
                        \"UPDATE users SET failed_login_attempts = failed_login_attempts + 1, 
                                         last_failed_login = NOW() 
                         WHERE user_id = ?\",
                        [$user['user_id']]
                    );
                    $error_message = 'Invalid email or password.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Login - LankanLens</title>
    <script src=\"https://cdn.tailwindcss.com\"></script>
</head>
<body class=\"bg-gray-900 text-white\">
    
<div class=\"min-h-screen flex items-center justify-center\">
    <div class=\"bg-gray-800 p-8 rounded-lg shadow-lg max-w-md w-full\">
        <h1 class=\"text-3xl font-bold mb-6 text-center\">Login to LankanLens</h1>
        
        <?php if ($error_message): ?>
            <div class=\"bg-red-500 bg-opacity-20 border border-red-500 text-red-200 px-4 py-3 rounded mb-4\">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method=\"POST\" action=\"\" class=\"space-y-4\">
            <div>
                <label for=\"email\" class=\"block text-sm font-medium mb-2\">Email Address</label>
                <input type=\"email\" name=\"email\" id=\"email\" required
                       class=\"w-full px-4 py-2 bg-gray-700 rounded focus:outline-none focus:ring-2 focus:ring-orange-500\"
                       placeholder=\"your.email@example.com\"
                       value=\"<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>\">
            </div>
            
            <div>
                <label for=\"password\" class=\"block text-sm font-medium mb-2\">Password</label>
                <input type=\"password\" name=\"password\" id=\"password\" required
                       class=\"w-full px-4 py-2 bg-gray-700 rounded focus:outline-none focus:ring-2 focus:ring-orange-500\"
                       placeholder=\"Enter your password\">
            </div>
            
            <div class=\"flex items-center\">
                <input type=\"checkbox\" name=\"remember\" id=\"remember\" class=\"mr-2\">
                <label for=\"remember\" class=\"text-sm\">Remember me for 30 days</label>
            </div>
            
            <button type=\"submit\" 
                    class=\"w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition\">
                Login
            </button>
        </form>
        
        <p class=\"text-center mt-6 text-gray-400\">
            Don't have an account? 
            <a href=\"/public/register.php?return=<?php echo urlencode($return_url); ?>\" 
               class=\"text-blue-400 hover:underline\">Sign Up</a>
        </p>
    </div>
</div>

</body>
</html>
```

### Registration Page Implementation

**File:** `public/register.php`

```php
<?php
/**
 * User Registration Page
 * Handles new user sign-ups with role selection
 */

require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../config/database.php';

// If already logged in, redirect
if (isLoggedIn()) {
    header('Location: /public/index.php');
    exit;
}

$error_message = '';
$success_message = '';
$return_url = $_GET['return'] ?? '/public/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $shop_name = trim($_POST['shop_name'] ?? '');
    
    // Validation
    if (strlen($full_name) < 3 || strlen($full_name) > 255) {
        $error_message = 'Full name must be between 3 and 255 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (!in_array($role, ['customer', 'vendor'])) {
        $error_message = 'Invalid role selected.';
    } else {
        $db = new Database();
        
        // Check if email already exists
        $existing = $db->fetchOne(\"SELECT user_id FROM users WHERE email = ?\", [$email]);
        
        if ($existing) {
            $error_message = 'An account with this email already exists.';
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Determine status based on role
            $status = ($role === 'vendor') ? 'pending' : 'active';
            
            // Insert user
            $result = $db->query(
                \"INSERT INTO users (full_name, email, password_hash, role, status, shop_name) 
                 VALUES (?, ?, ?, ?, ?, ?)\",
                [$full_name, $email, $password_hash, $role, $status, $shop_name]
            );
            
            if ($result) {
                $user_id = $db->lastInsertId();
                
                // Create session for customer, redirect vendor to pending page
                if ($role === 'customer') {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $role;
                    $_SESSION['status'] = $status;
                    $_SESSION['full_name'] = $full_name;
                    
                    header('Location: ' . $return_url);
                    exit;
                } else {
                    // Vendor - redirect to pending page
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $role;
                    $_SESSION['status'] = $status;
                    $_SESSION['full_name'] = $full_name;
                    
                    header('Location: /public/vendor-pending.php');
                    exit;
                }
            } else {
                $error_message = 'Failed to create account. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Sign Up - LankanLens</title>
    <script src=\"https://cdn.tailwindcss.com\"></script>
</head>
<body class=\"bg-gray-900 text-white\">

<div class=\"min-h-screen flex items-center justify-center py-12\">
    <div class=\"bg-gray-800 p-8 rounded-lg shadow-lg max-w-md w-full\">
        <h1 class=\"text-3xl font-bold mb-6 text-center\">Create Your Account</h1>
        
        <?php if ($error_message): ?>
            <div class=\"bg-red-500 bg-opacity-20 border border-red-500 text-red-200 px-4 py-3 rounded mb-4\">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method=\"POST\" action=\"\" class=\"space-y-4\">
            <div>
                <label for=\"full_name\" class=\"block text-sm font-medium mb-2\">Full Name</label>
                <input type=\"text\" name=\"full_name\" id=\"full_name\" required
                       class=\"w-full px-4 py-2 bg-gray-700 rounded focus:outline-none focus:ring-2 focus:ring-orange-500\"
                       placeholder=\"Enter your full name\"
                       value=\"<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>\">
            </div>
            
            <div>
                <label for=\"email\" class=\"block text-sm font-medium mb-2\">Email Address</label>
                <input type=\"email\" name=\"email\" id=\"email\" required
                       class=\"w-full px-4 py-2 bg-gray-700 rounded focus:outline-none focus:ring-2 focus:ring-orange-500\"
                       placeholder=\"your.email@example.com\"
                       value=\"<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>\">
            </div>
            
            <div>
                <label for=\"password\" class=\"block text-sm font-medium mb-2\">Password</label>
                <input type=\"password\" name=\"password\" id=\"password\" required minlength=\"8\"
                       class=\"w-full px-4 py-2 bg-gray-700 rounded focus:outline-none focus:ring-2 focus:ring-orange-500\"
                       placeholder=\"Create a strong password (min 8 chars)\">
            </div>
            
            <div>
                <label for=\"confirm_password\" class=\"block text-sm font-medium mb-2\">Confirm Password</label>
                <input type=\"password\" name=\"confirm_password\" id=\"confirm_password\" required
                       class=\"w-full px-4 py-2 bg-gray-700 rounded focus:outline-none focus:ring-2 focus:ring-orange-500\"
                       placeholder=\"Re-enter your password\">
            </div>
            
            <div>
                <label class=\"block text-sm font-medium mb-3\">I am registering as:</label>
                <div class=\"space-y-2\">
                    <div class=\"flex items-start\">
                        <input type=\"radio\" name=\"role\" value=\"customer\" id=\"role_customer\" 
                               checked class=\"mt-1 mr-3\">
                        <label for=\"role_customer\">
                            <span class=\"font-medium\">Customer</span>
                            <p class=\"text-sm text-gray-400\">I want to rent camera equipment</p>
                        </label>
                    </div>
                    <div class=\"flex items-start\">
                        <input type=\"radio\" name=\"role\" value=\"vendor\" id=\"role_vendor\" 
                               class=\"mt-1 mr-3\" onchange=\"toggleShopNameField()\">
                        <label for=\"role_vendor\">
                            <span class=\"font-medium\">Vendor</span>
                            <p class=\"text-sm text-gray-400\">I want to list my equipment for rent (requires admin approval)</p>
                        </label>
                    </div>
                </div>
            </div>
            
            <div id=\"shop_name_field\" style=\"display: none;\">
                <label for=\"shop_name\" class=\"block text-sm font-medium mb-2\">Shop Name (Optional)</label>
                <input type=\"text\" name=\"shop_name\" id=\"shop_name\"
                       class=\"w-full px-4 py-2 bg-gray-700 rounded focus:outline-none focus:ring-2 focus:ring-orange-500\"
                       placeholder=\"Your shop or business name\"
                       value=\"<?php echo htmlspecialchars($_POST['shop_name'] ?? ''); ?>\">
            </div>
            
            <button type=\"submit\" 
                    class=\"w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition\">
                Create Account
            </button>
        </form>
        
        <p class=\"text-center mt-6 text-gray-400\">
            Already have an account? 
            <a href=\"/public/login.php?return=<?php echo urlencode($return_url); ?>\" 
               class=\"text-blue-400 hover:underline\">Login</a>
        </p>
    </div>
</div>

<script>
function toggleShopNameField() {
    const vendorRadio = document.getElementById('role_vendor');
    const shopNameField = document.getElementById('shop_name_field');
    shopNameField.style.display = vendorRadio.checked ? 'block' : 'none';
}
</script>

</body>
</html>
```

### Protecting Routes - Usage Examples

**Vendor Dashboard Protection:**
```php
<?php
// vendor/dashboard.php
require_once __DIR__ . '/../includes/auth_helper.php';
requireActiveVendor(); // Only active vendors can access

// Dashboard code here...
?>
```

**Admin Panel Protection:**
```php
<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/auth_helper.php';
requireAdmin(); // Only admins can access

// Admin panel code here...
?>
```

**Product Page with Gated Content:**
```php
<?php
// public/product.php
require_once __DIR__ . '/../includes/auth_helper.php';

$is_logged_in = isLoggedIn();

// Fetch equipment and shop details...

// In HTML, conditionally render shop details:
if ($is_logged_in) {
    // Show full shop contact info
} else {
    // Show blurred content with login CTA
}
?>
```

---

## Coding Standards

### 1. PHP (Version 8.x) & PDO Database Connections

#### Database Connection Class (`config/database.php`)

Create a PDO wrapper class with methods for `query()`, `fetchOne()`, `fetchAll()`, `insert()`, `update()`, and `delete()`.

**Key Points:**
- Use prepared statements with parameter binding to prevent SQL injection
- Load credentials from `.env` file
- Set `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` for error handling
- Use charset `utf8mb4` for full Unicode support

#### Configuration File (`config/config.php`)

Define application constants and load `.env` file:
- Database credentials from `$_ENV`
- WhatsApp base URL
- Pricing discounts and pagination settings
- Error logging configuration
- Secure session settings (httponly, samesite)

### 2. Vanilla JavaScript Standards

#### WhatsApp Message Generator (`assets/js/booking.js`)

Create a `WhatsAppBooking` class with these methods:
- `openBookingModal()` — Display form modal
- `handleSendRequest()` — Validate form inputs
- `generateMessage()` — Build WhatsApp message with equipment name, duration, user name
- `openWhatsApp()` — Redirect to WhatsApp Web/App using `https://wa.me/[phone]?text=[message]`
- `logBookingRequest()` — POST to `/api/booking-api.php` for analytics

**Pattern:** Event delegation on booking buttons, form validation, URL encoding of message

### 3. Tailwind CSS (CDN + Custom Overrides)

#### HTML Head Setup (Include in all pages)

```html
<!-- Tailwind CSS via CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Optional: Custom Tailwind config -->
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    lankanlens: '#0066FF',
                    success: '#00AA44',
                }
            }
        }
    }
</script>

<!-- Custom CSS overrides -->
<link rel="stylesheet" href="/assets/css/styles.css">
```

#### Custom CSS Overrides (`assets/css/styles.css`)

Define animations and overrides:
- **Animations:** `spin`, `fadeIn`, `shake`, `slideUp`
- **Classes:** `.card-hover` (lift on hover), `.spinner`, `.shake`, `.slide-up`
- **Modal:** `.modal-overlay` with backdrop blur
- **Scrollbar:** Custom styling for modern browsers
- **Colors:** CSS custom properties (--color-primary, --color-success, --color-danger)

### 4. PHP Coding Standards

#### File Headers
Every PHP file should start with:
```php
<?php
/**
 * [File Name]
 * [Brief Description]
 * 
 * @package LankanLens
 * @version 1.0
 * @author [Your Name]
 */

// Require dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Your code here...
?>
```

#### Security Standards
- **SQL Injection Prevention:** Always use PDO prepared statements
  ```php
  // ✅ CORRECT
  $stmt = $db->query("SELECT * FROM shops WHERE city = ?", [$city]);
  
  // ❌ WRONG
  $stmt = $db->query("SELECT * FROM shops WHERE city = '$city'");
  ```

- **XSS Prevention:** Sanitize output
  ```php
  // ✅ CORRECT
  echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
  
  // ❌ WRONG
  echo $user_input;
  ```

- **CSRF Protection:** Include tokens in forms
  ```php
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
  ```

#### API Response Format
All API endpoints should return JSON with this structure:
```php
header('Content-Type: application/json');
echo json_encode([
    'success' => true/false,
    'message' => 'Human-readable message',
    'data' => [...],  // Only on success
    'error' => 'Error details'  // Only on failure
]);
```

---

## Environment Setup

### Prerequisites
- **PHP:** 8.0 or higher
- **MySQL:** 5.7 or higher
- **Apache:** 2.4 or higher (included in XAMPP)
- **Browser:** Modern browser with ES6+ support

### Step 1: Install XAMPP/WAMP

#### Windows:
1. Download XAMPP from [apachefriends.org](https://www.apachefriends.org/)
2. Run the installer: `xampp-windows-x64-[version]-installer.exe`
3. Install to `C:\xampp` (default)
4. During installation, ensure **Apache** and **MySQL** are selected

#### macOS:
1. Download XAMPP for macOS from [apachefriends.org](https://www.apachefriends.org/)
2. Open the `.dmg` file and drag XAMPP to `/Applications`
3. Launch XAMPP Control Panel

#### Linux:
```bash
cd /tmp
wget https://www.apachefriends.org/xampp-files/[version]/xampp-linux-[version]-installer.run
chmod +x xampp-linux-[version]-installer.run
sudo ./xampp-linux-[version]-installer.run
```

### Step 2: Start Apache & MySQL

#### Windows (XAMPP Control Panel):
1. Open `C:\xampp\xampp-control-panel.exe`
2. Click "Start" next to Apache
3. Click "Start" next to MySQL
4. Verify both show green status

#### macOS/Linux:
```bash
sudo /Applications/XAMPP/xamppfiles/bin/apachectl start
sudo /Applications/XAMPP/xamppfiles/bin/mysqld start
```

Or use the control panel GUI.

### Step 3: Clone/Setup Project

```bash
# Navigate to XAMPP web root
cd C:\xampp\htdocs  # Windows
# OR
cd /Applications/XAMPP/htdocs  # macOS
# OR
cd /opt/lampp/htdocs  # Linux

# Create project directory
mkdir LankanLens
cd LankanLens

# Initialize project files (or clone from git)
git clone <repo-url> .
```

### Step 4: Create MySQL Database

```bash
# Open MySQL CLI
# Windows:
C:\xampp\mysql\bin\mysql -u root -p

# macOS/Linux:
mysql -u root -p
```

Then execute:
```sql
CREATE DATABASE IF NOT EXISTS lankanlens CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lankanlens;

-- Import schema
SOURCE /path/to/database/schema.sql;
```

### Step 5: Configure Environment Variables

#### Create `.env` file in project root:

```bash
# .env (do NOT commit this file)

# Database Configuration
DB_HOST=localhost
DB_NAME=lankanlens
DB_USER=root
DB_PASS=

# Application Settings
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/LankanLens

# WhatsApp Configuration
WHATSAPP_BASE_URL=https://wa.me/

# File Upload
UPLOAD_DIR=/uploads/
MAX_FILE_SIZE=5242880
```

#### Create `.env.example` for the team:

```bash
# .env.example (safe to commit)

DB_HOST=localhost
DB_NAME=lankanlens
DB_USER=root
DB_PASS=

APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/LankanLens

WHATSAPP_BASE_URL=https://wa.me/

UPLOAD_DIR=/uploads/
MAX_FILE_SIZE=5242880
```

#### Add to `.gitignore`:
```
.env
logs/*.log
uploads/*
node_modules/
vendor/
```

### Step 6: Create Required Directories

```bash
# From project root
mkdir -p logs
mkdir -p uploads
mkdir -p assets/images/icons
chmod 755 logs uploads
```

### Step 7: Load .env in config/config.php

```php
<?php
// config/config.php

// Load .env file
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Use environment variables
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'lankanlens');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
?>
```

### Step 8: Test Installation

1. Open browser: `http://localhost/LankanLens/public/index.php`
2. You should see the home page
3. Check Apache error logs if issues occur:
   - Windows: `C:\xampp\apache\logs\error.log`
   - macOS: `/Applications/XAMPP/xamppfiles/logs/error.log`

---

## Quick Start Guide

### 1. Initial Setup (5 minutes)

```bash
# 1. Download and install XAMPP
# 2. Start Apache & MySQL
# 3. Navigate to project directory
cd C:\xampp\htdocs\LankanLens

# 4. Copy .env.example to .env
cp .env.example .env

# 5. Edit .env with your credentials
# DB_USER=root
# DB_PASS=(leave blank or your password)
```

### 2. Create Database (2 minutes)

```bash
# Open MySQL CLI
mysql -u root -p

# In MySQL:
CREATE DATABASE lankanlens CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lankanlens;
SOURCE database/schema.sql;
EXIT;
```

### 3. Populate Sample Data (1 minute)

```bash
mysql -u root -p lankanlens < database/seeds/shops-seed.sql
mysql -u root -p lankanlens < database/seeds/equipment-seed.sql
mysql -u root -p lankanlens < database/seeds/inventory-seed.sql
```

### 4. Start Developing

```bash
# Open in browser
http://localhost/LankanLens/public/index.php

# Start editing files:
# - public/index.php (home page)
# - assets/js/booking.js (booking logic)
# - assets/css/styles.css (custom styles)
# - config/database.php (database queries)
```

### 5. Useful Commands

```bash
# View MySQL logs
tail -f /var/log/mysql/mysql.log

# View PHP error logs
tail -f C:\xampp\apache\logs\error.log

# Test database connection
php -r "require 'config/database.php'; $db = new Database();"

# Format code (if using PHP Code Sniffer)
phpcs --standard=PSR12 assets/js/ public/ config/
```

---

## Additional Resources

- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [XAMPP Documentation](https://www.apachefriends.org/faq.html)
- [Vanilla JS Tips](https://www.sitepoint.com/premium/courses/vanilla-javascript-the-complete-guide)

---

**End of TechStack Document**
