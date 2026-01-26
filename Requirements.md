# LankanLens - Requirements Document

**Version:** 1.0  
**Date:** January 2026  
**Product:** Camera Rental Aggregator Platform for Sri Lanka  
**Document Owner:** Senior Product Manager

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Project Goals & Success Metrics](#project-goals--success-metrics)
3. [User Personas](#user-personas)
4. [Functional Requirements](#functional-requirements)
5. [User Interface Requirements](#user-interface-requirements)
6. [Data Requirements](#data-requirements)
7. [Technical Constraints & Assumptions](#technical-constraints--assumptions)

---

## Project Overview

**LankanLens** is a centralized camera rental aggregator designed specifically for the Sri Lankan photography market. The platform enables photographers and content creators to efficiently search, compare, and book camera equipment (lenses, bodies, lights, and accessories) across multiple rental shops distributed throughout Sri Lanka without managing multiple vendor relationships or payment systems.

Instead of traditional payment gateway integration, LankanLens uses a "WhatsApp Handshake" model—a streamlined mobile-first approach that aligns with Sri Lankan user behavior and reduces friction in the rental booking process.

### Key Differentiator
Unlike international platforms, LankanLens understands the local market:
- Pricing in LKR (Sri Lankan Rupees)
- Location-aware search based on Sri Lankan cities
- Direct shop owner involvement through WhatsApp
- Mobile-first design for users in emerging markets

---

## Project Goals & Success Metrics

### Primary Goals

1. **Market Consolidation**
   - Reduce the fragmentation of camera rental information across multiple shops
   - Create a single source of truth for available equipment in Sri Lanka
   - Goal: Aggregate 15+ rental shops within first 6 months

2. **User Accessibility**
   - Eliminate friction in the equipment discovery process
   - Enable photographers of all skill levels to find gear quickly
   - Goal: Search-to-WhatsApp conversion should take <2 minutes

3. **Shop Owner Enablement**
   - Provide rental shop owners with zero-cost inventory exposure
   - Generate qualified leads without payment infrastructure requirements
   - Goal: 100% of participating shops report inquiry volume increase

4. **Market Growth**
   - Establish LankanLens as the go-to platform for equipment rental in Sri Lanka
   - Build network effects that attract both users and shops
   - Goal: 1000+ monthly active users by month 12

### Success Metrics

| Metric | Target | Timeline |
|--------|--------|----------|
| Total Registered Shops | 15+ | 6 months |
| Monthly Active Users | 1,000+ | 12 months |
| Average Equipment Search Time | <2 min | Ongoing |
| Shop Response Rate (WhatsApp) | >80% | Ongoing |
| Return User Rate | 40%+ | 6 months |
| Mobile Traffic | 85%+ | Ongoing |

---

## User Personas

### Persona 1: Pradeep - The Professional Wedding Photographer

**Demographics:**
- Age: 32 years old
- Location: Colombo
- Experience: 8+ years in professional photography
- Tech Comfort: High (regularly uses editing software, social media)

**Goals:**
- Need equipment flexibility for multiple wedding bookings in a single week
- Want access to professional-grade gear without capital investment
- Prefer quick, hassle-free booking process via mobile
- Need guaranteed availability for peak wedding season dates

**Pain Points:**
- Currently juggling phone calls with 4-5 different shops
- High rental costs due to unavailable competitive pricing information
- Equipment availability uncertainty (often booking months in advance)
- Time-consuming negotiation process with individual shop owners

**How LankanLens Helps:**
- Compare prices across multiple shops in one place
- See availability calendar for specific equipment
- Quick WhatsApp coordination with multiple shops
- Access to customer reviews and ratings of rental quality

**Device Usage:** Primarily smartphone (iPhone 13), occasionally iPad for planning  
**Session Duration:** 10-15 minutes per session  
**Frequency:** 2-3 times per month during wedding season

---

### Persona 2: Ravi - The Rental Shop Owner

**Demographics:**
- Age: 45 years old
- Location: Kandy
- Business Type: Owner of "Epic 84 Camera Rentals" with 5 employees
- Tech Comfort: Medium (email, WhatsApp, basic phone)

**Goals:**
- Increase inquiries and rental bookings with minimal marketing cost
- Manage inventory visibility across growing demand
- Maintain direct relationships with customers
- Avoid payment processing fees and complex integrations

**Pain Points:**
- Limited online visibility (only local Google Maps presence)
- Competes with shops in Colombo for regional customers
- Spends time answering repetitive availability questions
- Misses bookings from photographers unaware of inventory

**How LankanLens Helps:**
- Free inventory listing with no commission
- Receive qualified inquiries directly to WhatsApp
- Maintain direct communication with customers
- Compete with larger shops through aggregated visibility

**Device Usage:** Android phone (basic smartphone), doesn't use computers much  
**Session Duration:** 5-10 minutes to update inventory  
**Frequency:** 3-4 times per week

---

### Persona 3: Anura - The Hobbyist/Occasional Photographer (Secondary Persona)

**Demographics:**
- Age: 26 years old
- Location: Galle
- Photography: Hobby/Instagram content creation
- Tech Comfort: High (Instagram, TikTok native)

**Goals:**
- Borrow equipment for weekend projects without breaking the budget
- Discover new gear to learn photography
- Connect with other photography enthusiasts

**Pain Points:**
- Rental prices feel high for occasional use
- Doesn't know where to start looking for gear
- Has no established relationships with rental shops

---

## Functional Requirements

### 1. Search & Discovery

#### 1.1 Equipment Search
- **Requirement:** Users can search for specific equipment types
  - Search by equipment name (e.g., "Canon 5D Mark IV", "Godox SL-60W")
  - Search by equipment category (Body, Lens, Light, Accessory)
  - Search by focal length range (for lenses: 14-24mm, 50mm, 70-200mm, etc.)
  - Real-time search suggestions with autocomplete
  - Instant results display (< 500ms response time)

#### 1.2 Location-Based Filtering
- **Requirement:** Users can filter equipment by shop location
  - Display all major Sri Lankan cities (Colombo, Kandy, Galle, Negombo, Jaffna, Anuradhapura)
  - Allow multiple city selection
  - Show "Equipment available in selected cities" count
  - Display distance from user (if location enabled)
  - Dropdown with city categorization (Western, Central, Southern, Northern regions)

#### 1.3 Date-Based Availability
- **Requirement:** Users can check equipment availability for specific rental periods
  - Calendar picker for start date (current date or future)
  - Calendar picker for end date (minimum 1-day rental)
  - Display price per day/weekend/week options
  - Show "Not Available" for booked dates
  - Display deposit/damage waiver costs (if applicable)
  - Real-time availability indicators (badge: "Available Now", "2 More Available", "Fully Booked")

#### 1.4 Advanced Filtering
- **Requirement:** Enable power users to narrow results efficiently
  - Filter by price range (LKR 500 - LKR 50,000+)
  - Filter by brand (Canon, Nikon, Sony, Godox, etc.)
  - Filter by rating (4+ stars, 4.5+ stars, etc.)
  - Filter by condition (Excellent, Good, Fair)
  - Combine multiple filters simultaneously

#### 1.5 Sorting Options
- **Requirement:** Users can organize results for easier decision-making
  - Sort by price (low to high, high to low)
  - Sort by rating (highest first)
  - Sort by nearest location
  - Sort by newest listing
  - Remember user's last sorting preference

### 2. Equipment Listings

#### 2.1 Equipment Detail Page
- **Requirement:** Comprehensive information display for each rental item
  - Equipment name, brand, model, and specifications
  - High-quality product images (minimum 3 images)
  - Detailed specifications (e.g., sensor size, resolution, aperture range, light output)
  - Daily/weekly/monthly rental rates (in LKR)
  - Deposit amount required
  - Damage waiver cost (optional insurance)
  - Current availability status
  - Customer reviews and ratings (1-5 star system)
  - Shop owner name and contact information
  - Shop location with embedded map
  - Equipment condition description
  - Rental terms and conditions specific to shop
  - What's included (cables, batteries, memory cards, carrying case, etc.)
  - Last updated timestamp

#### 2.2 Equipment Listing by Shop Owners
- **Requirement:** Shop owners can manage their equipment inventory
  - Add new equipment with all required fields
  - Edit existing equipment details (prices, availability, images)
  - Upload images (JPG, PNG max 5MB each)
  - Mark equipment as available/unavailable
  - Set rental price tiers (daily, weekly, monthly if applicable)
  - Bulk import/export equipment list (CSV format)
  - View analytics on equipment views and inquiries

### 3. WhatsApp Handshake Booking System

#### 3.1 WhatsApp Integration
- **Requirement:** Seamless redirection to WhatsApp for finalization
  - "Rent Now" button displays shop owner's WhatsApp number
  - Pre-filled WhatsApp message template with:
    - Equipment name and model
    - Requested rental dates
    - Customer name (if logged in)
    - Pickup/delivery location
  - One-click WhatsApp redirect (opens WhatsApp app or web)
  - Message template example:
    ```
    Hi! I'm interested in renting [Equipment Name] 
    from [Start Date] to [End Date]. 
    Can you confirm availability and pricing? Thanks!
    ```
  - Fallback to direct WhatsApp link if user doesn't have app installed
  - Display shop owner's response time average on listing

#### 3.2 Booking History (For Logged-In Users)
- **Requirement:** Users can track their rental inquiries
  - List all equipment inquiries sent via WhatsApp
  - Display inquiry date and time
  - Show shop owner response status (pending, responded)
  - Allow users to add notes/save equipment for later
  - Ability to contact same shop owner without re-searching

#### 3.3 Shop Owner WhatsApp Notifications
- **Requirement:** Shop owners receive clear booking inquiries
  - Automatic message formatting from platform
  - Inquiry includes all relevant details (customer needs, dates, location)
  - Option to sync inquiry history with platform (shop owner dashboard)

### 4. Shop Profiles & Ratings

#### 4.1 Shop Profile Page
- **Requirement:** Dedicated page for each rental shop
  - Shop name and location
  - Business hours and contact information
  - Complete inventory listing
  - Average rating (1-5 stars)
  - Total review count
  - Shop description/about section
  - Website link (if applicable)
  - Equipment categories they specialize in
  - Response time metrics (average time to respond to inquiries)

#### 4.2 Ratings & Reviews
- **Requirement:** Community-driven quality assurance
  - Users can rate experience with shop (1-5 stars)
  - Users can write text reviews (up to 500 characters)
  - Reviews are moderated (no spam/profanity)
  - Display review date, rating, and reviewer name
  - Shop owners can respond to reviews
  - Minimum 1 rental completion required to leave review
  - Reviews should display: Equipment condition, Communication, Fairness of pricing, Timeliness of delivery

### 5. User Account Management

#### 5.1 Customer Accounts (Optional/Basic)
- **Requirement:** Enable personalization without mandatory login
  - Optional account creation via email or WhatsApp number
  - Save favorite equipment for quick access
  - View rental inquiry history
  - Save preferred cities/equipment types for faster searches
  - Push notifications for new availability in saved categories
  - No password required option (WhatsApp-based login)

#### 5.2 Shop Owner Accounts
- **Requirement:** Administrative access for inventory management
  - Account creation with shop verification
  - Inventory management dashboard
  - Analytics and inquiry tracking
  - Equipment editing and image upload
  - Promotion/feature tools (paid optional feature)
  - Analytics: views per equipment, inquiries per day, response rate

### 6. Search & Listing Performance

#### 6.1 Performance Requirements
- **Requirement:** Fast, responsive search experience
  - Search results load in < 500ms
  - Autocomplete suggestions appear in < 300ms
  - Page load time < 2 seconds on 4G
  - Images lazy-load as user scrolls
  - Filters update results dynamically without full page refresh

#### 6.2 Mobile Responsiveness
- **Requirement:** Optimized for mobile-first usage
  - Touch-friendly buttons and UI elements
  - Swipe navigation for image galleries
  - Optimized for screens 320px - 1920px wide
  - Mobile menu hamburger for navigation
  - One-handed usability priority

---

## User Interface Requirements

### 1. Design Philosophy

**Aesthetic:** Modern Dark Theme with Photography-Centric Visuals
- **Primary Purpose:** Create an atmosphere that appeals to photographers
- **Color Psychology:** Dark mode reduces eye strain during extended searches, aligns with photography/cinema industry standards
- **Brand Identity:** Professional, trustworthy, creative-focused

### 2. Color Palette

**Primary Colors:**
- **Background:** `#0F0F0F` (Deep Black) - Main background
- **Surface:** `#1A1A1A` (Dark Charcoal) - Cards, containers
- **Accent Primary:** `#FF6B35` (Vibrant Orange) - Call-to-action buttons, highlights
- **Accent Secondary:** `#4A90E2` (Professional Blue) - Secondary actions, links
- **Text Primary:** `#FFFFFF` (White) - Main text
- **Text Secondary:** `#B0B0B0` (Light Gray) - Secondary text, metadata
- **Success:** `#2ECC71` (Green) - Availability, positive actions
- **Warning:** `#F39C12` (Amber) - Limited availability
- **Error:** `#E74C3C` (Red) - Not available, errors

### 3. Typography

**Font Stack (Tailwind + Google Fonts):**
```
- Headings: 'Poppins' or 'Inter' (Modern, geometric)
- Body: 'Inter' or 'Open Sans' (Highly readable)
- Monospace: 'Fira Code' (Equipment specs/pricing)
```

**Type Hierarchy:**
- **H1:** 48px, bold, brand color (page titles)
- **H2:** 32px, semi-bold, white (section headers)
- **H3:** 24px, medium, white (subsection headers)
- **Body:** 16px, regular, light gray (content)
- **Small:** 14px, regular, medium gray (metadata, timestamps)
- **Label:** 12px, semi-bold, uppercase (form labels, badges)

### 4. Component Design

#### 4.1 Navigation
- **Top Navigation Bar:**
  - Logo on left (LankanLens text + camera icon)
  - Search bar in center (with icons for filters)
  - Shop owner login/profile on right
  - Sticky navigation on scroll
  - Mobile hamburger menu (hidden on desktop)

#### 4.2 Search Interface
- **Search Bar Component:**
  - Rounded search input field (Tailwind `rounded-lg`)
  - Magnifying glass icon (in-field left)
  - Clear button (appears when text entered)
  - Autocomplete dropdown below
  - Large touch targets (min 44px height)

- **Filter Sidebar (Desktop) / Drawer (Mobile):**
  - Filter categories: Type, Location, Price, Rating
  - Collapsible sections for organization
  - Slider for price range
  - Checkbox lists for categories
  - "Apply Filters" button
  - "Clear All" option

#### 4.3 Equipment Card
```
+─────────────────────────────────────+
│  [Image with condition badge]       │
│  • Excellent / Good / Fair          │
├─────────────────────────────────────+
│ Canon 5D Mark IV                    │
│ ★★★★★ (234 reviews)                │
│ Colombo • Epic 84 Camera Rentals    │
│                                     │
│ Per Day: ₨ 3,500                    │
│ Per Week: ₨ 18,000 (Save 7%)        │
│                                     │
│ [Available Now] [Save] [Rent Now]  │
+─────────────────────────────────────+
```
- Card elevation/shadow on hover
- Image takes up 60% of card on mobile
- Rapid interaction feedback (button ripple effect)

#### 4.4 Equipment Detail Page
- **Hero Section:** Large image gallery (carousel, swipeable on mobile)
- **Info Column (Right/Below on Mobile):**
  - Equipment name, brand, model
  - Star rating with review count
  - Price breakdown (daily/weekly/monthly)
  - Availability calendar
  - Deposit and waiver info
  - "Rent Now" CTA button (sticky on mobile)
  
- **Description Section:**
  - Specifications list (grid layout)
  - What's Included (checkmark list)
  - Rental Terms (expandable section)
  
- **Shop Info Box:**
  - Shop name with link to profile
  - Location with map
  - Shop rating
  - Response time metric
  - Contact/message button

- **Reviews Section:**
  - Sort by: Newest, Highest Rating, Lowest Rating
  - Review cards with date, rating, text, reviewer name
  - Pagination for reviews

#### 4.5 Calendar Widget
- **Tailwind-based custom calendar:**
  - 2-month view on desktop, 1-month on mobile
  - Green for available dates
  - Red/crossed out for booked dates
  - Orange for partially available
  - Click to select start date, then end date
  - Clear date selection option

#### 4.6 Buttons & CTAs
- **Primary CTA (Rent Now):**
  - Background: `#FF6B35` with hover state `#FF5722`
  - Text: White, bold
  - Padding: `py-3 px-8` on desktop, `py-3 px-6` on mobile
  - Border radius: `rounded-lg`
  - Transition: Smooth color change on hover
  - Icon: WhatsApp logo embedded right
  
- **Secondary Button (Save for Later):**
  - Outline style with accent color border
  - Background: Transparent
  - Hover: Fill background with low opacity

#### 4.7 Badges & Status Indicators
```
[Available Now]        - Green badge
[1 More Available]     - Green badge with count
[Fully Booked]         - Red/disabled state
[Excellent Condition]  - Teal badge
[Limited Availability] - Amber badge
[New Listing]          - Blue "NEW" badge
```

### 5. Layout Grid

**Desktop (Tailwind):**
- Base grid: 12 columns
- Max-width: 1400px
- Padding: 24px horizontal
- Search results: 3-column grid of cards (responsive: 2 on 1024px, 1 on 768px)

**Mobile (Tailwind):**
- Single column layout
- Full-width cards with 16px margins
- Stack all sections vertically
- Bottom-sticky "Rent Now" button (48px height)

### 6. Dark Mode Implementation

**Tailwind Dark Mode:**
- Use Tailwind's `dark:` prefix for all dark mode colors
- Example: `bg-white dark:bg-gray-900 text-gray-900 dark:text-white`
- Respect user's system preference (prefers-color-scheme)
- Allow manual toggle in settings (if light mode eventually added)
- Default to dark mode on initial visit

### 7. Accessibility Requirements

- Minimum contrast ratio: 4.5:1 for body text, 3:1 for large text
- Alt text for all product images
- Descriptive link text (avoid "click here")
- Form labels associated with inputs
- Keyboard navigation fully supported
- ARIA labels for interactive components
- Focus indicators visible (ring color on focus)
- Icon buttons should have text labels or title attributes

### 8. Animation & Interactions

- **Page transitions:** Fade in (300ms)
- **Button hover:** Color change + subtle lift (5px shadow)
- **Form feedback:** Inline validation with icon (checkmark/X)
- **Loading states:** Skeleton screens for equipment lists, animated spinner for search
- **Image carousel:** Swipe on mobile, arrow buttons on desktop
- **Filter updates:** Smooth count badge updates
- **WhatsApp redirect:** Button press → confirmation toast → app redirect

### 9. Responsive Breakpoints (Tailwind)

| Device | Breakpoint | Key Changes |
|--------|------------|-------------|
| Mobile | < 640px | Single column, hamburger menu, bottom sheet filters |
| Tablet | 640px - 1024px | 2-column grid, sidebar visible |
| Desktop | > 1024px | 3-column grid, full navigation |

---

## Data Requirements

### 1. Equipment Database Schema

#### 1.1 Equipment Table
```
Equipment Record:
├── ID (Unique identifier)
├── Shop ID (Foreign key to Shop)
├── Equipment Type (Body, Lens, Light, Accessory)
├── Category (Camera Body, Prime Lens, Zoom Lens, etc.)
├── Brand (Canon, Nikon, Sony, Godox, etc.)
├── Model Name
├── Serial Number (optional, for tracking)
├── Specifications
│   ├── Sensor Type (for cameras)
│   ├── Megapixels / Resolution
│   ├── Aperture Range (for lenses)
│   ├── Focal Length (for lenses)
│   ├── Light Output (for lights, in watts)
│   ├── Color Temperature
│   └── Other relevant specs
├── Pricing
│   ├── Daily Rate (LKR)
│   ├── Weekly Rate (LKR, optional)
│   ├── Monthly Rate (LKR, optional)
│   ├── Deposit Required (LKR)
│   ├── Damage Waiver Cost (LKR)
│   └── Currency (Always LKR)
├── Condition (Excellent, Good, Fair)
├── Images (URLs to stored images)
├── Availability Status (Available, Unavailable, Maintenance)
├── Booking Calendar (Reserved dates)
├── What's Included (list of accessories, cables, batteries)
├── Rental Terms (text description)
├── Created Date
├── Last Updated Date
├── View Count (analytics)
└── Inquiry Count (analytics)
```

#### 1.2 Equipment Availability/Booking Table
```
Booking Record:
├── ID
├── Equipment ID
├── Start Date
├── End Date
├── Status (Booked, Pending, Cancelled)
├── Customer Name
├── Customer Phone
├── Rental Notes
└── Booking Date
```

### 2. Shop Database Schema

#### 2.1 Shop Table
```
Shop Record:
├── ID
├── Shop Name
├── Owner Name
├── Email
├── WhatsApp Number (with country code +94)
├── Phone Number
├── Address
├── City (Dropdown: Colombo, Kandy, Galle, Negombo, Jaffna, etc.)
├── Region/District
├── Latitude / Longitude (for maps)
├── Business Hours (JSON: {monday: "9am-6pm", ...})
├── Website URL (optional)
├── Description/About
├── Registration Date
├── Status (Active, Inactive, Verified)
├── Verification Document (optional)
├── Total Equipment Count
├── Average Response Time (minutes)
└── Last Activity Date
```

#### 2.2 Shop Ratings Table
```
Shop Rating Record:
├── ID
├── Shop ID
├── Overall Rating (1-5)
├── Equipment Condition Rating (1-5)
├── Communication Rating (1-5)
├── Pricing Fairness Rating (1-5)
├── Timeliness Rating (1-5)
├── Review Text (max 500 chars)
├── Reviewer Name (optional)
├── Review Date
├── Shop Response (optional reply text)
└── Helpful Count (upvotes)
```

### 3. User Database Schema

#### 3.1 Customer Account Table
```
Customer Record:
├── ID
├── Name (optional)
├── Email
├── WhatsApp Number
├── Phone
├── Preferred Cities (JSON array)
├── Preferred Equipment Types (JSON array)
├── Account Created Date
├── Last Login
├── Notification Preferences
└── Account Status (Active, Suspended)
```

#### 3.2 User Activity Log
```
Activity Record:
├── ID
├── User ID
├── Action (search, view_listing, send_inquiry, save_item)
├── Equipment ID (if applicable)
├── Shop ID (if applicable)
├── Search Query (if applicable)
├── Timestamp
└── Device Type (Mobile, Tablet, Desktop)
```

### 4. System Data Requirements

#### 4.1 Cities & Locations
```
Sri Lankan Cities (Required):
- Colombo (Western)
- Kandy (Central)
- Galle (Southern)
- Negombo (Western)
- Jaffna (Northern)
- Anuradhapura (North Central)
- Trincomalee (Eastern)
- Batticaloa (Eastern)
- Matara (Southern)
- Ratnapura (Sabaragamuwa)
```

#### 4.2 Equipment Categories
```
Main Categories:
├── Camera Bodies
│   ├── Full Frame DSLRs
│   ├── APS-C DSLRs
│   ├── Mirrorless Full Frame
│   ├── Mirrorless APS-C
│   └── Medium Format
├── Lenses
│   ├── Prime Lenses
│   ├── Zoom Lenses
│   ├── Macro Lenses
│   └── Specialty Lenses
├── Lighting
│   ├── Studio Lights
│   ├── Continuous Lights
│   ├── Strobes/Flashes
│   └── Light Modifiers
├── Accessories
│   ├── Tripods & Stands
│   ├── Batteries & Chargers
│   ├── Memory Cards
│   ├── Carrying Cases
│   └── Other Accessories
└── Audio Equipment
    ├── Microphones
    ├── Audio Recorders
    └── Cables
```

#### 4.3 Equipment Brands (Curated List)
```
Camera: Canon, Nikon, Sony, Fujifilm, Panasonic, Pentax
Lenses: Canon, Nikon, Sony, Tamron, Sigma, Tokina
Lighting: Godox, Neewer, Profoto, Elinchrom, Broncolor
Tripods: Manfrotto, Gitzo, Peak Design, Really Right Stuff
```

### 5. Pricing Data Model

#### 5.1 Rental Pricing Tiers (Per Equipment)
```
Pricing Example - Canon 5D Mark IV:
├── Daily: ₨ 3,500
├── Weekly: ₨ 18,000 (saves 7% vs daily × 7)
├── Monthly: ₨ 65,000 (saves 15% vs daily × 30)
├── Deposit: ₨ 25,000
├── Damage Waiver: ₨ 2,000/day
└── All prices in LKR (Sri Lankan Rupees)
```

#### 5.2 Price Range Index (For Filtering)
```
Buckets:
- ₨ 500 - ₨ 2,000
- ₨ 2,000 - ₨ 5,000
- ₨ 5,000 - ₨ 10,000
- ₨ 10,000 - ₨ 20,000
- ₨ 20,000 - ₨ 50,000
- ₨ 50,000+
```

### 6. Image Data Requirements

#### 6.1 Equipment Images
- **Format:** JPG, PNG (WebP for optimization)
- **Minimum Resolution:** 800×600px
- **Maximum File Size:** 5MB per image
- **Storage:** Cloud storage (AWS S3, Firebase, or local CDN)
- **Variants:**
  - Thumbnail: 200×150px
  - Card: 400×300px
  - Detail: 1200×900px
  - Full: 2400×1800px (max)
- **Minimum 3 images per equipment listing**

### 7. Review & Rating Data

```
Review Record:
├── Rating: 1-5 stars
├── Category Ratings (5 subcategories, each 1-5)
├── Review Text: Max 500 characters
├── Helpfulness: Upvote/downvote count
├── Verified Purchase: Boolean
└── Moderation Status: Pending/Approved/Rejected
```

### 8. Analytics Data to Collect

```
Search Analytics:
├── Search queries and frequency
├── Most viewed equipment
├── Most active cities
├── Filter usage patterns
├── Conversion rates (search → inquiry)
├── Device/browser breakdown
├── Traffic sources

Shop Analytics:
├── Inquiries received per day
├── Response time average
├── Conversion rate (inquiry → confirmed booking)
├── Equipment views per listing
├── Rating trends over time
└── Seasonal demand patterns
```

### 9. Data Retention & Privacy

```
Data Retention Policy:
├── User Inquiries: Keep for 2 years (for dispute resolution)
├── Completed Bookings: Keep for 3 years (legal/tax)
├── Reviews: Keep indefinitely (except deleted accounts)
├── Activity Logs: Keep for 1 year, then archive
├── Deleted Accounts: Anonymize after 30 days
└── Images: Keep while listing is active, then archive
```

---

## Technical Constraints & Assumptions

### 1. Technology Stack (Confirmed)
- **Backend:** Plain PHP (no framework)
- **Frontend:** HTML, CSS (Tailwind CSS), Vanilla JavaScript
- **Database:** MySQL/MariaDB (assumed)
- **Hosting:** XAMPP/Traditional web server (Windows OS)

### 2. Limitations & Design Decisions

#### 2.1 No Payment Gateway
- **Assumption:** WhatsApp-based negotiation eliminates need for automated payments
- **Implication:** No transaction fees, but manual shop owner follow-up required
- **Mitigation:** Platform provides messaging template to streamline WhatsApp conversation

#### 2.2 Inventory Management
- **Assumption:** Shop owners manually update availability via dashboard
- **Implication:** Real-time inventory sync not possible without API integration
- **Mitigation:** Encourage daily updates with analytics dashboard showing impact

#### 2.3 User Verification
- **Assumption:** No mandatory user accounts required for searching
- **Implication:** Can't verify legitimate photographers vs. casual browsers
- **Mitigation:** Review system with shop owner moderation

### 3. Security Considerations
- **WhatsApp Numbers:** Stored securely, only shared with users making inquiries
- **Customer Data:** Hashed passwords (if account system added), SSL/TLS encryption
- **Image Storage:** Malware scanning for uploaded images
- **Input Validation:** Prevent SQL injection, XSS attacks
- **Rate Limiting:** Prevent spam inquiries to shop owners

### 4. Performance Assumptions
- **Server:** Minimum PHP 7.4+
- **Database:** MySQL 5.7+ with proper indexing on city, category, availability
- **CDN:** Optional but recommended for image delivery
- **Caching:** Redis/Memcached for search results (optional optimization)

### 5. Scalability Roadmap (Out of Scope for V1)
- Mobile app (React Native/Flutter) for better UX
- Actual payment integration (Stripe, HubPay) if markets matures
- Direct booking without WhatsApp (SMS fallback for non-smartphone users)
- Video equipment database with AI-powered specs extraction
- Equipment comparison tool
- Wishlist & price alert system

### 6. Market-Specific Assumptions
- **Mobile-First:** 85%+ of users on mobile devices
- **WhatsApp Penetration:** 90%+ of target users have WhatsApp
- **Language:** English-primary with Sinhala support (future roadmap)
- **Network:** 4G LTE available in major cities, 3G fallback
- **Payment Culture:** Direct shop negotiation preferred over automated systems
- **Trust:** Direct communication with shop owner builds trust

---

## Roadmap & Future Considerations

### Phase 1 (MVP - Current)
- ✅ Equipment search and filtering
- ✅ WhatsApp integration
- ✅ Basic shop profiles
- ✅ Review system

### Phase 2 (Enhancement)
- 📋 User accounts with saved items
- 📋 Analytics dashboard for shop owners
- 📋 Mobile app version
- 📋 Sinhala language support

### Phase 3 (Maturation)
- 📋 Payment gateway integration (optional)
- 📋 Automated booking confirmation
- 📋 Equipment delivery/pickup coordination
- 📋 Insurance/damage claim system

---

## Success Criteria for Launch

- [ ] 10+ registered rental shops with active inventory
- [ ] Search functionality tested with 100+ equipment listings
- [ ] WhatsApp integration verified with real shop owners
- [ ] UI/UX tested on iOS and Android browsers
- [ ] Page load time < 2 seconds on 4G
- [ ] All images optimized for mobile viewing
- [ ] Mobile responsiveness verified across devices
- [ ] Privacy policy and terms of service published
- [ ] Shop owner onboarding process documented
- [ ] Initial 50+ user beta testing completed

---

**Document Status:** Ready for Development  
**Last Updated:** January 2026  
**Next Review:** After MVP launch
