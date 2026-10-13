<template>
    <div class="flex flex-col h-[calc(100vh-4rem)] bg-[#0F1020] rounded-md text-white">
        <!-- Header with title and close button -->
        <div class="flex justify-between items-center p-4">
            <h2 class="text-white font-medium">Market Watch</h2>
            <button class="text-white hover:text-gray-300 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Search -->
        <div class="p-4 pt-0">
            <div class="relative">
                <input
                    v-model="search"
                    class="w-full bg-[#1A1B2E] border border-gray-700 rounded-lg px-4 py-3 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    placeholder="Search asset"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Categories - Horizontal scrolling bar -->
        <div class="px-4 pb-4 overflow-x-auto">
            <div class="flex space-x-4 text-sm whitespace-nowrap pb-1">
                <button
                    v-for="category in categories"
                    :key="category"
                    @click="selectedCategory = category"
                    :class="[
            'px-4 py-1.5 rounded flex-shrink-0 transition-all font-medium',
            selectedCategory === category
              ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/30'
              : 'bg-[#1A1B2E] text-gray-400 hover:bg-[#252640] hover:text-gray-300'
          ]"
                >
                    {{ category.toUpperCase() }}
                </button>
            </div>
        </div>

        <!-- Assets List -->
        <div class="flex-1 overflow-y-auto">
            <div
                v-for="asset in filteredAssets"
                :key="asset.id"
                @click="selectAsset(asset)"
                class="flex items-start px-4 py-3 hover:bg-[#1A1B2E] cursor-pointer border-b border-gray-800 transition-colors"
                :class="{'bg-[#1A1B2E]': selectedAsset && selectedAsset.id === asset.id}"
            >
                <!-- Asset Icon (left side) -->
                <div class="flex-shrink-0 mr-4">
                    <div
                        class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm"
                        :style="{ backgroundColor: getRandomColor(asset.sy) }"
                    >
                        {{ getAssetShortName(asset) }}
                    </div>
                </div>

                <!-- Asset Details (right side) -->
                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <!-- Symbol and Name -->
                        <div>
                            <div class="flex items-center text-white font-medium">
                                {{ asset.sy }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5 line-clamp-1 max-w-xs">
                                {{ asset.name || getAssetName(asset) }}
                            </div>
                        </div>

                        <!-- Price and Change -->
                        <div class="text-right">
                            <div class="text-white font-medium">
                                {{ formatPrice(asset.rate || asset.buy_price) }}
                            </div>
                            <div
                                :class="[
                  'text-xs mt-0.5',
                  getChangeValue(asset) >= 0 ? 'text-green-400' : 'text-red-400'
                ]"
                            >
                                {{ formatChangePoints(asset) }} {{ formatChange(asset) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredAssets.length === 0 && !loading" class="p-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-gray-400">No assets found matching your criteria</p>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="p-10 text-center">
                <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-500 border-r-transparent align-[-0.125em] motion-reduce:animate-[spin_1.5s_linear_infinite]" role="status">
                    <span class="!absolute !-m-px !h-px !w-px !overflow-hidden !whitespace-nowrap !border-0 !p-0 ![clip:rect(0,0,0,0)]">Loading...</span>
                </div>
                <p class="mt-3 text-gray-400">Loading assets...</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        selectedSymbol: String
    },
    data() {
        return {
            allAssets: [], // Store all assets after initial fetch
            search: '',
            selectedCategory: 'indices', // Default category
            selectedAsset: null,
            categories: ['stocks', 'crypto', 'forex', 'commodities', 'indices'],
            loading: false,
            error: null,
            colorCache: {}, // Cache for consistent asset colors
        }
    },
    computed: {
        // Filter assets by selected category and search term
        filteredAssets() {
            if (!this.allAssets.length) return [];

            return this.allAssets
                .filter(asset => {
                    const matchesCategory = asset.type.toLowerCase() === this.selectedCategory.toLowerCase();
                    const matchesSearch = !this.search ||
                        asset.sy.toLowerCase().includes(this.search.toLowerCase()) ||
                        (asset.name && asset.name.toLowerCase().includes(this.search.toLowerCase()));

                    return matchesCategory && matchesSearch;
                })
                .sort((a, b) => a.sy.localeCompare(b.sy));
        }
    },
    methods: {
        async fetchAllAssets() {
            this.loading = true;
            this.error = null;

            try {
                // Fetch all assets in a single call
                const response = await fetch('/api/assets');
                this.allAssets = await response.json();

                // Select first asset in the filtered list if none is selected
                if (this.filteredAssets.length > 0 && !this.selectedAsset) {
                    this.selectAsset(this.filteredAssets[0]);
                }
            } catch (err) {
                this.error = 'Failed to load assets. Please try again.';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        selectAsset(asset) {
            this.selectedAsset = asset;
            this.$emit('symbol-selected', asset.ex_sym);
            this.$emit('asset-selected', asset);
        },

        getChangeValue(asset) {
            // Use change_percent if available, otherwise use change
            return asset.change_percent !== null ? Number(asset.change_percent) :
                asset.change !== null ? Number(asset.change) : 0;
        },

        formatChange(asset) {
            const value = this.getChangeValue(asset);

            // If value is zero, just show 0.00%
            if (value === 0) return '0.00%';

            // For non-zero values, add the appropriate sign and format
            const sign = value > 0 ? '+' : '';
            return `${sign}${value.toFixed(2)}%`;
        },

        formatChangePoints(asset) {
            // Format the raw change value (not percentage)
            if (!asset.change && asset.change !== 0) return '';

            const value = Number(asset.change);
            const sign = value > 0 ? '+' : '';
            return `${sign}${value.toFixed(2)}`;
        },

        formatPrice(price) {
            if (!price && price !== 0) return '-';

            const numPrice = Number(price);

            // Format with appropriate decimal places
            if (numPrice >= 1000) {
                return numPrice.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
            } else if (numPrice >= 100) {
                return numPrice.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                return numPrice.toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 4 });
            }
        },

        getAssetShortName(asset) {
            // Create a short abbreviation for the icon
            if (asset.type === 'indices') {
                // For indices, attempt to extract a meaningful number
                if (asset.sy.includes('500') || asset.name?.includes('500')) return '500';
                if (asset.sy.includes('100') || asset.name?.includes('100')) return '100';
                if (asset.sy.includes('30') || asset.name?.includes('30')) return '30';

                // If no specific number, use first 2-3 characters of the symbol
                const match = asset.sy.match(/^[A-Z0-9]{2,3}/);
                return match ? match[0] : asset.sy.substring(0, 2);
            } else {
                // For other assets, use first 2-3 characters
                const symbolParts = asset.sy.split('/');
                return symbolParts[0].substring(0, 3);
            }
        },

        getAssetName(asset) {
            // Generate a descriptive name if not provided
            if (asset.name) return asset.name;

            if (asset.type === 'indices') {
                if (asset.sy.includes('SPX')) return 'S&P 500 Index';
                if (asset.sy.includes('NSX') || asset.sy.includes('US100')) return 'US 100 Cash CFD';
                if (asset.sy.includes('DJI')) return 'Dow Jones Industrial Average';
                return asset.sy;
            } else if (asset.type === 'forex') {
                const parts = asset.sy.split('/');
                if (parts.length === 2) {
                    return `${this.getCurrencyName(parts[0])} / ${this.getCurrencyName(parts[1])}`;
                }
            }

            return asset.sy;
        },

        getCurrencyName(code) {
            const currencies = {
                'USD': 'US Dollar',
                'EUR': 'Euro',
                'GBP': 'British Pound',
                'JPY': 'Japanese Yen',
                'AUD': 'Australian Dollar',
                'CAD': 'Canadian Dollar',
                'CHF': 'Swiss Franc',
                'NZD': 'New Zealand Dollar'
            };

            return currencies[code] || code;
        },

        getRandomColor(key) {
            // Return a consistent color for each asset to create a good visual
            if (this.colorCache[key]) {
                return this.colorCache[key];
            }

            // Predefined colors for major assets
            if (key.includes('SPX') || key.includes('500')) {
                return '#e74c3c'; // Red for SPX/S&P 500
            } else if (key.includes('NSX') || key.includes('100')) {
                return '#3498db'; // Blue for NASDAQ/US 100
            } else if (key.includes('DJI') || key.includes('30')) {
                return '#2ecc71'; // Green for Dow Jones
            }

            const colors = [
                '#3498db', // Blue
                '#2ecc71', // Green
                '#e74c3c', // Red
                '#f39c12', // Orange
                '#9b59b6', // Purple
                '#1abc9c', // Teal
                '#d35400', // Dark Orange
                '#c0392b', // Dark Red
                '#16a085', // Dark Teal
                '#8e44ad'  // Dark Purple
            ];

            // Create a hash from the asset symbol to get a consistent index
            let hash = 0;
            for (let i = 0; i < key.length; i++) {
                hash = ((hash << 5) - hash) + key.charCodeAt(i);
                hash |= 0; // Convert to 32bit integer
            }

            const index = Math.abs(hash % colors.length);
            this.colorCache[key] = colors[index];

            return colors[index];
        }
    },
    mounted() {
        // Fetch all assets once on component mount
        this.fetchAllAssets();
    },
    watch: {
        // When changing categories, select first asset in new category if available
        filteredAssets(newAssets) {
            if (newAssets.length > 0 &&
                (!this.selectedAsset ||
                    this.selectedAsset.type.toLowerCase() !== this.selectedCategory.toLowerCase())) {
                this.selectAsset(newAssets[0]);
            }
        }
    }
}
</script>

<style scoped>
/* Hide scrollbar for Chrome, Safari and Opera */
.overflow-x-auto::-webkit-scrollbar {
    display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.overflow-x-auto {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}

/* Custom scrollbar for assets list */
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #0F1020;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #1A1B2E;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #383A56;
}

/* Text truncation */
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
