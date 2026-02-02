/**
 * WhatsApp Booking System
 * Handles rental booking modal interactions and WhatsApp message generation
 */

class WhatsAppBooking {
    constructor() {
        this.modal = document.getElementById('booking-modal');
        this.form = document.getElementById('booking-form');
        this.equipmentData = {};
        
        // Form elements
        this.nameInput = document.getElementById('user-full-name');
        this.durationSelect = document.getElementById('rental-duration');
        this.customDaysInput = document.getElementById('custom-days');
        this.notesTextarea = document.getElementById('additional-notes');
        
        // Display elements
        this.modalEquipmentName = document.getElementById('modal-equipment-name');
        this.modalShopName = document.getElementById('modal-shop-name');
        this.modalShopPhone = document.getElementById('modal-shop-phone');
        this.modalDailyRate = document.getElementById('modal-daily-rate');
        this.modalNumDays = document.getElementById('modal-num-days');
        this.modalTotalPrice = document.getElementById('modal-total-price');
        
        // Buttons
        this.sendBtn = document.getElementById('modal-send-btn');
        this.cancelBtn = document.getElementById('modal-cancel-btn');
        this.closeBtn = document.getElementById('modal-close-btn');
        
        // Error display
        this.nameError = document.getElementById('name-error');
        this.charCount = document.getElementById('char-count');
        
        // Custom duration container
        this.customDurationInput = document.getElementById('custom-duration-input');
        
        this.initializeEventListeners();
    }
    
    /**
     * Initialize all event listeners
     */
    initializeEventListeners() {
        // Event listener: "Check Availability" buttons → openBookingModal()
        document.addEventListener('click', (e) => {
            const checkAvailabilityBtn = e.target.closest('[data-action="check-availability"]');
            if (checkAvailabilityBtn) {
                e.preventDefault();
                this.openBookingModal(checkAvailabilityBtn);
            }
        });
        
        // Event listener: Duration dropdown → updatePrice()
        if (this.durationSelect) {
            this.durationSelect.addEventListener('change', () => {
                this.handleDurationChange();
            });
        }
        
        // Event listener: Custom days input → updatePrice()
        if (this.customDaysInput) {
            this.customDaysInput.addEventListener('input', () => {
                this.updatePrice();
            });
        }
        
        // Event listener: Name input → validate
        if (this.nameInput) {
            this.nameInput.addEventListener('input', () => {
                this.validateName();
            });
        }
        
        // Event listener: Notes textarea → character counter
        if (this.notesTextarea) {
            this.notesTextarea.addEventListener('input', () => {
                this.updateCharacterCount();
            });
        }
        
        // Event listener: "Send Request" button → handleSendRequest()
        if (this.sendBtn) {
            this.sendBtn.addEventListener('click', () => {
                this.handleSendRequest();
            });
        }
        
        // Event listener: Cancel button → close modal
        if (this.cancelBtn) {
            this.cancelBtn.addEventListener('click', () => {
                this.closeModal();
            });
        }
        
        // Event listener: Close button → close modal
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => {
                this.closeModal();
            });
        }
        
        // Event listener: Click outside modal → close modal
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.closeModal();
                }
            });
        }
        
        // Event listener: Escape key → close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal && !this.modal.classList.contains('hidden')) {
                this.closeModal();
            }
        });
    }
    
    /**
     * Open booking modal
     * Extract equipment data from card/button, show modal, populate header, focus name input
     */
    openBookingModal(button) {
        // Extract equipment data from button's data attributes
        this.equipmentData = {
            id: button.dataset.equipmentId || '',
            name: button.dataset.equipmentName || 'Unknown Equipment',
            shopId: button.dataset.shopId || '',
            shopName: button.dataset.shopName || 'Unknown Shop',
            shopPhone: button.dataset.shopPhone || '',
            shopWhatsapp: button.dataset.shopWhatsapp || button.dataset.shopPhone || '',
            dailyRate: parseFloat(button.dataset.dailyRate) || 0
        };
        
        // Populate modal header with equipment data
        if (this.modalEquipmentName) {
            this.modalEquipmentName.textContent = this.equipmentData.name;
        }
        
        if (this.modalShopName) {
            this.modalShopName.textContent = this.equipmentData.shopName;
        }
        
        if (this.modalShopPhone) {
            this.modalShopPhone.textContent = this.equipmentData.shopPhone;
        }
        
        if (this.modalDailyRate) {
            this.modalDailyRate.textContent = this.formatCurrency(this.equipmentData.dailyRate);
        }
        
        // Reset form
        this.resetForm();
        
        // Show modal
        if (this.modal) {
            this.modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }
        
        // Focus name input after a short delay (for animation)
        setTimeout(() => {
            if (this.nameInput) {
                this.nameInput.focus();
            }
        }, 100);
    }
    
    /**
     * Handle duration dropdown change
     * Show/hide custom duration input and update price
     */
    handleDurationChange() {
        const selectedValue = this.durationSelect.value;
        
        // Show/hide custom duration input
        if (selectedValue === 'custom') {
            this.customDurationInput.classList.remove('hidden');
            this.customDaysInput.required = true;
            this.customDaysInput.focus();
        } else {
            this.customDurationInput.classList.add('hidden');
            this.customDaysInput.required = false;
            this.customDaysInput.value = '';
        }
        
        // Update price calculation
        this.updatePrice();
    }
    
    /**
     * Update price calculation
     * Calculate total (daily_rate × duration_days), update display
     */
    updatePrice() {
        let durationDays = 0;
        
        // Get duration in days
        const selectedValue = this.durationSelect.value;
        if (selectedValue === 'custom') {
            durationDays = parseInt(this.customDaysInput.value) || 0;
        } else if (selectedValue) {
            durationDays = parseInt(selectedValue) || 0;
        }
        
        // Calculate total
        const dailyRate = this.equipmentData.dailyRate || 0;
        const totalPrice = dailyRate * durationDays;
        
        // Update display
        if (this.modalNumDays) {
            this.modalNumDays.textContent = durationDays;
        }
        
        if (this.modalTotalPrice) {
            this.modalTotalPrice.textContent = this.formatCurrency(totalPrice);
        }
        
        // Enable/disable send button based on form validity
        this.updateSendButtonState();
    }
    
    /**
     * Validate name input
     * Name: required, length 2-50 chars
     */
    validateName() {
        const name = this.nameInput.value.trim();
        const isValid = name.length >= 2 && name.length <= 50;
        
        if (name.length > 0 && !isValid) {
            this.nameError.classList.remove('hidden');
            this.nameInput.classList.add('border-red-500');
            this.nameInput.classList.remove('border-gray-300');
        } else {
            this.nameError.classList.add('hidden');
            this.nameInput.classList.remove('border-red-500');
            this.nameInput.classList.add('border-gray-300');
        }
        
        // Update send button state
        this.updateSendButtonState();
        
        return isValid;
    }
    
    /**
     * Update character count for notes textarea
     */
    updateCharacterCount() {
        const count = this.notesTextarea.value.length;
        if (this.charCount) {
            this.charCount.textContent = count;
        }
    }
    
    /**
     * Update send button state based on form validity
     */
    updateSendButtonState() {
        const name = this.nameInput.value.trim();
        const nameValid = name.length >= 2 && name.length <= 50;
        
        let durationValid = false;
        const selectedValue = this.durationSelect.value;
        if (selectedValue === 'custom') {
            const customDays = parseInt(this.customDaysInput.value);
            durationValid = customDays >= 1 && customDays <= 365;
        } else if (selectedValue) {
            durationValid = true;
        }
        
        const formValid = nameValid && durationValid;
        
        if (this.sendBtn) {
            this.sendBtn.disabled = !formValid;
        }
    }
    
    /**
     * Handle send request button click
     * Validate form, generate WhatsApp message, open WhatsApp, close modal
     */
    handleSendRequest() {
        // Final validation
        if (!this.validateForm()) {
            this.showValidationError();
            return;
        }
        
        // Generate WhatsApp message
        const message = this.generateMessage();
        
        // Open WhatsApp
        this.openWhatsApp(message);
        
        // Close modal
        this.closeModal();
        
        // Show success message (optional)
        this.showSuccessToast('Opening WhatsApp...');
    }
    
    /**
     * Validate entire form
     */
    validateForm() {
        const name = this.nameInput.value.trim();
        const nameValid = name.length >= 2 && name.length <= 50;
        
        let durationValid = false;
        let durationDays = 0;
        const selectedValue = this.durationSelect.value;
        
        if (selectedValue === 'custom') {
            durationDays = parseInt(this.customDaysInput.value);
            durationValid = durationDays >= 1 && durationDays <= 365;
        } else if (selectedValue) {
            durationDays = parseInt(selectedValue);
            durationValid = true;
        }
        
        return nameValid && durationValid;
    }
    
    /**
     * Generate WhatsApp message
     * Build message with proper grammar (singular/plural "day"/"days")
     */
    generateMessage() {
        const name = this.nameInput.value.trim();
        const notes = this.notesTextarea.value.trim();
        
        // Get duration
        let durationDays = 0;
        const selectedValue = this.durationSelect.value;
        if (selectedValue === 'custom') {
            durationDays = parseInt(this.customDaysInput.value);
        } else {
            durationDays = parseInt(selectedValue);
        }
        
        // Proper grammar for day/days
        const dayWord = durationDays === 1 ? 'day' : 'days';
        
        // Calculate total price
        const totalPrice = this.equipmentData.dailyRate * durationDays;
        
        // Build message
        let message = `Hi! I'm interested in renting *${this.equipmentData.name}* for *${durationDays} ${dayWord}*.\n\n`;
        message += `My name is: ${name}\n`;
        message += `Estimated total: Rs ${this.formatCurrency(totalPrice)} LKR\n\n`;
        message += `Please confirm availability and provide the final price.`;
        
        // Add notes if provided
        if (notes) {
            message += `\n\n*Additional Notes:*\n${notes}`;
        }
        
        return message;
    }
    
    /**
     * Open WhatsApp with pre-filled message
     * Detect mobile vs. desktop and open accordingly
     */
    openWhatsApp(message) {
        // Clean phone number (remove spaces, dashes, etc.)
        let phoneNumber = this.equipmentData.shopWhatsapp.replace(/[\s\-\(\)]/g, '');
        
        // Ensure phone number starts with country code
        if (!phoneNumber.startsWith('+')) {
            // Assume Sri Lankan number (+94)
            if (phoneNumber.startsWith('0')) {
                phoneNumber = '+94' + phoneNumber.substring(1);
            } else if (phoneNumber.startsWith('94')) {
                phoneNumber = '+' + phoneNumber;
            } else {
                phoneNumber = '+94' + phoneNumber;
            }
        }
        
        // Encode message for URL
        const encodedMessage = encodeURIComponent(message);
        
        // Construct WhatsApp URL
        const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodedMessage}`;
        
        // Detect mobile vs. desktop
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        if (isMobile) {
            // Mobile: Open in same window (opens WhatsApp app)
            window.location.href = whatsappUrl;
        } else {
            // Desktop: Open in new tab (opens WhatsApp Web)
            window.open(whatsappUrl, '_blank');
        }
    }
    
    /**
     * Close modal and reset form
     */
    closeModal() {
        if (this.modal) {
            this.modal.classList.add('hidden');
            document.body.style.overflow = ''; // Restore scrolling
        }
        
        // Reset form after animation
        setTimeout(() => {
            this.resetForm();
        }, 300);
    }
    
    /**
     * Reset form to initial state
     */
    resetForm() {
        if (this.form) {
            this.form.reset();
        }
        
        // Reset custom duration input
        if (this.customDurationInput) {
            this.customDurationInput.classList.add('hidden');
        }
        
        // Reset error states
        if (this.nameError) {
            this.nameError.classList.add('hidden');
        }
        
        if (this.nameInput) {
            this.nameInput.classList.remove('border-red-500');
            this.nameInput.classList.add('border-gray-300');
        }
        
        // Reset character count
        if (this.charCount) {
            this.charCount.textContent = '0';
        }
        
        // Reset pricing display
        if (this.modalNumDays) {
            this.modalNumDays.textContent = '0';
        }
        
        if (this.modalTotalPrice) {
            this.modalTotalPrice.textContent = '0';
        }
        
        // Disable send button
        if (this.sendBtn) {
            this.sendBtn.disabled = true;
        }
    }
    
    /**
     * Show validation error with shake animation
     */
    showValidationError() {
        if (this.form) {
            this.form.classList.add('shake');
            setTimeout(() => {
                this.form.classList.remove('shake');
            }, 500);
        }
        
        // Show error message
        this.showErrorToast('Please fill in all required fields correctly');
    }
    
    /**
     * Format currency with comma separators
     */
    formatCurrency(amount) {
        return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    /**
     * Show success toast notification
     */
    showSuccessToast(message) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2 animate-slide-up';
        toast.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }
    
    /**
     * Show error toast notification
     */
    showErrorToast(message) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2 animate-slide-up';
        toast.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
    }
}

// Initialize booking system when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize if modal exists on page
    if (document.getElementById('booking-modal')) {
        window.whatsappBooking = new WhatsAppBooking();
    }
});
