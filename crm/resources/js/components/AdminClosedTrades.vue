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
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </template>

        <table v-else class="w-full text-xs text-left text-gray-200-">
            <thead class="text-xs uppercase -bg-[#0F1020]">
            <tr class="border-b border-gray-300">
                <th scope="col" class="px-4 py-3 text-xs">ID</th>
                <th scope="col" class="px-4 py-3 text-xs">Open Time</th>
                <th scope="col" class="px-4 py-3 text-xs">Close Time</th>
                <th scope="col" class="px-4 py-3">Instrument</th>
                <th scope="col" class="px-4 py-3">Volume</th>
                <th scope="col" class="px-4 py-3">Direction</th>
                <th scope="col" class="px-4 py-3">Leverage</th>
                <th scope="col" class="px-4 py-3">Margin</th>
                <th scope="col" class="px-4 py-3">Open Rate</th>
                <th scope="col" class="px-4 py-3">Closed Rate</th>
                <th scope="col" class="px-4 py-3">PnL</th>
                <th scope="col" class="px-4 py-3">Take/Profit</th>
                <th scope="col" class="px-4 py-3">Stop/Loss</th>
                <th v-if="can_delete" scope="col" class="px-4 py-3">Actions</th>
            </tr>
            </thead>
            <tbody>
            <template v-if="trades.length > 0">
                <tr v-for="trade in trades" :key="trade.id"
                    class="border-b border-gray-300 hover:bg-gray-300 transition-colors">
                    <td class="px-4 py-3 font-mono">{{ trade.trade_id }}</td>
                    <td class="px-4 py-3">{{ trade.opened_at }}</td>
                    <td class="px-4 py-3">{{ trade.close_at }}</td>
                    <td class="px-4 py-3">{{ trade?.asset?.sy }}</td>
                    <td class="px-4 py-3 font-medium">{{ formatAmount(trade.volume_lots) }}</td>
                    <td class="px-4 py-3">
            <span :class="[
              'px-2.5 py-0.5 rounded text-xs font-medium',
              trade.trade_type === 'buy' ? 'bg-green-500/20 text-green-500' : 'bg-red-500/20 text-red-500'
            ]">
              {{ trade.trade_type }}
            </span>
                    </td>
                    <td class="px-4 py-3 font-medium">{{ formatAmount(trade.leverage) }}</td>
                    <td class="px-4 py-3 font-medium">{{ formatAmount(trade.margin) }}</td>
                    <td class="px-4 py-3 font-mono">{{ formatPrice(trade.opening_price, trade.asset) }}</td>
                    <td class="px-4 py-3 font-mono">{{ formatPrice(trade.closing_price, trade.asset) }}</td>
                    <td class="px-4 py-3 font-mono"
                        :class="trade.profit > 0 ? 'text-green-500' : 'text-red-500'">
                        {{ formatAmount(trade.profit) }}
                    </td>
                    <td class="px-4 py-3">{{ trade.take_profit > 0 ? formatPrice(trade.take_profit, trade.asset) : '-' }}</td>
                    <td class="px-4 py-3">{{ trade.stop_loss > 0 ? formatPrice(trade.stop_loss, trade.asset) : '-' }}</td>
                    <td v-if="can_delete" class="px-4 py-3">
                        <button
                            v-if="!deletingTrades[trade.id]"
                            @click="deleteTrade(trade.id)"
                            class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                        <button
                            v-else
                            class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded opacity-75 cursor-not-allowed"
                            disabled>
                            <span class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        </button>
                    </td>
                </tr>
            </template>
            <tr v-else>
                <td :colspan="can_delete ? 14 : 13" class="px-4 py-8 text-center text-gray-400">
                    No closed positions
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import {toast} from "vue-sonner";

export default {
    name: 'AdminClosedTrades',
    data() {
        return {
            trades: [],
            deletingTrades: {},
            loading: false,
            pollInterval: null,
        }
    },

    props: {
        trades_url: String,
        user_id: String,
        can_delete: Boolean,
    },

    created() {
        this.$splade.off('refresh-trades');
        this.$splade.on('refresh-trades', () => this.fetchTrades());
    },

    methods: {
        async fetchTrades() {
            this.loading = true
            const response = await axios.get(this.trades_url)
            this.trades = response.data
            this.loading = false
        },

        async deleteTrade(id) {
            if (!window.confirm('Are you sure you want to delete this trade?')) {
                return
            }

            this.deletingTrades[id] = true
            try {
                await axios.delete(`/panel/trades/${id}`)
                toast.success('Trade deleted successfully')
                this.$splade.emit('refresh-trades')
                await this.fetchTrades()
            } finally {
                this.deletingTrades[id] = false
            }
        },

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

        startPolling() {
            if (this.pollInterval) {
                return
            }

            this.pollInterval = setInterval(() => {
                this.fetchTrades()
            }, 10000)
        },

        // Same as AdminOpenTrades: pushes a fetch the moment a trade
        // closes/updates instead of waiting on the next poll tick. The
        // poll above stays on as a fallback for missed/dropped events.
        startLiveUpdates() {
            if (!this.user_id || !window.Echo) {
                return
            }

            window.Echo.private(`admin.trade.${this.user_id}`)
                .listen('.trade.updated', () => this.fetchTrades())
        },
    },

    mounted() {
        this.startPolling();
        this.startLiveUpdates();
        this.fetchTrades();
    },

    beforeUnmount() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval)
        }

        if (this.user_id && window.Echo) {
            window.Echo.leave(`admin.trade.${this.user_id}`)
        }
    },
}
</script>
