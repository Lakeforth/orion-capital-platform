<template>
    <div class="bg-white dark:bg-boxdark relative shadow-md sm:rounded-lg overflow-hidden">
        <!-- Search and Filter Header -->
        <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
            <div class="w-full md:w-1/2">
                <form class="flex items-center" @submit.prevent="applyFilters">
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
                            placeholder="Search investments"
                        >
                    </div>
                </form>
            </div>

            <!-- Status Filter -->
            <div class="flex items-center space-x-3">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</span>
                <div class="flex space-x-2">
                    <button
                        @click="toggleStatus('in_progress')"
                        :class="[
              'px-3 py-1 text-xs font-medium rounded-full',
              selectedStatus === 'in_progress'
                ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100'
                : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
            ]"
                    >
                        In Progress
                    </button>
                    <button
                        @click="toggleStatus('completed')"
                        :class="[
              'px-3 py-1 text-xs font-medium rounded-full',
              selectedStatus === 'completed'
                ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
            ]"
                    >
                        Completed
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center p-6">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-4 py-3">Name</th>
                    <th scope="col" class="px-4 py-3">Amount</th>
                    <th scope="col" class="px-4 py-3">Start Date</th>
                    <th scope="col" class="px-4 py-3">End Date</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Expected Profit</th>
                    <th scope="col" class="px-4 py-3">Current Profit</th>
                    <th scope="col" class="px-4 py-3">Inv Date</th>
                    <th scope="col" class="px-4 py-3">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="investment in investments.data" :key="investment.id" class="border-b dark:border-gray-700">
                    <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ investment.title }}
                    </th>
                    <td class="px-4 py-3">
                        {{ formatAmount(investment.amount) }}
                    </td>
                    <td class="px-4 py-3">
                        {{ formatDate(investment.start_date) }}
                    </td>
                    <td class="px-4 py-3">
                        {{ formatDate(investment.end_date) }}
                    </td>
                    <td class="px-4 py-3">
              <span :class="getStatusClass(investment.status)">
                {{ investment.status }}
              </span>
                    </td>
                    <td class="px-4 py-3">
                        {{ formatAmount(investment.total_profit) }}
                    </td>
                    <td class="px-4 py-3">
                        {{ formatAmount(investment.profit_earned) }}
                    </td>
                    <td class="px-4 py-3">
                        {{ formatDate(investment.created_at) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button
                            @click="showInvestmentDetails(investment)"
                            class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                        >
                            View
                        </button>
                    </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="investments.data && investments.data.length === 0" class="border-b dark:border-gray-700">
                    <td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                        No investments found
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700">
            <div class="flex-1 flex justify-between sm:hidden">
                <button
                    @click="goToPage(investments.current_page - 1)"
                    :disabled="!investments.prev_page_url"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 disabled:opacity-50"
                >
                    Previous
                </button>
                <button
                    @click="goToPage(investments.current_page + 1)"
                    :disabled="!investments.next_page_url"
                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 disabled:opacity-50"
                >
                    Next
                </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Showing
                        <span class="font-medium">{{ investments.from || 0 }}</span>
                        to
                        <span class="font-medium">{{ investments.to || 0 }}</span>
                        of
                        <span class="font-medium">{{ investments.total || 0 }}</span>
                        results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <button
                            @click="goToPage(investments.current_page - 1)"
                            :disabled="!investments.prev_page_url"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-300 dark:border-gray-600 disabled:opacity-50"
                        >
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 dark:border-gray-600">
              Page {{ investments.current_page || 1 }}
            </span>
                        <button
                            @click="goToPage(investments.current_page + 1)"
                            :disabled="!investments.next_page_url"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-300 dark:border-gray-600 disabled:opacity-50"
                        >
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Investment Details Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75 dark:bg-gray-900 dark:opacity-90" @click="closeModal"></div>
                </div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-boxdark rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div v-if="selectedInvestment" class="bg-white dark:bg-boxdark px-4 pt-5 pb-4 sm:p-6">
                        <!-- Modal header -->
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                Investment Details
                            </h3>
                            <button
                                @click="closeModal"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal content -->
                        <div class="mt-4 space-y-4">
                            <!-- Basic investment info -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <span class="block text-sm font-medium text-gray-500 dark:text-gray-400">Investment ID</span>
                                        <span class="block mt-1 text-sm text-gray-900 dark:text-white">{{ selectedInvestment.id }}</span>
                                    </div>
                                    <span :class="getStatusClass(selectedInvestment.status)">
                    {{ selectedInvestment.status }}
                  </span>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-sm font-medium text-gray-500 dark:text-gray-400">Name</span>
                                        <span class="block mt-1 text-lg font-bold text-gray-900 dark:text-white">
                      {{ selectedInvestment.title }}
                    </span>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-medium text-gray-500 dark:text-gray-400">Amount</span>
                                        <span class="block mt-1 text-lg font-bold text-gray-900 dark:text-white">
                      {{ formatAmount(selectedInvestment.amount) }}
                    </span>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</span>
                                        <span class="block mt-1 text-sm text-gray-900 dark:text-white">
                      {{ formatDate(selectedInvestment.start_date) }}
                    </span>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-medium text-gray-500 dark:text-gray-400">End Date</span>
                                        <span class="block mt-1 text-sm text-gray-900 dark:text-white">
                      {{ formatDate(selectedInvestment.end_date) }}
                    </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Profit information -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <div class="mb-3">
                                    <span class="block text-sm font-medium text-gray-500 dark:text-gray-400">Profit Information</span>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-sm text-gray-500 dark:text-gray-400">Expected Profit</span>
                                        <span class="block mt-1 text-sm font-medium text-gray-900 dark:text-white">
                      {{ formatAmount(selectedInvestment.total_profit) }}
                    </span>
                                    </div>
                                    <div>
                                        <span class="block text-sm text-gray-500 dark:text-gray-400">Current Profit</span>
                                        <span class="block mt-1 text-sm font-medium text-gray-900 dark:text-white">
                      {{ formatAmount(selectedInvestment.profit_earned) }}
                    </span>
                                    </div>
                                    <div>
                                        <span class="block text-sm text-gray-500 dark:text-gray-400">ROI</span>
                                        <span class="block mt-1 text-sm font-medium text-gray-900 dark:text-white">
                      {{ calculateROI(selectedInvestment) }}%
                    </span>
                                    </div>
                                    <div>
                                        <span class="block text-sm text-gray-500 dark:text-gray-400">Progress</span>
                                        <div class="mt-1 w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                            <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: calculateProgress(selectedInvestment) + '%' }"></div>
                                        </div>
                                        <span class="block mt-1 text-xs text-gray-500 dark:text-gray-400">
                      {{ calculateProgress(selectedInvestment) }}% complete
                    </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional details if available -->
                            <div v-if="selectedInvestment.description" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <span class="block text-sm font-medium text-gray-500 dark:text-gray-400">Description</span>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ selectedInvestment.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="closeModal"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'InvestmentTable',
    props: {
        apiUrl: {
            type: String,
            required: true
        },
        defaultStatus: {
            type: String,
            default: 'in_progress',
            validator: (value) => ['in_progress', 'completed'].includes(value)
        }
    },
    data() {
        return {
            investments: {
                data: [],
                current_page: 1,
                from: 0,
                to: 0,
                total: 0,
                next_page_url: null,
                prev_page_url: null
            },
            loading: true,
            searchQuery: '',
            selectedStatus: this.defaultStatus,
            filterTimeout: null,
            showModal: false,
            selectedInvestment: null
        };
    },
    created() {
        this.fetchInvestments();
    },
    methods: {
        async fetchInvestments(page = 1) {
            this.loading = true;

            try {
                // Build URL with query parameters
                const params = new URLSearchParams();
                params.append('page', page);

                if (this.searchQuery) {
                    params.append('search', this.searchQuery);
                }

                // Add status filter
                params.append('status', this.selectedStatus);

                const url = `${this.apiUrl}?${params.toString()}`;

                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error('Failed to fetch investments');
                }

                const data = await response.json();
                this.investments = data;
            } catch (error) {
                console.error('Error fetching investments:', error);
                // You could add error handling/display here
            } finally {
                this.loading = false;
            }
        },

        goToPage(page) {
            if (page < 1) return;

            // Only fetch if valid page
            if ((page > this.investments.current_page && this.investments.next_page_url) ||
                (page < this.investments.current_page && this.investments.prev_page_url)) {
                this.fetchInvestments(page);
            }
        },

        applyFilters() {
            // Clear any existing timeout
            if (this.filterTimeout) {
                clearTimeout(this.filterTimeout);
            }

            // Debounce filter changes to prevent too many API calls
            this.filterTimeout = setTimeout(() => {
                this.fetchInvestments(1); // Reset to page 1 when filtering
            }, 300);
        },

        toggleStatus(status) {
            if (this.selectedStatus === status) {
                return; // Do nothing if clicking the already selected status
            }

            this.selectedStatus = status;
            this.applyFilters();
        },

        formatAmount(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';

            const date = new Date(dateString);
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }).format(date);
        },

        getStatusClass(status) {
            const baseClasses = 'px-2 py-1 rounded text-xs font-medium';

            const statusMap = {
                'in_progress': `${baseClasses} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300`,
                'completed': `${baseClasses} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300`,
                'active': `${baseClasses} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300`,
                'pending': `${baseClasses} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300`,
                'cancelled': `${baseClasses} bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300`,
            };

            // Normalize status to lowercase for comparison
            const normalizedStatus = status.toLowerCase().replace(' ', '_');

            // Return the mapped class or a default
            return statusMap[normalizedStatus] || `${baseClasses} bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300`;
        },

        showInvestmentDetails(investment) {
            this.selectedInvestment = investment;
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            setTimeout(() => {
                this.selectedInvestment = null;
            }, 200); // Small delay for animation
        },

        calculateROI(investment) {
            if (!investment || !investment.amount || investment.amount <= 0) return '0.00';

            const roi = (investment.total_profit / investment.amount) * 100;
            return roi.toFixed(2);
        },

        calculateProgress(investment) {
            if (!investment) return 0;

            const startDate = new Date(investment.start_date);
            const endDate = new Date(investment.end_date);
            const currentDate = new Date();

            // If not started yet
            if (currentDate < startDate) return 0;

            // If already completed
            if (currentDate >= endDate || investment.status.toLowerCase() === 'completed') return 100;

            // Calculate progress percentage
            const totalDuration = endDate - startDate;
            const elapsedDuration = currentDate - startDate;
            const progress = (elapsedDuration / totalDuration) * 100;

            return Math.min(Math.round(progress), 100);
        }
    }
};
</script>
