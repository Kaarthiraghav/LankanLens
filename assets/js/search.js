/**
 * Search Form Handler
 * Handles equipment search form validation, submission, and user feedback
 */

document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('search-form');
    const searchButton = searchForm.querySelector('button[type="submit"]');
    const searchError = document.getElementById('search-error');
    
    // Form fields
    const searchTermInput = document.getElementById('search-term');
    const citySelect = document.getElementById('city');
    const rentalDateInput = document.getElementById('rental-date');

    /**
     * Validate search form inputs
     * @returns {Object} Validation result with isValid flag and error message
     */
    function validateForm() {
        const searchTerm = searchTermInput.value.trim();
        const city = citySelect.value;
        const rentalDate = rentalDateInput.value;

        // Validate search term (min 2 characters)
        if (searchTerm.length < 2) {
            return {
                isValid: false,
                message: 'Please enter at least 2 characters for your search.'
            };
        }

        // Validate city selection
        if (!city) {
            return {
                isValid: false,
                message: 'Please select a city.'
            };
        }

        // Validate rental date
        if (!rentalDate) {
            return {
                isValid: false,
                message: 'Please select a rental start date.'
            };
        }

        // Validate date is not in the past
        const selectedDate = new Date(rentalDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            return {
                isValid: false,
                message: 'Please select a date that is today or in the future.'
            };
        }

        return { isValid: true };
    }

    /**
     * Show error message to user
     * @param {string} message - Error message to display
     */
    function showError(message) {
        const errorElement = searchError.querySelector('p') || searchError;
        errorElement.textContent = message;
        searchError.classList.remove('hidden');
        
        // Apply shake animation
        searchError.classList.add('shake');
        setTimeout(() => {
            searchError.classList.remove('shake');
        }, 500);
    }

    /**
     * Hide error message
     */
    function hideError() {
        searchError.classList.add('hidden');
    }

    /**
     * Set loading state on search button
     * @param {boolean} isLoading - Whether to show loading state
     */
    function setLoadingState(isLoading) {
        if (isLoading) {
            searchButton.disabled = true;
            searchButton.innerHTML = `
                <span class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Searching...
                </span>
            `;
        } else {
            searchButton.disabled = false;
            searchButton.innerHTML = `
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Search Equipment
                </span>
            `;
        }
    }

    /**
     * Show toast notification
     * @param {string} message - Message to display
     * @param {string} type - Toast type: 'success', 'error', 'info'
     */
    function showToast(message, type = 'error') {
        // Remove existing toasts
        const existingToast = document.getElementById('search-toast');
        if (existingToast) {
            existingToast.remove();
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.id = 'search-toast';
        toast.className = `fixed bottom-4 right-4 z-50 max-w-sm px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 fade-in ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 
            'bg-blue-500'
        } text-white`;
        toast.innerHTML = `
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${type === 'success' 
                        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'
                        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                    }
                </svg>
                <p class="font-medium">${message}</p>
            </div>
        `;

        document.body.appendChild(toast);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-x-full');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
    }

    /**
     * Handle form submission
     * @param {Event} e - Form submit event
     */
    function handleSubmit(e) {
        e.preventDefault();
        hideError();

        // Validate form
        const validation = validateForm();
        if (!validation.isValid) {
            showError(validation.message);
            return;
        }

        // Get form data
        const formData = {
            search_term: searchTermInput.value.trim(),
            city: citySelect.value,
            rental_date: rentalDateInput.value
        };

        // Set loading state
        setLoadingState(true);

        // Send AJAX request
        fetch('/LankanLens/api/search-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            setLoadingState(false);

            if (data.success) {
                // Build redirect URL with query parameters
                const params = new URLSearchParams({
                    q: formData.search_term,
                    city: formData.city,
                    date: formData.rental_date
                });

                // Redirect to results page
                window.location.href = `/LankanLens/public/results.php?${params.toString()}`;
            } else {
                // Show error message from API
                showToast(data.message || 'Search failed. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            setLoadingState(false);
            showToast('Unable to connect to search service. Please try again.', 'error');
        });
    }

    // Attach submit event listener
    searchForm.addEventListener('submit', handleSubmit);

    // Real-time validation feedback (optional enhancement)
    searchTermInput.addEventListener('input', function() {
        if (this.value.trim().length >= 2) {
            this.classList.remove('border-red-500');
            this.classList.add('border-green-500');
        } else if (this.value.length > 0) {
            this.classList.remove('border-green-500');
            this.classList.add('border-red-500');
        } else {
            this.classList.remove('border-red-500', 'border-green-500');
        }
    });

    citySelect.addEventListener('change', function() {
        if (this.value) {
            this.classList.remove('border-red-500');
            this.classList.add('border-green-500');
        }
    });

    rentalDateInput.addEventListener('change', function() {
        if (this.value) {
            this.classList.remove('border-red-500');
            this.classList.add('border-green-500');
        }
    });
});
