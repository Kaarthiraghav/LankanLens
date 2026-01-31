# LankanLens - Master To-Do List

**Project:** Camera Rental Aggregator for Sri Lanka  
**Status:** Planning → Development  
**Last Updated:** January 26, 2026

---

## Phase 1: Environment & Database Setup

### Folder Structure & Configuration
- [x] Create project folder structure: `/config`, `/includes`, `/public`, `/api`, `/vendor`, `/admin`, `/assets/css`, `/assets/js`, `/assets/images`, `/database`, `/logs`, `/uploads`
- [x] Create `.env.example` file with DB credentials template
- [x] Create `.env` file with local database credentials (do NOT commit)
- [x] Add `.gitignore` to exclude `.env`, `/logs`, `/uploads`, `/node_modules`
- [x] Create `config/config.php` with app constants and environment variable loader

### MySQL Database & Schema
- [x] Create MySQL database `lankanlens` with UTF8MB4 charset
- [x] Create `database/schema.sql` with all 11 table definitions:
  - [x] `shops` table (shop_id, shop_name, phone, whatsapp_number, city, etc.)
  - [x] `equipment_categories` table (category_id, category_name, slug)
  - [x] `equipment` table (equipment_id, category_id, brand, model, shop_id, description)
  - [x] `inventory` table (inventory_id, equipment_id, shop_id, daily_rate_lkr, available_quantity)
  - [x] `booking_requests` table (request_id, user_name, equipment_id, shop_id, rental_duration_days)
  - [x] `shop_locations` table (location_id, shop_id, city_name, address)
  - [x] `shop_reviews` table (review_id, shop_id, user_name, rating)
  - [x] `search_logs` table (log_id, search_term, search_city, result_count)
  - [x] `users` table (user_id, full_name, email, password_hash, role, status, approved_by)
  - [x] `sessions` table (session_id, user_id, payload, last_activity) - Optional
  - [x] `admin_logs` table (log_id, admin_user_id, action_type, target_user_id)
- [x] Create database/migrations folder for future schema changes
- [x] Execute schema.sql to create all tables in MySQL

### PDO Database Connection
- [x] Create `config/database.php` with PDO connection class
  - [x] Implement `__construct()` to load environment variables and establish PDO connection
  - [x] Implement `query()` method with prepared statements
  - [x] Implement `fetchOne()` and `fetchAll()` methods
  - [x] Implement `insert()`, `update()`, `delete()` helper methods
  - [x] Add error logging to `/logs/errors.log`

### Project Bootstrap
- [x] Create `/logs` directory with write permissions (chmod 755)
- [x] Create `/uploads` directory with write permissions
- [x] Test PDO connection by running a simple query in terminal: `php -r "require 'config/database.php'; $db = new Database();"`

---

## Phase 3.5: Authentication System (LOGIN, REGISTER, GATED CONTENT)

### User Registration Page
- [x] Create `public/register.php` with:
  - [x] Registration form with fields:
    - [x] Full Name (input, required, 3-255 chars)
    - [x] Email (input type="email", required, unique validation)
    - [x] Password (input type="password", required, min 8 chars)
    - [x] Confirm Password (input type="password", required, must match)
    - [x] Role Selection (radio buttons):
      - [x] Customer (default, immediate activation)
      - [x] Vendor (requires admin approval)
    - [x] Shop Name (input, optional, shown if Vendor role selected)
    - [x] Terms & Conditions checkbox (required)
  - [x] Client-side validation (JavaScript in `assets/js/auth.js`)
  - [x] Password strength indicator (Weak, Medium, Strong)
  - [x] Real-time email uniqueness check via AJAX (optional)
  - [x] Submit button with loading state
  - [x] Link to login page: "Already have an account? Login"

### User Registration Backend
- [x] Create registration handler in `public/register.php` (POST section):
  - [x] Validate all inputs server-side:
    - [x] Full name: 3-255 characters
    - [x] Email: Valid format, check uniqueness in database
    - [x] Password: Min 8 characters, hash with `password_hash($password, PASSWORD_BCRYPT)`
    - [x] Confirm password: Must match password
    - [x] Role: Must be 'customer' or 'vendor'
  - [x] Set account status based on role:
    - [x] Customer: status = 'active' (instant activation)
    - [x] Vendor: status = 'pending' (awaits admin approval)
  - [x] Insert user into `users` table
  - [x] Create session variables:
    - [x] `$_SESSION['user_id']`
    - [x] `$_SESSION['email']`
    - [x] `$_SESSION['role']`
    - [x] `$_SESSION['status']`
    - [x] `$_SESSION['full_name']`
  - [x] Redirect based on role:
    - [x] Customer: Redirect to return URL or home page
    - [x] Vendor: Redirect to `vendor-pending.php` with pending message
  - [x] Handle errors: Display validation errors with red messages

### User Login Page
- [x] Create `public/login.php` with:
  - [x] Login form with fields:
    - [x] Email (input type="email", required)
    - [x] Password (input type="password", required)
    - [x] Remember Me checkbox (optional, extends session to 30 days)
  - [x] Submit button with loading state: "Logging In..."
  - [x] "Forgot Password?" link (placeholder for future)
  - [x] Link to registration: "Don't have an account? Sign Up"
  - [x] Accept return URL parameter: `?return=/public/product.php?id=5`

### User Login Backend
- [x] Create login handler in `public/login.php` (POST section):
  - [x] Validate email and password (not empty)
  - [x] Query `users` table by email
  - [x] If email not found: Return generic error "Invalid email or password"
  - [x] Check failed login attempts:
    - [x] If `failed_login_attempts >= 5` and within 15 minutes: Lock account temporarily
    - [x] Display message: "Account locked. Try again in X minutes."
  - [x] Verify password using `password_verify($password, $password_hash)`
  - [x] If password incorrect:
    - [x] Increment `failed_login_attempts` in database
    - [x] Update `last_failed_login` timestamp
    - [x] Return error "Invalid email or password"
  - [x] If password correct:
    - [x] Check account status:
      - [x] If 'suspended': Error "Your account has been suspended. Contact admin."
      - [x] If 'rejected': Error "Your vendor application was not approved."
      - [x] If 'pending' and role='vendor': Redirect to `vendor-pending.php`
      - [x] If 'active': Proceed with login
    - [x] Reset `failed_login_attempts` to 0
    - [x] Update `last_login_at` timestamp
    - [x] Create session with user data
    - [x] If "Remember Me" checked:
      - [x] Generate random token: `bin2hex(random_bytes(32))`
      - [x] Store token in database `remember_token` field
      - [x] Set cookie with 30-day expiration
    - [x] Redirect based on role:
      - [x] Admin → `/admin/dashboard.php`
      - [x] Vendor (active) → `/vendor/dashboard.php`
      - [x] Customer → Return URL or `/public/index.php`

### Logout Handler
- [x] Create `public/logout.php`:
  - [x] Destroy session with `session_destroy()`
  - [x] Clear remember_token cookie
  - [x] Clear remember_token in database
  - [x] Redirect to home page with success message

### Vendor Pending Approval Page
- [x] Create `public/vendor-pending.php`:
  - [x] Check if user is logged in and role='vendor' and status='pending'
  - [x] Display pending approval message:
    - [x] Icon: Hourglass or pending status icon
    - [x] Header: "Your Vendor Account is Pending Approval"
    - [x] Message: "Thank you for registering! Our admin team will review your application within 1-2 business days."
    - [x] Email notification: "You'll receive an email at [email] once approved"
    - [x] Option to browse equipment as customer while waiting
  - [x] Button: "Browse Equipment" → Redirect to home page
  - [x] Logout link

### Unauthorized Access Page
- [x] Create `public/unauthorized.php`:
  - [x] Display "Access Denied" message
  - [x] Explain: "You don't have permission to access this page"
  - [x] Button: "Return to Home" → Redirect to index.php
  - [x] Suggest login if not authenticated

---

## Phase 2.5: Authentication UI & Gated Content Logic

### Gated Content Styling
- [x] Add CSS to `assets/css/styles.css` for gated content:
  - [x] `.gated-content` - Container for blurred shop details
  - [x] `.blur-filter` - CSS blur effect: `filter: blur(8px); pointer-events: none;`
  - [x] `.login-overlay` - Semi-transparent overlay with login CTA
  - [x] `.lock-icon` - Lock emoji or SVG icon styling
  - [x] Responsive design for mobile (overlay covers full section)

### Gated Content JavaScript
- [x] Create `assets/js/auth.js` with:
  - [x] `checkAuthState()` - Check if user is logged in via session
  - [x] `showLoginModal()` - Display login prompt modal when guest clicks gated content
  - [x] `redirectToLogin(returnUrl)` - Save return URL and redirect to login page
  - [x] Event listeners for "Login to Rent" buttons
  - [x] Form validation for login and registration forms
  - [x] Password strength checker (real-time feedback)
  - [x] Toggle password visibility (eye icon)

### Product Detail Page - Gated Content Implementation
- [x] Update `public/product.php` to implement gated content:
  - [x] Include `auth_helper.php` at top
  - [x] Check if user is logged in: `$is_logged_in = isLoggedIn();`
  - [x] Fetch equipment AND shop details from database
  - [x] Equipment details section (ALWAYS visible):
    - [x] Equipment name, brand, model, description
    - [x] Equipment images and specifications
    - [x] Daily/weekly/monthly pricing
    - [x] Availability status
    - [x] Condition badge
  - [x] Shop details section (GATED for guests):
    - [x] If logged in: Show full shop name, address, phone, WhatsApp, "Rent Now" button
    - [x] If NOT logged in:
      - [x] Apply blur filter to shop name, address, phone
      - [x] Show placeholder text (████████)
      - [x] Display overlay with lock icon and "Login to View Shop Details"
      - [x] Replace "Rent Now" with "Login to Rent" button
      - [x] Link to login page with return URL: `/public/login.php?return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>`

### Search Results Page - Gated Content
- [x] Update `public/results.php` or `public/search.php`:
  - [x] Include `auth_helper.php`
  - [x] Pass `$is_logged_in` flag to each equipment card
  - [x] In equipment card template:
    - [x] Equipment info: Always visible
    - [x] Shop name: Show if logged in, else show "Login to View Shop"
    - [x] "Rent Now" button: Show if logged in, else show "Login to Rent"
    - [x] Apply blur effect to shop section if not logged in

---

## Phase 2: Shared Components & Layout (Continued)

### Authentication Middleware & Helper Functions
- [x] Create `includes/auth_helper.php` with authentication middleware:
  - [x] `isLoggedIn()` - Check if user has active session
  - [x] `getUserRole()` - Return current user role (customer, vendor, admin)
  - [x] `getUserStatus()` - Return current user status (active, pending, suspended, rejected)
  - [x] `isCustomer()`, `isVendor()`, `isAdmin()` - Role check helpers
  - [x] `requireLogin($returnUrl)` - Redirect to login if not authenticated
  - [x] `requireRole($role)` - Redirect if user doesn't have required role
  - [x] `requireAdmin()` - Protect admin-only routes
  - [x] `requireActiveVendor()` - Protect vendor routes (must be active)
  - [x] `getCurrentUserId()` - Get logged-in user ID
  - [x] `getCurrentUserName()` - Get logged-in user's full name
  - [x] `logout()` - Destroy session and redirect to home
  - [x] `canAccess($resourceType, $resourceOwnerId)` - Check resource ownership

### HTML Layout & Header
- [x] Create `includes/header.php` with:
  - [x] HTML5 doctype and meta tags (charset, viewport)
  - [x] Tailwind CSS CDN link: `<script src="https://cdn.tailwindcss.com"></script>`
  - [x] Custom CSS link: `<link rel="stylesheet" href="/assets/css/styles.css">`
  - [x] Logo and site title
  - [x] Open `<body>` tag (closing in footer.php)

### Navigation Bar
- [x] Create `includes/navbar.php` with:
  - [x] LankanLens logo/brand link to home
  - [x] Search shortcut link
  - [x] "Browse by Category" dropdown (camera bodies, lenses, lighting, accessories)
  - [x] **Authentication Menu (Conditional):**
    - [x] If NOT logged in: "Login" and "Sign Up" buttons
    - [x] If logged in as Customer: "Welcome, [Name]" with dropdown (Profile, Booking History, Logout)
    - [x] If logged in as Vendor: "Vendor Dashboard" link + user dropdown
    - [x] If logged in as Admin: "Admin Panel" link + user dropdown
  - [x] Mobile hamburger menu (optional for Phase 2, can be Phase 3)
  - [x] Tailwind classes for responsive design (flex, justify-between, items-center)
  - [x] Styling: bg-white, border-bottom, shadow-sm

### Footer
- [x] Create `includes/footer.php` with:
  - [x] Close `</body>` and `</html>` tags
  - [x] Footer content: Copyright, About, Contact, Social links
  - [x] Terms & Conditions, Privacy Policy links
  - [x] LKR currency note
  - [x] Tailwind bg-gray-800, text-white styling

### Error Handler
- [x] Create `includes/error-handler.php` to:
  - [x] Display user-friendly error messages
  - [x] Log errors to `/logs/errors.log`
  - [x] Handle PDO exceptions gracefully
  - [x] Display "404 Not Found" or "Something went wrong" pages

### Custom CSS & Animations
- [x] Create `assets/css/styles.css` with:
  - [x] CSS variables: `--color-primary`, `--color-success`, `--color-danger`
  - [x] Keyframe animations: `@keyframes spin`, `fadeIn`, `shake`, `slideUp`
  - [x] Utility classes: `.spinner`, `.fade-in`, `.shake`, `.slide-up`, `.card-hover`
  - [x] Modal overlay: `.modal-overlay { backdrop-filter: blur(4px); }`
  - [x] Responsive typography for mobile (max-width: 768px)

---

## Phase 3: Home Page & Search Form

### Home Page Layout
- [x] Create `public/index.php` with:
  - [x] Require `includes/header.php`
  - [x] Require `includes/navbar.php`
  - [x] Hero section with background image and call-to-action
  - [x] Search form section (centered, prominent)

### Search Form Component
- [x] Create search form in `public/index.php` or `includes/search-form.php`:
  - [x] Input field: Equipment search term (placeholder: "e.g., Sony A7R IV")
  - [x] Dropdown: City selection (hardcoded or fetch from database)
  - [x] Date picker: Rental start date (HTML5 date input)
  - [x] Button: "Search" with data-action attribute
  - [x] Tailwind styling: grid layout, rounded inputs, primary button color
- [x] Add form ID and data attributes for JavaScript targeting
- [x] Add validation message container (hidden by default)

### JavaScript: Search Form Logic
- [x] Create `assets/js/search.js` with:
  - [x] Event listener on "Search" button click
  - [x] Client-side validation: search term (min 2 chars), city selected, date selected
  - [x] Show loading state on button: "Searching..." with spinner
  - [x] Send AJAX POST request to `/api/search-api.php`
  - [x] On success: redirect to `/public/results.php?q=[term]&city=[city]&date=[date]`
  - [x] On error: display error toast notification

### Featured Gear Section (Optional)
- [x] Add "Popular Rentals" or "Featured Gear" section below search form
- [x] Display 4-6 random equipment items in a grid
- [x] Each item card shows: image, equipment name, shop name, rating, daily rate, "Check Availability" button

---

## Phase 4: Search Backend & API

### Search API Endpoint
- [x] Create `public/api/search-api.php` with:
  - [x] Validate incoming POST parameters: search_term, city, rental_date
  - [x] Sanitize inputs (trim, htmlspecialchars)
  - [x] Query database with full-text search on equipment_name, brand, model_number
  - [x] Filter by city (via shop's primary_city)
  - [x] Filter by availability (inventory.available_quantity > 0)
  - [x] Order results by: brand exact match DESC, daily_rate_lkr ASC, average_rating DESC
  - [x] Limit results to 50 items
  - [x] Return JSON response with success/error status and results array
  - [x] Log search to `search_logs` table for analytics

### Database Query for Search
- [x] Write SQL query that:
  - [x] SELECTs equipment fields: equipment_id, equipment_name, brand, model_number, description, image_url
  - [x] JOINs with inventory table
  - [x] JOINs with shops table
  - [x] LEFT JOINs with shop_reviews for average_rating
  - [x] Uses WHERE clause with LIKE for search term
  - [x] Uses WHERE for city filter
  - [x] Uses WHERE for available_quantity > 0
  - [x] Uses FULLTEXT INDEX for better performance
  - [x] ORDER BY for relevance sorting

### Error Handling in API
- [x] Handle PDO connection errors gracefully
- [x] Handle empty search results (return success with empty array)
- [x] Handle invalid parameters (return error with 400 status code)
- [x] Log all API errors to `/logs/errors.log`

---

## Phase 5: Results Page & Gear Cards

### Results Page Layout
- [x] Create `public/results.php` with:
  - [x] Require header and navbar
  - [x] Get query parameters: search_term, city, rental_date from URL
  - [x] Display search summary: "Results for '[term]' in [City] ([count] items found)"
  - [x] Results container (initially empty, populated by JavaScript)
  - [x] Empty state container (hidden, shown if no results)
  - [x] Pagination controls (if > 12 results)

### Gear Card Component
- [x] Design equipment card layout with:
  - [x] Equipment image (placeholder if not available)
  - [x] Equipment name and brand (e.g., "Sony A7R IV")
  - [x] Shop name with star rating (e.g., "⭐ Pro Lens Rental - 4.8/5")
  - [x] Condition badge (Excellent, Good, Fair)
  - [x] Daily rate in LKR (e.g., "Rs 15,500 LKR/day")
  - [x] Availability badge ("In Stock")
  - [x] "Check Availability" button with data-action attribute
  - [x] Tailwind card styling: rounded corners, shadow, hover effects

### Results Grid Display
- [ ] Create `assets/js/results.js` with:
  - [ ] On page load: Parse URL parameters
  - [ ] Fetch results from `/api/search-api.php` via AJAX
  - [ ] Show loading spinner while fetching
  - [ ] On success: Loop through results and render gear cards
  - [ ] Apply card-hover animation class to cards
  - [ ] Add staggered fade-in animation (100ms delay per card)
  - [ ] Attach click handlers to "Check Availability" buttons

### Empty State UI
- [ ] Create empty state template in `public/results.php`:
  - [ ] SVG icon (camera with question mark)
  - [ ] Headline: "No results found for '[term]' in [City]"
  - [ ] Subheading: "But don't worry! Here's what we recommend:"
  - [ ] Buttons: "Search in nearby cities", "Browse similar gear", "View all equipment", "Try another search"
  - [ ] Each button has click handlers in `assets/js/empty-state.js`

### Empty State Logic
- [ ] Create `assets/js/empty-state.js` with:
  - [ ] "Search in nearby cities" → Remove city filter, rerun search across all cities, group results by city
  - [ ] "Browse similar gear" → Show all items in the same equipment category
  - [ ] "View all equipment" → Show all available items in selected city, grouped by category
  - [ ] "Try another search" → Scroll to top, focus search form

---

## Phase 6: Product Detail Page (Optional Phase 3.5)

### Product Detail Modal/Page
- [ ] Create `public/product.php` or modal template with:
  - [ ] Large product image carousel (if multiple images)
  - [ ] Equipment specs in JSON format (parsed from equipment.specifications)
  - [ ] Shop details: name, address, phone, website, opening hours
  - [ ] Rental pricing: daily, weekly, monthly rates
  - [ ] Availability calendar (optional, can be Phase 4)
  - [ ] Rental policies: min/max days, deposit, insurance, delivery options
  - [ ] Customer reviews section
  - [ ] "Check Availability" button (triggers booking modal)
  - [ ] Tailwind styling: 2-column layout (image + details)

### Specs Display
- [ ] Parse equipment.specifications JSON field and display as formatted list
- [ ] Example: Camera bodies show: resolution, sensor size, AF points, ISO range, etc.
- [ ] Example: Lenses show: focal length, aperture, mount, weight, etc.

---

## Phase 7: The Handshake - Booking Modal & WhatsApp

### Booking Modal HTML
- [ ] Create booking modal template in `includes/booking-modal.php` or `public/index.php`:
  - [ ] Modal container with id="booking-modal" (hidden by default)
  - [ ] Modal header: "Request to Rent: [Equipment Name]"
  - [ ] Subheader: Shop info (name, phone)
  - [ ] Form field: User Full Name (required, min 2 chars, max 50 chars)
  - [ ] Form field: Rental Duration dropdown (1 day, 2 days, 3 days, 1 week, 2 weeks, 1 month, Custom)
  - [ ] Form field: Additional Notes textarea (optional, max 200 chars)
  - [ ] Pricing display: "Daily rate × Days = Total price"
  - [ ] Buttons: "Send Request" (primary), "Cancel" (secondary)
  - [ ] Tailwind styling: centered modal, semi-transparent overlay, rounded corners, shadow

### JavaScript: Booking Modal Interaction
- [ ] Create `assets/js/booking.js` with `WhatsAppBooking` class:
  - [ ] Event listener: "Check Availability" button → `openBookingModal()`
  - [ ] `openBookingModal()` → Extract equipment data from card, show modal, populate header, focus name input
  - [ ] Event listener: Duration dropdown → `updatePrice()` 
  - [ ] `updatePrice()` → Calculate total (daily_rate × duration_days), update display
  - [ ] Event listener: "Send Request" button → `handleSendRequest()`
  - [ ] `handleSendRequest()` → Validate form, generate WhatsApp message, open WhatsApp, close modal

### WhatsApp Message Generator
- [ ] In `assets/js/booking.js`, implement `generateMessage()` method:
  - [ ] Build message: "Hi, I'm interested in renting [Equipment Name] for [Duration] days.\nMy name is [User Name].\nPlease confirm availability and provide the final price.\n[Additional Notes]"
  - [ ] Use template literals for dynamic content
  - [ ] Use proper grammar (singular/plural "day"/"days")

### WhatsApp Link Generator
- [ ] In `assets/js/booking.js`, implement `openWhatsApp()` method:
  - [ ] Encode message using `encodeURIComponent()`
  - [ ] Construct WhatsApp URL: `https://wa.me/[phone_number]?text=[encoded_message]`
  - [ ] Detect mobile vs. desktop using navigator.userAgent regex
  - [ ] Mobile: `window.location.href = whatsappUrl` (open WhatsApp app)
  - [ ] Desktop: `window.open(whatsappUrl, '_blank')` (open WhatsApp Web)
  - [ ] Show success toast: "Opening WhatsApp..."

### Booking Request Logging (Optional)
- [ ] Create `/api/booking-api.php` endpoint to log booking requests:
  - [ ] Accept POST: user_name, equipment_id, shop_id, rental_duration_days, additional_notes
  - [ ] Insert into `booking_requests` table with status='pending'
  - [ ] Calculate estimated_total_lkr (daily_rate × days)
  - [ ] Return JSON success response
  - [ ] Log all requests for shop owner follow-up and analytics

### Form Validation & Error Messages
- [ ] In `assets/js/booking.js`, implement validation:
  - [ ] Name: required, length 2-50 chars
  - [ ] Duration: required (dropdown selected)
  - [ ] Show real-time validation feedback (green checkmark for valid, red X for invalid)
  - [ ] On invalid form: show error message, apply shake animation
  - [ ] Disable "Send Request" button until form is valid

### Toast Notifications
- [ ] Create `assets/js/toast.js` or add to `booking.js`:
  - [ ] `showSuccess(message)` → Show green toast (bottom-right, 3 second duration)
  - [ ] `showError(message)` → Show red toast (bottom-right, 5 second duration)
  - [ ] Auto-dismiss after timeout
  - [ ] Use Tailwind classes: `fixed`, `bottom-4`, `right-4`, `bg-green-500`/`bg-red-500`

---

## Phase 8: Vendor Dashboard & Listing Management

### Vendor Dashboard Page
- [ ] Create `vendor/dashboard.php` with:
  - [ ] Protect route: `require_once '../includes/auth_helper.php'; requireActiveVendor();`
  - [ ] Check vendor status: If 'pending', redirect to vendor-pending.php
  - [ ] Display welcome message: "Welcome, [Vendor Name]"
  - [ ] Quick stats cards:
    - [ ] Total Listings (count equipment where shop_id = vendor's shop)
    - [ ] Total Views (sum of view_count)
    - [ ] Total Inquiries (count booking_requests)
    - [ ] Active Listings vs. Unavailable
  - [ ] Action buttons:
    - [ ] "Add New Equipment" → `/vendor/add-equipment.php`
    - [ ] "Manage Listings" → `/vendor/my-listings.php`
    - [ ] "View Inquiries" → `/vendor/inquiries.php`
    - [ ] "Analytics" → `/vendor/analytics.php`
  - [ ] Recent inquiries table (last 5 booking requests)
  - [ ] Sidebar navigation for vendor sections

### Add Equipment Page
- [ ] Create `vendor/add-equipment.php` with:
  - [ ] Protect route: `requireActiveVendor()`
  - [ ] Equipment creation form:
    - [ ] Category (dropdown from equipment_categories)
    - [ ] Equipment Name (input, required)
    - [ ] Brand (dropdown or text, required)
    - [ ] Model Number (input, optional)
    - [ ] Equipment Type (input, e.g., "Full-Frame Mirrorless")
    - [ ] Description (textarea, max 1000 chars)
    - [ ] Specifications (JSON or structured fields)
    - [ ] Condition (dropdown: Excellent, Good, Fair)
    - [ ] Images (file upload, accept jpg/png, max 5 images, 5MB each)
    - [ ] Daily Rate LKR (input number, required)
    - [ ] Weekly Rate LKR (input number, optional)
    - [ ] Monthly Rate LKR (input number, optional)
    - [ ] Available Quantity (input number, min 0)
    - [ ] Deposit Required LKR (input number)
    - [ ] Delivery Available (checkbox)
  - [ ] Submit button: "Add Equipment"
  - [ ] Cancel button: Return to dashboard

### Add Equipment Backend
- [ ] Handle form submission in `vendor/add-equipment.php`:
  - [ ] Validate all required fields
  - [ ] Process image uploads:
    - [ ] Validate file types (jpg, png, webp only)
    - [ ] Validate file size (max 5MB per image)
    - [ ] Generate unique filenames: `uniqid() . '_' . $filename`
    - [ ] Move files to `/uploads/equipment/` directory
    - [ ] Store image URLs in database
  - [ ] Get vendor's shop_id from session or users table
  - [ ] Insert into `equipment` table with shop_id
  - [ ] Insert into `inventory` table with pricing and quantity
  - [ ] Redirect to dashboard with success toast: "Equipment added successfully!"
  - [ ] Handle errors: Display validation errors

### Manage Listings Page
- [ ] Create `vendor/my-listings.php`:
  - [ ] Protect route: `requireActiveVendor()`
  - [ ] Query all equipment where shop_id = vendor's shop
  - [ ] Display listings table with columns:
    - [ ] Image thumbnail
    - [ ] Equipment name
    - [ ] Brand & model
    - [ ] Daily rate
    - [ ] Available quantity
    - [ ] Status (Available, Unavailable)
    - [ ] Actions: Edit, Delete, Mark Unavailable
  - [ ] Edit button → `/vendor/edit-equipment.php?id=[equipment_id]`
  - [ ] Delete button → Confirm modal → DELETE query
  - [ ] Toggle availability → UPDATE inventory SET available_quantity = 0

### Edit Equipment Page
- [ ] Create `vendor/edit-equipment.php`:
  - [ ] Protect route: `requireActiveVendor()`
  - [ ] Verify ownership: Check if equipment.shop_id matches vendor's shop
  - [ ] Pre-populate form with existing equipment data
  - [ ] Allow updates to all fields (name, description, pricing, images, quantity)
  - [ ] Handle image replacement (delete old, upload new)
  - [ ] Submit → UPDATE equipment and inventory tables
  - [ ] Redirect to my-listings with success message

---

## Phase 9: Admin Panel (God Mode)

### Admin Dashboard
- [ ] Create `admin/dashboard.php`:
  - [ ] Protect route: `requireAdmin()`
  - [ ] Display admin control panel with sections:
    - [ ] **System Overview:**
      - [ ] Total users (count by role: customers, vendors, admins)
      - [ ] Pending vendor approvals (count where role='vendor' AND status='pending')
      - [ ] Total equipment listings
      - [ ] Total shops
      - [ ] Total booking requests
    - [ ] **Quick Actions:**
      - [ ] "Approve Vendors" → `/admin/vendor-approvals.php`
      - [ ] "Manage Users" → `/admin/users.php`
      - [ ] "Moderate Listings" → `/admin/listings.php`
      - [ ] "View System Logs" → `/admin/logs.php`
  - [ ] Recent activity feed (last 10 admin actions from admin_logs)
  - [ ] System health indicators (database status, error count)

### Vendor Approval Queue
- [ ] Create `admin/vendor-approvals.php`:
  - [ ] Protect route: `requireAdmin()`
  - [ ] Query pending vendors: `SELECT * FROM users WHERE role='vendor' AND status='pending'`
  - [ ] Display table with columns:
    - [ ] Full Name
    - [ ] Email
    - [ ] Shop Name
    - [ ] Registration Date
    - [ ] Actions: Approve, Reject, View Details
  - [ ] Approve button → Modal: "Approve [Vendor Name]?"
  - [ ] On approve:
    - [ ] UPDATE users SET status='active', approved_by=[admin_user_id] WHERE user_id=[vendor_id]
    - [ ] Log action in admin_logs table
    - [ ] Send email notification to vendor (optional)
    - [ ] Show success toast: "Vendor approved!"
  - [ ] Reject button → Modal: "Reject [Vendor Name]? (Optional: Reason)"
  - [ ] On reject:
    - [ ] UPDATE users SET status='rejected' WHERE user_id=[vendor_id]
    - [ ] Log action in admin_logs
    - [ ] Send rejection email with reason
    - [ ] Show toast: "Vendor rejected"

### User Management Page
- [ ] Create `admin/users.php`:
  - [ ] Protect route: `requireAdmin()`
  - [ ] Display all users with filters:
    - [ ] Filter by role (All, Customer, Vendor, Admin)
    - [ ] Filter by status (All, Active, Pending, Suspended, Rejected)
    - [ ] Search by name or email
  - [ ] Users table with columns:
    - [ ] User ID, Full Name, Email, Role, Status, Last Login, Actions
  - [ ] Actions per user:
    - [ ] Edit (change role, status, details)
    - [ ] Suspend (change status to 'suspended')
    - [ ] Delete (with confirmation modal)
    - [ ] View Activity (show user's booking history, searches)
  - [ ] Pagination (20 users per page)

### Equipment Moderation Page
- [ ] Create `admin/listings.php`:
  - [ ] Protect route: `requireAdmin()`
  - [ ] Display all equipment listings across all vendors
  - [ ] Table with columns:
    - [ ] Equipment ID, Name, Brand, Shop Name, Status, Actions
  - [ ] Actions:
    - [ ] Edit (admin can edit any listing)
    - [ ] Delete (remove inappropriate or spam listings)
    - [ ] Feature (mark as featured/promoted)
  - [ ] Filter by shop, category, status
  - [ ] Search by equipment name or brand

### Admin Logs Page
- [ ] Create `admin/logs.php`:
  - [ ] Protect route: `requireAdmin()`
  - [ ] Display recent admin actions from `admin_logs` table
  - [ ] Show: Admin name, Action type, Target (user/equipment), Timestamp
  - [ ] Filter by action type (Approve Vendor, Reject Vendor, Delete Listing, etc.)
  - [ ] Export logs as CSV (optional)

---

## Phase 8: Additional Pages & Features (Continued)

### About Page
- [ ] Create `public/about.php` with:
  - [ ] Project mission and vision
  - [ ] How LankanLens works (3-step explanation with icons)
  - [ ] Partner shops list (linked to their detail pages)
  - [ ] FAQ section (accordion style, optional)

### Contact Page
- [ ] Create `public/contact.php` with:
  - [ ] Contact form: name, email, message (optional)
  - [ ] Shop owner information for partnerships
  - [ ] Direct contact links for urgent inquiries

### Shop Detail Page (Optional)
- [ ] Create `public/shop.php?shop_id=[id]` with:
  - [ ] Shop profile: name, description, address, phone, website
  - [ ] All equipment available at this shop (grid view)
  - [ ] Shop reviews and ratings
  - [ ] Map of shop location (if coordinates stored)
  - [ ] Operating hours and rental policies

---

## Phase 9: Data & Seeding

### Sample Shop Data
- [ ] Create `database/seeds/shops-seed.sql` with:
  - [ ] INSERT statements for 5-10 sample shops in different cities
  - [ ] Real Sri Lankan city names: Colombo, Kandy, Galle, Jaffna, Matara, etc.
  - [ ] Realistic shop names (Pro Lens Rental, Photo Gear Hub, etc.)
  - [ ] Realistic phone numbers (+94 format)
  - [ ] Realistic emails and websites
  - [ ] Sample ratings (3.5-5.0 stars)

### Sample User Data
- [ ] Create `database/seeds/users-seed.sql` with:
  - [ ] INSERT admin user:
    - [ ] Email: admin@lankanlens.lk
    - [ ] Password: 'password' (hashed with bcrypt) - CHANGE IN PRODUCTION
    - [ ] Role: 'admin', Status: 'active'
  - [ ] INSERT sample customer user:
    - [ ] Email: customer@example.com
    - [ ] Password: 'password' (hashed)
    - [ ] Role: 'customer', Status: 'active'
  - [ ] INSERT sample pending vendor:
    - [ ] Email: vendor@example.com
    - [ ] Password: 'password' (hashed)
    - [ ] Role: 'vendor', Status: 'pending'
    - [ ] Shop Name: "Epic Camera Rentals"
  - [ ] INSERT sample active vendor:
    - [ ] Email: active.vendor@example.com
    - [ ] Password: 'password' (hashed)
    - [ ] Role: 'vendor', Status: 'active'
    - [ ] Shop Name: "Pro Lens Rental"

### Sample Equipment Categories
- [ ] Create `database/seeds/categories-seed.sql` with:
  - [ ] INSERT statements for 4 categories:
    - [ ] Camera Bodies (slugs: camera-bodies)
    - [ ] Lenses (slugs: lenses)
    - [ ] Lighting Gear (slugs: lighting-gear)
    - [ ] Accessories (slugs: accessories)
  - [ ] Include category_description for each

### Sample Equipment Data
- [ ] Create `database/seeds/equipment-seed.sql` with:
  - [ ] INSERT statements for 20-30 realistic equipment items
  - [ ] Mix of brands: Sony, Canon, Nikon, etc.
  - [ ] Realistic model names and descriptions
  - [ ] Specifications as JSON (e.g., `{"resolution": "61MP", "sensor": "Full-Frame"}`)
  - [ ] Assign equipment to different shops

### Sample Inventory Data
- [ ] Create `database/seeds/inventory-seed.sql` with:
  - [ ] INSERT statements linking equipment to shops
  - [ ] Realistic daily/weekly/monthly rates in LKR
  - [ ] Various availability quantities (0, 1, 2, 3+)
  - [ ] Insurance and delivery options

### Execute Seeds
- [ ] Run all seed files to populate database:
  ```bash
  mysql -u root -p lankanlens < database/seeds/shops-seed.sql
  mysql -u root -p lankanlens < database/seeds/categories-seed.sql
  mysql -u root -p lankanlens < database/seeds/equipment-seed.sql
  mysql -u root -p lankanlens < database/seeds/inventory-seed.sql
  ```
- [ ] Verify data in MySQL: `SELECT * FROM shops LIMIT 5;` etc.

---

## Phase 10: Testing, Optimization & Polish

### Manual Testing - Core Flows
- [ ] Test Search Journey:
  - [ ] Home page loads correctly
  - [ ] Enter search term and select city
  - [ ] Click "Search" button
  - [ ] Results page loads with correct results
  - [ ] Empty state displays when no results
  - [ ] All navigation links work
  
- [ ] Test Authentication Flow:
  - [ ] Guest user can browse equipment but shop details are blurred
  - [ ] Clicking "Login to Rent" redirects to login page
  - [ ] Login with valid customer credentials redirects back to product page
  - [ ] Shop details are now visible (no blur)
  - [ ] "Rent Now" button is active
  - [ ] Register as customer grants immediate access
  - [ ] Register as vendor shows "Pending Approval" page
  - [ ] Vendor cannot access vendor dashboard until approved
  - [ ] Admin can approve vendor from admin panel
  - [ ] Approved vendor can access vendor dashboard
  - [ ] Logout destroys session and redirects to home
  
- [ ] Test Booking Journey:
  - [ ] Click "Check Availability" on a gear card (must be logged in)
  - [ ] Modal opens with correct equipment info
  - [ ] Name validation works
  - [ ] Duration dropdown populates and updates price
  - [ ] "Send Request" button generates WhatsApp link
  - [ ] WhatsApp opens with correct pre-filled message

- [ ] Test Vendor Dashboard:
  - [ ] Vendor can access dashboard only if status='active'
  - [ ] Vendor can add new equipment listing
  - [ ] Image upload works (max 5 images, 5MB each)
  - [ ] Equipment appears in vendor's "My Listings"
  - [ ] Vendor can edit their own equipment
  - [ ] Vendor can delete their own equipment
  - [ ] Vendor cannot access admin panel
  
- [ ] Test Admin Panel:
  - [ ] Admin can access admin dashboard
  - [ ] Admin sees pending vendor approvals
  - [ ] Admin can approve vendor (status changes to 'active')
  - [ ] Admin can reject vendor (status changes to 'rejected')
  - [ ] Admin can view all users and edit them
  - [ ] Admin can suspend or delete users
  - [ ] Admin can view and moderate all equipment listings
  - [ ] Admin logs are recorded for all actions

### Bug Fixes & Edge Cases
- [ ] Fix any JavaScript console errors
- [ ] Test with special characters in search term
- [ ] Test with very long equipment names
- [ ] Test with zero-availability items
- [ ] Test on mobile devices (viewport scaling)
- [ ] Test form validation with empty/invalid inputs
- [ ] Test WhatsApp link with different phone formats

### Performance Optimization
- [ ] Minify `assets/css/styles.css`
- [ ] Minify `assets/js/*.js` files (optional for Phase 2)
- [ ] Add database indexes for frequently queried fields
- [ ] Test search performance with 100+ equipment items
- [ ] Add query caching if searches are slow (optional)

### Responsive Design Testing
- [ ] Test on mobile (375px), tablet (768px), desktop (1024px)
- [ ] Verify Tailwind breakpoints working correctly
- [ ] Check that modal is usable on small screens
- [ ] Verify gear cards stack properly on mobile
- [ ] Test touch interactions (buttons, dropdowns)

### Browser Compatibility
- [ ] Test on Chrome (latest)
- [ ] Test on Firefox (latest)
- [ ] Test on Safari (latest)
- [ ] Test on Edge (latest)
- [ ] Verify ES6 JavaScript syntax is supported

### Code Documentation
- [ ] Add PHPDoc comments to all classes and functions
- [ ] Add JSDoc comments to JavaScript methods
- [ ] Add inline comments for complex logic
- [ ] Update README.md with setup instructions
- [ ] Create API documentation (endpoint list, parameters, responses)

### Security Review
- [ ] Verify all SQL queries use prepared statements
- [ ] Check that user input is sanitized (htmlspecialchars, trim)
- [ ] Verify CSRF tokens are used in forms (if applicable)
- [ ] Check that sensitive data is not logged
- [ ] Verify .env file is not committed to git
- [ ] Test for XSS vulnerabilities
- [ ] Test for SQL injection vulnerabilities
- [ ] **Authentication Security:**
  - [ ] Verify passwords are hashed with bcrypt (PASSWORD_BCRYPT)
  - [ ] Check that password_hash and password_verify are used correctly
  - [ ] Verify session data is not exposed in URLs
  - [ ] Check that remember_token is generated securely (random_bytes)
  - [ ] Verify failed login attempts are tracked and accounts lock after 5 attempts
  - [ ] Test that session expires after 2 hours of inactivity
  - [ ] Verify logout clears all session data and cookies
  - [ ] Check that admin routes are protected with requireAdmin()
  - [ ] Verify vendor routes check both role AND status
  - [ ] Test that gated content cannot be bypassed via direct URL access

### Error Logging & Monitoring
- [ ] Verify errors are logged to `/logs/errors.log`
- [ ] Check log file format (timestamp, error type, message)
- [ ] Test that user-facing errors are friendly
- [ ] Verify admin can view recent errors
- [ ] Set up log rotation (optional, for long-term)

### Performance Metrics
- [ ] Measure home page load time
- [ ] Measure search response time (< 2 seconds)
- [ ] Measure results page render time
- [ ] Measure modal open/close animation smoothness
- [ ] Check for any layout shifts (CLS)

### Final QA Checklist
- [ ] All pages load without errors
- [ ] All links navigate correctly
- [ ] All forms submit and validate
- [ ] Database queries are efficient
- [ ] Mobile design is responsive
- [ ] Animations are smooth (no jank)
- [ ] WhatsApp integration works on mobile & desktop
- [ ] Empty states are user-friendly
- [ ] Error messages are clear and helpful

---

## Phase 11: Deployment & Go-Live (Optional for MVP)

### Pre-Deployment Setup
- [ ] Configure production `.env` file (different DB credentials if needed)
- [ ] Update `APP_URL` in config.php to production domain
- [ ] Set `APP_DEBUG=false` in production
- [ ] Ensure error logging is directed to appropriate location
- [ ] Test all features in production environment

### Database Migration
- [ ] Create production database backup
- [ ] Run schema.sql on production database
- [ ] Run seed data (or migrate from staging)
- [ ] Verify data integrity

### Deployment
- [ ] Deploy code to production server
- [ ] Set correct file permissions (755 for dirs, 644 for files)
- [ ] Verify `.env` file is not accessible via web
- [ ] Test all core flows in production
- [ ] Monitor error logs for any issues

### Go-Live Monitoring
- [ ] Monitor database performance
- [ ] Monitor server resource usage (CPU, memory, disk)
- [ ] Monitor application error logs
- [ ] Gather user feedback
- [ ] Track key metrics: searches, bookings, conversion rate

---

## Phase 12: Future Enhancements (Phase 2+)

### Feature Wishlist
- [ ] User accounts & saved favorites
- [ ] Booking confirmation via email/SMS
- [ ] Shop owner dashboard to manage inventory
- [ ] Rating and review system (user-facing)
- [ ] Advanced filters (price range, equipment type, brand)
- [ ] Rental availability calendar
- [ ] Payment integration (for deposits)
- [ ] Automated SMS reminders
- [ ] Mobile app (React Native or Flutter)
- [ ] Multi-language support (Sinhala, Tamil)
- [ ] Shop location map view
- [ ] Equipment comparison tool
- [ ] Wishlist/favorites system
- [ ] Referral program for users

### Performance Improvements
- [ ] Add caching layer (Redis, Memcached)
- [ ] Implement pagination for large result sets
- [ ] Add image optimization and CDN
- [ ] Implement lazy loading for images
- [ ] Add service worker for offline support

### Analytics & Reporting
- [ ] Dashboard for search trends
- [ ] Shop performance metrics
- [ ] Conversion funnel analysis
- [ ] User behavior tracking
- [ ] Revenue reports

---

## Summary

**Total Estimated Effort:** 40-60 development hours (MVP)  
**Recommended Timeline:** 4-6 weeks (1 full-time developer)  
**Key Milestones:**
- Week 1: Complete Phase 1-3 (Setup, Components, Home Page)
- Week 2: Complete Phase 4-5 (Search API, Results Page)
- Week 3: Complete Phase 6-7 (Booking Modal, WhatsApp Integration)
- Week 4: Complete Phase 8-10 (Data, Testing, Polish)
- Week 5-6: Deployment & Monitoring

---

**Document Status:** Ready for Development  
**Last Updated:** January 26, 2026
