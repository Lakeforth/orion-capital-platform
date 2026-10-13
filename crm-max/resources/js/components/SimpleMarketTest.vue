<template>
    <div class="market-data-terminal bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
        <!-- Header with title and connection status -->
        <header class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Market Data Terminal</h2>
            <div class="flex items-center text-sm">
                <div
                    class="w-3 h-3 rounded-full mr-2"
                    :class="{
            'bg-green-500': connectionStatus === 'connected',
            'bg-yellow-500 animate-pulse': connectionStatus === 'connecting',
            'bg-red-500': connectionStatus === 'disconnected'
          }"
                ></div>
                <span>{{ statusText }}</span>
            </div>
        </header>

        <!-- Control panel -->
        <div class="flex flex-wrap gap-4 mb-6">
            <div class="flex gap-2">
                <button
                    @click="connect"
                    :disabled="connectionStatus === 'connected' || connectionStatus === 'connecting'"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Connect
                </button>
                <button
                    @click="disconnect"
                    :disabled="connectionStatus !== 'connected'"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Disconnect
                </button>
            </div>

            <div class="flex gap-2">
                <select
                    v-model="category"
                    @change="handleCategoryChange"
                    class="border border-gray-300 rounded px-3 py-2"
                    :disabled="connectionStatus !== 'connected'"
                >
                    <option value="">Select category</option>
                    <option value="crypto">Cryptocurrency</option>
                    <option value="stocks">Stocks</option>
                    <option value="forex">Forex</option>
                    <option value="indices">Indices</option>
                    <option value="commodities">Commodities</option>
                </select>
            </div>
        </div>

        <!-- Error message -->
        <div v-if="error" class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded mb-6 flex justify-between items-center">
            <span>{{ error }}</span>
            <button @click="error = ''" class="text-red-700 font-bold">×</button>
        </div>

        <!-- Data display -->
        <div class="border border-gray-200 rounded overflow-hidden">
            <div v-if="assets.length === 0" class="flex items-center justify-center h-64 text-gray-500">
                {{ emptyStateMessage }}
            </div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">24h Change</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="asset in assets" :key="asset.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap font-medium">{{ asset.symbol }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ asset.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right font-mono">{{ formatPrice(asset.price) }}</td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-right font-mono"
                            :class="{
                  'text-green-500': asset.changePercent24h > 0,
                  'text-red-500': asset.changePercent24h < 0
                }"
                        >
                            {{ formatChange(asset.changePercent24h) }}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-4 pt-4 border-t border-gray-200 text-sm text-gray-500 flex justify-between">
            <div>
                <span v-if="assets.length > 0">Showing {{ assets.length }} assets</span>
            </div>
            <div v-if="category">Category: <span class="capitalize">{{ category }}</span></div>
        </footer>
    </div>
</template>

<script>
import { io } from 'socket.io-client';
import { defineComponent, ref, computed, onMounted, onUnmounted, watch } from 'vue';

export default defineComponent({
    name: 'MarketDataClient',

    setup() {
        // State
        const connectionStatus = ref('disconnected'); // 'connected', 'connecting', 'disconnected'
        const assets = ref([]);
        const category = ref('');
        const error = ref('');
        const reconnectAttempt = ref(0);
        const socket = ref(null);
        let reconnectTimer = null;

        // Configuration - would typically come from environment or props
        const serverUrl = 'https://your-market-data-host.example'; // Replace with your actual server URL
        const apiKey = '9e37abad-04e9-47fb-bbd5-b8e344ff7e5a'; // Replace with your actual API key

        // Computed properties
        const statusText = computed(() => {
            if (connectionStatus.value === 'connected') return 'Connected';
            if (connectionStatus.value === 'connecting') {
                return reconnectAttempt.value > 0
                    ? `Connecting (Attempt ${reconnectAttempt.value})...`
                    : 'Connecting...';
            }
            return 'Disconnected';
        });

        const emptyStateMessage = computed(() => {
            if (connectionStatus.value !== 'connected') {
                return 'Connect to the server to view market data';
            } else if (!category.value) {
                return 'Select a category to subscribe to asset data';
            } else {
                return 'No assets available in this category';
            }
        });

        // Methods
        function connect() {
            if (connectionStatus.value === 'connected' || connectionStatus.value === 'connecting') return;

            try {
                connectionStatus.value = 'connecting';
                error.value = '';

                // Initialize Socket.IO connection with authentication
                socket.value = io(serverUrl, {
                    auth: { apiKey },
                    reconnection: true,
                    reconnectionAttempts: 5,
                    reconnectionDelay: 1000,
                    reconnectionDelayMax: 5000,
                    timeout: 10000
                });

                // Connection event handlers
                socket.value.on('connect', () => {
                    connectionStatus.value = 'connected';
                    error.value = '';
                    reconnectAttempt.value = 0;
                    console.log('Connected to WebSocket server');

                    // Resubscribe to category if it was selected before
                    if (category.value) {
                        subscribeToCategory(category.value);
                    }
                });

                socket.value.on('disconnect', (reason) => {
                    connectionStatus.value = 'disconnected';
                    console.log('Disconnected:', reason);

                    if (reason === 'io server disconnect') {
                        // the disconnection was initiated by the server, need to reconnect manually
                        error.value = 'Server disconnected the client. Please reconnect manually.';
                    }
                });

                socket.value.on('connect_error', (err) => {
                    error.value = `Connection error: ${err.message}`;
                    console.error('Connection error:', err);

                    if (!socket.value.connected) {
                        connectionStatus.value = 'disconnected';
                    }
                });

                socket.value.on('error', (err) => {
                    error.value = `WebSocket error: ${typeof err === 'string' ? err : err.message}`;
                    console.error('WebSocket error:', err);
                });

                socket.value.on('reconnect_attempt', (attempt) => {
                    connectionStatus.value = 'connecting';
                    reconnectAttempt.value = attempt;
                    console.log(`Reconnection attempt ${attempt}`);
                });

                socket.value.on('reconnect_failed', () => {
                    connectionStatus.value = 'disconnected';
                    error.value = 'Failed to reconnect after several attempts.';
                });

                // Data event handlers for all categories
                ['crypto', 'stocks', 'forex', 'indices', 'commodities'].forEach(cat => {
                    socket.value.on(`data:category:${cat}`, (data) => {
                        console.log(`Received ${data.length} ${cat} assets`);
                        assets.value = data;
                    });

                    socket.value.on(`data:category:${cat}:update`, handleUpdates);
                });

                socket.value.on('data:update', handleUpdates);

            } catch (err) {
                connectionStatus.value = 'disconnected';
                error.value = `Failed to connect: ${err.message}`;
                console.error('Connection setup error:', err);
            }
        }

        function disconnect() {
            if (socket.value) {
                socket.value.disconnect();
                socket.value = null;
                connectionStatus.value = 'disconnected';
                assets.value = [];
                reconnectAttempt.value = 0;
            }

            if (reconnectTimer) {
                clearTimeout(reconnectTimer);
                reconnectTimer = null;
            }
        }

        function handleUpdates(updates) {
            if (!Array.isArray(updates)) return;

            // Create a map of current assets by ID
            const assetMap = new Map(assets.value.map(asset => [asset.id, asset]));

            // Update with new data
            updates.forEach(update => {
                assetMap.set(update.id, update);
            });

            // Convert map back to array
            assets.value = Array.from(assetMap.values());
        }

        function subscribeToCategory(selectedCategory) {
            if (!socket.value || connectionStatus.value !== 'connected') return;

            assets.value = [];
            console.log(`Subscribing to ${selectedCategory}`);
            socket.value.emit('subscribe:category', selectedCategory);
        }

        function handleCategoryChange() {
            if (category.value && connectionStatus.value === 'connected') {
                subscribeToCategory(category.value);
            }
        }

        function formatPrice(price) {
            if (price === undefined || price === null) return 'N/A';

            if (price < 0.01) return price.toFixed(6);
            if (price < 1) return price.toFixed(4);
            if (price < 10000) return price.toFixed(2);
            return price.toLocaleString(undefined, { maximumFractionDigits: 0 });
        }

        function formatChange(change) {
            if (change === undefined || change === null) return 'N/A';
            return `${change > 0 ? '+' : ''}${change.toFixed(2)}%`;
        }

        // Clean up on unmount
        onUnmounted(() => {
            disconnect();
        });

        // Watch for connection status changes
        watch(connectionStatus, (newStatus) => {
            console.log(`Connection status changed to: ${newStatus}`);
        });

        return {
            connectionStatus,
            assets,
            category,
            error,
            reconnectAttempt,
            statusText,
            emptyStateMessage,
            connect,
            disconnect,
            handleCategoryChange,
            formatPrice,
            formatChange
        };
    }
});
</script>

<style scoped>
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
