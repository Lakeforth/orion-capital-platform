<template>
    <div>
        <!-- Toggle Buttons -->
        <div class="mb-6 flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1 w-full md:w-auto">
            <button
                @click="activeTab = 'investment'"
                :class="[
          'px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 focus:outline-none w-1/2 md:w-auto',
          activeTab === 'investment'
            ? 'bg-blue-600 text-white shadow-sm'
            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
        ]"
            >
                Investing Portfolio
            </button>
            <button
                @click="activeTab = 'ecn'"
                :class="[
          'px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 focus:outline-none w-1/2 md:w-auto',
          activeTab === 'ecn'
            ? 'bg-blue-600 text-white shadow-sm'
            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
        ]"
            >
                ECN Portfolio
            </button>
        </div>

        <!-- Investment Portfolio -->
        <div v-if="activeTab === 'investment'">
            <div class="flex justify-between items-center mb-1 mt-4">
                <h2 class="text-lg text-black font-medium dark:text-gray-100">Investment Portfolio</h2>
            </div>

            <investment_table :api-url="investmentApiUrl" :default-status="defaultInvestmentStatus" />

        </div>

        <!-- ECN Portfolio -->
        <div v-else>
            <div class="flex justify-between items-center mb-1 mt-4">
                <h2 class="text-lg text-black font-medium dark:text-gray-100">ECN Portfolio</h2>
            </div>
            <ecn_table
                :submit-url="ecnSubmitUrl"
                :api-url="ecnApiUrl"
            />
        </div>
    </div>
</template>

<script>
export default {
    name: 'PortfolioToggle',
    props: {
        // API URLs
        investmentApiUrl: {
            type: String,
            required: true
        },
        ecnApiUrl: {
            type: String,
            required: true
        },
        ecnSubmitUrl: {
            type: String,
            required: true
        },
        // Default status for investment table
        defaultInvestmentStatus: {
            type: String,
            default: 'in_progress'
        },
        // Optional: Allow setting which tab is active on component mount
        initialActiveTab: {
            type: String,
            default: 'investment',
            validator: (value) => ['investment', 'ecn'].includes(value)
        }
    },
    data() {
        return {
            activeTab: this.initialActiveTab
        };
    },
    watch: {
        // Track tab changes and emit event for parent components
        activeTab(newTab) {
            this.$emit('tab-changed', newTab);
        }
    }
};
</script>
