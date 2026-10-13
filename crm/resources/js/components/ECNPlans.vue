<template>
    <div class="mx-auto">
        <div class="bg-white dark:bg-boxdark relative shadow-sm sm:rounded-lg overflow-hidden">
            <!-- Search and Filter Header -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between p-4 gap-4">
                <div class="flex-1 max-w-lg w-full">
                    <!-- Search Input -->
                    <label for="simple-search" class="sr-only">Search</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="simple-search"
                            v-model="searchQuery"
                            @input="updateFilters"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="Search"
                        >
                    </div>
                </div>

                <!-- Category Filter Checkboxes -->
                <div class="flex flex-col items-start w-full md:w-auto">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-400 mb-2">Filter by Category:</div>
                    <div class="flex flex-wrap gap-3">
                        <div v-for="category in categories" :key="category" class="flex items-center">
                            <input
                                type="checkbox"
                                :id="'category-' + category"
                                :value="category"
                                v-model="selectedCategories"
                                @change="updateFilters"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                            >
                            <label :for="'category-' + category" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                {{ category }}
                            </label>
                        </div>
                        <button
                            @click="clearFilters"
                            class="text-sm text-blue-600 hover:underline dark:text-blue-500"
                        >
                            Clear all
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading indicator -->
            <div v-if="isLoading" class="p-4 flex justify-center items-center">
                <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <!-- Alert for success or error messages -->
            <div v-if="alertMessage" :class="[
                'p-4 mb-4 mx-4 text-sm rounded-lg',
                alertType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            ]">
                {{ alertMessage }}
            </div>

            <!-- Table -->
            <div v-if="!isLoading" class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Exchanger</th>
                        <th scope="col" class="px-4 py-3">Name</th>
                        <th scope="col" class="px-4 py-3">Buy Price</th>
                        <th scope="col" class="px-4 py-3">Sell Price</th>
                        <th scope="col" class="px-4 py-3">Unit</th>
                        <th scope="col" class="px-4 py-3">Total</th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(item, index) in investments" :key="item.id" class="border-b dark:border-gray-700">
                        <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            <div class="flex gap-3 items-center">
                                <p>{{ item.title }}</p>
                            </div>
                        </th>
                        <td class="px-4 py-3">{{ item.category }}</td>
                        <td class="px-4 py-3">{{ formatPrice(item.price) }}
                            <p>Min unit : {{ item.min_investment }}</p>
                        </td>
                        <td class="px-4 py-3">{{ formatPrice(item.sale_price) }}</td>
                        <td class="px-4 py-3">
                            <input
                                type="number"
                                v-model.number="quantities[index]"
                                min="1"
                                :disabled="isLoadingItem(item.id)"
                                class="w-20 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            />
                        </td>
                        <td class="px-4 py-3">
                            {{ formatPrice(item.price * quantities[index]) }}
                        </td>
                        <td class="px-4 py-3 flex items-center justify-end">
                            <button
                                @click="openConfirmModal(item, quantities[index])"
                                :disabled="isLoadingItem(item.id)"
                                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="isLoadingItem(item.id)" class="inline-flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Loading...
                                </span>
                                <span v-else>Buy</span>
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- No results message -->
            <div v-if="!isLoading && investments.length === 0" class="p-4 text-center text-gray-500 dark:text-gray-400">
                No investments found matching your criteria.
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75 dark:bg-gray-900 dark:opacity-90"></div>
                </div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-boxdark rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-boxdark px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    Confirm Purchase
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-300">
                                        Are you sure you want to purchase from <span class="font-bold">{{ selectedItem?.title }}</span>?
                                    </p>
                                    <div class="mt-4 space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Exchange:</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedItem?.title }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Category:</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedItem?.category }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Price per unit:</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ formatPrice(selectedItem?.price) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Units:</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedQuantity }}</span>
                                        </div>
                                        <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total cost:</span>
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ formatPrice(selectedItem?.price * selectedQuantity) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="button"
                            @click="confirmPurchase"
                            :disabled="isProcessingPurchase"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isProcessingPurchase" class="inline-flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                            <span v-else>Confirm Purchase</span>
                        </button>
                        <button
                            type="button"
                            @click="closeConfirmModal"
                            :disabled="isProcessingPurchase"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ECNPlans',
    props: {
        dataUrl: {
            type: String,
            required: true
        },
        submitUrl: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            investments: [],
            categories: [],
            searchQuery: '',
            selectedCategories: [],
            quantities: [],
            loadingItems: {},
            isLoading: true,
            alertMessage: '',
            alertType: 'success',
            alertTimeout: null,
            debounceTimer: null,
            showConfirmModal: false,
            selectedItem: null,
            selectedQuantity: 0,
            isProcessingPurchase: false
        };
    },
    created() {
        // Initialize from URL parameters if they exist
        this.initFromUrlParams();

        // Fetch investments data
        this.fetchInvestments();
    },
    methods: {
        initFromUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);

            // Get search query from URL if it exists
            const searchFromUrl = urlParams.get('search');
            if (searchFromUrl) {
                this.searchQuery = searchFromUrl;
            }

            // Get categories from URL if they exist
            const categoriesFromUrl = urlParams.getAll('category');
            if (categoriesFromUrl.length > 0) {
                this.selectedCategories = categoriesFromUrl;
            }
        },

        updateUrlParams() {
            const params = new URLSearchParams();

            // Add search query to URL if it exists
            if (this.searchQuery) {
                params.append('search', this.searchQuery);
            }

            // Add selected categories to URL
            this.selectedCategories.forEach(category => {
                params.append('category', category);
            });

            // Update URL without refreshing the page
            const newUrl = `${window.location.pathname}?${params.toString()}`;
            window.history.pushState({ path: newUrl }, '', newUrl);
        },

        async fetchInvestments() {
            this.isLoading = true;

            try {
                // Build URL with query parameters
                const params = new URLSearchParams();

                if (this.searchQuery) {
                    params.append('search', this.searchQuery);
                }

                this.selectedCategories.forEach(category => {
                    params.append('category[]', category);
                });

                const url = `${this.dataUrl}?${params.toString()}`;

                // Fetch data from API
                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error('Failed to fetch investments');
                }

                const data = await response.json();

                // Update investments and categories
                this.investments = data.investments || [];
                this.categories = data.categories || [];

                // Initialize quantities array
                this.resetQuantities();

            } catch (error) {
                console.error('Error fetching investments:', error);
                this.showAlert('Failed to load investments. Please refresh the page.', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        updateFilters() {
            // Clear any existing debounce timer
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }

            // Debounce the filter update to prevent too many API calls
            this.debounceTimer = setTimeout(() => {
                this.updateUrlParams();
                this.fetchInvestments();
            }, 300);
        },

        clearFilters() {
            this.searchQuery = '';
            this.selectedCategories = [];
            this.updateFilters();
        },

        isLoadingItem(itemId) {
            return this.loadingItems[itemId] === true;
        },

        resetQuantities() {
            // Reset quantities array with 1s for each investment
            this.quantities = Array(this.investments.length).fill(1);
        },

        formatPrice(price) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(price);
        },

        showAlert(message, type = 'success') {
            // Clear any existing timeout
            if (this.alertTimeout) {
                clearTimeout(this.alertTimeout);
            }

            // Set the alert message and type
            this.alertMessage = message;
            this.alertType = type;

            // Auto-hide the alert after 5 seconds
            this.alertTimeout = setTimeout(() => {
                this.alertMessage = '';
            }, 5000);
        },

        openConfirmModal(item, quantity) {
            // Store the selected item and quantity for the confirmation modal
            this.selectedItem = item;
            this.selectedQuantity = quantity < 1 ? 1 : quantity;
            this.showConfirmModal = true;
        },

        closeConfirmModal() {
            this.showConfirmModal = false;
            this.selectedItem = null;
            this.selectedQuantity = 0;
        },

        async confirmPurchase() {
            if (!this.selectedItem) return;

            this.isProcessingPurchase = true;

            try {
                // Get CSRF token
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                // Prepare form data
                const formData = new FormData();
                formData.append('investment_plan_id', this.selectedItem.id);
                formData.append('unit', this.selectedQuantity);

                // Make the API request
                const response = await fetch(this.submitUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    body: formData
                });


                if (!response.ok) {
                    // Extract the error details from the response
                    const errorData = await response.json();
                    throw {
                        message: errorData.message || 'Failed to complete purchase',
                        status: response.status,
                        apiError: errorData
                    };
                }

                const result = await response.json();

                // Close the modal
                this.closeConfirmModal();

                // Show success message
                this.showAlert('Investment purchased successfully!', 'success');

                // Emit success event for parent components
                this.$emit('purchase-complete', {
                    success: true,
                    data: result,
                    item: this.selectedItem,
                    units: this.selectedQuantity
                });

                // Reload the page to reflect the updated inventory
                window.location.reload();
            }catch (error) {
                console.error('Purchase error:', error);

                // Get the error message (either from our custom error object or fallback)
                const errorMessage = error.apiError?.message || error.message || 'Failed to complete purchase';

                // Close the modal
                this.closeConfirmModal();

                // Show error message with the actual error from the API
                this.showAlert(errorMessage, 'error');

                // Emit error event for parent components
                this.$emit('purchase-complete', {
                    success: false,
                    error: errorMessage,
                    apiError: error.apiError,
                    item: this.selectedItem
                });
            } finally {
                this.isProcessingPurchase = false;
            }
        }

    }
};
</script>
