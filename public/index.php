<?php
include_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main>
    <!-- Hero Section -->
    <section class="relative bg-gray-900 text-white">
        <div class="absolute inset-0">
            <img src="<?php echo BASE_URL; ?>assets/images/hero.jpg" alt="Camera gear" class="w-full h-full object-cover opacity-60">
            <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/40 to-black/60"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 py-20 md:py-28">
            <div class="max-w-2xl">
                <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-4">Rent camera gear in Sri Lanka — fast.</h1>
                <p class="text-lg md:text-xl text-gray-200 mb-8">
                    Discover trusted rental shops, compare prices, and send WhatsApp requests in minutes.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="#search" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">Search Gear</a>
                    <a href="<?php echo BASE_URL; ?>public/register.php" class="bg-white/10 hover:bg-white/20 text-white px-6 py-3 rounded-lg font-semibold">Join as Vendor</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Search Form Section -->
    <section id="search" class="bg-gray-50 py-12">
        <div class="max-w-5xl mx-auto px-6">
            <div class="bg-white shadow-lg rounded-2xl p-8 md:p-10">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 text-center mb-8">Find the gear you need</h2>
                
                <form id="search-form" class="space-y-6" method="GET" action="<?php echo BASE_URL; ?>public/results.php">
                    <!-- Grid Layout for Form Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Equipment Search Term -->
                        <div class="lg:col-span-2">
                            <label for="search-term" class="block text-sm font-semibold text-gray-700 mb-2">
                                Equipment
                            </label>
                            <input 
                                type="text" 
                                id="search-term" 
                                name="q" 
                                placeholder="e.g., Sony A7R IV" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150" 
                                required
                            >
                        </div>

                        <!-- City Dropdown -->
                        <div>
                            <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">
                                City
                            </label>
                            <select 
                                id="city" 
                                name="city" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 bg-white" 
                                required
                            >
                                <option value="">Select city</option>
                                <option value="Colombo">Colombo</option>
                                <option value="Kandy">Kandy</option>
                                <option value="Galle">Galle</option>
                                <option value="Jaffna">Jaffna</option>
                                <option value="Matara">Matara</option>
                                <option value="Negombo">Negombo</option>
                                <option value="Batticaloa">Batticaloa</option>
                            </select>
                        </div>

                        <!-- Rental Date Picker -->
                        <div>
                            <label for="rental-date" class="block text-sm font-semibold text-gray-700 mb-2">
                                Start Date
                            </label>
                            <input 
                                type="date" 
                                id="rental-date" 
                                name="date" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150" 
                                min="<?php echo date('Y-m-d'); ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- Search Button -->
                    <div class="pt-2">
                        <button 
                            type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white py-4 px-6 rounded-lg font-semibold text-lg shadow-md hover:shadow-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5" 
                            data-action="search"
                        >
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                Search Equipment
                            </span>
                        </button>
                    </div>

                    <!-- Error Message Container -->
                    <div id="search-error" class="hidden">
                        <p class="text-sm text-red-600 text-center bg-red-50 border border-red-200 rounded-lg py-2 px-4">
                            Please fill in all fields to continue.
                        </p>
                    </div>
                </form>

                <!-- Quick Tips -->
                <div class="mt-6 text-center text-sm text-gray-600">
                    <p>Popular searches: Canon 5D, Sony A7III, DJI Ronin, Godox Lighting</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Gear Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <!-- Section Header -->
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">Popular Rentals</h2>
                <p class="text-lg text-gray-600">Explore our most requested camera gear across Sri Lanka</p>
            </div>

            <!-- Equipment Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Equipment Card 1 -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 card-hover">
                    <div class="relative h-48 bg-gray-50 flex items-center justify-center overflow-hidden">
                        <img src="<?php echo BASE_URL; ?>assets/images/Body/Sony/A7R_IV.jpg" alt="Sony A7R IV" class="max-w-full max-h-full object-contain" onerror="this.src='https://via.placeholder.com/400x300?text=Sony+A7R+IV'">
                        <span class="absolute top-3 right-3 bg-green-500 text-white text-xs font-semibold px-3 py-1 rounded-full">In Stock</span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-lg text-gray-900 mb-1">Sony A7R IV</h3>
                        <p class="text-sm text-gray-600 mb-3">Full-Frame Mirrorless</p>
                        
                        <div class="flex items-center gap-1 mb-3">
                            <span class="text-yellow-400">⭐</span>
                            <span class="text-sm font-semibold text-gray-700">4.9</span>
                            <span class="text-sm text-gray-500">(24 reviews)</span>
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-2xl font-bold text-blue-600">Rs 18,500</p>
                                <p class="text-xs text-gray-500">per day</p>
                            </div>
                            <span class="bg-blue-50 text-blue-700 text-xs font-medium px-2 py-1 rounded">Excellent</span>
                        </div>

                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-lg font-semibold transition duration-200" data-action="check-availability">
                            Check Availability
                        </button>
                    </div>
                </div>

                <!-- Equipment Card 2 -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 card-hover">
                    <div class="relative h-48 bg-gray-50 flex items-center justify-center overflow-hidden">
                        <img src="<?php echo BASE_URL; ?>assets/images/Body/Canon_EOS/R5.jpg" alt="Canon EOS R5" class="max-w-full max-h-full object-contain" onerror="this.src='https://via.placeholder.com/400x300?text=Canon+EOS+R5'">
                        <span class="absolute top-3 right-3 bg-green-500 text-white text-xs font-semibold px-3 py-1 rounded-full">In Stock</span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-lg text-gray-900 mb-1">Canon EOS R5</h3>
                        <p class="text-sm text-gray-600 mb-3">8K Video Camera</p>
                        
                        <div class="flex items-center gap-1 mb-3">
                            <span class="text-yellow-400">⭐</span>
                            <span class="text-sm font-semibold text-gray-700">4.8</span>
                            <span class="text-sm text-gray-500">(18 reviews)</span>
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-2xl font-bold text-blue-600">Rs 22,000</p>
                                <p class="text-xs text-gray-500">per day</p>
                            </div>
                            <span class="bg-blue-50 text-blue-700 text-xs font-medium px-2 py-1 rounded">Excellent</span>
                        </div>

                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-lg font-semibold transition duration-200" data-action="check-availability">
                            Check Availability
                        </button>
                    </div>
                </div>

                <!-- Equipment Card 3 -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 card-hover">
                    <div class="relative h-48 bg-gray-50 flex items-center justify-center overflow-hidden">
                        <img src="<?php echo BASE_URL; ?>assets/images/Lens/Sony/FE_24_70mm.jpg" alt="Sony FE 24-70mm f/2.8 GM" class="max-w-full max-h-full object-contain" onerror="this.src='https://via.placeholder.com/400x300?text=Sony+24-70mm'">
                        <span class="absolute top-3 right-3 bg-green-500 text-white text-xs font-semibold px-3 py-1 rounded-full">In Stock</span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-lg text-gray-900 mb-1">Sony FE 24-70mm</h3>
                        <p class="text-sm text-gray-600 mb-3">f/2.8 GM Lens</p>
                        
                        <div class="flex items-center gap-1 mb-3">
                            <span class="text-yellow-400">⭐</span>
                            <span class="text-sm font-semibold text-gray-700">4.9</span>
                            <span class="text-sm text-gray-500">(31 reviews)</span>
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-2xl font-bold text-blue-600">Rs 8,500</p>
                                <p class="text-xs text-gray-500">per day</p>
                            </div>
                            <span class="bg-blue-50 text-blue-700 text-xs font-medium px-2 py-1 rounded">Excellent</span>
                        </div>

                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-lg font-semibold transition duration-200" data-action="check-availability">
                            Check Availability
                        </button>
                    </div>
                </div>

                <!-- Equipment Card 4 -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 card-hover">
                    <div class="relative h-48 bg-gray-50 flex items-center justify-center overflow-hidden">
                        <img src="<?php echo BASE_URL; ?>assets/images/Other/DJI/Ronin_S.jpg" alt="DJI Ronin-S" class="max-w-full max-h-full object-contain" onerror="this.src='https://via.placeholder.com/400x300?text=DJI+Ronin-S'">
                        <span class="absolute top-3 right-3 bg-green-500 text-white text-xs font-semibold px-3 py-1 rounded-full">In Stock</span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-lg text-gray-900 mb-1">DJI Ronin-S</h3>
                        <p class="text-sm text-gray-600 mb-3">3-Axis Gimbal</p>
                        
                        <div class="flex items-center gap-1 mb-3">
                            <span class="text-yellow-400">⭐</span>
                            <span class="text-sm font-semibold text-gray-700">4.7</span>
                            <span class="text-sm text-gray-500">(15 reviews)</span>
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-2xl font-bold text-blue-600">Rs 6,500</p>
                                <p class="text-xs text-gray-500">per day</p>
                            </div>
                            <span class="bg-green-50 text-green-700 text-xs font-medium px-2 py-1 rounded">Good</span>
                        </div>

                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-lg font-semibold transition duration-200" data-action="check-availability">
                            Check Availability
                        </button>
                    </div>
                </div>

                <!-- Equipment Card 5 -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 card-hover">
                    <div class="relative h-48 bg-gray-50 flex items-center justify-center overflow-hidden">
                        <img src="<?php echo BASE_URL; ?>assets/images/Lighting/Godox/AD600_Pro.webp" alt="Godox AD600Pro" class="max-w-full max-h-full object-contain" onerror="this.src='https://via.placeholder.com/400x300?text=Godox+AD600Pro'">
                        <span class="absolute top-3 right-3 bg-green-500 text-white text-xs font-semibold px-3 py-1 rounded-full">In Stock</span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-lg text-gray-900 mb-1">Godox AD600Pro</h3>
                        <p class="text-sm text-gray-600 mb-3">Studio Flash</p>
                        
                        <div class="flex items-center gap-1 mb-3">
                            <span class="text-yellow-400">⭐</span>
                            <span class="text-sm font-semibold text-gray-700">4.8</span>
                            <span class="text-sm text-gray-500">(12 reviews)</span>
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-2xl font-bold text-blue-600">Rs 5,000</p>
                                <p class="text-xs text-gray-500">per day</p>
                            </div>
                            <span class="bg-blue-50 text-blue-700 text-xs font-medium px-2 py-1 rounded">Excellent</span>
                        </div>

                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-lg font-semibold transition duration-200" data-action="check-availability">
                            Check Availability
                        </button>
                    </div>
                </div>

                <!-- Equipment Card 6 -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 card-hover">
                    <div class="relative h-48 bg-gray-50 flex items-center justify-center overflow-hidden">
                        <img src="<?php echo BASE_URL; ?>assets/images/Lens/Canon/EF_70_200mm_IS_III.jpg" alt="Canon EF 70-200mm" class="max-w-full max-h-full object-contain" onerror="this.src='https://via.placeholder.com/400x300?text=Canon+70-200mm'">
                        <span class="absolute top-3 right-3 bg-yellow-500 text-white text-xs font-semibold px-3 py-1 rounded-full">2 Left</span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-lg text-gray-900 mb-1">Canon EF 70-200mm</h3>
                        <p class="text-sm text-gray-600 mb-3">f/2.8L IS III USM</p>
                        
                        <div class="flex items-center gap-1 mb-3">
                            <span class="text-yellow-400">⭐</span>
                            <span class="text-sm font-semibold text-gray-700">4.9</span>
                            <span class="text-sm text-gray-500">(28 reviews)</span>
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-2xl font-bold text-blue-600">Rs 9,500</p>
                                <p class="text-xs text-gray-500">per day</p>
                            </div>
                            <span class="bg-blue-50 text-blue-700 text-xs font-medium px-2 py-1 rounded">Excellent</span>
                        </div>

                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-lg font-semibold transition duration-200" data-action="check-availability">
                            Check Availability
                        </button>
                    </div>
                </div>
            </div>

            <!-- Browse All Link -->
            <div class="text-center mt-10">
                <a href="<?php echo BASE_URL; ?>public/results.php" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-semibold text-lg">
                    Browse All Equipment
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>
</main>

<!-- Include Search JavaScript -->
<script src="<?php echo BASE_URL; ?>assets/js/search.js"></script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
