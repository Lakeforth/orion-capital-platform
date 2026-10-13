<template>
    <div class="flex flex-col h-full bg-[#0F1020] rounded-md p-4">
        <!-- Buy/Sell Price Display with dynamic pricing -->
        <div class="flex mb-4">
            <button
                @click="tradeDirection = 'buy'"
                class="flex-1 py-3 text-center transition-colors"
                :class="tradeDirection === 'buy' ? bg_color : 'bg-green-500/20'"
            >
                <div class="text-xs font-medium">BUY</div>
                <div class="font-semibold">${{ formatPrice(asset?.buy_price) }}</div>
            </button>
            <button
                @click="tradeDirection = 'sell'"
                class="flex-1 py-3 text-center transition-colors"
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

        <!-- Trading Controls Section -->
        <div class="mb-6">
            <!-- Units Control with validation -->
            <div class="flex justify-between mb-2">
                <span class="text-gray-400">UNITS</span>
                <div class="flex gap-2">
                    <button
                        @click="decrementUnits"
                        class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors"
                        :disabled="units <= 0.1"
                    >
                        -
                    </button>
                    <input
                        v-model.number="units"
                        type="number"
                        class="w-22 text-center bg-gray-700 rounded px-2"
                        step="0.1"
                        @input="validateUnits"
                        @focus="userIsEditing = true"
                        @blur="userIsEditing = false"
                    >
                    <button
                        @click="incrementUnits"
                        class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors"
                    >
                        +
                    </button>
                </div>
            </div>
        </div>

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
                    class="w-full text-center bg-gray-700 rounded px-2 py-1"
                    min="0"
                    step="0.01"
                    placeholder="Enter take profit value"
                >
                <button
                    @click="decrementTakeProfit"
                    class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors"
                >
                    -
                </button>
                <button
                    @click="incrementTakeProfit"
                    class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors"
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
                    class="w-full text-center bg-gray-700 rounded px-2 py-1"
                    min="0"
                    step="0.01"
                    placeholder="Enter stop loss value"
                >
                <button
                    @click="decrementStopLoss"
                    class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors"
                >
                    -
                </button>
                <button
                    @click="incrementStopLoss"
                    class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors"
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
            class="w-full py-3 rounded-lg font-semibold transition-colors"
            :class="tradeDirection === 'buy' ? bg_color : 'bg-red-500 hover:bg-red-600'"
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
</template>

<script>
import {toast} from "vue-sonner";
import axios from "axios";

export default {
    props: {
        asset: {
            type: Object,
            default: null
        },
        bg_color : {
            type: String,
            default: 'bg-green-500'
        }
    },

    data() {
        return {
            tradeDirection: 'buy',
            units: 0.1,
            leverage: 2,
            is_loading: false,
            leverageMultiplier: 10,
            takeProfit: null,
            stopLoss: null,
            isFavorite: false,
            successSound: null,
            takeProfitEnabled: false,
            stopLossEnabled: false,
            marginLevel: 0,
            marginLevelValue: 0,
            userIsEditing: false, // Track whether user is actively editing
            initialLoad: true, // Track initial load to prevent unwanted resets
            userAiTrade: false,
            aiTradeModuleEnabled: false,
            aiTradeDisabledMessage: 'please disable AI Trading',
            isTogglingAiTrade: false
        }
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
        formatPrice(price) {
            return (price || 0).toFixed(2);
        },

        incrementUnits() {
            this.units = parseFloat((this.units + 0.1).toFixed(1));
        },

        decrementUnits() {
            if (this.units > 0.1) {
                this.units = parseFloat((this.units - 0.1).toFixed(1));
            }
        },

        validateUnits() {
            // Ensure a valid numeric value (fixes issues with partial input like "0.")
            // if (isNaN(this.units) || this.units === null) {
            //     this.units = 0.1;
            //     return;
            // }
            //
            // // Enforce minimum value
            // if (this.units < 0.1) {
            //     this.units = 0.1;
            // }

            // Format to one decimal place for consistency
            this.units = parseFloat(this.units.toFixed(1));
        },

        updateLeverageMultiplier() {
            this.leverageMultiplier = Math.round(this.leverage / 10) * 10;
        },

        toggleFavorite() {
            this.isFavorite = !this.isFavorite;
            this.$emit('toggle-favorite', {
                symbol: this.asset.sym,
                isFavorite: this.isFavorite
            });
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
                    volume: this.units,
                    leverage: this.leverage,
                    type: this.tradeDirection,
                    id: this.asset.id,
                    is_take_profit: this.takeProfitEnabled,
                    take_profit: this.takeProfitEnabled ? this.takeProfit : null,
                    is_stop_loss: this.stopLossEnabled,
                    stop_loss: this.stopLossEnabled ? this.stopLoss : null,
                    margin: this.calculateAmount
                });

                toast.success(response?.data?.message);
                this.$splade.emit('refresh-trades');
                this.is_loading = false;
                this.successSound.play().catch(e => console.warn('Audio playback failed:', e));

                // Reset form
                this.resetTradingValues();
                await this.getData();

            } catch (error) {
                let msg = error?.response?.data?.message ?? error?.response?.message;
                toast.error(msg);
                console.error('Trade execution failed:', error);
                this.is_loading = false;
            }
        },

        resetTradingValues() {
            // Don't reset units if user is actively editing
            if (!this.userIsEditing) {
                this.units = 0.1;
            }
            this.leverage = 2;
            this.takeProfitEnabled = false;
            this.stopLossEnabled = false;
            this.takeProfit = null;
            this.stopLoss = null;
            this.updateLeverageMultiplier();
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

        async getData() {
            try {
                const { data } = await axios.get("/api/user/trade-data");
                this.marginLevel = data?.data.marginLevel;
                this.marginLevelValue = data?.data.marginLevelValue;
                this.userAiTrade = Boolean(data?.data?.ai_trade);
                this.aiTradeModuleEnabled = Boolean(data?.data?.ai_trade_module_enabled);
                this.aiTradeDisabledMessage = data?.data?.ai_trade_disabled_message || 'please disable AI Trading';
            } catch (error) {
                console.error("Error fetching trade data:", error);
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
        },
    },

    created() {
        this.successSound = new Audio("/sounds/success.mp3");
        this.getData();
    },

    mounted() {
        // Set initialLoad to false after component is mounted
        this.$nextTick(() => {
            this.initialLoad = false;
        });
    },

    watch: {
        asset: {
            handler(newAsset, oldAsset) {
                // Only reset values when the asset actually changes (not just price updates)
                // and user is not currently editing
                if (
                    newAsset &&
                    (!oldAsset || newAsset.id !== oldAsset.id) &&
                    !this.userIsEditing &&
                    !this.initialLoad
                ) {
                    this.resetTradingValues();
                }
            },
            deep: true
        },

        tradeDirection(newDirection) {
            this.$emit('direction-change', newDirection);
        }
    }
}
</script>
