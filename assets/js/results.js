/**
 * Results Page JavaScript
 * Handles fetching search results, rendering equipment cards, pagination, and event handlers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize results display
    initializeResults();
});

/**
 * Initialize results page on load
 */
function initializeResults() {
    // Get search parameters from window object (set by results.php)
    const searchTerm = window.searchParams?.search_term || '';
    const city = window.searchParams?.city || '';
    const rentalDate = window.searchParams?.rental_date || '';
    const isLoggedIn = window.searchParams?.is_logged_in || false;

    // Fetch and display results
    fetchSearchResults(searchTerm, city, rentalDate, isLoggedIn);
}

/**
 * Fetch search results from API
 */
async function fetchSearchResults(searchTerm, city, rentalDate, isLoggedIn) {
    const resultsContainer = document.getElementById('results-container');
    const emptyState = document.getElementById('empty-state');
    const loadingSpinner = document.querySelector('.spinner');
    const resultCounter = document.querySelector('[data-result-count]');
    const initialLoading = document.getElementById('initial-loading');

    // Show loading spinner
    if (initialLoading) {
        initialLoading.style.display = 'flex';
    }

    try {
        // Prepare request payload
        const payload = {};
        
        // Add parameters only if they have values
        if (searchTerm && searchTerm.trim() !== '') {
            payload.search_term = searchTerm.trim();
        }
        
        if (city && city.trim() !== '' && city.toLowerCase() !== 'all') {
            payload.city = city.trim();
        }
        
        if (rentalDate && rentalDate.trim() !== '') {
            payload.rental_date = rentalDate.trim();
        }

        // Fetch from API
        const response = await fetch('/api/search-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        // Hide loading spinner
        if (initialLoading) {
            initialLoading.style.display = 'none';
        }

        if (data.success && data.results && data.results.length > 0) {
            // Display results
            renderEquipmentCards(data.results, isLoggedIn, resultsContainer);

            // Update result counter
            if (resultCounter) {
                resultCounter.textContent = `${data.results.length} items found`;
                resultCounter.parentElement.style.display = 'block';
            }

            // Hide empty state
            if (emptyState) {
                emptyState.style.display = 'none';
            }

            // Handle pagination if > 12 results
            handlePagination(data.results);
        } else {
            // Show empty state
            if (resultsContainer) {
                resultsContainer.innerHTML = '';
            }

            if (emptyState) {
                emptyState.style.display = 'flex';
            }

            if (resultCounter) {
                resultCounter.textContent = '0 items found';
                resultCounter.parentElement.style.display = 'block';
            }

            // Attach empty state button handlers
            attachEmptyStateHandlers(searchTerm, city, rentalDate);
        }
    } catch (error) {
        console.error('Error fetching search results:', error);

        // Hide loading spinner
        if (loadingSpinner) {
            loadingSpinner.style.display = 'none';
        }

        // Show error message
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="col-span-full py-12 text-center">
                    <p class="text-red-500 text-lg font-semibold">Error loading results</p>
                    <p class="text-gray-600 mt-2">Please try again later</p>
                </div>
            `;
        }
    }
}

/**
 * Render equipment cards from results
 */
function renderEquipmentCards(results, isLoggedIn, container) {
    if (!container) return;

    // Clear previous results
    container.innerHTML = '';

    // Get template
    const template = document.getElementById('equipment-card-template');
    if (!template) {
        console.error('Equipment card template not found');
        return;
    }

    // Display first 12 results by default
    const displayResults = results.slice(0, 12);

    displayResults.forEach((equipment, index) => {
        // Clone template
        const cardClone = template.content.cloneNode(true);

        // Populate card with data
        const card = cardClone.querySelector('.equipment-card');
        if (card) {
            card.classList.add('card-hover');
            // Set animation delay for staggered fade-in
            card.style.animationDelay = `${index * 100}ms`;
        }

        // Set image
        const imageEl = cardClone.querySelector('.equipment-image');
        if (imageEl && equipment.image_url) {
            imageEl.src = equipment.image_url;
            imageEl.alt = equipment.equipment_name;
        }

        // Set equipment name
        const nameEl = cardClone.querySelector('[data-equipment-name]');
        if (nameEl) {
            nameEl.textContent = equipment.equipment_name || 'Unnamed Equipment';
        }

        // Set brand
        const brandEl = cardClone.querySelector('[data-brand-name]');
        if (brandEl) {
            brandEl.textContent = equipment.brand || 'Unknown Brand';
        }

        // Set condition badge
        const conditionBadge = cardClone.querySelector('[data-condition-badge]');
        if (conditionBadge) {
            const condition = equipment.condition || 'Good';
            conditionBadge.textContent = condition;
            // Set color based on condition
            conditionBadge.classList.remove('bg-green-500', 'bg-blue-500', 'bg-yellow-500');
            switch (condition.toLowerCase()) {
                case 'excellent':
                    conditionBadge.classList.add('bg-green-500');
                    break;
                case 'good':
                    conditionBadge.classList.add('bg-blue-500');
                    break;
                case 'fair':
                    conditionBadge.classList.add('bg-yellow-500');
                    break;
                default:
                    conditionBadge.classList.add('bg-blue-500');
            }
        }

        // Set shop name and rating
        const shopInfoEl = cardClone.querySelector('[data-shop-info]');
        if (shopInfoEl) {
            const rating = equipment.average_rating ? equipment.average_rating.toFixed(1) : '0.0';
            shopInfoEl.textContent = `⭐ ${equipment.shop_name || 'Unknown Shop'} - ${rating}/5`;
        }

        // Set daily rate
        const priceEl = cardClone.querySelector('[data-daily-rate]');
        if (priceEl) {
            const formattedPrice = formatCurrency(equipment.daily_rate_lkr);
            priceEl.textContent = `Rs ${formattedPrice} LKR/day`;
        }

        // Set data attributes for button handlers
        const checkAvailBtn = cardClone.querySelector('[data-action="check-availability"]');
        if (checkAvailBtn) {
            checkAvailBtn.dataset.equipmentId = equipment.equipment_id;
            checkAvailBtn.dataset.equipmentName = equipment.equipment_name;
            checkAvailBtn.dataset.shopName = equipment.shop_name;
            checkAvailBtn.dataset.dailyRate = equipment.daily_rate_lkr;
            checkAvailBtn.dataset.shopPhone = equipment.shop_phone;
            checkAvailBtn.dataset.isLoggedIn = isLoggedIn ? 'true' : 'false';
        }

        // Append card to container
        container.appendChild(cardClone);
    });

    // Attach event listeners to all "Check Availability" buttons
    attachCheckAvailabilityHandlers();
}

/**
 * Format currency with comma separators
 */
function formatCurrency(amount) {
    if (!amount) return '0';
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Handle pagination for results > 12
 */
function handlePagination(results) {
    const paginationControls = document.getElementById('pagination-controls');
    if (!paginationControls || results.length <= 12) {
        if (paginationControls) {
            paginationControls.style.display = 'none';
        }
        return;
    }

    // Show pagination controls
    paginationControls.style.display = 'flex';

    const totalPages = Math.ceil(results.length / 12);
    let currentPage = 1;

    const prevBtn = paginationControls.querySelector('[data-action="prev-page"]');
    const nextBtn = paginationControls.querySelector('[data-action="next-page"]');
    const pageInfo = paginationControls.querySelector('[data-page-info]');

    // Update page info display
    function updatePageInfo() {
        if (pageInfo) {
            pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
        }
        // Disable/enable buttons based on current page
        if (prevBtn) {
            prevBtn.disabled = currentPage === 1;
        }
        if (nextBtn) {
            nextBtn.disabled = currentPage === totalPages;
        }
    }

    // Previous page handler
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                displayPage(results, currentPage);
                updatePageInfo();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }

    // Next page handler
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                displayPage(results, currentPage);
                updatePageInfo();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }

    // Initialize page info
    updatePageInfo();
}

/**
 * Display specific page of results
 */
function displayPage(allResults, pageNumber) {
    const startIndex = (pageNumber - 1) * 12;
    const endIndex = startIndex + 12;
    const pageResults = allResults.slice(startIndex, endIndex);

    const isLoggedIn = window.searchParams?.is_logged_in || false;
    const resultsContainer = document.getElementById('results-container');

    renderEquipmentCards(pageResults, isLoggedIn, resultsContainer);
}

/**
 * Attach click handlers to "Check Availability" buttons
 */
function attachCheckAvailabilityHandlers() {
    const buttons = document.querySelectorAll('[data-action="check-availability"]');

    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const isLoggedIn = this.dataset.isLoggedIn === 'true';

            if (!isLoggedIn) {
                // Redirect to login with return URL
                const returnUrl = window.location.href;
                window.location.href = `/public/login.php?return=${encodeURIComponent(returnUrl)}`;
                return;
            }

            // Trigger booking modal (to be implemented)
            const equipmentData = {
                equipment_id: this.dataset.equipmentId,
                equipment_name: this.dataset.equipmentName,
                shop_name: this.dataset.shopName,
                daily_rate: parseFloat(this.dataset.dailyRate),
                shop_phone: this.dataset.shopPhone
            };

            // Log for now, will integrate with booking modal in next phase
            console.log('Check Availability clicked for:', equipmentData);
            
            // Placeholder: show toast notification
            showToast(`Availability check for ${equipmentData.equipment_name}`, 'info');
        });
    });
}

/**
 * Attach handlers to empty state buttons
 */
function attachEmptyStateHandlers(searchTerm, city, rentalDate) {
    const emptyState = document.getElementById('empty-state');
    if (!emptyState) return;

    const buttons = emptyState.querySelectorAll('button');

    buttons.forEach(button => {
        const action = button.getAttribute('data-action');

        switch (action) {
            case 'search-all-cities':
                button.addEventListener('click', function() {
                    // Remove city filter and search again across all cities
                    if (searchTerm) {
                        const newUrl = `/public/results.php?q=${encodeURIComponent(searchTerm)}`;
                        window.location.href = newUrl;
                    } else {
                        // If no search term, just reload to show all equipment
                        window.location.href = '/public/results.php?city=all';
                    }
                });
                break;

            case 'view-all-equipment':
                button.addEventListener('click', function() {
                    // Show all equipment in the selected city (or all cities if city is empty)
                    if (city) {
                        const newUrl = `/public/results.php?city=${encodeURIComponent(city)}`;
                        window.location.href = newUrl;
                    } else {
                        // Show ALL equipment from all cities
                        window.location.href = '/public/results.php?city=all';
                    }
                });
                break;

            case 'try-another-search':
                button.addEventListener('click', function() {
                    // Redirect to home page for new search
                    window.location.href = '/public/index.php';
                });
                break;
        }
    });
}

/**
 * Show toast notification (placeholder)
 */
function showToast(message, type = 'success') {
    // Placeholder for toast notification
    console.log(`[${type.toUpperCase()}] ${message}`);
}
