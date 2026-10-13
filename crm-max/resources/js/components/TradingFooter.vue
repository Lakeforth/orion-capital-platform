<template>
    <div>
        <!-- Open trades panel that appears directly above the footer when toggled -->
        <div
            v-if="showPositions"
            class="fixed left-0 right-0 bg-[#242636] border-t border-gray-800 z-10 max-h-[50vh] overflow-auto
            scrollbar-thin scrollbar-thumb-[#1A1B2E] scrollbar-track-[#0F1020] hover:scrollbar-thumb-gray-500
            transition-all duration-300 ease-in-out"
            style="bottom: 212px;"
        >
            <open_trades />
        </div>

        <!-- Full Trading Panel -->
        <div
            v-if="showTradePanel"
            class="fixed bottom-[212px] left-0 right-0 bg-[#0F1020] border-t border-gray-800 z-30
            overflow-auto max-h-[70vh] p-4 rounded-t-lg shadow-lg
            scrollbar-thin scrollbar-thumb-[#1A1B2E] scrollbar-track-[#0F1020] hover:scrollbar-thumb-gray-500
            transition-all duration-300 ease-in-out"
        >
            <!-- Close button -->
            <div class="flex justify-end mb-4">
                <button @click="showTradePanel = false" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Buy/Sell Price Display with dynamic pricing -->
            <div class="flex mb-4">
                <button
                    @click="setTradeDirection('buy')"
                    class="flex-1 py-3 text-center text-white transition-colors"
                    :class="tradeDirection === 'buy' ? 'bg-green-500' : 'bg-green-500/20'"
                >
                    <div class="text-xs font-medium">BUY</div>
                    <div class="font-semibold">${{ formatPrice(asset?.buy_price) }}</div>
                </button>
                <button
                    @click="setTradeDirection('sell')"
                    class="flex-1 py-3 text-center text-white transition-colors"
                    :class="tradeDirection === 'sell' ? 'bg-red-500' : 'bg-red-500/20'"
                >
                    <div class="text-xs font-medium">SELL</div>
                    <div class="font-semibold">${{ formatPrice(asset?.sell_price) }}</div>
                </button>
            </div>

            <!-- Asset Information Display -->
            <div v-if="asset" class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-full bg-yellow-600 flex items-center justify-center">
                    {{ asset.sym?.substring(0, 2) }}
                </div>
                <div class="flex-1">
                    <div class="font-medium">{{ asset.sym }}</div>
                    <div class="text-sm text-gray-400">{{ asset.type }}</div>
                </div>
                <button
                    @click="toggleFavorite"
                    class="text-gray-400 hover:text-white transition-colors"
                    :class="{ 'text-yellow-500': isFavorite }"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </button>
            </div>

            <!-- Units Control with validation -->
            <div class="mb-6">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-400">UNITS</span>
                    <div class="flex gap-2">
                        <button
                            @click="decrementUnits"
                            class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors text-white"
                            :disabled="units <= 0.1"
                        >
                            -
                        </button>
                        <input
                            v-model.number="units"
                            type="number"
                            class="w-16 text-center bg-gray-700 rounded px-2 text-white"
                            step="0.1"
                            min="0.1"
                            @input="validateUnits"
                        >
                        <button
                            @click="incrementUnits"
                            class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors text-white"
                        >
                            +
                        </button>
                    </div>
                </div>
            </div>

            <!-- Take profit Toggle and Input -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-400">Take profit</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="takeProfitEnabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <div v-if="takeProfitEnabled" class="flex gap-2">
                    <input
                        v-model.number="takeProfit"
                        type="number"
                        class="w-full text-center bg-gray-700 rounded px-2 py-1 text-white"
                        min="0"
                        step="0.01"
                        placeholder="Enter take profit value"
                    >
                    <button
                        @click="decrementTakeProfit"
                        class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors text-white"
                    >
                        -
                    </button>
                    <button
                        @click="incrementTakeProfit"
                        class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors text-white"
                    >
                        +
                    </button>
                </div>
            </div>

            <!-- Stop Loss Toggle and Input -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-400">Stop loss</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="stopLossEnabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <div v-if="stopLossEnabled" class="flex gap-2">
                    <input
                        v-model.number="stopLoss"
                        type="number"
                        class="w-full text-center bg-gray-700 rounded px-2 py-1 text-white"
                        min="0"
                        step="0.01"
                        placeholder="Enter stop loss value"
                    >
                    <button
                        @click="decrementStopLoss"
                        class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors text-white"
                    >
                        -
                    </button>
                    <button
                        @click="incrementStopLoss"
                        class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors text-white"
                    >
                        +
                    </button>
                </div>
            </div>

            <!-- Trade Information Display -->
            <div class="space-y-4 mb-6" v-if="asset">
                <div>
                    <div class="text-sm text-gray-400">Amount</div>
                    <div class="text-lg font-medium">
                        ${{ calculateAmount }} USD
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-400">Leverage</div>
                    <div>1:{{ asset.leverage }}</div>
                </div>
            </div>

            <div v-if="aiTradeModuleEnabled" class="mb-6 rounded-lg border border-gray-800 bg-[#17192B] p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-white">AI Trading</div>
                        <div class="text-xs text-gray-400">
                            {{ userAiTrade ? 'Manual trading is disabled while AI Trading is on.' : 'Manual trading is available.' }}
                        </div>
                    </div>
                    <button
                        @click="toggleAiTrade"
                        type="button"
                        class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                        :class="userAiTrade ? 'bg-amber-500 text-black' : 'bg-gray-700 text-white hover:bg-gray-600'"
                        :disabled="isTogglingAiTrade"
                    >
                        {{ isTogglingAiTrade ? 'Updating...' : (userAiTrade ? 'On' : 'Off') }}
                    </button>
                </div>

                <p v-if="userAiTrade" class="mt-3 text-xs text-amber-300">
                    {{ aiTradeDisabledMessage }}
                </p>
            </div>

            <!-- Execute Trade Button -->
            <button
                @click="executeTrade"
                class="w-full py-3 rounded-lg font-semibold transition-colors text-white"
                :class="tradeDirection === 'buy' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600'"
                :disabled="!canTrade || is_loading || userAiTrade"
            >
                <svg v-if="is_loading" aria-hidden="true" role="status" class="inline w-5 h-5 me-3 text-white animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="#E5E7EB"/>
                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentColor"/>
                </svg>
                <span v-if="is_loading">Loading...</span>
                <span v-else>{{ tradeDirection.toUpperCase() }}</span>
            </button>
        </div>

        <!-- Sticky footer -->
        <div class="sticky-footer fixed bottom-0 left-0 right-0 bg-[#141527] text-white border-t border-gray-800 z-20">
            <!-- Portfolio header with toggle -->
            <div class="flex items-center justify-between px-4 py-2 bg-[#1A1B2E]">
                <div class="flex items-center">
                    <Link href="/dashboard/trades" class="text-sm text-primary font-medium">Order Book</Link>
                </div>
                <button @click="togglePositions" class="flex items-center">
                    <span class="text-sm text-gray-400">Show Positions</span>
                    <svg
                        class="w-4 h-4 ml-1 text-gray-400 transition-transform duration-200"
                        :class="{'rotate-180': showPositions}"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>

            <!-- Asset info -->
            <div class="flex items-center px-4 py-2 bg-[#1A1B2E] border-t border-gray-800">
                <div class="flex items-center">
                    <!-- Medal icon for bronze -->
                    <div class="w-7 h-7 rounded-full bg-[#242636] flex items-center justify-center mr-2">
                        <svg class="w-4 h-4 text-amber-700" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-amber-700 font-bold">BRONZE</div>
                        <div class="flex items-center text-xs text-gray-400">
                            <span>Leverage: 1:{{ asset?.leverage || '100' }}</span>
                            <span class="mx-3">|</span>
                            <span>AI Trading:</span>
                            <button
                                @click="toggleAiTrade"
                                type="button"
                                class="ml-1 rounded-full px-2 py-0.5 text-[10px] font-semibold transition-colors"
                                :class="userAiTrade ? 'bg-amber-500 text-black' : 'bg-gray-700 text-white hover:bg-gray-600'"
                                :disabled="isTogglingAiTrade || !aiTradeModuleEnabled"
                            >
                                {{ isTogglingAiTrade ? '...' : (userAiTrade ? 'On' : 'Off') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected asset -->
            <div class="flex items-center justify-between px-4 py-2 bg-[#1A1B2E] border-t border-gray-800">
                <div class="flex items-center">
                    <!-- Asset label -->
                    <div class="w-7 h-7 rounded-md bg-[#242636] flex items-center justify-center mr-2 text-xs font-bold">
                        {{ asset?.sym?.substring(0, 2) || 'NI' }}
                    </div>
                    <div>
                        <div class="text-sm">{{ asset?.sym || 'NRG Energy' }}</div>
                        <div class="text-xs text-gray-400">{{ asset?.type || 'Stocks' }}</div>
                    </div>
                </div>

                <!-- Buy/Sell buttons - now open trade panel -->
                <div class="flex items-center space-x-2">
                    <button
                        @click="openBuyPanel"
                        class="px-4 py-1 rounded text-white text-sm font-medium"
                        :class="tradeDirection === 'buy' ? 'bg-green-600' : 'bg-green-600/40'"
                    >
                        BUY
                    </button>
                    <button
                        @click="openSellPanel"
                        class="px-4 py-1 rounded text-white text-sm font-medium"
                        :class="tradeDirection === 'sell' ? 'bg-red-600' : 'bg-red-600/40'"
                    >
                        SELL
                    </button>
                </div>
            </div>

            <!-- Stats grid -->
            <div class="grid grid-cols-5 divide-x divide-gray-800 text-center py-1 bg-[#1A1B2E] border-t border-gray-800">
                <div class="px-2">
                    <div class="text-xs text-gray-400">P/L:</div>
                    <div class="text-sm">{{ formatNumber(pnl) }}</div>
                </div>
                <div class="px-2">
                    <div class="text-xs text-gray-400">EQUITY:</div>
                    <div class="text-sm">{{ formatNumber(balance) }}</div>
                </div>
                <div class="px-2">
                    <div class="text-xs text-gray-400">MARGIN:</div>
                    <div class="text-sm">{{ formatNumber(margin) }}</div>
                </div>
                <div class="px-2">
                    <div class="text-xs text-gray-400">FREE MARGIN:</div>
                    <div class="text-sm">{{ formatNumber(freeMargin) }}</div>
                </div>
                <div class="px-2">
                    <div class="text-xs text-gray-400">MARGIN LEVEL:</div>
                    <div class="text-sm">{{ formatNumber(marginLevel) }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import { toast } from "vue-sonner";

export default {
    name: "StickyFooter",
    props: {
        asset: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            // Footer state
            margin: 0,
            freeMargin: 0,
            marginLevel: 0,
            pnl: 0,
            balance: 0,
            showPositions: false,
            has_account: false,
            showTradePanel: false,
            tradeDirection: 'buy',

            // Trading panel state
            units: 0.1,
            is_loading: false,
            isFavorite: false,
            takeProfitEnabled: false,
            stopLossEnabled: false,
            takeProfit: null,
            stopLoss: null,
            successSound: null,
            userAiTrade: false,
            aiTradeModuleEnabled: false,
            aiTradeDisabledMessage: 'please disable AI Trading',
            isTogglingAiTrade: false
        };
    },
    computed: {
        calculateAmount() {
            if (!this.asset) return '0.00';

            const leverage = this.asset?.leverage ?? 1;

            const price = this.tradeDirection === 'buy'
                ? this.asset.buy_price
                : this.asset.sell_price;

            const unit_price = (this.units * (price || 0)) / leverage;

            return (unit_price).toFixed(2);
        },

        canTrade() {
            return this.asset && this.units >= 0.1;
        }
    },
    methods: {
        formatNumber(value) {
            return value
            // // Avoid NaN by checking if value is a valid number
            // if (value === undefined || value === null || isNaN(value)) {
            //     return '0.00';
            // }
            // return parseFloat(value).toFixed(2);
        },
        formatPrice(price) {
            return (price || 0).toFixed(2);
        },
        togglePositions() {
            this.showPositions = !this.showPositions;
            if (this.showPositions && this.showTradePanel) {
                this.showTradePanel = false;
            }
        },
        openBuyPanel() {
            this.tradeDirection = 'buy';
            this.$emit('direction-change', 'buy');
            this.showTradePanel = true;
            if (this.showPositions) {
                this.showPositions = false;
            }
        },
        openSellPanel() {
            this.tradeDirection = 'sell';
            this.$emit('direction-change', 'sell');
            this.showTradePanel = true;
            if (this.showPositions) {
                this.showPositions = false;
            }
        },
        setTradeDirection(direction) {
            this.tradeDirection = direction;
            this.$emit('direction-change', direction);
        },
        async getData() {
            try {
                const { data } = await axios.get("/api/user/trade-data");
                // Use exactly the same assignment as in TradeNav
                this.margin = data?.data.margin;
                this.balance = data?.data.balance;

                this.marginLevel = data?.data.marginLevel;
                this.pnl = data?.data.pnl;
                this.has_account = data?.data.has_account;
                this.total_balance = data?.data.total_balance ?? 0;
                this.freeMargin = data?.data.freeMargin;
                this.userAiTrade = Boolean(data?.data?.ai_trade);
                this.aiTradeModuleEnabled = Boolean(data?.data?.ai_trade_module_enabled);
                this.aiTradeDisabledMessage = data?.data?.ai_trade_disabled_message || 'please disable AI Trading';



                if(data?.data?.marginValue > 0){
                    setTimeout(() => {
                        this.getData()
                    }, 5000);
                }


            } catch (error) {
                console.error("Error fetching trade data:", error);
            }
        },

        // Trading panel methods
        incrementUnits() {
            this.units = parseFloat((this.units + 0.1).toFixed(1));
        },
        decrementUnits() {
            if (this.units > 0.1) {
                this.units = parseFloat((this.units - 0.1).toFixed(1));
            }
        },
        validateUnits() {
            if (this.units < 0.1) {
                this.units = 0.1;
            }
        },
        toggleFavorite() {
            this.isFavorite = !this.isFavorite;
            this.$emit('toggle-favorite', {
                symbol: this.asset.sym,
                isFavorite: this.isFavorite
            });
        },
        incrementTakeProfit() {
            if (this.takeProfit === null) this.takeProfit = 0;
            this.takeProfit = parseFloat((this.takeProfit + 0.01).toFixed(2));
        },
        decrementTakeProfit() {
            if (this.takeProfit > 0) {
                this.takeProfit = parseFloat((this.takeProfit - 0.01).toFixed(2));
            }
        },
        incrementStopLoss() {
            if (this.stopLoss === null) this.stopLoss = 0;
            this.stopLoss = parseFloat((this.stopLoss + 0.01).toFixed(2));
        },
        decrementStopLoss() {
            if (this.stopLoss > 0) {
                this.stopLoss = parseFloat((this.stopLoss - 0.01).toFixed(2));
            }
        },
        resetTradingValues() {
            this.units = 0.1;
            this.takeProfitEnabled = false;
            this.stopLossEnabled = false;
            this.takeProfit = null;
            this.stopLoss = null;
        },
        async executeTrade() {
            if (!this.canTrade) return;
            if (this.userAiTrade) {
                toast.error(this.aiTradeDisabledMessage);
                return;
            }

            try {
                this.is_loading = true;
                const response = await axios.post('/api/trades', {
                    qty: this.units,
                    type: this.tradeDirection,
                    id: this.asset.id,
                    is_take_profit: this.takeProfitEnabled,
                    take_profit: this.takeProfitEnabled ? this.takeProfit : null,
                    is_stop_loss: this.stopLossEnabled,
                    stop_loss: this.stopLossEnabled ? this.stopLoss : null,
                    amount: this.calculateAmount
                });

                toast.success(response?.data?.message);
                this.$splade.emit('refresh-trades');
                this.is_loading = false;

                if (this.successSound) {
                    this.successSound.play().catch(e => console.warn('Audio playback failed:', e));
                }

                // Reset form and close panel
                this.resetTradingValues();
                this.showTradePanel = false;

                // Refresh data
                await this.getData();

            } catch (error) {
                let msg = error?.response?.data?.message ?? error?.response?.message;
                toast.error(msg);
                console.error('Trade execution failed:', error);
                this.is_loading = false;
            }
        },
        async toggleAiTrade() {
            try {
                this.isTogglingAiTrade = true;
                const { data } = await axios.post('/api/user/ai-trade/toggle');

                this.userAiTrade = Boolean(data?.data?.ai_trade);
                this.aiTradeModuleEnabled = Boolean(data?.data?.ai_trade_module_enabled ?? this.aiTradeModuleEnabled);
                this.aiTradeDisabledMessage = data?.data?.ai_trade_disabled_message || this.aiTradeDisabledMessage;

                toast.success(data?.message || 'AI Trading updated successfully');
            } catch (error) {
                const msg = error?.response?.data?.message ?? error?.response?.message ?? 'Failed to update AI Trading';
                toast.error(msg);
            } finally {
                this.isTogglingAiTrade = false;
            }
        }
    },
    mounted() {
        // Initialize success sound
        this.successSound = new Audio("/sounds/success.mp3");

        // Fetch data when the component is mounted
        this.getData();

        // this.$splade.on('refresh-trades', () => this.getData());

        // Add event listener for custom event
        // document.addEventListener("get-new-trades", this.getData);
    },
    beforeUnmount() {
        // Clean up event listener
        document.removeEventListener("refresh-trades", this.getData);
    },
    watch: {
        asset: {
            handler(newAsset) {
                if (newAsset) {
                    // Update any necessary data when the asset changes
                    this.resetTradingValues();
                }
            },
            deep: true
        }
    }
};
</script>

<style scoped>
/* Additional styling for smooth transitions */
.sticky-footer {
    height: 212px; /* Approximate height of the footer */
}
</style>
