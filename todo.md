# LankanLens - Master To-Do List

**Project:** Camera Rental Aggregator for Sri Lanka  
**Status:** Planning → Development  
**Last Updated:** January 26, 2026

---

## Phase 1: Environment & Database Setup

### Folder Structure & Configuration
- [ ] Create project folder structure: `/config`, `/includes`, `/public`, `/api`, `/assets/css`, `/assets/js`, `/assets/images`, `/database`, `/logs`, `/uploads`
- [ ] Create `.env.example` file with DB credentials template
- [ ] Create `.env` file with local database credentials (do NOT commit)
- [ ] Add `.gitignore` to exclude `.env`, `/logs`, `/uploads`, `/node_modules`
- [ ] Create `config/config.php` with app constants and environment variable loader

### MySQL Database & Schema
- [ ] Create MySQL database `lankanlens` with UTF8MB4 charset
- [ ] Create `database/schema.sql` with all 8 table definitions:
  - [ ] `shops` table (shop_id, shop_name, phone, whatsapp_number, city, etc.)
  - [ ] `equipment_categories` table (category_id, category_name, slug)
  - [ ] `equipment` table (equipment_id, category_id, brand, model, shop_id, description)
  - [ ] `inventory` table (inventory_id, equipment_id, shop_id, daily_rate_lkr, available_quantity)
  - [ ] `booking_requests` table (request_id, user_name, equipment_id, shop_id, rental_duration_days)
  - [ ] `shop_locations` table (location_id, shop_id, city_name, address)
  - [ ] `shop_reviews` table (review_id, shop_id, user_name, rating)
  - [ ] `search_logs` table (log_id, search_term, search_city, result_count)
- [ ] Create database/migrations folder for future schema changes
- [ ] Execute schema.sql to create all tables in MySQL

### PDO Database Connection
- [ ] Create `config/database.php` with PDO connection class
  - [ ] Implement `__construct()` to load environment variables and establish PDO connection
  - [ ] Implement `query()` method with prepared statements
  - [ ] Implement `fetchOne()` and `fetchAll()` methods
  - [ ] Implement `insert()`, `update()`, `delete()` helper methods
  - [ ] Add error logging to `/logs/errors.log`

### Project Bootstrap
- [ ] Create `/logs` directory with write permissions (chmod 755)
- [ ] Create `/uploads` directory with write permissions
- [ ] Test PDO connection by running a simple query in terminal: `php -r "require 'config/database.php'; $db = new Database();"`

---

## Phase 2: Shared Components & Layout

### HTML Layout & Header
- [ ] Create `includes/header.php` with:
  - [ ] HTML5 doctype and meta tags (charset, viewport)
  - [ ] Tailwind CSS CDN link: `<script src="https://cdn.tailwindcss.com"></script>`
  - [ ] Custom CSS link: `<link rel="stylesheet" href="/assets/css/styles.css">`
  - [ ] Logo and site title
  - [ ] Open `<body>` tag (closing in footer.php)

### Navigation Bar
- [ ] Create `includes/navbar.php` with:
  - [ ] LankanLens logo/brand link to home
  - [ ] Search shortcut link
  - [ ] "Browse by Category" dropdown (camera bodies, lenses, lighting, accessories)
  - [ ] Mobile hamburger menu (optional for Phase 2, can be Phase 3)
  - [ ] Tailwind classes for responsive design (flex, justify-between, items-center)
  - [ ] Styling: bg-white, border-bottom, shadow-sm

### Footer
- [ ] Create `includes/footer.php` with:
  - [ ] Close `</body>` and `</html>` tags
  - [ ] Footer content: Copyright, About, Contact, Social links
  - [ ] Terms & Conditions, Privacy Policy links
  - [ ] LKR currency note
  - [ ] Tailwind bg-gray-800, text-white styling

### Error Handler
- [ ] Create `includes/error-handler.php` to:
  - [ ] Display user-friendly error messages
  - [ ] Log errors to `/logs/errors.log`
  - [ ] Handle PDO exceptions gracefully
  - [ ] Display "404 Not Found" or "Something went wrong" pages

### Custom CSS & Animations
- [ ] Create `assets/css/styles.css` with:
  - [ ] CSS variables: `--color-primary`, `--color-success`, `--color-danger`
  - [ ] Keyframe animations: `@keyframes spin`, `fadeIn`, `shake`, `slideUp`
  - [ ] Utility classes: `.spinner`, `.fade-in`, `.shake`, `.slide-up`, `.card-hover`
  - [ ] Modal overlay: `.modal-overlay { backdrop-filter: blur(4px); }`
  - [ ] Responsive typography for mobile (max-width: 768px)

---

## Phase 3: Home Page & Search Form

### Home Page Layout
- [ ] Create `public/index.php` with:
  - [ ] Require `includes/header.php`
  - [ ] Require `includes/navbar.php`
  - [ ] Hero section with background image and call-to-action
  - [ ] Search form section (centered, prominent)

### Search Form Component
- [ ] Create search form in `public/index.php` or `includes/search-form.php`:
  - [ ] Input field: Equipment search term (placeholder: "e.g., Sony A7R IV")
  - [ ] Dropdown: City selection (hardcoded or fetch from database)
  - [ ] Date picker: Rental start date (HTML5 date input)
  - [ ] Button: "Search" with data-action attribute
  - [ ] Tailwind styling: grid layout, rounded inputs, primary button color
- [ ] Add form ID and data attributes for JavaScript targeting
- [ ] Add validation message container (hidden by default)

### JavaScript: Search Form Logic
- [ ] Create `assets/js/search.js` with:
  - [ ] Event listener on "Search" button click
  - [ ] Client-side validation: search term (min 2 chars), city selected, date selected
  - [ ] Show loading state on button: "Searching..." with spinner
  - [ ] Send AJAX POST request to `/api/search-api.php`
  - [ ] On success: redirect to `/public/results.php?q=[term]&city=[city]&date=[date]`
  - [ ] On error: display error toast notification

### Featured Gear Section (Optional)
- [ ] Add "Popular Rentals" or "Featured Gear" section below search form
- [ ] Display 4-6 random equipment items in a grid
- [ ] Each item card shows: image, equipment name, shop name, rating, daily rate, "Check Availability" button

---

## Phase 4: Search Backend & API

### Search API Endpoint
- [ ] Create `public/api/search-api.php` with:
  - [ ] Validate incoming POST parameters: search_term, city, rental_date
  - [ ] Sanitize inputs (trim, htmlspecialchars)
  - [ ] Query database with full-text search on equipment_name, brand, model_number
  - [ ] Filter by city (via shop's primary_city)
  - [ ] Filter by availability (inventory.available_quantity > 0)
  - [ ] Order results by: brand exact match DESC, daily_rate_lkr ASC, average_rating DESC
  - [ ] Limit results to 50 items
  - [ ] Return JSON response with success/error status and results array
  - [ ] Log search to `search_logs` table for analytics

### Database Query for Search
- [ ] Write SQL query that:
  - [ ] SELECTs equipment fields: equipment_id, equipment_name, brand, model_number, description, image_url
  - [ ] JOINs with inventory table
  - [ ] JOINs with shops table
  - [ ] LEFT JOINs with shop_reviews for average_rating
  - [ ] Uses WHERE clause with LIKE for search term
  - [ ] Uses WHERE for city filter
  - [ ] Uses WHERE for available_quantity > 0
  - [ ] Uses FULLTEXT INDEX for better performance
  - [ ] ORDER BY for relevance sorting

### Error Handling in API
- [ ] Handle PDO connection errors gracefully
- [ ] Handle empty search results (return success with empty array)
- [ ] Handle invalid parameters (return error with 400 status code)
- [ ] Log all API errors to `/logs/errors.log`

---

## Phase 5: Results Page & Gear Cards

### Results Page Layout
- [ ] Create `public/results.php` with:
  - [ ] Require header and navbar
  - [ ] Get query parameters: search_term, city, rental_date from URL
  - [ ] Display search summary: "Results for '[term]' in [City] ([count] items found)"
  - [ ] Results container (initially empty, populated by JavaScript)
  - [ ] Empty state container (hidden, shown if no results)
  - [ ] Pagination controls (if > 12 results)

### Gear Card Component
- [ ] Design equipment card layout with:
  - [ ] Equipment image (placeholder if not available)
  - [ ] Equipment name and brand (e.g., "Sony A7R IV")
  - [ ] Shop name with star rating (e.g., "⭐ Pro Lens Rental - 4.8/5")
  - [ ] Condition badge (Excellent, Good, Fair)
  - [ ] Daily rate in LKR (e.g., "₹15,500 LKR/day")
  - [ ] Availability badge ("In Stock")
  - [ ] "Check Availability" button with data-action attribute
  - [ ] Tailwind card styling: rounded corners, shadow, hover effects

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

## Phase 8: Additional Pages & Features

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
  
- [ ] Test Booking Journey:
  - [ ] Click "Check Availability" on a gear card
  - [ ] Modal opens with correct equipment info
  - [ ] Name validation works
  - [ ] Duration dropdown populates and updates price
  - [ ] "Send Request" button generates WhatsApp link
  - [ ] WhatsApp opens with correct pre-filled message

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
