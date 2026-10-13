<template>
    <div>

        <div class="flex justify-between align-items-center py-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Open Positions
            </h2>

            <button
                type="button"
                @click="formOpen = !formOpen"
                :class="formOpen ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'"
                class="text-white px-4 py-2 rounded"
            >
                {{ formOpen ? 'Close Form' : 'Open Trade'}}
            </button>

        </div>

        <div v-if="formOpen" class="trade-form space-y-6 bg-white rounded border border-gray-100 py-3 px-3">
            <!-- Margin Information -->
            <div class="grid sm:grid-cols-4 grid-cols-2 gap-4">
                <div class="border p-3 rounded">
                    <span class="text-gray-600">Required Margin</span>
                    <div class="font-bold">{{ requiredMargin }}</div>
                </div>
                <div class="border p-3 rounded">
                    <span class="text-gray-600">Available Margin</span>
                    <div class="font-bold">{{ availableMargin }}</div>
                </div>

                <div class="border p-3 rounded">
                    <span class="text-gray-600">Asset Rate</span>
                    <div class="font-bold">{{ buyPrice }}</div>
                </div>

                <div class="border p-3 rounded">
                    <span class="text-gray-600">Leverage</span>
                    <div class="font-bold">{{ leverage }}</div>
                </div>
            </div>

            <!-- Selected Asset Details -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <!-- Asset Search Field -->
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asset</label>
                    <input
                        type="text"
                        v-model="searchQuery"
                        @input="searchAssets"
                        @focus="showResults = true"
                        placeholder="Enter asset name or symbol"
                        class="w-full p-2 border rounded"
                    />

                    <!-- Search Results Dropdown -->
                    <div
                        v-if="showResults && searchResults.length"
                        class="absolute z-10 w-full mt-1 bg-white border rounded shadow-lg"
                    >
                        <div
                            v-for="asset in searchResults"
                            :key="asset.id"
                            @click="selectAsset(asset)"
                            class="p-2 hover:bg-gray-100 cursor-pointer"
                        >
                            {{ asset.symbol }} - {{ asset.name }}
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qty</label>
                    <input
                        type="number"
                        v-model.number="quantity"
                        class="w-full p-2 border rounded"
                        min="1"
                        step="0.01"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Direction</label>
                    <select
                        v-model="direction"
                        class="w-full p-2 border rounded"
                    >
                        <option value="buy">Buy</option>
                        <option value="sell">Sell</option>
                    </select>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-4">
                <button
                    @click="submitForm"
                    class="py-3 px-6 text-white rounded-lg font-semibold transition-colors"
                    :class="direction === 'buy' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600'"
                    :disabled="is_loading"
                >
                    <svg v-if="is_loading" aria-hidden="true" role="status" class="inline w-5 h-5 me-3 text-white animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="#E5E7EB"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentColor"/>
                    </svg>
                    <span v-if="is_loading">Loading...</span>
                    <span v-else>{{ direction.toUpperCase() }}</span>
                </button>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="text-red-500 mt-2">{{ error }}</div>
        </div>

    </div>
</template>

<script>
import axios from 'axios';
import {toast} from "vue-sonner";

/**
 * Opens a trade on ONE specific challenge's own trading account --
 * deliberately its own component rather than reusing AdminOpenTrade.vue,
 * because that one posts to /api/admin/trades, which resolves "the"
 * trading account via ResolveTradingAccount middleware off the
 * *authenticated admin*, not the client being traded for. That's fine
 * for a client with a single account but silently breaks (null
 * trading_account_id, or an outright rejection) for any prop firm
 * client, who always has 2+ accounts (their own + each challenge).
 * This component posts to a challenge-scoped endpoint instead, which
 * binds the correct trading account explicitly server-side -- no
 * ambiguity possible.
 */
export default {
    name: 'ChallengeOpenTrade',

    data() {
        return {
            searchQuery: '',
            searchResults: [],
            selectedAsset: null,
            showResults: false,
            formOpen: false,
            debounceTimeout: null,
            loading: false,
            is_loading: false,
            error: null,

            requiredMargin: 0,
            availableMargin: 0,
            leverage: 1,
            successSound: null,

            availableMarginValue: 0,
            buyPrice: 0,
            quantity: 1,
            direction: 'buy',
        }
    },

    props: {
        tradeDataUrl: String,
        storeUrl: String,
    },

    computed: {
        canSubmit() {
            return this.selectedAsset &&
                this.quantity > 0 &&
                this.direction &&
                this.requiredMargin <= this.availableMarginValue;
        }
    },

    methods: {

        async searchAssets() {
            if (this.debounceTimeout) {
                clearTimeout(this.debounceTimeout);
            }

            if (!this.searchQuery.trim()) {
                this.searchResults = [];
                return;
            }

            this.debounceTimeout = setTimeout(async () => {
                try {
                    this.loading = true;
                    const response = await axios.get('/api/assets/search', {
                        params: { query: this.searchQuery }
                    });

                    this.searchResults = response.data;
                    this.error = null;
                } catch (error) {
                    console.error('Error searching assets:', error);
                    this.error = 'Failed to search assets. Please try again.';
                    this.searchResults = [];
                } finally {
                    this.loading = false;
                }
            }, 300);
        },

        selectAsset(asset) {
            this.selectedAsset = asset;
            this.searchQuery = asset.sym;
            this.showResults = false;
            this.leverage = asset.leverage ?? 1;
            this.buyPrice = asset.rate;
            this.calculateRequiredMargin();
        },

        calculateRequiredMargin() {
            if (this.selectedAsset && this.quantity) {
                this.requiredMargin = (this.buyPrice * this.quantity) / this.leverage;
            }
        },

        async submitForm() {

            if (!this.canSubmit) {
                if (!this.selectedAsset) {
                    toast.error('Please select an asset')
                    return;
                }
                toast.error('Open price is more than available Margin')
                return;
            }

            try {
                this.is_loading = true;
                const response = await axios.post(this.storeUrl, {
                    id: this.selectedAsset.id,
                    volume: this.quantity,
                    type: this.direction,
                    leverage: this.leverage,
                    margin: this.requiredMargin,

                    is_take_profit: false,
                    take_profit: null,
                    is_stop_loss: false,
                    stop_loss: null,
                });

                toast.success(response?.data?.message);

                this.successSound.play().catch(e => console.warn('Audio playback failed:', e));

                // The trades table below is a plain Splade table, not one of
                // the client-side trading components that listen for the
                // 'refresh-trades' bus event -- reload so it (and the
                // margin/balance figures elsewhere on the page) reflect the
                // new trade immediately, rather than looking like it "didn't
                // show up" until a manual refresh.
                window.location.reload();

            } catch (error) {
                console.error('Error submitting trade:', error);
                toast.error(error?.response?.data?.message || 'Failed to submit trade. Please try again.');
                this.error = 'Failed to submit trade. Please try again.';
            } finally {
                this.is_loading = false;
            }
        },

        clearForm() {
            this.searchQuery = '';
            this.selectedAsset = null;
            this.searchResults = [];
            this.error = null;
            this.quantity = 1;
            this.direction = 'buy';
            this.leverage = 1;
            this.buyPrice = 0;
            this.requiredMargin = 0;
        },

        closeDropdown(e) {
            if (!this.$el.contains(e.target)) {
                this.showResults = false;
            }
        },

        async getData() {
            try {
                const { data } = await axios.get(this.tradeDataUrl);
                this.availableMargin = data?.data.marginLevel;
                this.availableMarginValue = data?.data.marginLevelValue;
            } catch (error) {
                console.error("Error fetching trade data:", error);
            }
        },
    },

    watch: {
        quantity() {
            this.calculateRequiredMargin();
        },
        leverage() {
            this.calculateRequiredMargin();
        }
    },

    mounted() {
        this.successSound = new Audio("/sounds/success.mp3");
        this.getData();
        document.addEventListener('click', this.closeDropdown);
    },

    beforeDestroy() {
        document.removeEventListener('click', this.closeDropdown);
    }
}
</script>
