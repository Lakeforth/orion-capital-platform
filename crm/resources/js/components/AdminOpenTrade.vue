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
                class="flex-1-  text-white px-4 py-2 rounded
                "
            >
                {{ formOpen ? 'Close Form' : 'Open Trade'}}
            </button>

        </div>



        <div v-if="formOpen" class="trade-form space-y-6 bg-white rounded border border-gray-100 py-3 px-3">
            <!-- Account Picker (only shown when this client has more than one trading account -- with exactly one it's auto-selected and there's nothing to choose) -->
            <div v-if="trading_accounts.length > 1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Trading Account</label>
                <select v-model="selectedAccountId" class="w-full p-2 border rounded">
                    <option :value="null" disabled>Select which account to trade on…</option>
                    <option v-for="acc in trading_accounts" :key="acc.account_id" :value="acc.account_id">
                        {{ acc.label }}
                    </option>
                </select>
            </div>

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

            <!-- Quantity and Direction -->
            <div class="grid grid-cols-2 gap-4">

            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-4">
                <button
                    @click="submitForm"
                    class="w-full- py-3 px-6 text-white rounded-lg font-semibold transition-colors"
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

export default {
    name: 'AdminOpenTrade',

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

            // New fields from the image
            requiredMargin: 0,
            availableMargin: 0,
            leverage : 1,
            successSound: null,

            availableMarginValue: 0,
            buyPrice: 0,
            quantity: 1,
            direction: 'buy',
            selectedAccountId: null,
        }
    },

    props: {
        user_data_url: String,
        user_id: String,
        user_leverage: Number,
        trading_accounts: {
            type: Array,
            default: () => [],
        },
    },

    computed: {
        canSubmit() {
            if (this.trading_accounts.length > 1 && !this.selectedAccountId) {
                return false;
            }

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
            console.log(asset?.leverage)
            console.log('user_leverage',this.user_leverage)
            this.selectedAsset = asset;
            this.searchQuery = asset.sym;
            this.showResults = false;
            this.leverage = asset.leverage ?? this.user_leverage;
            this.buyPrice = asset.rate;
            this.calculateRequiredMargin();
        },

        calculateRequiredMargin() {
            if (this.selectedAsset && this.quantity) {
                this.requiredMargin = (this.buyPrice * this.quantity) / this.leverage;
            }
        },

        async submitForm() {

            if (this.trading_accounts.length > 1 && !this.selectedAccountId) {
                toast.error('Please select which trading account to place this order on')
                return;
            }

            if (!this.canSubmit){
                if(!this.selectedAsset){
                    toast.error('Please select an asset')
                    return;
                }
                toast.error('Open price is more than available Margin')
                return;
            }

            try {
                this.is_loading = true;
                const response = await axios.post('/api/admin/trades', {
                    id: this.selectedAsset.id,
                    volume: this.quantity,
                    type: this.direction,
                    leverage: this.leverage,
                    requiredMargin: this.requiredMargin,

                    margin: this.requiredMargin,
                    user_id: this.user_id,

                    is_take_profit: false,
                    take_profit: null,
                    is_stop_loss: false,
                    stop_loss: null,

                }, {
                    headers: this.selectedAccountId ? { 'X-TRADING-ACCOUNT-ID': this.selectedAccountId } : {},
                });

                toast.success(response?.data?.message);


                this.$splade.emit('refresh-trades');

                this.is_loading = false;

                this.successSound.play().catch(e => console.warn('Audio playback failed:', e));

                this.clearForm();

                await this.getData();

            } catch (error) {
                console.error('Error submitting trade:', error);
                toast.error('Failed to submit trade. Please try again.');
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
                const url = this.selectedAccountId
                    ? `${this.user_data_url}${this.user_data_url.includes('?') ? '&' : '?'}account=${encodeURIComponent(this.selectedAccountId)}`
                    : this.user_data_url;
                const { data } = await axios.get(url);
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
        },
        selectedAccountId() {
            this.getData();
        },
    },

    mounted() {
        this.successSound = new Audio("/sounds/notification.mp3");
        this.leverage = this.user_leverage ?? 1;

        if (this.trading_accounts.length === 1) {
            this.selectedAccountId = this.trading_accounts[0].account_id;
        }

        this.getData();
        document.addEventListener('click', this.closeDropdown);
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.withCredentials = true;
    },

    beforeDestroy() {
        document.removeEventListener('click', this.closeDropdown);
    }
}
</script>
