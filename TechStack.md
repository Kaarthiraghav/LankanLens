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
│   └── error-handler.php      # Global error handling
│
├── public/
│   ├── index.php             # Landing page / Home
│   ├── search.php            # Search results page
│   ├── product.php           # Product detail page
│   ├── api/
│   │   ├── search-api.php    # Search endpoint (JSON response)
│   │   ├── booking-api.php   # Booking request endpoint
│   │   ├── shop-list-api.php # Shop data endpoint
│   │   └── equipment-api.php # Equipment data endpoint
│   └── thank-you.php         # Post-booking confirmation page
│
├── assets/
│   ├── css/
│   │   ├── styles.css        # Global custom CSS (overrides for Tailwind)
│   │   └── tailwind.config.js # Tailwind configuration (optional)
│   │
│   ├── js/
│   │   ├── search.js         # Search form logic & autocomplete
│   │   ├── booking.js        # Booking modal & WhatsApp generator
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
│   │   └── inventory-seed.sql# Sample inventory data
│   └── migrations/
│       ├── 001-initial-schema.sql
│       └── 002-add-ratings-table.sql
│
├── logs/
│   ├── errors.log            # PHP error logs
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

-- Insert sample equipment
INSERT INTO equipment (category_id, equipment_name, brand, model_number, equipment_type, description, shop_id, condition_status) VALUES
(2, 'Sony A7R IV', 'Sony', 'ILCE-7RM4', 'Full-Frame Mirrorless', '61MP Full-Frame Mirrorless Camera', 1, 'excellent');

-- Insert sample inventory
INSERT INTO inventory (equipment_id, shop_id, available_quantity, total_quantity, daily_rate_lkr, weekly_rate_lkr, monthly_rate_lkr) VALUES
(1, 1, 2, 2, 15500, 100000, 350000);
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
