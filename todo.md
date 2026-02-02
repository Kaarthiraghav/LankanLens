# LankanLens - Master To-Do List

**Project:** Camera Rental Aggregator for Sri Lanka  
**Status:** Planning → Development  
**Last Updated:** February 2, 2026

---

## Admin

### Phase 1: Environment & Database Setup
- [x] Create project folder structure: `/config`, `/includes`, `/public`, `/api`, `/vendor`, `/admin`, `/assets/css`, `/assets/js`, `/assets/images`, `/database`, `/logs`, `/uploads`
- [x] Create `.env.example` file with DB credentials template
- [x] Create `.env` file with local database credentials (do NOT commit)
- [x] Add `.gitignore` to exclude `.env`, `/logs`, `/uploads`, `/node_modules`
- [x] Create `config/config.php` with app constants and environment variable loader
- [x] Ensure BASE_URL is centralized via `includes/nav.php` and used across pages
- [x] Create MySQL database `lankanlens` with UTF8MB4 charset
- [x] Create `database/schema.sql` with tables for shops, equipment_categories, equipment, inventory, booking_requests, users, admin_logs, search_logs
- [x] Create database/migrations folder for future schema changes
- [x] Execute schema.sql to create all tables in MySQL
- [x] Create `config/database.php` with PDO helpers (query, fetchOne, fetchAll, insert, update, delete)
- [x] Add error logging to `/logs/errors.log`
- [x] Create `/logs` and `/uploads` directories with write permissions
- [x] Test PDO connection with a simple query

### Phase 2: Shared Components & Auth Foundation
- [x] Create `includes/auth_helper.php` with role, status, and guard middleware
- [x] Create `includes/header.php`, `includes/navbar.php`, and `includes/footer.php`
- [x] Create `includes/error-handler.php` for user-friendly errors and logging
- [x] Create `assets/css/styles.css` with core utilities and layout styles

### Phase 2.5: Admin Master Catalog
- [x] Create `admin/manage-master-gear.php` (CRUD for master equipment list)
- [x] Implement image path storage (link to pre-stored assets via `image_url`)
- [x] Enforce unique brand + model + category rules where applicable
- [x] Ensure master gear stores `equipment_name`, `brand`, `model_number`, `equipment_type`, `specifications`, `image_url`
- [x] Add validation and admin audit logging for catalog changes

### Phase 3.5: Authentication & Gated Content
- [x] Create `public/register.php`, `public/login.php`, `public/logout.php`
- [x] Create `public/vendor-pending.php` and `public/unauthorized.php`
- [x] Add gated content CSS and JS (blur + login CTA)
- [x] Update product and results pages to hide shop details for guests

### Phase 9: Admin Panel
- [x] Create `admin/dashboard.php` (system overview + quick actions)
- [x] Create `admin/vendor-approvals.php`
- [x] Create `admin/users.php`
- [x] Create `admin/listings.php`
- [x] Create `admin/logs.php`

### Phase 10: Data, QA, and Deployment
- [] Create seed files for shops, users, categories, equipment, inventory
- [] Run seed scripts and verify sample data
- [] Perform manual QA for auth, search, bookings, vendor flows, admin flows
- [] Security review: prepared statements, CSRF, XSS, session checks
- [] Optimize performance (indexes, caching strategy, asset minification)
- [] Prepare production `.env` and deployment checklist

---

## Vendor

### Phase 4: Vendor Inventory (Updated)
- [x] Create `api/get-models.php` (fetch models by Brand + Category)
- [x] Refactor `vendor/add-equipment.php` to use dynamic dropdowns (no file uploads)
- [x] Add Live Image Preview logic using Vanilla JS
- [x] Form fields: hidden `equipment_id`, condition, daily_rate_lkr, available_quantity, deposit_required_lkr, delivery_available
- [x] On submit, insert inventory row linked to selected master `equipment_id` and vendor `shop_id`

### Phase 8: Vendor Dashboard & Listing Management
- [x] Create `vendor/dashboard.php`
- [] Create `vendor/my-listings.php`
- [] Create `vendor/edit-equipment.php`
- [] Create `vendor/inquiries.php`
- [] Create `vendor/analytics.php`
- [] Enforce `requireActiveVendor()` on all vendor routes
- [] Verify ownership checks for listing updates

---

## Customer

### Phase 3: Home Page & Search Form
- [x] Create `public/index.php` with search form
- [x] Create `assets/js/search.js` with validation and redirects
- [x] Add featured gear section

### Phase 4: Search Backend & API
- [x] Create `api/search-api.php` with filters and logging
- [] Add SQL indexes for search performance

### Phase 5: Results Page & Gear Cards
- [x] Create `public/results.php` and results grid UI
- [x] Create `assets/js/results.js` for fetching and rendering
- [x] Build empty state UI and button actions

### Phase 6: Product Detail Page
- [x] Create `public/product.php` (specs, pricing, availability)
- [x] Parse and display `specifications` JSON
- [x] Apply gated shop details for guests

### Phase 7: Booking Modal & WhatsApp
- [x] Create `includes/booking-modal.php`
- [x] Create `assets/js/booking.js` for booking + WhatsApp flow
- [x] Create `api/booking-api.php` to log booking requests

### Phase 8: Additional Pages
- [] Create `public/about.php`
- [] Create `public/contact.php`
- [] Create `public/shop.php`

---

## Summary

**Master Catalog Model:** Admin owns the master gear list. Vendors only add pricing, quantity, and condition.  
**Key Milestones:**
- Week 1: Admin foundation + master catalog
- Week 2: Vendor inventory flow + search
- Week 3: Customer results + product detail + booking
- Week 4: Admin panel + QA + deployment readiness

---

**Document Status:** Updated for Master Catalog workflow  
**Last Updated:** February 2, 2026
