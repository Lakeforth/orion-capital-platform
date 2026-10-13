<template>
    <div class="p-4 max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Crypto Ticker</h1>

        <!-- Connection Status -->
        <div class="mb-4">
            <div class="flex items-center mb-2">
                <div :class="['w-3 h-3 rounded-full mr-2', isConnected ? 'bg-green-500' : 'bg-red-500']"></div>
                <span>{{ isConnected ? 'Connected' : 'Disconnected' }}</span>
            </div>
            <button
                @click="toggleConnection"
                class="px-4 py-2 rounded text-white font-medium"
                :class="isConnected ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-500 hover:bg-blue-600'"
            >
                {{ isConnected ? 'Disconnect' : 'Connect' }}
            </button>
        </div>

        <!-- Pair Selection -->
        <div class="mb-4">
            <h2 class="text-lg font-semibold mb-2">Select Pairs</h2>
            <div class="flex flex-wrap gap-2">
                <div v-for="pair in availablePairs" :key="pair" class="flex items-center">
                    <input
                        type="checkbox"
                        :id="pair"
                        :value="pair"
                        v-model="selectedPairs"
                        class="mr-1"
                    />
                    <label :for="pair">{{ pair }}</label>
                </div>
            </div>
            <button
                @click="fetchPrices"
                class="mt-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded font-medium"
                :disabled="!isConnected || selectedPairs.length === 0"
            >
                Update Prices
            </button>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
            {{ errorMessage }}
        </div>

        <!-- Price Cards -->
        <div v-if="Object.keys(prices).length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="(data, pair) in prices" :key="pair" class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-xl font-bold">{{ pair }}</h3>
                    <span class="text-xs text-gray-500">Last update: {{ formatTime(data.lastUpdate) }}</span>
                </div>

                <!-- Price -->
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Current Price</p>
                    <p :class="['text-2xl font-bold', getPriceColor(data)]">
                        {{ formatPrice(data.last) }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <p class="text-sm text-gray-600">24h Change</p>
                        <p :class="['text-lg font-bold', getChangeColor(data.priceChange)]">
                            {{ formatChange(data.priceChange) }}
                            ({{ formatPercentage(data.priceChangePercentage) }})
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">24h High/Low</p>
                        <p class="text-lg">
                            <span class="text-green-600">{{ formatPrice(data.high) }}</span> /
                            <span class="text-red-600">{{ formatPrice(data.low) }}</span>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Volume (24h)</p>
                        <p class="text-lg">{{ formatVolume(data.volume) }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Volume (USD)</p>
                        <p class="text-lg">${{ formatVolume(data.volumeUSD) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="isConnected && selectedPairs.length > 0" class="text-center py-8">
            <p>Waiting for data...</p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CryptoTicker',
    data() {
        return {
            ws: null,
            isConnected: false,
            errorMessage: '',
            pingInterval: null,
            updateInterval: null,
            lastRequestId: 1,

            availablePairs: ['BTC-USD', 'ETH-USD', 'ADA-USD', 'XRP-USD', 'SOL-USD', 'DOGE-USD', 'SHIB-USD'],
            selectedPairs: ['BTC-USD', 'ETH-USD'],
            prices: {},
            previousPrices: {}
        };
    },

    methods: {
        toggleConnection() {
            if (this.isConnected) {
                this.disconnect();
            } else {
                this.connect();
            }
        },

        connect() {
            try {
                this.errorMessage = '';

                // Close any existing connection
                if (this.ws) {
                    this.ws.close();
                    this.ws = null;
                }

                // Create new WebSocket connection
                this.ws = new WebSocket('wss://trade.cex.io/api/spot/ws-public');

                this.ws.onopen = () => {
                    console.log('WebSocket connection opened');
                };

                this.ws.onmessage = (event) => {
                    try {
                        const message = JSON.parse(event.data);

                        if (message.e === 'connected') {
                            console.log('Connected to CEX.IO WebSocket');
                            this.isConnected = true;
                            this.startPingInterval();
                            this.fetchPrices();
                            this.startUpdateInterval();
                        }
                        else if (message.e === 'pong') {
                            // Ping-pong for keeping connection alive
                        }
                        else if (message.e === 'get_ticker' && message.ok === 'ok') {
                            this.updatePrices(message.data);
                        }
                        else if (message.error) {
                            this.errorMessage = `Error: ${message.error}`;
                        }
                    } catch (e) {
                        console.error('Error parsing message:', e);
                        this.errorMessage = 'Error processing server response';
                    }
                };

                this.ws.onerror = () => {
                    console.error('WebSocket error');
                    this.errorMessage = 'WebSocket connection error';
                    this.isConnected = false;
                    this.stopPingInterval();
                    this.stopUpdateInterval();
                };

                this.ws.onclose = () => {
                    console.log('WebSocket connection closed');
                    this.isConnected = false;
                    this.stopPingInterval();
                    this.stopUpdateInterval();
                };
            } catch (e) {
                console.error('Failed to connect:', e);
                this.errorMessage = 'Failed to establish WebSocket connection';
            }
        },

        disconnect() {
            if (this.ws) {
                this.ws.close();
                this.ws = null;
            }

            this.isConnected = false;
            this.stopPingInterval();
            this.stopUpdateInterval();
        },

        startPingInterval() {
            this.stopPingInterval();
            this.pingInterval = setInterval(() => {
                if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                    try {
                        this.ws.send(JSON.stringify({ e: 'ping' }));
                    } catch (e) {
                        console.error('Error sending ping:', e);
                        this.disconnect();
                    }
                } else {
                    this.stopPingInterval();
                }
            }, 5000);
        },

        stopPingInterval() {
            if (this.pingInterval) {
                clearInterval(this.pingInterval);
                this.pingInterval = null;
            }
        },

        startUpdateInterval() {
            this.stopUpdateInterval();
            this.updateInterval = setInterval(() => {
                if (this.isConnected && this.selectedPairs.length > 0) {
                    this.fetchPrices();
                }
            }, 5000);
        },

        stopUpdateInterval() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
        },

        generateRequestId() {
            return `${Date.now()}_${this.lastRequestId++}`;
        },

        fetchPrices() {
            if (!this.isConnected || this.selectedPairs.length === 0) return;

            const request = {
                e: 'get_ticker',
                oid: this.generateRequestId(),
                data: {
                    pairs: [...this.selectedPairs]
                }
            };

            try {
                if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                    this.ws.send(JSON.stringify(request));
                } else {
                    this.errorMessage = 'WebSocket not connected';
                    this.isConnected = false;
                }
            } catch (e) {
                console.error('Error sending request:', e);
                this.errorMessage = 'Failed to send request';
            }
        },

        updatePrices(data) {
            if (!data) return;

            // Store previous prices for comparison
            for (const pair in data) {
                if (data[pair] && data[pair].last) {
                    // Save current price as previous for next update
                    if (this.prices[pair]) {
                        this.previousPrices[pair] = this.prices[pair].last;
                    }

                    // Update the price data
                    this.prices[pair] = {
                        ...data[pair],
                        lastUpdate: new Date()
                    };
                }
            }
        },

        // Safe formatting methods
        formatTime(date) {
            if (!date) return '';
            try {
                return date.toLocaleTimeString();
            } catch (e) {
                return '';
            }
        },

        formatPrice(price) {
            if (price === undefined || price === null) return '0';
            try {
                const num = parseFloat(price);
                if (isNaN(num)) return '0';

                if (num < 0.001) return num.toFixed(8);
                if (num < 0.1) return num.toFixed(6);
                if (num < 1) return num.toFixed(4);
                if (num < 1000) return num.toFixed(2);
                return num.toLocaleString(undefined, { maximumFractionDigits: 2 });
            } catch (e) {
                return '0';
            }
        },

        formatChange(change) {
            if (change === undefined || change === null) return '0';
            try {
                const num = parseFloat(change);
                if (isNaN(num)) return '0';

                const prefix = num > 0 ? '+' : '';
                return prefix + this.formatPrice(num);
            } catch (e) {
                return '0';
            }
        },

        formatPercentage(percentage) {
            if (percentage === undefined || percentage === null) return '0%';
            try {
                const num = parseFloat(percentage);
                if (isNaN(num)) return '0%';

                const prefix = num > 0 ? '+' : '';
                return prefix + num.toFixed(2) + '%';
            } catch (e) {
                return '0%';
            }
        },

        formatVolume(volume) {
            if (volume === undefined || volume === null) return '0';
            try {
                const num = parseFloat(volume);
                if (isNaN(num)) return '0';

                if (num >= 1000000) {
                    return (num / 1000000).toFixed(2) + 'M';
                } else if (num >= 1000) {
                    return (num / 1000).toFixed(2) + 'K';
                }

                return num.toFixed(2);
            } catch (e) {
                return '0';
            }
        },

        getPriceColor(data) {
            if (!data || !this.previousPrices[data.symbol]) return 'text-gray-800';

            try {
                const current = parseFloat(data.last);
                const previous = parseFloat(this.previousPrices[data.symbol]);

                if (isNaN(current) || isNaN(previous)) return 'text-gray-800';

                if (current > previous) return 'text-green-600';
                if (current < previous) return 'text-red-600';
                return 'text-gray-800';
            } catch (e) {
                return 'text-gray-800';
            }
        },

        getChangeColor(change) {
            if (change === undefined || change === null) return 'text-gray-800';
            try {
                const num = parseFloat(change);
                if (isNaN(num)) return 'text-gray-800';

                if (num > 0) return 'text-green-600';
                if (num < 0) return 'text-red-600';
                return 'text-gray-800';
            } catch (e) {
                return 'text-gray-800';
            }
        }
    },

    mounted() {
        this.connect();
    },

    beforeUnmount() {
        this.disconnect();
    }
};
</script>
