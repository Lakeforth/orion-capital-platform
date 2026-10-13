<template>
    <div class="flex flex-col h-[calc(100vh-4rem)] bg-[#0F1020] rounded-md text-white">
        <!-- Header with title and connection status -->
        <div class="flex justify-between items-center p-4">
            <h2 class="text-white font-medium">Market Watch</h2>
            <div class="flex items-center space-x-3">
                <div class="flex items-center text-xs">
                    <div class="w-2 h-2 rounded-full mr-2" :class="wsConnected ? 'bg-green-500' : 'bg-red-500'"></div>
                    <span :class="wsConnected ? 'text-green-400' : 'text-red-400'">{{ wsConnected ? 'Live' : 'Disconnected' }}</span>
                </div>
<!--                <button-->
<!--                    @click="toggleConnection"-->
<!--                    class="text-xs px-2 py-1 rounded bg-[#1A1B2E] hover:bg-[#252640] transition-colors"-->
<!--                    :class="wsConnected ? 'text-green-400' : 'text-red-400'"-->
<!--                >-->
<!--                    {{ wsConnected ? 'Disconnect' : 'Connect' }}-->
<!--                </button>-->
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
                    'animate-flash': asset.flashing
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
                    <div v-if="asset.type === 'forex' && selectedCategory === 'forex'"
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
        selectedSymbol: String,
        apiKey: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            allAssets: [], // Store all assets after initial fetch
            search: '',
            selectedCategory: 'forex', // Default set to forex for our implementation
            selectedAsset: null,
            categories: ['stocks', 'crypto', 'forex', 'commodities', 'indices'],
            loading: false,
            error: null,
            colorCache: {}, // Cache for consistent asset colors

            // WebSocket related data
            forexSocket: null,
            cryptoSocket: null, // Added for crypto websocket
            wsConnected: false,
            wsStatus: false, // Whether to show websocket status
            subscribedSymbols: [], // Currently subscribed symbols
            symbolMap: {}, // Maps WebSocket symbols to our asset objects
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

                // Setup symbol mapping for WebSocket data
                this.setupSymbolMapping();

                // Initialize WebSocket for forex if category is forex
                if (this.selectedCategory === 'forex') {
                    this.initForexWebSocket();
                } else if (this.selectedCategory === 'crypto') {
                    this.initCryptoWebSocket();
                }
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

            // Clean up previous WebSocket subscriptions if needed
            this.cleanupWebSockets();

            // Initialize appropriate WebSocket based on category
            if (category === 'forex') {
                this.wsStatus = true;
                this.initForexWebSocket();
            } else if (category === 'crypto') {
                this.wsStatus = true;
                this.initCryptoWebSocket();
            } else {
                this.wsStatus = false;
            }
        },

        selectAsset(asset) {
            this.selectedAsset = asset;
            this.$emit('symbol-selected', asset.ex_sym);
            this.$emit('asset-selected', asset);
        },

        // ===== WebSocket handling =====
        // ----- Forex WebSocket -----
        initForexWebSocket() {
            // Close existing connection if any
            if (this.forexSocket) {
                this.forexSocket.close();
                this.forexSocket = null;
            }

            // Create new WebSocket connection
            this.forexSocket = new WebSocket('wss://forex.financialmodelingprep.com');

            this.forexSocket.onopen = () => {
                this.wsConnected = true;

                // Login with API key
                const login = {
                    'event': 'login',
                    'data': {
                        'apiKey': this.apiKey
                    }
                };
                this.forexSocket.send(JSON.stringify(login));

                // Subscribe to forex symbols
                this.subscribeToForexSymbols();
            };

            this.forexSocket.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);

                    // Process the data update without logging
                    this.$nextTick(() => {
                        this.handleForexUpdate(data);
                        // Force reactivity update
                        this.forceRefresh();
                    });
                } catch (err) {
                    // No error logging
                }
            };

            this.forexSocket.onclose = () => {
                this.wsConnected = false;

                // Attempt to reconnect after a delay
                setTimeout(() => {
                    if (this.selectedCategory === 'forex') {
                        this.initForexWebSocket();
                    }
                }, 5000);
            };

            this.forexSocket.onerror = (error) => {
                this.wsConnected = false;
            };
        },

        setupSymbolMapping() {
            // Create a mapping from WebSocket symbols to our asset objects
            this.symbolMap = {};

            for (const asset of this.allAssets) {
                if (asset.type.toLowerCase() === 'forex') {
                    // Convert our symbol format to WebSocket format (e.g., "EUR/USD" to "eurusd")
                    const parts = asset.sy.split('/');
                    if (parts.length === 2) {
                        const wsSymbol = (parts[0] + parts[1]).toLowerCase();
                        this.symbolMap[wsSymbol] = asset;
                    }
                }
            }
        },

        subscribeToForexSymbols() {
            if (!this.forexSocket || this.forexSocket.readyState !== WebSocket.OPEN) {
                return;
            }

            // Collect symbols to subscribe to
            const allSymbolsToSubscribe = [];
            for (const asset of this.filteredAssets) {
                if (asset.type.toLowerCase() === 'forex') {
                    const parts = asset.sy.split('/');
                    if (parts.length === 2) {
                        const wsSymbol = (parts[0] + parts[1]).toLowerCase();
                        if (!allSymbolsToSubscribe.includes(wsSymbol)) {
                            allSymbolsToSubscribe.push(wsSymbol);
                        }
                    }
                }
            }

            // Limit subscriptions to 22 symbols to avoid API limit issues
            const MAX_SUBSCRIPTIONS = 22;
            const symbolsToSubscribe = allSymbolsToSubscribe.slice(0, MAX_SUBSCRIPTIONS);

            // Mark which assets are subscribed
            this.allAssets.forEach(asset => {
                if (asset.type.toLowerCase() === 'forex') {
                    const parts = asset.sy.split('/');
                    if (parts.length === 2) {
                        const wsSymbol = (parts[0] + parts[1]).toLowerCase();

                        // Set subscribed status based on whether it's in our subscription list
                        if (this.$set) {
                            this.$set(asset, 'subscribed', symbolsToSubscribe.includes(wsSymbol));
                        } else {
                            asset.subscribed = symbolsToSubscribe.includes(wsSymbol);
                        }
                    }
                }
            });

            // Send subscription if we have symbols
            if (symbolsToSubscribe.length > 0) {
                const subscribe = {
                    'event': 'subscribe',
                    'data': {
                        'ticker': symbolsToSubscribe
                    }
                };
                this.forexSocket.send(JSON.stringify(subscribe));
                this.subscribedSymbols = symbolsToSubscribe;
            }
        },

        unsubscribeFromForexSymbols() {
            if (!this.forexSocket || this.forexSocket.readyState !== WebSocket.OPEN || !this.subscribedSymbols.length) {
                return;
            }

            const unsubscribe = {
                'event': 'unsubscribe',
                'data': {
                    'ticker': this.subscribedSymbols
                }
            };
            this.forexSocket.send(JSON.stringify(unsubscribe));
            this.subscribedSymbols = [];
        },

        handleForexUpdate(data) {
            // Skip if not a data message
            if (!data.s) return;

            // Find the corresponding asset
            const asset = this.symbolMap[data.s];
            if (!asset) return;

            // Store the previous price for change calculation
            const prevPrice = asset.rate || asset.buy_price;

            // Parse bid and ask prices properly
            const bidPrice = parseFloat(data.bp);
            const askPrice = parseFloat(data.ap);

            // Calculate mid price
            const midPrice = (bidPrice + askPrice) / 2;

            // Update the asset data with reactivity that works in both Vue 2 and Vue 3
            // For Vue 2
            if (this.$set) {
                this.$set(asset, 'rate', midPrice);
                this.$set(asset, 'buy_price', bidPrice);
                this.$set(asset, 'sell_price', askPrice);
                this.$set(asset, 'liveData', true);
                this.$set(asset, 'subscribed', true);
                this.$set(asset, 'lastUpdate', new Date().toISOString());
            }
            // For Vue 3 or direct assignment as fallback
            else {
                asset.rate = midPrice;
                asset.buy_price = bidPrice;
                asset.sell_price = askPrice;
                asset.liveData = true;
                asset.subscribed = true;
                asset.lastUpdate = new Date().toISOString();

                // Force reactivity
                this.allAssets = [...this.allAssets];
            }

            // Calculate and update price change
            if (prevPrice) {
                const priceChange = midPrice - prevPrice;

                // Update change values with proper reactivity
                if (this.$set) {
                    this.$set(asset, 'change', priceChange);

                    if (prevPrice > 0) {
                        this.$set(asset, 'change_percent', (priceChange / prevPrice) * 100);
                    }
                } else {
                    asset.change = priceChange;

                    if (prevPrice > 0) {
                        asset.change_percent = (priceChange / prevPrice) * 100;
                    }
                }

                // Flash effect on price change
                if (priceChange !== 0) {
                    if (this.$set) {
                        this.$set(asset, 'flashing', true);
                    } else {
                        asset.flashing = true;
                    }

                    setTimeout(() => {
                        if (this.$set) {
                            this.$set(asset, 'flashing', false);
                        } else {
                            asset.flashing = false;
                        }
                    }, 1000);
                }
            }
        },

        // ----- Crypto WebSocket -----
        initCryptoWebSocket() {
            // Close existing connection if any
            if (this.cryptoSocket) {
                this.cryptoSocket.close();
                this.cryptoSocket = null;
            }

            // Filter crypto assets with valid coin_id
            const cryptoAssetsWithId = this.filteredAssets.filter(asset =>
                asset.type.toLowerCase() === 'crypto' && asset.coin_id
            );

            // If no valid assets, return early
            if (cryptoAssetsWithId.length === 0) return;

            // Get comma-separated list of coin_ids
            const coinIds = cryptoAssetsWithId.map(asset => asset.coin_id).join(',');

            // Create WebSocket connection with coin_ids as query parameters
            this.cryptoSocket = new WebSocket(`wss://ws.coincap.io/prices?assets=${coinIds}`);

            this.cryptoSocket.onopen = () => {
                this.wsConnected = true;

                // Mark assets as subscribed
                cryptoAssetsWithId.forEach(asset => {
                    if (this.$set) {
                        this.$set(asset, 'subscribed', true);
                    } else {
                        asset.subscribed = true;
                    }
                });
            };

            this.cryptoSocket.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.$nextTick(() => {
                        this.handleCryptoUpdate(data);
                        // Force reactivity update
                        this.forceRefresh();
                    });
                } catch (err) {
                    // Silent error handling
                }
            };

            this.cryptoSocket.onclose = () => {
                this.wsConnected = false;

                // Attempt to reconnect after delay if still on crypto category
                setTimeout(() => {
                    if (this.selectedCategory === 'crypto') {
                        this.initCryptoWebSocket();
                    }
                }, 5000);
            };

            this.cryptoSocket.onerror = () => {
                this.wsConnected = false;
            };
        },

        handleCryptoUpdate(data) {
            // Iterate through all received prices
            for (const [coinId, priceStr] of Object.entries(data)) {
                // Find matching asset by coin_id
                const asset = this.allAssets.find(a =>
                    a.type.toLowerCase() === 'crypto' && a.coin_id === coinId
                );

                if (asset) {
                    // Store previous price for change calculation
                    const prevPrice = asset.rate || asset.buy_price;

                    // Parse new price
                    const newPrice = parseFloat(priceStr);

                    // Update asset with proper reactivity (Vue 2 or 3)
                    if (this.$set) {
                        this.$set(asset, 'rate', newPrice);
                        this.$set(asset, 'buy_price', newPrice);
                        this.$set(asset, 'liveData', true);
                        this.$set(asset, 'lastUpdate', new Date().toISOString());
                    } else {
                        asset.rate = newPrice;
                        asset.buy_price = newPrice;
                        asset.liveData = true;
                        asset.lastUpdate = new Date().toISOString();
                    }

                    // Calculate price change
                    if (prevPrice) {
                        const priceChange = newPrice - prevPrice;

                        // Update change values
                        if (this.$set) {
                            this.$set(asset, 'change', priceChange);
                            if (prevPrice > 0) {
                                this.$set(asset, 'change_percent', (priceChange / prevPrice) * 100);
                            }
                        } else {
                            asset.change = priceChange;
                            if (prevPrice > 0) {
                                asset.change_percent = (priceChange / prevPrice) * 100;
                            }
                        }

                        // Add flash effect on change
                        if (priceChange !== 0) {
                            if (this.$set) {
                                this.$set(asset, 'flashing', true);
                            } else {
                                asset.flashing = true;
                            }

                            setTimeout(() => {
                                if (this.$set) {
                                    this.$set(asset, 'flashing', false);
                                } else {
                                    asset.flashing = false;
                                }
                            }, 1000);
                        }
                    }
                }
            }
        },

        cleanupWebSockets() {
            // Unsubscribe and close any active WebSockets
            if (this.forexSocket && this.forexSocket.readyState === WebSocket.OPEN) {
                this.unsubscribeFromForexSymbols();
                this.forexSocket.close();
            }
            this.forexSocket = null;

            // Close crypto WebSocket if active
            if (this.cryptoSocket && this.cryptoSocket.readyState === WebSocket.OPEN) {
                this.cryptoSocket.close();
            }
            this.cryptoSocket = null;

            this.wsConnected = false;
        },

        toggleConnection() {
            if (this.wsConnected) {
                // Disconnect
                this.cleanupWebSockets();
            } else {
                // Connect to appropriate WebSocket based on category
                if (this.selectedCategory === 'forex') {
                    this.initForexWebSocket();
                } else if (this.selectedCategory === 'crypto') {
                    this.initCryptoWebSocket();
                }
            }
        },

        // Debug method to force a UI refresh
        forceRefresh() {
            this.allAssets = [...this.allAssets];
        },

        // ===== Formatting and display methods =====
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

            // For forex, always use 6 decimal places
            if (this.selectedCategory === 'forex') {
                return numPrice.toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 4 });
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

            // Update WebSocket subscriptions if in forex category
            if (this.selectedCategory === 'forex' && this.forexSocket) {
                this.unsubscribeFromForexSymbols();
                this.subscribeToForexSymbols();
            } else if (this.selectedCategory === 'crypto' && this.cryptoSocket) {
                // Reinitialize crypto WebSocket to update subscriptions
                this.initCryptoWebSocket();
            }
        },

        // When search changes, update WebSocket subscriptions
        search() {
            if (this.selectedCategory === 'forex' && this.forexSocket) {
                this.unsubscribeFromForexSymbols();
                this.subscribeToForexSymbols();
            } else if (this.selectedCategory === 'crypto' && this.cryptoSocket) {
                // Reinitialize crypto WebSocket to update subscriptions
                this.initCryptoWebSocket();
            }
        }
    },
    beforeUnmount() {
        // Clean up WebSocket connections
        this.cleanupWebSockets();
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

/* Flash animation for price updates */
@keyframes flash {
    0% { background-color: #1A1B2E; }
    50% { background-color: rgba(52, 152, 219, 0.3); }
    100% { background-color: #1A1B2E; }
}

.animate-flash {
    animation: flash 1s ease-out;
}
</style>
