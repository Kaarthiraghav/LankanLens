<?php
/**
 * Booking Modal Component
 * WhatsApp-based rental request modal with pricing calculator
 * Usage: Include this file in any page that needs booking functionality
 */
?>

<!-- Booking Modal - Hidden by default -->
<div id="booking-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Semi-transparent backdrop -->
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

    <!-- Modal container - centered -->
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <!-- Modal panel -->
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-white" id="modal-title">
                            Request to Rent: <span id="modal-equipment-name" class="font-bold">-</span>
                        </h3>
                        <!-- Shop Info Subheader -->
                        <div class="mt-2 flex items-center gap-2 text-blue-100 text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span id="modal-shop-name" class="font-medium">-</span>
                            <span class="mx-1">•</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <span id="modal-shop-phone" class="text-xs">-</span>
                        </div>
                    </div>
                    <!-- Close button -->
                    <button type="button" id="modal-close-btn" class="ml-3 text-blue-100 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="bg-white px-6 py-5">
                <form id="booking-form" class="space-y-4">
                    
                    <!-- User Full Name Field -->
                    <div>
                        <label for="user-full-name" class="block text-sm font-medium text-gray-700 mb-1">
                            Your Full Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="user-full-name" 
                            name="user_full_name"
                            required
                            minlength="2"
                            maxlength="50"
                            placeholder="Enter your full name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        />
                        <p class="mt-1 text-xs text-gray-500">This will be shared with the shop owner</p>
                        <p id="name-error" class="mt-1 text-xs text-red-600 hidden">Name must be 2-50 characters</p>
                    </div>

                    <!-- Rental Duration Dropdown -->
                    <div>
                        <label for="rental-duration" class="block text-sm font-medium text-gray-700 mb-1">
                            Rental Duration <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="rental-duration" 
                            name="rental_duration"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all appearance-none bg-white"
                        >
                            <option value="">Select duration...</option>
                            <option value="1">1 day</option>
                            <option value="2">2 days</option>
                            <option value="3">3 days</option>
                            <option value="7">1 week (7 days)</option>
                            <option value="14">2 weeks (14 days)</option>
                            <option value="30">1 month (30 days)</option>
                            <option value="custom">Custom duration</option>
                        </select>
                        
                        <!-- Custom Duration Input (shown when "Custom" is selected) -->
                        <div id="custom-duration-input" class="mt-2 hidden">
                            <input 
                                type="number" 
                                id="custom-days" 
                                name="custom_days"
                                min="1"
                                max="365"
                                placeholder="Enter number of days"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <!-- Additional Notes Textarea -->
                    <div>
                        <label for="additional-notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Additional Notes <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <textarea 
                            id="additional-notes" 
                            name="additional_notes"
                            maxlength="200"
                            rows="3"
                            placeholder="Any special requests or questions? (Max 200 characters)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none transition-all"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500 text-right">
                            <span id="char-count">0</span>/200 characters
                        </p>
                    </div>

                    <!-- Pricing Display Section -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Estimated Pricing</h4>
                        
                        <div class="space-y-2">
                            <!-- Daily Rate -->
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Daily Rate:</span>
                                <span class="font-medium text-gray-900">₨ <span id="modal-daily-rate">0</span> LKR</span>
                            </div>
                            
                            <!-- Number of Days -->
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Duration:</span>
                                <span class="font-medium text-gray-900"><span id="modal-num-days">0</span> day(s)</span>
                            </div>
                            
                            <!-- Divider -->
                            <div class="border-t border-blue-300 my-2"></div>
                            
                            <!-- Total Price -->
                            <div class="flex justify-between items-center">
                                <span class="text-base font-semibold text-gray-800">Total:</span>
                                <span class="text-xl font-bold text-blue-600">₨ <span id="modal-total-price">0</span> LKR</span>
                            </div>
                        </div>
                        
                        <p class="mt-3 text-xs text-gray-600 italic">
                            * Final price may vary. Confirm with shop owner via WhatsApp.
                        </p>
                    </div>

                </form>
            </div>

            <!-- Modal Footer - Action Buttons -->
            <div class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <!-- Cancel Button (Secondary) -->
                <button 
                    type="button" 
                    id="modal-cancel-btn"
                    class="w-full sm:w-auto px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400"
                >
                    Cancel
                </button>
                
                <!-- Send Request Button (Primary) -->
                <button 
                    type="button" 
                    id="modal-send-btn"
                    class="w-full sm:w-auto px-6 py-2.5 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Send Request via WhatsApp
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Include booking.js for modal interaction -->
<script src="<?php echo BASE_URL ?? '/'; ?>assets/js/booking.js" defer></script>
