<template>
    <div class="flex flex-col h-[calc(100vh-4rem)] bg-[#0F1020] rounded-md text-white">
        <!-- Search -->
        <div class="p-4">
            <input
                v-model="search"
                class="w-full bg-[#1A1B2E] border border-gray-700 rounded-lg px-4 py-3"
                placeholder="search asset"
                @input="handleSearch"
            />
        </div>

        <!-- Categories - Now scrollable -->
        <div class="px-4 pb-4 overflow-x-auto">
            <div class="flex space-x-4 text-sm whitespace-nowrap pb-1">
                <button
                    v-for="cat in ['STOCKS', 'CRYPTO', 'FOREX', 'COMMODITIES', 'INDICES']"
                    :key="cat"
                    @click="selectedCategory = cat.toLowerCase(); handleSearch()"
                    :class="[
                        'px-3 py-1 rounded flex-shrink-0',
                        selectedCategory === cat.toLowerCase() ? 'bg-blue-500' : 'text-gray-400'
                    ]"
                >
                    {{ cat }}
                </button>
            </div>
        </div>

        <!-- Headers -->
        <div class="flex justify-between px-4 py-2 border-b border-gray-800 text-sm text-gray-400">
            <div>ASSET PAIR</div>
            <div>BUY/SELL</div>
        </div>

        <!-- Assets List -->
        <div class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-[#1A1B2E] scrollbar-track-[#0F1020] hover:scrollbar-thumb-gray-500">
            <div
                v-for="asset in assets"
                :key="asset.symbol"
                @click="selectAsset(asset)"
                class="flex text-white text-sm justify-between px-4 py-3 hover:bg-[#1A1B2E] cursor-pointer border-b border-gray-800"
            >
                <div class="font-light">{{ asset.sy }}</div>
                <div class="flex space-x-4 font-light">
                    <span class="text-white sell_spread">
                        {{ asset.sell_price }}
                    </span>
                    <span class="text-white buy_spread">
                        {{ asset.buy_price }}
                    </span>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="p-4 text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500 border-t-transparent mx-auto"></div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        selectedSymbol: String
    },
    data() {
        return {
            assets: [],
            search: '',
            selectedCategory: '',
            selectedAsset: false,
            categories: ['Crypto', 'Forex', 'Stocks'],
            loading: false,
            searchTimeout: null,
            error: null
        }
    },
    methods: {
        async fetchAssets() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get('/api/assets', {
                    params: { search: this.search, category: this.selectedCategory }
                });
                this.assets = response.data;

                if(!this.selectedAsset){
                    let asset = response.data[0];

                    this.$emit('symbol-selected', asset.ex_sym);
                    this.$emit('asset-selected', asset);

                    console.log('asset', asset)

                    this.selectedAsset = asset
                }

            } catch (err) {
                this.error = 'Failed to load assets. Please try again.';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        selectAsset(asset) {
            this.$emit('symbol-selected', asset.ex_sym);
            this.$emit('asset-selected', asset);

            this.selectedAsset = asset;
        },

        handleSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.fetchAssets();
            }, 300);
        }
    },
    mounted() {
        this.fetchAssets();
    },
    beforeUnmount() {
        clearTimeout(this.searchTimeout);
    }
}
</script>

<style scoped>
/* Hide scrollbar for Chrome, Safari and Opera */
.overflow-x-auto::-webkit-scrollbar {
    display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.overflow-x-auto {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
</style>
