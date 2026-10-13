<template>
    <div class="w-full overflow-x-auto bg-[#0F1020]
scrollbar-thin scrollbar-thumb-[#1A1B2E] scrollbar-track-[#0F1020] hover:scrollbar-thumb-gray-500
">
        <table class="w-full text-xs text-left text-gray-200">
            <thead class="text-xs uppercase bg-[#0F1020]">
            <tr class="border-b border-gray-700">
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
                <tr v-for="(trade, index) in trades"
                    :key="trade.id"
                    class="border-b border-gray-700 hover:bg-[#1A1B2E] transition-colors">
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
                    </td>
                </tr>
            </template>
            <tr v-else>
                <td colspan="11" class="px-4 py-8 text-center text-gray-400">
                    No open positions
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import {toast} from "vue-sonner";

export default {
    data() {
        return {
            trades: [],
            loadingTrades: {},
            pollInterval: null,
            successSound: null,

        }
    },

    created() {
        this.successSound = new Audio("/sounds/success.mp3");

        this.setupEventListener();
    },

    methods: {

        setupEventListener() {
            this.$splade.off('refresh-trades'); // Remove any existing listeners
            this.$splade.on('refresh-trades', () => this.fetchTrades());
        },

        async fetchTrades() {
            const response = await axios.get('/api/trades')
            this.trades = response.data
            if(response.data?.length > 0){
                const res =  await axios.get('/api/trades/run')
                this.trades = res.data
            }
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
        formatAmount(value) {
            // Implement your crypto_amt helper function here
            return value
        },
        startPolling() {
            this.pollInterval = setInterval(() => {
                this.fetchTrades()
            }, 5000)
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
            clearInterval(this.pollInterval)
        }
    }
}
</script>
