<template>
    <div class="w-full overflow-x-auto
scrollbar-thin scrollbar-thumb-[#1A1B2E] scrollbar-track-[#0F1020] hover:scrollbar-thumb-gray-500
">
        <template v-if="trades.length < 1 && loading">
            <div class="flex py-2 px-3 bg-white rounded">
                <div role="status" class="space-y-2.5 animate-pulse w-full">
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
                <th scope="col" class="px-4 py-3 text-xs">Account</th>
                <th scope="col" class="px-4 py-3 text-xs">Open Time</th>
                <th scope="col" class="px-4 py-3">Types</th>
                <th scope="col" class="px-4 py-3">Volume</th>
                <th scope="col" class="px-4 py-3">Leverage</th>
                <th scope="col" class="px-4 py-3">Margin</th>
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

            <template v-if="displayTrades.length > 0">
                <template v-for="(trade, index) in displayTrades"
                          :key="trade.id">
                    <tr class="border-b border-gray-300 hover:bg-gray-300 transition-colors">
                        <td class="px-4 py-3">{{ index + 1 }}</td>
                        <td class="px-4 py-3 font-mono">{{ trade.trading_account?.account_id ?? '—' }}</td>
                        <td class="px-4 py-3">{{ trade.opened_at }}</td>
                        <td class="px-4 py-3">
             <span :class="[
               'px-2.5 py-0.5 rounded text-xs font-medium',
               trade.trade_type === 'buy' ? 'bg-green-500/20 text-green-500' : 'bg-red-500/20 text-red-500'
             ]">
               {{ trade.trade_type }}
             </span>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ formatAmount(trade.volume_lots) }}</td>
                        <td class="px-4 py-3 font-medium">{{ formatAmount(trade.leverage) }}</td>
                        <td class="px-4 py-3 font-medium">{{ formatAmount(trade.margin) }}</td>
                        <td class="px-4 py-3">{{ trade?.asset?.sy }}</td>
                        <td class="px-4 py-3 font-mono">{{ formatPrice(trade.opening_price, trade.asset) }}</td>
                        <td class="px-4 py-3 font-mono">{{ formatPrice(trade.closing_price, trade.asset) }}</td>
                        <td class="px-4 py-3 font-mono"
                            :class="trade.profit > 0 ? 'text-green-500' : 'text-red-500'">
                            {{ formatAmount(trade.profit) }}
                        </td>
                        <td class="px-4 py-3">{{ trade.take_profit > 0 ? formatPrice(trade.take_profit, trade.asset) : '-' }}</td>
                        <td class="px-4 py-3">{{ trade.stop_loss > 0 ? formatPrice(trade.stop_loss, trade.asset) : '-' }}</td>
                        <td class="px-4 py-3">
                            <button
                                v-if="!read_only"
                                @click="setSelected(trade)"
                                class="px-3 py-1 text-xs font-medium text-white bg-yellow-500 rounded hover:bg-red-600 transition-colors">
                                Edit
                            </button>

                        </td>

                        <td class="px-4 py-3">
                            <button
                                v-if="!read_only && !loadingTrades[trade.id]"
                                @click="closeTrade(trade.id)"
                                class="px-3 py-1 text-xs font-medium text-white bg-red-500 rounded hover:bg-red-600 transition-colors">
                                Close
                            </button>
                            <button
                                v-else-if="!read_only"
                                class="px-3 py-1 text-xs font-medium text-white bg-red-500 rounded opacity-75 cursor-not-allowed"
                                disabled>
                                <span class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                Processing...
                            </button>
                        </td>
                    </tr>
                    <tr v-if="selectedAsset && selectedAsset?.id === trade.id" class="border-b border-gray-300 hover:bg-gray-300 transition-colors">
                        <td class="px-4 py-3">{{ index + 1 }}</td>
                        <td class="px-4 py-3 font-mono">{{ selectedAsset.trading_account?.account_id ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <input
                                type="datetime-local"
                                v-model="selectedAsset.opened_at"
                                class="w-full p-2 border rounded"
                            />
                        </td>
                        <td class="px-4 py-3">

                        </td>
                        <td class="px-4 py-3 font-medium">
<!--                            {{ trade.qty }}-->
                        </td>
                        <td class="px-4 py-3">{{ selectedAsset?.asset?.sy }}</td>
                        <td class="px-4 py-3 font-mono">
                            <input
                                type="number"
                                v-model.number="selectedAsset.opening_price"
                                class="w-25 p-2 border rounded"
                                step="any"
                            />
                        </td>
                        <td class="px-4 py-3 font-mono">{{ formatPrice(selectedAsset.closing_price, selectedAsset.asset) }}</td>

                        <td class="px-4 py-3 font-mono">
                           pnl :  {{ formatAmount(((selectedAsset.amount / selectedAsset.opening_price) * selectedAsset.closing_price) - selectedAsset.amount)  }}

                            <input
                                type="number"
                                v-model.number="selectedAsset.profit_lost"
                                class="w-25 p-2 border rounded"
                                step="0.01"
                            />
                        </td>

                        <td class="px-4 py-3">{{ selectedAsset.take_profit }}</td>
                        <td class="px-4 py-3">{{ selectedAsset.stop_loss }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-4">
                                <button
                                    @click.prevent="updateAsset"
                                    class="px-3 py-1 text-xs font-medium text-white bg-green-500 rounded hover:bg-red-600 transition-colors">
                                    Update
                                </button>
                                <button
                                    @click="selectedAsset = null"
                                    class="px-3 py-1 text-xs font-medium text-white bg-red-500 rounded hover:bg-red-600 transition-colors">
                                    Close
                                </button>
                            </div>

                        </td>
                    </tr>

                </template>
            </template>
            <tr v-else>
                <td colspan="12" class="px-4 py-8 text-center text-gray-400">
                    No open positions
                </td>
            </tr>


            </tbody>
        </table>
    </div>
</template>
<script>
import {toast} from "vue-sonner";
import {connectPriceFeed, isPriceEntryFresh, livePnlFor} from "../livePriceFeed";

export default {
    name : 'AdminOpenTrades',
    data() {
        return {
            trades: [],
            loadingTrades: {},
            loading: false,
            pollInterval: null,
            successSound: null,
            selectedAsset:null,
            livePrices: {},
            disconnectFeed: null,
        }
    },

    created() {
        this.successSound = new Audio("/sounds/notification.mp3");

        this.setupEventListener();
    },

    props: {
        read_only: { type: Boolean, default: false },
        trades_url: String,
        feed_url: String,
        feed_key: String,
        user_id: String,
        // close_trade_url: String
    },

    computed: {
        /**
         * Server rows re-priced against the newest tick from the same feed the
         * trade room uses. The ten-second poll below is still what owns the
         * row set and the authoritative numbers -- this only fills the gap
         * between polls, which is where the back office and the trade room
         * used to visibly disagree on the same open position.
         *
         * A live entry only gets used while it's actually fresh
         * (isPriceEntryFresh) -- a connection blip just means this falls back
         * to the poll's own number for a beat, not a stale one held past its
         * useful life.
         */
        displayTrades() {
            return this.trades.map((trade) => {
                const liveEntry = this.livePrices[trade?.asset?.coin_id]

                if (!isPriceEntryFresh(liveEntry)) {
                    return trade
                }

                const live = livePnlFor(trade, liveEntry.price)

                return live ? {...trade, ...live} : trade
            })
        },
    },

    watch: {
        // The "PNL Balance" figure above this table (OpenPnlBalance.vue) used
        // to be server-rendered once and never update again, so it drifted
        // away from this table's own live numbers the longer the page sat
        // open -- QA caught open positions summing to more than the header
        // showed. Matching poll intervals wouldn't actually fix that (two
        // independent round trips still don't land in the same instant);
        // emitting this table's own total instead means the header is never
        // computed separately at all, so it can't disagree with what's below
        // it.
        displayTrades: {
            immediate: true,
            deep: true,
            handler(trades) {
                const total = trades.reduce((sum, trade) => sum + (Number(trade.profit) || 0), 0)

                this.$splade.emit('open-pnl-updated', total)
            },
        },
    },

    methods: {

        setupEventListener() {
            this.$splade.off('refresh-trades'); // Remove any existing listeners
            this.$splade.on('refresh-trades', () => this.fetchTrades());
        },

        async updateAsset() {
            console.log(this.selectedAsset)
            const response = await axios.post(`/api/trades/${this.selectedAsset?.id}/update`, {
                opened_at: this.selectedAsset?.opened_at,
                opening_price: this.selectedAsset?.opening_price,
                profit: this.selectedAsset?.profit_lost,
            })

            this.selectedAsset = null

        },

        async fetchTrades() {
            this.loading = true
            const response = await axios.get(this.trades_url)
            this.trades = response.data
            this.loading = false
            // if(response.data?.length > 0){
            //     const res =  await axios.get('/api/trades/run')
            //     this.trades = res.data
            // }
        },

        async closeTrade(id) {
            this.loadingTrades[id] = true
            try {
                const  response = await axios.post(`/api/trades/${id}/close`)
                toast.success(response?.data?.message);

                this.successSound.play().catch(e => console.warn('Audio playback failed:', e));

                this.$splade.emit('refresh-trades');


                await this.fetchTrades()
            } finally {
                this.loadingTrades[id] = false
            }
        },
        // Rounds to 2 decimal places for display -- PnL in particular
        // carries JS floating-point tails (102.50000000000537) off of
        // subtraction/multiplication in livePnlFor(); this is display-only,
        // the underlying trade record keeps its real precision. Only for
        // dollar amounts (margin, leverage, profit) -- prices use
        // formatPrice() below, mirrors Asset::displayPrecision() on the
        // backend, since a flat 2dp here was truncating forex prices like
        // 1.08450 down to 1.08.
        formatAmount(value) {
            const num = Number(value)

            if (!Number.isFinite(num)) {
                return value
            }

            return num.toFixed(2)
        },
        pricePrecision(asset) {
            if (!asset) return 5
            if (asset.sym === 'XAU') return 2
            if (asset.sym === 'XAG') return 3
            if (asset.sym === 'NATGAS') return 3
            if (asset.type === 'forex') return asset.base === 'JPY' ? 3 : 5
            if (asset.type === 'crypto') return Number(asset.rate) >= 100 ? 2 : 4
            if (['stocks', 'indices', 'commodities', 'metals'].includes(asset.type)) return 2
            return 5
        },
        formatPrice(value, asset) {
            const num = Number(value)

            if (!Number.isFinite(num)) {
                return value
            }

            return num.toFixed(this.pricePrecision(asset))
        },
        setSelected(value) {
            // Implement your crypto_amt helper function here
            this.selectedAsset = value
        },
        startPolling() {
            if (this.pollInterval) {
                return
            }

            this.pollInterval = setInterval(() => {
                this.fetchTrades()
            }, 10000)
        },

        startPriceFeed() {
            if (this.disconnectFeed || !this.feed_url) {
                return
            }

            this.disconnectFeed = connectPriceFeed({
                url: this.feed_url,
                apiKey: this.feed_key,
                onPrices: (prices) => {
                    this.livePrices = {...this.livePrices, ...prices}
                },
            })
        },

        // Pushes a fetch the moment this client's trade actually opens/
        // closes/updates, instead of waiting on the next 10s poll tick --
        // the poll stays on as a fallback for missed/dropped events.
        startLiveUpdates() {
            if (!this.user_id || !window.Echo) {
                return
            }

            window.Echo.private(`admin.trade.${this.user_id}`)
                .listen('.trade.updated', () => this.fetchTrades())
        },
    },
    mounted() {

        this.$splade.on('refresh-trades', () => this.fetchTrades());

        // Both unconditionally: polling used to start only if the very first
        // fetch happened to return trades, so a position opened after the
        // admin loaded the page never appeared at all.
        this.startPolling();
        this.startPriceFeed();
        this.startLiveUpdates();

        this.fetchTrades();
    },
    beforeUnmount() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval)
        }

        if (this.disconnectFeed) {
            this.disconnectFeed()
            this.disconnectFeed = null
        }

        if (this.user_id && window.Echo) {
            window.Echo.leave(`admin.trade.${this.user_id}`)
        }
    }
}
</script>
