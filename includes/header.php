<?php
include_once __DIR__ . '/nav.php';
/**
 * Header Template
 * 
 * This file is included at the top of all pages to provide:
 * - HTML5 doctype and meta tags
 * - Tailwind CSS and custom styles
 * - Page title (can be overridden by including page)
 * 
 * Usage: <?php include 'includes/header.php'; ?>
 * 
 * Optional page variable: $page_title (defaults to "LankanLens - Camera Rental Aggregator")
 */

// Set default page title if not provided
if (!isset($page_title)) {
    $page_title = 'LankanLens - Camera Rental Aggregator';
}

// Ensure session is started for auth checks
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Character Encoding -->
    <meta charset="UTF-8">
    
    <!-- Viewport for Responsive Design -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Page Title -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="LankanLens - Rent professional camera equipment and photography gear from multiple shops across Sri Lanka. Browse, compare, and book camera rentals online.">
    <meta name="keywords" content="camera rental, photography equipment, Sri Lanka, lens rental, professional gear">
    <meta name="author" content="LankanLens">
    <meta name="theme-color" content="#1f2937">
    
    <!-- Open Graph Meta Tags (Social Media Sharing) -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="Rent professional camera equipment from multiple shops across Sri Lanka">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo isset($_SERVER['REQUEST_URI']) ? htmlspecialchars($_SERVER['REQUEST_URI']) : 'https://lankanlens.lk'; ?>">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    
    <!-- Font Awesome Icons (Optional for additional icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon (placeholder) -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>assets/images/favicon.ico">
    
    <!-- Preconnect to external resources for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
</head>
<body class="bg-gray-50 text-gray-900">
    <!-- Main Content Container -->
    <div class="min-h-screen flex flex-col">
        <!-- Header Section (Logo & Branding) -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <!-- <div class="container mx-auto px-4 py-4">
                <!-- Logo and Site Title 
                <!-- <div class="flex items-center justify-start gap-3">
                    <!-- Logo Icon (Camera SVG) -
                    <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg"> 
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    
                    <!-- Site Title and Tagline -
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            <a href="<?//php echo BASE_URL; ?>public/index.php" class="hover:text-blue-600 transition-colors">LankanLens</a>
                        </h1>
                        <p class="text-xs text-gray-500">Professional Camera Rental Network</p>
                    </div>
                </div>
            </div>-->
        </header>
        
        <!-- Navigation Bar (Included separately via navbar.php) -->
        <!-- This will be included by each page or added here if needed -->
        
        <!-- Main Content Area (Pages will render inside this container) -->
        <main class="flex-grow">
