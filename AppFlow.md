# LankanLens - Application Flow & User Journeys

**Version:** 1.0  
**Date:** January 2026  
**Document Owner:** UX Architect

---

## Table of Contents

1. [Journey 1: The Search Journey (Happy Path)](#journey-1-the-search-journey-happy-path)
2. [Journey 2: The Booking Journey - The Handshake (Happy Path)](#journey-2-the-booking-journey---the-handshake-happy-path)
3. [Journey 3: The Empty State (Edge Case)](#journey-3-the-empty-state-edge-case)

---

## Journey 1: The Search Journey (Happy Path)

### Overview
User lands on the home page, enters a search term, selects a city and date, clicks search, and views results from multiple shops.

### Step-by-Step Flow

#### **Step 1: User Lands on Home Page**
- **File:** `index.php`
- **Action:** Page loads displaying the LankanLens header, hero section, and search form
- **Visual Transitions:**
  - Smooth fade-in animation of the hero image (camera equipment)
  - Search form prominently displayed in the center
  - Call-to-action button "Search Equipment" visible
- **User Sees:**
  - "Search for cameras, lenses, lights, and accessories"
  - Three input fields: Search Term, City Dropdown, Date Picker
  - "Search" button (primary CTA)

#### **Step 2: User Enters a Search Term (e.g., "Sony")**
- **File:** `index.php` (client-side validation via `js/search.js`)
- **Action:** User types into the "Search Equipment" input field
- **Visual Transitions:**
  - Input field shows a blinking cursor
  - Optional: Show real-time autocomplete dropdown with matching equipment types (if JavaScript autocomplete is enabled)
  - Placeholder text: "e.g., Sony A7R IV, Nikon Z9, Canon EOS R5..."
- **Validation:**
  - Minimum 2 characters required
  - Accept alphanumeric characters and spaces
  - No special characters except hyphens

#### **Step 3: User Selects a City (e.g., "Colombo")**
- **File:** `index.php` (dropdown populated from database via `js/search.js`)
- **Action:** User clicks the "Select City" dropdown and chooses their location
- **Visual Transitions:**
  - Dropdown expands with smooth slide-down animation
  - List shows Sri Lankan cities: Colombo, Kandy, Galle, Jaffna, Matara, Nugegoda, etc.
  - Selected city highlighted with blue background
- **Data Source:** Cities are pre-loaded from database in `search.php` or hardcoded in `index.php`
- **User Flow:**
  - Click dropdown → Cities appear → Select "Colombo" → Dropdown collapses

#### **Step 4: User Selects a Date**
- **File:** `index.php` (date picker via `js/search.js` using native HTML5 `<input type="date">`)
- **Action:** User clicks the date picker and selects their rental start date
- **Visual Transitions:**
  - Calendar/date picker modal opens
  - Today's date is highlighted
  - Past dates are disabled (greyed out)
  - Selected date shows with blue highlight and checkmark
- **Date Validation:**
  - Minimum date: Today
  - Maximum date: 90 days from today (optional constraint)
  - Date format: YYYY-MM-DD (stored internally, displayed as DD/MM/YYYY to user)

#### **Step 5: User Clicks "Search" Button**
- **File:** `index.php` → `search.php` (via AJAX POST request)
- **Action:** Form data is submitted to the backend
- **Visual Transitions:**
  - Button shows disabled state (greyed out)
  - Loading spinner appears on button: "Searching..." with animated dots
  - Page briefly shows a semi-transparent overlay
  - "Fetching results from shops near you..." message appears
- **Data Sent:**
  ```
  POST /search.php
  {
    "search_term": "Sony",
    "city": "Colombo",
    "rental_date": "2026-02-15"
  }
  ```

#### **Step 6: Backend Processes the Query**
- **File:** `search.php` (API endpoint)
- **Action:** PHP queries the database for matching equipment
- **Process:**
  1. Validate search inputs
  2. Query `equipment` table: Match `equipment_name`, `brand`, `equipment_type` against search term
  3. Filter by `city_id` from shops (through `shop_location` join)
  4. Filter by availability: Check `inventory` table for `available_quantity > 0`
  5. Join with `shops` and `ratings` tables for shop details and reviews
  6. Sort results by:
     - Exact brand matches first (e.g., "Sony" exact match)
     - Rental price (low to high)
     - Shop rating (high to low)
  7. Return JSON response with results
- **Database Query:**
  ```sql
  SELECT 
    e.equipment_id, e.equipment_name, e.brand, e.equipment_type, e.description,
    e.daily_rate_lkr, i.available_quantity,
    s.shop_name, s.shop_phone, s.shop_city,
    r.average_rating, r.total_reviews
  FROM equipment e
  JOIN inventory i ON e.equipment_id = i.equipment_id
  JOIN shops s ON e.shop_id = s.shop_id
  LEFT JOIN ratings r ON s.shop_id = r.shop_id
  WHERE 
    (e.equipment_name LIKE '%Sony%' OR e.brand LIKE '%Sony%')
    AND s.shop_city = 'Colombo'
    AND i.available_quantity > 0
  ORDER BY e.brand DESC, e.daily_rate_lkr ASC, r.average_rating DESC
  ```

#### **Step 7: Results Page Loads with Results**
- **File:** `results.php` (or AJAX updates to `index.php`)
- **Action:** Search results are rendered and displayed to the user
- **Visual Transitions:**
  - Loading spinner fades out
  - Results container slides in from bottom with staggered animation
  - Each result card animates in sequentially (0.1s delay between cards)
  - Page scrolls to top of results automatically
- **Results Display:**
  - **Header:** "Sony equipment available in Colombo (4 results)"
  - **Filter Bar:** Option to filter by equipment type, price range, rating (optional)
  - **Result Cards:** Each card displays:
    - Equipment image (or generic fallback icon)
    - Equipment name (e.g., "Sony A7R IV")
    - Shop name with rating (e.g., "⭐ Pro Lens Rental - 4.8/5")
    - Daily rental rate (e.g., "₹15,500 LKR per day")
    - Availability badge (e.g., "In stock")
    - Condition (e.g., "Excellent")
    - "Check Availability" button (primary CTA)

#### **Step 8: User Explores Results (Optional)**
- **File:** `results.php` or `product.php` (if clicking for more details)
- **Action:** User can:
  - Scroll through results
  - Click a card to view detailed specs (opens product detail modal)
  - Compare equipment across shops
  - Read shop reviews
- **Visual Transitions:**
  - Hover effect: Result card shows slight shadow lift and scale (1.02x)
  - Click → Product detail modal slides in from right
  - Product detail includes: Full specs, rental terms, cancellation policy, shop address

---

## Journey 2: The Booking Journey - The Handshake (Happy Path)

### Overview
User finds equipment → Clicks "Check Availability" → Modal appears for name and duration → Clicks "Send Request" → WhatsApp opens with pre-filled message.

### Step-by-Step Flow

#### **Step 1: User Clicks "Check Availability" Button**
- **File:** `results.php` or `product.php` (JavaScript event listener in `js/booking.js`)
- **Action:** User clicks the "Check Availability" button on a search result or product detail page
- **Visual Transitions:**
  - Button shows pressed state (darker background, slight inset shadow)
  - Button briefly shows loading state: "Opening..." with spinner
  - Modal appears with fade-in + scale animation (from 0.8x to 1x over 300ms)
  - Background dims with semi-transparent overlay
- **Data Prepared:**
  - Equipment ID, equipment name, shop ID, shop name, shop phone number passed to modal

#### **Step 2: Modal Appears with Booking Form**
- **File:** `index.php` or `results.php` (modal HTML structure in `js/booking.js` or template)
- **Action:** Modal displays a form asking for user details
- **Visual Transitions:**
  - Modal slides up from bottom with smooth easing (cubic-bezier(0.25, 0.46, 0.45, 0.94))
  - Form inputs have focus states with blue outline
  - Floating labels animate: "Your Name" label floats up when input is focused
- **Modal Contains:**
  - **Header:** "Request to Rent: [Equipment Name]"
  - **Subheader:** Shop: [Shop Name] | Contact: [Shop Phone]
  - **Field 1 - Full Name**
    - Input field with placeholder: "Enter your full name"
    - Validation: Required, min 2 characters
    - Character counter: "0/50 characters"
  - **Field 2 - Rental Duration**
    - Dropdown: "How many days do you need?"
    - Options: 1 day, 2 days, 3 days, 1 week, 2 weeks, 1 month, Custom
    - If "Custom" selected: Additional input field for specific number of days
  - **Field 3 - Additional Notes (Optional)**
    - Text area: "Any special requests? (e.g., delivery, insurance)"
    - Character counter: "0/200 characters"
  - **Pricing Display:**
    - Daily rate: "₹15,500 LKR/day"
    - Rental duration: "3 days"
    - Subtotal: "₹46,500 LKR"
    - Note: "Final price will be confirmed by shop owner via WhatsApp"
  - **Action Buttons:**
    - "Send Request" (primary, blue, full-width)
    - "Cancel" (secondary, grey outline)

#### **Step 3: User Fills in Their Name**
- **File:** `js/booking.js` (client-side validation)
- **Action:** User types their full name into the "Full Name" field
- **Visual Transitions:**
  - Cursor blinks in the input field
  - Character counter updates in real-time (e.g., "12/50 characters")
  - As user types, a green checkmark appears when name is valid (>= 2 characters)
  - Invalid state (< 2 characters): Red warning icon appears
- **Validation (Real-time):**
  - Minimum 2 characters
  - Maximum 50 characters
  - Allow letters, spaces, hyphens
  - Trim whitespace

#### **Step 4: User Selects Rental Duration**
- **File:** `js/booking.js` (dropdown handler)
- **Action:** User clicks the "How many days?" dropdown and selects duration
- **Visual Transitions:**
  - Dropdown opens with smooth slide-down animation
  - Options have hover states (light blue background)
  - Selected option shows checkmark and is highlighted
  - Pricing updates dynamically below (e.g., "3 days × ₹15,500 = ₹46,500 LKR")
  - Price update animates with a brief flash or color change
- **Duration Options:**
  - 1 day, 2 days, 3 days, 1 week (7 days), 2 weeks (14 days), 1 month (30 days)
  - Custom: If selected, reveals input field for specific number of days
- **Price Recalculation:**
  ```javascript
  Total Price = Daily Rate × Number of Days
  ```

#### **Step 5: User Clicks "Send Request" Button**
- **File:** `js/booking.js` → `booking.php` (via AJAX POST)
- **Action:** Form data is validated and submitted to backend
- **Client-side Validation:**
  - Name is not empty and >= 2 characters
  - Duration is selected
  - Show error messages if validation fails (red text, shake animation)
- **Visual Transitions:**
  - Button becomes disabled and shows loading state: "Sending..." with spinner
  - Brief 500ms delay to show loading state (improves perceived quality)
  - If validation fails: Form shakes horizontally (CSS animation)
  - If validation succeeds: Modal briefly shows success checkmark, then closes

#### **Step 6: Backend Generates WhatsApp Message**
- **File:** `booking.php` (API endpoint)
- **Action:** PHP generates a WhatsApp-formatted message URL
- **Process:**
  1. Validate request data (name, duration, equipment ID, shop ID)
  2. Retrieve shop WhatsApp number from `shops` table
  3. Construct pre-filled WhatsApp message:
     ```
     Hi, I'm interested in renting [Equipment Name] for [Duration] days.
     My name is [User Name].
     Please confirm availability and provide the final price.
     Additional notes: [User Notes if provided]
     ```
  4. URL encode the message
  5. Create WhatsApp link: `https://wa.me/[SHOP_PHONE]?text=[ENCODED_MESSAGE]`
  6. Optional: Log this request in `booking_requests` table for analytics
  7. Return JSON response with WhatsApp URL and confirmation
- **Database Insert (Optional):**
  ```sql
  INSERT INTO booking_requests (user_name, equipment_id, shop_id, rental_duration_days, additional_notes, created_at)
  VALUES ('[name]', [equipment_id], [shop_id], [duration], '[notes]', NOW())
  ```

#### **Step 7: Browser Opens WhatsApp**
- **File:** `js/booking.js` (after successful API response)
- **Action:** JavaScript opens WhatsApp with pre-filled message via mobile redirect or WhatsApp Web
- **Visual Transitions:**
  - Modal closes with fade-out animation
  - Success toast notification appears: "✓ Opening WhatsApp..."
  - Browser opens WhatsApp (either mobile app or web.whatsapp.com)
  - User is greeted with pre-filled message ready to send
- **Platform-Specific Behavior:**
  - **Mobile:** Opens WhatsApp mobile app with pre-filled message
  - **Desktop:** Redirects to WhatsApp Web with pre-filled message
  - **No WhatsApp Installed:** User directed to web.whatsapp.com or app store
- **Pre-filled Message Example:**
  ```
  Hi, I'm interested in renting Sony A7R IV for 3 days.
  My name is Pradeep.
  Please confirm availability and provide the final price.
  Additional notes: Do you offer delivery to Colombo 4?
  ```

#### **Step 8: User Sends Message & Shop Owner Responds**
- **File:** N/A (occurs in WhatsApp, outside app)
- **Action:** User sends the pre-filled message to shop owner
- **Expected Flow:**
  - Shop owner receives message in WhatsApp
  - Shop owner confirms availability and final price
  - Shop owner provides rental terms, pickup/delivery options
  - User and shop owner finalize details via WhatsApp
- **Visual Transitions:** None (user is in WhatsApp app)
- **Back in App (Optional):**
  - User can close WhatsApp and return to LankanLens
  - Optional: Show "Booking Request Sent" confirmation screen
  - Display: "Shop owner will respond within 2 hours"
  - Option to browse more equipment or save favorite

---

## Journey 3: The Empty State (Edge Case)

### Overview
User searches for equipment that isn't in the database or isn't available in their selected city. The app gracefully handles this scenario and guides the user toward alternatives.

### Step-by-Step Flow

#### **Step 1: User Performs a Search with No Results**
- **File:** `search.php` (database query returns empty array)
- **Action:** Backend query finds zero matching results
- **Scenarios:**
  - Equipment type doesn't exist in inventory (e.g., "underwater camera housing")
  - Equipment exists but not in selected city (e.g., "Arri Alexa" in Matara)
  - Equipment exists but all inventory is currently rented out
  - Typo in search term (e.g., "Sny" instead of "Sony")

#### **Step 2: Results Page Displays Empty State**
- **File:** `results.php` (or `index.php` with AJAX update)
- **Action:** Instead of blank page, user sees helpful empty state message
- **Visual Transitions:**
  - Loading spinner fades out
  - Centered empty state illustration animates in (gentle fade + slight bounce)
  - Headline text fades in below illustration
  - Call-to-action buttons slide up from bottom
- **Empty State Content:**
  - **Illustration:** Grey icon of a camera with a question mark (or generic "not found" icon)
  - **Headline:** "No results found for '[search term]' in [City]"
  - **Subheading:** "But don't worry! Here's what we recommend:"
  - **Body Text:** Choose one of the messages below based on context:
    - **If equipment doesn't exist anywhere:**
      ```
      "We don't have [Equipment Name] in our inventory yet. 
      Our rental shops in Sri Lanka primarily offer..."
      ```
    - **If equipment exists elsewhere:**
      ```
      "We found [Equipment Name] available in Kandy, Galle, and Nugegoda.
      Select a nearby city to see options."
      ```
    - **If out of stock:**
      ```
      "[Equipment Name] is not currently available in [City], 
      but it may be available from other rental shops soon."
      ```

#### **Step 3: Offer Alternative Actions**
- **File:** `results.php` (JavaScript handlers in `js/empty-state.js`)
- **Action:** Display multiple pathways to help user find what they need
- **Visual Transitions:**
  - Buttons appear in sequence (staggered animation, 100ms delay each)
  - Buttons have hover states (slight scale, shadow lift)
- **Action Options Presented:**

##### **Option A: Expand Search to Other Cities**
- **Button Text:** "Search in nearby cities"
- **Action:** When clicked:
  1. Disable current city filter
  2. Rerun search across all Sri Lankan cities
  3. Display results grouped by city
  4. Show distance/travel time to each city (optional)
- **Visual Transition:** Page re-runs search, results table updates with city headers
- **Results Format:**
  ```
  Colombo (15 results)
  - Sony A7R IV - Pro Lens Rental - ₹15,500/day
  - Sony A7R III - Photography Hub - ₹12,000/day
  
  Kandy (8 results)
  - Sony A7R IV - Mountain Camera Rentals - ₹16,000/day
  - Sony A6700 - Gear Central - ₹9,500/day
  
  Galle (3 results)
  - Sony A6400 - Coastal Photo Rentals - ₹7,500/day
  ```

##### **Option B: Browse Similar Equipment**
- **Button Text:** "Browse similar gear"
- **Action:** When clicked:
  1. Parse search term to extract equipment type
  2. If search was "Sony A7R IV": Show all full-frame mirrorless cameras
  3. If search was "16mm lens": Show all wide-angle lenses
  4. Filter by city, sort by popularity
- **Visual Transition:** Results page updates with related equipment
- **Results Display:**
  ```
  You searched for: Sony A7R IV
  
  Similar Full-Frame Cameras Available in Colombo:
  - Sony A7R III - Pro Lens Rental - ₹12,000/day
  - Canon EOS R5 - Photography Pro - ₹18,000/day
  - Nikon Z9 - Gear Haven - ₹20,000/day
  ```

##### **Option C: View All Equipment in City**
- **Button Text:** "View all equipment in [City]"
- **Action:** When clicked:
  1. Clear search term filter
  2. Keep city filter
  3. Display all available equipment grouped by category
- **Visual Transition:** Results reorganize by equipment type (lenses, bodies, lights, etc.)
- **Results Format:**
  ```
  Available in Colombo:
  
  Camera Bodies (12 items)
  - Sony A7R IV, Canon EOS R5, Nikon Z9, ...
  
  Lenses (28 items)
  - Sigma 24-70mm f/2.8, Sony 85mm f/1.8, ...
  
  Lighting (8 items)
  - Godox SL-60W, Neewer LED Panel, ...
  ```

##### **Option D: Request Equipment (New Feature)**
- **Button Text:** "Request this equipment"
- **Action:** When clicked:
  1. Modal opens asking user: "What equipment are you looking for?"
  2. User enters equipment details
  3. Submit form as a "request" to shop owners
  4. Shop owners can respond if they acquire the item
- **Visual Transition:** Request modal slides up with form
- **Form Fields:**
  - Equipment name (required)
  - Equipment type (dropdown)
  - Preferred city (dropdown)
  - When needed (date picker)
  - Email/WhatsApp for shop owner response
- **Confirmation:** "Request submitted! Shops will contact you if they acquire this equipment."

#### **Step 4: Try Another Search**
- **Button Text:** "Try a new search"
- **Action:** When clicked:
  1. Clear all filters and search inputs
  2. Scroll to top of page
  3. Focus on search input field
  4. Show placeholder text: "Try searching for... Canon, Nikon, telephoto, tripod"
- **Visual Transition:** Page scrolls smoothly to top, search form highlights with subtle pulse animation

#### **Step 5: Browse Popular Categories (Optional)**
- **File:** `results.php` (or `index.php`)
- **Action:** Below empty state message, display popular equipment categories
- **Visual Transitions:**
  - Category cards appear in grid layout (fade-in with stagger)
  - Cards have hover effects (shadow lift, slight scale)
- **Category Cards Display:**
  - **Card 1:** "📷 Camera Bodies" - "12 available in Colombo"
  - **Card 2:** "🔍 Lenses" - "28 available in Colombo"
  - **Card 3:** "💡 Lighting Gear" - "8 available in Colombo"
  - **Card 4:** "🪜 Accessories" - "15 available in Colombo"
- **Click Handler:** Clicking a category filters results to show only that category

#### **Step 6: Fallback: Contact Shop Owners Directly**
- **File:** `results.php` (or contact template)
- **Action:** If all else fails, provide option to contact shops manually
- **Visual Transitions:**
  - Section slides up from bottom with gradient background
  - Contact information appears with stagger animation
- **Content:**
  ```
  Didn't find what you're looking for?
  
  Contact these shops in Colombo directly:
  
  1. Pro Lens Rental
     ☎ +94 701 234 567 | 💬 WhatsApp
  
  2. Photography Pro
     ☎ +94 702 345 678 | 💬 WhatsApp
  
  3. Gear Haven
     ☎ +94 703 456 789 | 💬 WhatsApp
  ```
- **Buttons:**
  - "Call" - Opens phone dialer
  - "WhatsApp" - Opens WhatsApp with shop name pre-filled
  - "Visit Website" - Opens shop website if available

---

## Summary of PHP File Structure

### Core Files Involved:

| File | Purpose | Journey(s) |
|------|---------|-----------|
| `index.php` | Landing page with search form | Journey 1 (Steps 1-2) |
| `search.php` | API endpoint for search queries | Journey 1 (Steps 5-6) |
| `results.php` | Results page display | Journey 1 (Steps 7-8), Journey 3 (Steps 2-6) |
| `product.php` | Product detail modal | Journey 1 (Step 8 - optional) |
| `booking.php` | API endpoint for booking requests | Journey 2 (Step 6) |
| `js/search.js` | Client-side search form logic | Journey 1 (Steps 2-5) |
| `js/booking.js` | Booking modal and WhatsApp link generation | Journey 2 (Steps 1-8) |
| `js/empty-state.js` | Empty state interaction handlers | Journey 3 (Steps 3-6) |

### Database Tables Referenced:

| Table | Purpose |
|-------|---------|
| `equipment` | Equipment catalog (name, brand, type, description, daily_rate_lkr) |
| `shops` | Shop details (name, phone, city, rating) |
| `inventory` | Equipment availability (available_quantity, shop_id, equipment_id) |
| `shop_location` | Shop locations (for city-based filtering) |
| `ratings` | Shop ratings and reviews (average_rating, total_reviews) |
| `booking_requests` | Logged booking requests (user_name, equipment_id, shop_id, created_at) |

---

## Visual Design Guidelines

### Loading States
- Use subtle spinner (rotating circle) instead of progress bars
- Display supportive messaging: "Searching nearby shops...", "Fetching availability..."
- Fade spinner out before showing results (don't abruptly replace)

### Modal Animations
- Entrance: Fade-in + scale-up (300ms, cubic-bezier(0.25, 0.46, 0.45, 0.94))
- Exit: Scale-down + fade-out (200ms)
- Overlay dim: Semi-transparent black (rgba(0, 0, 0, 0.5))

### Error & Validation
- Invalid input: Red border on input field + red error text below
- Form shake: 10px horizontal movement (200ms duration)
- Success: Green checkmark icon + green text

### Empty States
- Use illustrations (SVG icons or simple graphics)
- Provide 2-3 clear action buttons
- Ensure copy is supportive, not apologetic

### Color Scheme
- Primary: Blue (#0066FF or similar)
- Success: Green (#00AA44)
- Error: Red (#CC0000)
- Neutral: Grey (#666666)
- Text: Dark grey (#333333) on white background

---

**End of AppFlow Document**
