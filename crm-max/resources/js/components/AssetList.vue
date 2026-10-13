<template>
    <div class="flex flex-col h-[calc(100vh-4rem)] bg-[#0F1020] rounded-md text-white">
        <!-- Header with title and connection status -->
        <div class="flex justify-between items-center p-4">
            <h2 class="text-white font-medium">Market Watch</h2>
            <div class="flex items-center space-x-3">
                <div class="flex items-center text-xs">
                    <div
                        class="w-2 h-2 rounded-full mr-2"
                        :class="wsConnected ? 'bg-green-500' : 'bg-red-500'"
                    ></div>
                    <span :class="wsConnected ? 'text-green-400' : 'text-red-400'">{{ wsConnected ? 'Live' : 'Disconnected' }}</span>
                </div>
                <button
                    @click="toggleConnection"
                    class="text-xs px-2 py-1 rounded bg-[#1A1B2E] hover:bg-[#252640] transition-colors"
                    :class="wsConnected ? 'text-green-400' : 'text-red-400'"
                >
                    {{ wsConnected ? 'Disconnect' : 'Connect' }}
                </button>
                <button class="text-white hover:text-gray-300 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
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
                    @click="selectCategory(category)"
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
                :class="{
                    'bg-[#1A1B2E]': selectedAsset && selectedAsset.id === asset.id,
                    'flash-green': asset.flashColor === 'green',
                    'flash-red': asset.flashColor === 'red'
                }"
            >
                <!-- Asset Icon (left side) -->
                <div class="flex-shrink-0 mr-4 relative">
                    <div
                        class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm"
                        :style="{ backgroundColor: getRandomColor(asset.sy) }"
                    >
                        {{ getAssetShortName(asset) }}
                    </div>
                    <!-- Status dot indicator -->
                    <div
                         class="absolute -top-1 -right-1 w-3 h-3 rounded-full border border-[#0F1020]"
                         :class="asset.liveData ? 'bg-blue-500' : !asset.subscribed ? 'bg-gray-500' : 'bg-transparent'">
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
                                {{ asset.name }}
                            </div>
                        </div>

                        <!-- Price and Change -->
                        <div class="text-right">
                            <div class="text-white font-medium">
                                {{ formatPrice(asset.price || asset.rate || asset.buy_price) }}
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
import { io } from 'socket.io-client';

export default {
    props: {
        selectedSymbol: String,
        apiKey: {
            type: String,
            required: true
        },
        socketUrl: {
            type: String,
            default: 'https://your-market-data-host.example'
        }
    },
    data() {
        return {
            allAssets: [], // Store all assets after initial fetch
            search: '',
            selectedCategory: 'crypto', // Default set to forex for our implementation
            selectedAsset: null,
            // categories: ['stocks', 'crypto', 'forex'],
            categories: ['stocks', 'crypto', 'forex', 'commodities', 'indices'],
            loading: false,
            error: null,
            colorCache: {}, // Cache for consistent asset colors

            // WebSocket related data
            socket: null,
            wsConnected: false,
            flashTimeouts: {} // Track timeouts for flash animations
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
        // ===== Core asset handling =====
        async fetchAllAssets() {
            this.loading = true;
            this.error = null;

            try {
                // Fetch all assets in a single call
                const response = await fetch('/api/assets');
                const data = await response.json();

                this.allAssets = data;

                // Select first asset in the filtered list if none is selected
                if (this.filteredAssets.length > 0 && !this.selectedAsset) {
                    this.selectAsset(this.filteredAssets[0]);
                }

                // Add UI properties to assets
                this.allAssets.forEach(asset => {
                    asset.liveData = false;
                    asset.subscribed = false;
                    asset.flashColor = null;
                });

                // Initialize WebSocket
                this.initSocket();
            } catch (err) {
                this.error = 'Failed to load assets. Please try again.';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        selectCategory(category) {
            if (this.selectedCategory === category) return;

            this.selectedCategory = category;

            // Update subscription if connected
            if (this.wsConnected) {
                this.subscribeToCategory();
            }
        },

        selectAsset(asset) {
            this.selectedAsset = asset;
            this.$emit('symbol-selected', asset.ex_sym);
            this.$emit('asset-selected', asset);
        },


        initSocket() {
            // Clean up existing socket if any
            if (this.socket) {
                this.socket.disconnect();
                this.socket = null;
            }

            // Create new socket connection with explicit transport
            this.socket = io(this.socketUrl, {
                transports: ['websocket'], // Force usage of websockets
                auth: { apiKey: this.apiKey }
            });

            // Connection event handlers
            this.socket.on('connect', () => {
                console.log('WebSocket connected');
                this.wsConnected = true;
                this.subscribeToCategory();
            });

            // Add logging for connection errors to help with debugging on mobile devices
            this.socket.on('connect_error', (error) => {
                console.error('WebSocket connect_error:', error);
            });
            this.socket.on('error', (error) => {
                console.error('WebSocket error:', error);
            });

            this.socket.on('disconnect', () => {
                console.log('WebSocket disconnected');
                this.wsConnected = false;
                // Clear live data status
                this.allAssets.forEach(asset => {
                    asset.liveData = false;
                    asset.subscribed = false;
                });
            });

            // Data event handlers (unchanged)
            this.socket.on('data:all', this.handleBulkData);
            this.socket.on('data:update', this.handleBulkData);
            this.socket.on('data:category:crypto', this.handleBulkData);
            this.socket.on('data:category:forex', this.handleBulkData);
            this.socket.on('data:category:stocks', this.handleBulkData);
            this.socket.on('data:category:indices', this.handleBulkData);
            this.socket.on('data:category:commodities', this.handleBulkData);
            this.socket.on('data:category:crypto:update', this.handleBulkData);
            this.socket.on('data:category:forex:update', this.handleBulkData);
            this.socket.on('data:category:stocks:update', this.handleBulkData);
            this.socket.on('data:category:indices:update', this.handleBulkData);
            this.socket.on('data:category:commodities:update', this.handleBulkData);
            this.socket.on('turbo:update', this.handleAssetUpdate);
        },

        // ===== WebSocket handling =====
        initSocketOld() {
            // Clean up existing socket if any
            if (this.socket) {
                this.socket.disconnect();
                this.socket = null;
            }

            // Create new socket connection
            this.socket = io(this.socketUrl, {
                auth: { apiKey: this.apiKey }
            });

            // Connection event handlers
            this.socket.on('connect', () => {
                console.log('WebSocket connected');
                this.wsConnected = true;

                // Subscribe to current category
                this.subscribeToCategory();
            });

            this.socket.on('disconnect', () => {
                console.log('WebSocket disconnected');
                this.wsConnected = false;

                // Clear live data status
                this.allAssets.forEach(asset => {
                    asset.liveData = false;
                    asset.subscribed = false;
                });
            });

            // Data event handlers
            this.socket.on('data:all', this.handleBulkData);
            this.socket.on('data:update', this.handleBulkData);

            // Category data
            this.socket.on('data:category:crypto', this.handleBulkData);
            this.socket.on('data:category:forex', this.handleBulkData);
            this.socket.on('data:category:stocks', this.handleBulkData);
            this.socket.on('data:category:indices', this.handleBulkData);
            this.socket.on('data:category:commodities', this.handleBulkData);

            // Category updates
            this.socket.on('data:category:crypto:update', this.handleBulkData);
            this.socket.on('data:category:forex:update', this.handleBulkData);
            this.socket.on('data:category:stocks:update', this.handleBulkData);
            this.socket.on('data:category:indices:update', this.handleBulkData);
            this.socket.on('data:category:commodities:update', this.handleBulkData);

            // Turbo updates (single asset)
            this.socket.on('turbo:update', this.handleAssetUpdate);
        },

        subscribeToCategory() {
            if (!this.socket || !this.wsConnected) return;

            // Subscribe to current category
            console.log(`Subscribing to ${this.selectedCategory} category`);
            this.socket.emit('subscribe:category', this.selectedCategory);

            // Get symbols for current filtered assets (limit to 20)
            const symbols = this.filteredAssets.slice(0, 20).map(asset => asset.sy);

            // Subscribe to symbols in turbo mode
            if (symbols.length > 0) {
                console.log('Subscribing to symbols:', symbols);
                this.socket.emit('subscribe:symbols', {
                    symbols,
                    mode: 'turbo'
                });

                // Mark assets as subscribed
                this.allAssets.forEach(asset => {
                    asset.subscribed = symbols.includes(asset.sy);
                });
            }
        },

        // Handle multiple assets update
        handleBulkData(data) {
            if (!Array.isArray(data) || !data.length) return;

            // Process each asset update
            data.forEach(assetData => {
                this.handleAssetUpdate(assetData);
            });
        },

        // Handle single asset update
        handleAssetUpdate(data) {
            if (!data || !data.id) return;

            // Find asset by matching coin_id with incoming data id
            // This is the key matching logic!
            const asset = this.allAssets.find(a => a.coin_id === data.id);

            if (!asset) return;

            // Store previous price for change animation
            const prevPrice = asset.price || asset.rate || asset.buy_price;

            // Update asset data with price
            if (data.price !== undefined) {
                asset.price = data.price;
                asset.rate = data.price;
                asset.buy_price = data.price;
            }

            // Update change values
            if (data.change24h !== undefined) {
                asset.change = data.change24h;
            }

            if (data.changePercent24h !== undefined) {
                asset.change_percent = data.changePercent24h;
            }

            // Mark as live data
            asset.liveData = true;
            asset.lastUpdate = new Date().toISOString();

            // Add flash effect if price changed
            if (prevPrice !== undefined && data.price !== undefined && prevPrice !== data.price) {
                // Clear existing timeout if any
                if (this.flashTimeouts[asset.id]) {
                    clearTimeout(this.flashTimeouts[asset.id]);
                }

                // Add flash effect
                asset.flashColor = data.price > prevPrice ? 'green' : 'red';

                // Clear flash after 1 second
                this.flashTimeouts[asset.id] = setTimeout(() => {
                    asset.flashColor = null;
                    delete this.flashTimeouts[asset.id];
                }, 1000);
            }
        },

        toggleConnection() {
            if (this.wsConnected) {
                // Disconnect
                if (this.socket) {
                    this.socket.disconnect();
                }
                this.wsConnected = false;
                this.allAssets.forEach(asset => {
                    asset.liveData = false;
                    asset.subscribed = false;
                });
            } else {
                // Connect
                this.initSocket();
            }
        },

        // ===== Formatting and display methods =====
        getChangeValue(asset) {
            // Use change_percent if available, otherwise use change
            return asset.change_percent !== null ? Number(asset.change_percent) :
                asset.changePercent24h !== null ? Number(asset.changePercent24h) :
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
            return `${sign}${value.toFixed(4)}`;
        },

        formatPrice(price) {
            if (!price && price !== 0) return '-';

            const numPrice = Number(price);

            // For forex, always use 5 decimal places
            if (this.selectedCategory === 'forex') {
                return numPrice.toLocaleString(undefined, { minimumFractionDigits: 5, maximumFractionDigits: 5 });
            }

            // For other asset types, use the original formatting
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
    beforeUnmount() {
        // Clean up resources
        if (this.socket) {
            this.socket.disconnect();
            this.socket = null;
        }

        // Clear all timeouts
        Object.values(this.flashTimeouts).forEach(timeout => {
            clearTimeout(timeout);
        });
        this.flashTimeouts = {};
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

/* Flash animations for price updates */
@keyframes flash-green {
    0% { background-color: transparent; }
    30% { background-color: rgba(46, 204, 113, 0.3); }
    100% { background-color: transparent; }
}

@keyframes flash-red {
    0% { background-color: transparent; }
    30% { background-color: rgba(231, 76, 60, 0.3); }
    100% { background-color: transparent; }
}

.flash-green {
    animation: flash-green 1s ease-out;
}

.flash-red {
    animation: flash-red 1s ease-out;
}
</style>
