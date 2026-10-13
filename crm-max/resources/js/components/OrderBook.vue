<template>
    <div>
        <!-- Header and Toggle Buttons -->
        <div class="flex gap-4 mb-4">
            <div class="flex-1">
                <Link :href="backUrl" class="flex gap-2 items-center text-lg font-semibold">
                    <!-- Use an icon from a Vue-compatible source instead of Blade svg -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Order books
                </Link>
            </div>

            <div class="flex justify-end">
                <div class="inline-flex rounded-md shadow-sm" role="group">
                    <button @click="setTradeStatus('open')"
                            :class="{'bg-green-600': tradeStatus == 'open'}"
                            class="px-4 py-2 text-sm font-medium rounded-l-lg border border-gray-700 hover:bg-gray-700 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-700">
                        Open
                    </button>
                    <button @click="setTradeStatus('closed')"
                            :class="{'bg-green-600': tradeStatus == 'closed'}"
                            class="px-4 py-2 text-sm font-medium rounded-r-lg border border-gray-700 hover:bg-gray-700 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-700">
                        Closed
                    </button>
                </div>
            </div>
        </div>

        <!-- Existing Table Content -->
        <div class="w-full overflow-x-auto
scrollbar-thin scrollbar-thumb-[#1A1B2E] scrollbar-track-[#0F1020] hover:scrollbar-thumb-gray-500
">
            <template v-if="trades.length < 1 && loading">
                <div class="flex py-2 px-3 bg-white rounded">
                    <div role="status" class="space-y-2.5 animate-pulse w-full">
                        <!-- Loading skeleton -->
                        <!-- ... (keeping all existing loading skeleton code) ... -->
                        <div class="flex items-center w-full">
                            <div class="h-3.5 bg-gray-200 rounded-full dark:bg-gray-700 w-full"></div>
                            <div class="h-3.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-full"></div>
                            <div class="h-3.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-full"></div>
                        </div>
                        <div class="flex items-center w-full">
                            <div class="h-3.5 bg-gray-200 rounded-full dark:bg-gray-700 w-full"></div>
                            <div class="h-3.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-40"></div>
                            <div class="h-3.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-60"></div>
                        </div>
                        <div class="flex items-center w-full">
                            <div class="h-3.5 bg-gray-200 rounded-full dark:bg-gray-700 w-full"></div>
                            <div class="h-3.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-full"></div>
                            <div class="h-3.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-full"></div>
                        </div>
                        <div class="flex items-center w-full">
                            <div class="h-3.5 bg-gray-200 rounded-full dark:bg-gray-700 w-60"></div>
                            <div class="h-3.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-40"></div>
                            <div class="h-3.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-full"></div>
                        </div>
                        <div class="flex items-center w-full">
                            <div class="h-3.5 bg-gray-200 rounded-full dark:bg-gray-700 w-full"></div>
                            <div class="h-3.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-full"></div>
                            <div class="h-3.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-full"></div>
                        </div>
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </template>

            <table v-else class="w-full text-xs text-left text-gray-200-">
                <thead class="text-xs uppercase -bg-[#0F1020]">
                <tr class="border-b border-gray-300">
                    <th scope="col" class="px-4 py-3 text-xs">S/N</th>
                    <th scope="col" class="px-4 py-3 text-xs">Open Time</th>
                    <th scope="col" class="px-4 py-3">Types</th>
                    <th scope="col" class="px-4 py-3">Units</th>
                    <th scope="col" class="px-4 py-3">Instrument</th>
                    <th scope="col" class="px-4 py-3">Open Rate</th>
                    <th scope="col" class="px-4 py-3">Market Rate</th>
                    <th scope="col" class="px-4 py-3">PnL</th>
                    <th scope="col" class="px-4 py-3">Take/Profit</th>
                    <th scope="col" class="px-4 py-3">Stop/Loss</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                </tr>
                </thead>
                <tbody>

                <template v-if="trades.length > 0">
                    <template v-for="(trade, index) in trades"
                              :key="trade.id">
                        <tr class="border-b border-gray-300 hover:bg-gray-300 transition-colors">
                            <td class="px-4 py-3">{{ index + 1 }}</td>
                            <td class="px-4 py-3">{{ trade.open_at }}</td>
                            <td class="px-4 py-3">
                             <span :class="[
                               'px-2.5 py-0.5 rounded text-xs font-medium',
                               trade.trade_type === 'buy' ? 'bg-green-500/20 text-green-500' : 'bg-red-500/20 text-red-500'
                             ]">
                               {{ trade.trade_type }}
                             </span>
                            </td>
                            <td class="px-4 py-3 font-medium">{{ trade.qty }}</td>
                            <td class="px-4 py-3">{{ trade?.asset?.sy }}</td>
                            <td class="px-4 py-3 font-mono">{{ formatAmount(trade.opening_price) }}</td>
                            <td class="px-4 py-3 font-mono">{{ formatAmount(trade.closing_price) }}</td>
                            <td class="px-4 py-3 font-mono"
                                :class="trade.profit > 0 ? 'text-green-500' : 'text-red-500'">
                                {{ formatAmount(trade.profit) }}
                            </td>
                            <td class="px-4 py-3">{{ trade.take_profit }}</td>
                            <td class="px-4 py-3">{{ trade.stop_loss }}</td>


                            <td class="px-4 py-3">
                                <div v-if="trade.close_at">
                                    <button
                                        disabled
                                        class="px-3 py-1 text-xs font-medium text-white rounded ">
                                        Closed
                                    </button>
                                </div>
                                <div v-else>
                                    <button
                                        v-if="!loadingTrades[trade.id]"
                                        @click="closeTrade(trade.id)"
                                        class="px-3 py-1 text-xs font-medium text-white bg-red-500 rounded hover:bg-red-600 transition-colors">
                                        Close
                                    </button>
                                    <button
                                        v-else
                                        class="px-3 py-1 text-xs font-medium text-white bg-red-500 rounded opacity-75 cursor-not-allowed"
                                        disabled>
                                        <span class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                        Processing...
                                    </button>
                                </div>

                            </td>
                        </tr>

                    </template>
                </template>
                <tr v-else>
                    <td colspan="11" class="px-4 py-8 text-center text-gray-400">
                        No positions
                    </td>
                </tr>


                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    name: 'OrderBook',
    data() {
        return {
            trades: [],
            loadingTrades: {},
            loading: false,
            pollInterval: null,
            successSound: null,
            selectedAsset: null,
            tradeStatus: 'open'
        }
    },

    created() {
        this.successSound = new Audio("/sounds/success.mp3");
        this.setupEventListener();
    },

    props: {
        open_trades_url: {
            type: String,
            required: true
        },
        closed_trades_url: {
            type: String,
            required: true
        },
        back_url: {
            type: String,
            required: true
        }
    },

    computed: {
        currentTradesUrl() {
            return this.tradeStatus === 'open' ? this.open_trades_url : this.closed_trades_url;
        },
        backUrl() {
            return this.back_url;
        }
    },

    methods: {
        setTradeStatus(status) {
            this.tradeStatus = status;
            this.fetchTrades();
        },

        setupEventListener() {
            this.$splade.off('refresh-trades'); // Remove any existing listeners
            this.$splade.on('refresh-trades', () => this.fetchTrades());
        },

        async updateAsset() {
            console.log(this.selectedAsset);
            const response = await axios.post(`/api/trades/${this.selectedAsset?.id}/update`, {
                opening_price: this.selectedAsset?.opening_price,
            });

            this.selectedAsset = null;
        },

        async fetchTrades() {
            this.loading = true;
            const response = await axios.get(this.currentTradesUrl);
            this.trades = response.data;
            this.loading = false;
        },

        formatAmount(value) {
            // Implement your crypto_amt helper function here
            return value;
        },

        setSelected(value) {
            this.selectedAsset = value;
        },

        startPolling() {
            this.pollInterval = setInterval(() => {
                this.fetchTrades();
            }, 10000);
        },

        async closeTrade(tradeId) {
            // Set loading state for this specific trade
            this.$set(this.loadingTrades, tradeId, true);

            try {
                await axios.post(`/api/trades/${tradeId}/close`);
                // Play success sound
                this.successSound.play();
                // Refresh trades list
                this.fetchTrades();
            } catch (error) {
                console.error('Error closing trade:', error);
            } finally {
                // Reset loading state
                this.$set(this.loadingTrades, tradeId, false);
            }
        }
    },

    mounted() {
        this.$splade.on('refresh-trades', () => this.fetchTrades());
        this.fetchTrades().then(() => {
            if (this.trades.length > 0) {
                this.startPolling();
            }
        });
    },

    beforeUnmount() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }
}
</script>
