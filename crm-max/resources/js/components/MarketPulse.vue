<template>
    <div class="market-pulse">
        <div class="connection-status" :class="{ connected, error: !!error }">
            <span>Status: {{ connected ? 'Connected' : 'Disconnected' }}</span>
            <span v-if="error" class="error-message">Error: {{ error }}</span>
        </div>

        <div class="controls">
            <div class="form-group">
                <label for="api-key">API Key:</label>
                <input
                    id="api-key"
                    v-model="apiKey"
                    type="password"
                    placeholder="Enter your API key"
                />
            </div>

            <button @click="connect" :disabled="!apiKey || connecting || connected">
                {{ connecting ? 'Connecting...' : 'Connect' }}
            </button>
            <button @click="disconnect" :disabled="!connected">Disconnect</button>

            <div class="category-selection">
                <label>Subscribe to:</label>
                <div class="categories">
                    <label v-for="category in availableCategories" :key="category">
                        <input
                            type="checkbox"
                            :value="category"
                            v-model="selectedCategories"
                            :disabled="!connected"
                            @change="handleCategoryChange"
                        />
                        {{ formatCategory(category) }}
                    </label>
                </div>
            </div>
        </div>

        <div v-if="assets.length" class="data-table-container">
            <h3>{{ assets.length }} Assets</h3>
            <table class="data-table">
                <thead>
                <tr>
                    <th>Symbol</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>24h Change</th>
                    <th>Updated</th>
                </tr>
                </thead>
                <tbody>
                <tr
                    v-for="asset in sortedAssets"
                    :key="asset.assetId"
                    :class="{ updated: recentlyUpdated.includes(asset.assetId) }"
                >
                    <td>{{ asset.symbol }}</td>
                    <td>{{ asset.name }}</td>
                    <td>{{ formatCategory(asset.category) }}</td>
                    <td>{{ formatPrice(asset.price) }}</td>
                    <td :class="asset.change24h >= 0 ? 'positive' : 'negative'">
                        {{ formatPercent(asset.change24h) }}
                    </td>
                    <td>{{ formatTime(asset.timestamp) }}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div v-else-if="connected && !loading" class="no-data">
            No assets data available. Try subscribing to a category.
        </div>
        <div v-else-if="loading" class="loading">
            Loading assets data...
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { io } from 'socket.io-client';

export default {
    name: 'MarketPulse',

    props: {
        serverUrl: {
            type: String,
            default: 'http://localhost:3000'
        },
        websocketPath: {
            type: String,
            default: '/market-data'
        },
        initialApiKey: {
            type: String,
            default: ''
        }
    },

    setup(props) {
        // State
        const socket = ref(null);
        const connected = ref(false);
        const connecting = ref(false);
        const error = ref('');
        const loading = ref(false);
        const apiKey = ref(props.initialApiKey);
        const assets = ref([]);
        const assetsMap = ref({});
        const recentlyUpdated = ref([]);

        // Available asset categories
        const availableCategories = [
            'cryptocurrency',
            'stock',
            'forex',
            'commodity',
            'index'
        ];

        const selectedCategories = ref([]);

        // Computed properties
        const sortedAssets = computed(() => {
            return [...assets.value].sort((a, b) => a.symbol.localeCompare(b.symbol));
        });

        // Connect to websocket
        const connect = async () => {
            if (!apiKey.value || connecting.value || connected.value) return;

            try {
                connecting.value = true;
                error.value = '';

                socket.value = io(props.serverUrl, {
                    path: props.websocketPath,
                    auth: { apiKey: apiKey.value },
                    transports: ['websocket'],
                    reconnectionAttempts: 3
                });

                // Connection events
                socket.value.on('connect', () => {
                    console.log('Connected to MarketPulse');
                    connected.value = true;
                    connecting.value = false;

                    // Subscribe to default category if any is selected
                    if (selectedCategories.value.length > 0) {
                        subscribeToCategories();
                    }
                });

                socket.value.on('connect_error', (err) => {
                    console.error('Connection error:', err.message);
                    error.value = err.message;
                    connecting.value = false;
                });

                socket.value.on('disconnect', (reason) => {
                    console.log('Disconnected:', reason);
                    connected.value = false;

                    if (reason === 'io server disconnect') {
                        // Server disconnected us, try to reconnect
                        socket.value.connect();
                    }
                });

                // Asset data events
                socket.value.on('initialData', (data) => {
                    console.log('Received initial data:', data.length, 'assets');
                    loading.value = false;

                    // Update assets map
                    const newMap = {};
                    data.forEach(asset => {
                        newMap[asset.assetId] = asset;
                    });

                    assetsMap.value = newMap;
                    assets.value = Object.values(newMap);
                });

                socket.value.on('asset', (asset) => {
                    console.log('Asset update:', asset.symbol, asset.price);

                    // Update the asset in our map
                    assetsMap.value = {
                        ...assetsMap.value,
                        [asset.assetId]: asset
                    };

                    // Update assets list
                    assets.value = Object.values(assetsMap.value);

                    // Add to recently updated for highlighting
                    recentlyUpdated.value.push(asset.assetId);

                    // Remove from recently updated after animation
                    setTimeout(() => {
                        recentlyUpdated.value = recentlyUpdated.value.filter(id => id !== asset.assetId);
                    }, 2000);
                });

                socket.value.on('subscribed', (response) => {
                    if (response.success) {
                        console.log('Subscription successful:', response);
                    } else {
                        console.error('Subscription failed:', response.error);
                        error.value = `Subscription failed: ${response.error}`;
                    }
                });
            } catch (err) {
                console.error('Failed to connect:', err);
                error.value = err.message;
                connecting.value = false;
            }
        };

        // Disconnect from websocket
        const disconnect = () => {
            if (socket.value) {
                socket.value.disconnect();
                socket.value = null;
                connected.value = false;
                assets.value = [];
                assetsMap.value = {};
            }
        };

        // Subscribe to selected categories
        const subscribeToCategories = () => {
            if (!socket.value || !connected.value) return;

            loading.value = true;

            socket.value.emit('subscribe', {
                categories: selectedCategories.value
            });
        };

        // Handle category selection change
        const handleCategoryChange = () => {
            subscribeToCategories();
        };

        // Format category name
        const formatCategory = (category) => {
            return category.charAt(0).toUpperCase() + category.slice(1);
        };

        // Format price
        const formatPrice = (price) => {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2,
                maximumFractionDigits: price < 1 ? 6 : 2
            }).format(price);
        };

        // Format percent
        const formatPercent = (percent) => {
            return new Intl.NumberFormat('en-US', {
                style: 'percent',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
                signDisplay: 'always'
            }).format(percent / 100);
        };

        // Format timestamp
        const formatTime = (timestamp) => {
            return new Date(timestamp).toLocaleTimeString();
        };

        // Cleanup on component unmount
        onUnmounted(() => {
            disconnect();
        });

        return {
            // State
            socket,
            connected,
            connecting,
            error,
            loading,
            apiKey,
            assets,
            recentlyUpdated,
            availableCategories,
            selectedCategories,
            sortedAssets,

            // Methods
            connect,
            disconnect,
            subscribeToCategories,
            handleCategoryChange,
            formatCategory,
            formatPrice,
            formatPercent,
            formatTime
        };
    }
};
</script>

<style scoped>
.market-pulse {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.connection-status {
    padding: 10px;
    margin-bottom: 20px;
    background-color: #f8d7da;
    border-radius: 4px;
    color: #721c24;
    display: flex;
    justify-content: space-between;
}

.connection-status.connected {
    background-color: #d4edda;
    color: #155724;
}

.connection-status.error {
    background-color: #f8d7da;
    color: #721c24;
}

.error-message {
    font-weight: bold;
}

.controls {
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.form-group {
    display: flex;
    flex-direction: column;
    margin-right: 10px;
}

label {
    margin-bottom: 5px;
    font-weight: bold;
}

input[type="password"],
input[type="text"] {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    min-width: 200px;
}

button {
    padding: 8px 16px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

button:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
}

button:not(:disabled):hover {
    background-color: #45a049;
}

.category-selection {
    margin-left: 20px;
}

.categories {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 5px;
}

.categories label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: normal;
}

.data-table-container {
    margin-top: 20px;
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.data-table th,
.data-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.data-table th {
    background-color: #f2f2f2;
    position: sticky;
    top: 0;
}

.data-table tr:hover {
    background-color: #f5f5f5;
}

.data-table tr.updated {
    background-color: #fffde7;
    transition: background-color 2s;
}

.positive {
    color: #4CAF50;
}

.negative {
    color: #f44336;
}

.loading, .no-data {
    margin-top: 20px;
    padding: 20px;
    text-align: center;
    background-color: #f9f9f9;
    border-radius: 4px;
}
</style>
