<template>
    <div class="mobile-dashboard bg-[#1A1B2E] text-white">
        <!-- Top navigation bar -->
        <div class="top-bar flex items-center justify-between p-3 bg-[#141527] border-b border-gray-800">
            <!-- Left side - Hamburger menu and logo -->
            <div class="flex items-center">
                <button class="mr-2">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <img :src="logoUrl" alt="Logo" class="h-8">
            </div>

            <!-- Right side - User avatar -->
            <button class="rounded-full bg-green-600 flex items-center justify-center">
                <img class="w-8 h-8 rounded-full" :src="userAvatar" alt="User avatar">
            </button>
        </div>

        <!-- Account stats bar - Exactly like in the second image -->
        <div class="account-stats bg-[#141527] flex items-center justify-between px-4 py-2 border-b border-gray-800">
            <!-- Bonus indicator -->
            <div class="flex items-center space-x-1">
                <span class="text-gray-400 text-xs">Bonus:</span>
                <span class="text-green-500 text-xs">$ {{ formatNumber(bonus) }}</span>
            </div>

            <!-- Balance with dropdown -->
            <div class="flex items-center space-x-1">
                <div class="w-2 h-2 rounded-full bg-gray-600"></div>
                <span class="text-white text-sm font-medium">$ {{ formatNumber(balance) }}</span>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>

            <!-- Deposit button -->
            <button class="bg-green-600 text-white rounded px-4 py-1 text-sm font-medium">
                Deposit
            </button>
        </div>

        <!-- Slot for main content -->
        <slot></slot>
    </div>
</template>

<script>
export default {
    name: "MobileDashboard",
    props: {
        userAvatar: {
            type: String,
            default: "/images/avatar-placeholder.png"
        },
        logoUrl: {
            type: String,
            default: "/images/logo.png"
        }
    },
    data() {
        return {
            balance: 953.03,
            bonus: 0.00
        };
    },
    methods: {
        formatNumber(value) {
            return parseFloat(value).toFixed(2);
        },
        async getData() {
            try {
                const { data } = await axios.get("/api/user/trade-data");
                this.balance = data?.data.balance || 953.03;
                this.bonus = data?.data.bonus || 0.00;
            } catch (error) {
                console.error("Error fetching trade data:", error);
            }
        },
    },
    mounted() {
        // Fetch data when the component is mounted
        this.getData();

        // Add event listener for refreshing data
        this.$splade.on('refresh-trades', () => this.getData());
    },
    beforeUnmount() {
        // Clean up event listener
        document.removeEventListener("refresh-trades", this.getData);
    },
};
</script>

<style scoped>

</style>
