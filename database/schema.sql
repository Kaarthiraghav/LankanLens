-- LankanLens Database Schema
-- MySQL 5.7+ with UTF8MB4 support
-- Last Updated: January 26, 2026
-- Total Tables: 11

USE lankanlens;

-- ============================================================
-- Table 1: shops
-- Stores camera rental shop information across Sri Lanka
-- ============================================================

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

-- ============================================================
-- Table 2: equipment_categories
-- Categories for organizing equipment (lenses, bodies, lighting, etc.)
-- ============================================================

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

-- ============================================================
-- Table 3: equipment
-- Detailed equipment/gear items (cameras, lenses, lighting, accessories)
-- ============================================================

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

-- ============================================================
-- Table 4: inventory
-- Tracks equipment availability and pricing for each shop
-- ============================================================

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

-- ============================================================
-- Table 5: booking_requests
-- Logs all user booking requests for analytics and follow-up
-- ============================================================

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

-- ============================================================
-- Table 6: shop_locations
-- Extended location data for shops (supports multi-city shops)
-- ============================================================

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

-- ============================================================
-- Table 7: shop_reviews
-- User reviews and ratings for shops
-- ============================================================

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

-- ============================================================
-- Table 8: search_logs
-- Analytics: Logs user searches for insights
-- ============================================================

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

-- ============================================================
-- Table 9: users
-- Authentication and user management (customers, vendors, admins)
-- ============================================================

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

-- ============================================================
-- Table 10: sessions (Optional - Database Session Storage)
-- Stores PHP sessions in database instead of file system
-- ============================================================

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

-- ============================================================
-- Table 11: admin_logs
-- Audit trail for admin actions (approvals, moderation, user management)
-- ============================================================

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

-- ============================================================
-- Verify Schema Creation
-- ============================================================

-- Show all tables
SHOW TABLES;

-- Verify character set
SELECT @@character_set_database, @@collation_database;
