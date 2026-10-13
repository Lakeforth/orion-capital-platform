<template>
    <div class="mx-auto">
        <div class="bg-white dark:bg-boxdark relative shadow-sm sm:rounded-lg overflow-hidden">
            <!-- Search and Filter Header -->
            <div class="flex items-center justify-between p-4">
                <div class="flex flex-1 items-center space-x-4">
                    <!-- Search Input -->
                    <div class="flex-1 max-w-lg">
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
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                placeholder="Search"
                            >
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="flex items-center">
                        <div class="relative">
                            <button
                                @click="toggleCategoryDropdown"
                                class="flex items-center justify-center py-2 px-4 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                type="button"
                            >
                                <svg class="-ml-1 mr-1.5 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path clip-rule="evenodd" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                </svg>
                                Category Filter
                            </button>
                            <div v-if="showCategoryDropdown" class="absolute z-10 w-44 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600">
                                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                    <li v-for="category in categories" :key="category">
                                        <a
                                            href="#"
                                            @click.prevent="selectCategory(category)"
                                            class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                        >
                                            {{ category }}
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="#"
                                            @click.prevent="selectCategory('all')"
                                            class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                        >
                                            Show All
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert for success or error messages -->
            <div v-if="alertMessage" :class="[
                'p-4 mb-4 mx-4 text-sm rounded-lg',
                alertType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            ]">
                {{ alertMessage }}
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Name</th>
                        <th scope="col" class="px-4 py-3">Profitability</th>
                        <th scope="col" class="px-4 py-3">Profit Per Day</th>
                        <th scope="col" class="px-4 py-3">Period</th>
                        <th scope="col" class="px-4 py-3">Category</th>
                        <th scope="col" class="px-4 py-3">Price</th>
                        <th scope="col" class="px-4 py-3">Unit</th>
                        <th scope="col" class="px-4 py-3">Total</th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(item, index) in filteredInvestments" :key="item.id" class="border-b dark:border-gray-700">
                        <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            <div class="flex gap-3 items-center">
                                <img :src="item.image" class="w-5 h-5 rounded" />
                                <p>{{ item.title }}</p>
                            </div>
                        </th>
                        <td class="px-4 py-3">{{ item.percent_profit }}%</td>
                        <td class="px-4 py-3">{{ item.profit_per_day }}%</td>
                        <td class="px-4 py-3">{{ item.period_value }}</td>
                        <td class="px-4 py-3">{{ item.category }}</td>
                        <td class="px-4 py-3">{{ formatPrice(item.price) }}</td>
                        <td class="px-4 py-3">
                            <input
                                type="number"
                                v-model.number="quantities[index]"
                                min="1"
                                :disabled="isLoading(item.id)"
                                class="w-20 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            />
                        </td>
                        <td class="px-4 py-3">
                            {{ formatPrice(item.price * quantities[index]) }}
                        </td>
                        <td class="px-4 py-3 flex items-center justify-end">
                            <button
                                @click="buyInvestment(item, quantities[index])"
                                :disabled="isLoading(item.id)"
                                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="isLoading(item.id)" class="inline-flex items-center">
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
        </div>
    </div>
</template>

<script>
export default {
    name: 'InvestmentTable',
    props: {
        investments: {
            type: Array,
            required: true
        },
        submitUrl: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            searchQuery: '',
            selectedCategory: 'all',
            showCategoryDropdown: false,
            quantities: [],
            loadingItems: {},
            alertMessage: '',
            alertType: 'success',
            alertTimeout: null
        };
    },
    computed: {
        // Extract unique categories from investments
        categories() {
            const uniqueCategories = [...new Set(this.investments.map(item => item.category))];
            return uniqueCategories;
        },

        // Filter investments based on search query and selected category
        filteredInvestments() {
            return this.investments.filter(item => {
                const matchesSearch = item.title.toLowerCase().includes(this.searchQuery.toLowerCase());
                const matchesCategory = this.selectedCategory === 'all' || item.category === this.selectedCategory;
                return matchesSearch && matchesCategory;
            });
        }
    },
    created() {
        // Initialize all quantities to 1
        this.resetQuantities();
    },
    watch: {
        filteredInvestments: {
            handler() {
                // Make sure we have enough quantities for all investments
                this.ensureQuantities();
            },
            immediate: true
        }
    },
    methods: {
        isLoading(itemId) {
            return this.loadingItems[itemId] === true;
        },

        resetQuantities() {
            // Reset quantities array with 1s for each investment
            this.quantities = Array(this.filteredInvestments.length).fill(1);
        },

        ensureQuantities() {
            // Make sure we have enough quantities for all investments
            if (this.quantities.length < this.filteredInvestments.length) {
                // Add more 1s to match the number of investments
                const diff = this.filteredInvestments.length - this.quantities.length;
                this.quantities = [...this.quantities, ...Array(diff).fill(1)];
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(price);
        },

        toggleCategoryDropdown() {
            this.showCategoryDropdown = !this.showCategoryDropdown;
        },

        selectCategory(category) {
            this.selectedCategory = category;
            this.showCategoryDropdown = false;
            // Reset quantities when changing category
            this.resetQuantities();
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

        async buyInvestment(item, quantity) {
            // Ensure quantity is valid
            const units = quantity < 1 ? 1 : quantity;

            // Set loading state for this investment
            this.loadingItems = {...this.loadingItems, [item.id]: true};

            try {
                // Get CSRF token
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                // Prepare form data
                const formData = new FormData();
                formData.append('investment_plan_id', item.id);
                formData.append('unit', units);

                // Make the API request
                const response = await fetch(this.submitUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    body: formData
                });

                // Handle response
                if (!response.ok) {
                    throw new Error('Failed to complete purchase');
                }

                const result = await response.json();

                // Show success message
                this.showAlert('Investment purchased successfully!', 'success');

                // Emit success event for parent components
                this.$emit('purchase-complete', {
                    success: true,
                    data: result,
                    item: item,
                    units: units
                });

                window.location.reload();
            } catch (error) {
                console.error('Purchase error:', error);

                // Show error message
                this.showAlert('Failed to complete purchase. Please try again.', 'error');

                // Emit error event for parent components
                this.$emit('purchase-complete', {
                    success: false,
                    error: error.message,
                    item: item
                });
            } finally {
                // Reset loading state
                this.loadingItems = {...this.loadingItems, [item.id]: false};
            }
        }
    }
};
</script>
