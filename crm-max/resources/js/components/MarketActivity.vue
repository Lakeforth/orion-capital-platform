<template>
    <div id="live-market-activity-body" class="p-5 border-t border-gray-900 dark:border-gray-700 dark:bg-gray-900">
        <template v-if="assetTimers.length">
            <div class="w-full">
                <div class="flex justify-between items-center mb-4 font-medium text-gray-700 dark:text-gray-300">
                    <div class="w-1/3 px-2">Asset</div>
                    <div class="w-1/3 px-2 text-center">Profit(%)</div>
                    <div class="w-1/3 px-4 text-right">Exp</div>
                </div>

                <div class="space-y-1">
                    <div
                        v-for="asset in assetTimers"
                        :key="asset.id"
                        class="flex justify-between items-center text-gray-600 hover:text-gray-50 dark:text-gray-400 hover:bg-gray-600  rounded-lg p-2"
                    >
                        <div class="w-1/3 px-2">{{ asset.name }}</div>
                        <div class="w-1/3 px-2 text-center">{{ asset.est_profit }}%</div>
                        <div class="w-1/2 px-4 text-right">
                            <template v-if="!asset.isClosed">
                                <div class="inline-flex items-center space-x-2 font-mono text-sm">
                  <span :class="[
                    'time-block',
                    asset.timeStatus === 'danger' && 'text-red-600 dark:text-red-400',
                    asset.timeStatus === 'warning' && 'text-yellow-600 dark:text-yellow-400'
                  ]">
                    <span class="digits">{{ asset.timeComponents.days }}</span>
                    <span class="label text-xs text-gray-500">d</span>
                  </span>
                                    <span :class="[
                    'time-block',
                    asset.timeStatus === 'danger' && 'text-red-600 dark:text-red-400',
                    asset.timeStatus === 'warning' && 'text-yellow-600 dark:text-yellow-400'
                  ]">
                    <span class="digits">{{ asset.timeComponents.hours }}</span>
                    <span class="label text-xs text-gray-500">h</span>
                  </span>
                                    <span :class="[
                    'time-block',
                    asset.timeStatus === 'danger' && 'text-red-600 dark:text-red-400',
                    asset.timeStatus === 'warning' && 'text-yellow-600 dark:text-yellow-400'
                  ]">
                    <span class="digits">{{ asset.timeComponents.minutes }}</span>
                    <span class="label text-xs text-gray-500">m</span>
                  </span>
                                    <span :class="[
                    'time-block',
                    asset.timeStatus === 'danger' && 'text-red-600 dark:text-red-400',
                    asset.timeStatus === 'warning' && 'text-yellow-600 dark:text-yellow-400'
                  ]">
                    <span class="digits">{{ asset.timeComponents.seconds }}</span>
                    <span class="label text-xs text-gray-500">s</span>
                  </span>
                                </div>
                            </template>
                            <template v-else>
                                <span class="text-red-500 dark:text-red-400">Contract closed</span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template v-else>
            <p class="mb-2 text-center text-gray-500 dark:text-gray-400">
                No Activity
            </p>
        </template>
    </div>
</template>

<script>
export default {
    name: 'MarketActivity',

    props: {
        assets: {
            type: Array,
            required: true,
            default: () => []
        }
    },

    data() {
        return {
            assetTimers: this.initializeAssetTimers(),
            timer: null
        };
    },

    methods: {
        initializeAssetTimers() {
            return this.assets.map(asset => ({
                ...asset,
                timeComponents: this.getTimeComponents(asset.expires_at),
                timeStatus: this.getTimeStatus(asset.expires_at),
                remainingMs: asset.expires_at
            }));
        },

        formatNumber(number) {
            return number.toString().padStart(2, '0');
        },

        getTimeComponents(remainingMs) {
            const days = Math.floor(remainingMs / (1000 * 60 * 60 * 24));
            const hours = Math.floor((remainingMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((remainingMs % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((remainingMs % (1000 * 60)) / 1000);

            return {
                days: this.formatNumber(days),
                hours: this.formatNumber(hours),
                minutes: this.formatNumber(minutes),
                seconds: this.formatNumber(seconds)
            };
        },

        getTimeStatus(remainingMs) {
            if (remainingMs < 3600000) return 'danger'; // Less than 1 hour
            if (remainingMs < 86400000) return 'warning'; // Less than 1 day
            return 'normal';
        },

        updateTimers() {
            this.assetTimers = this.assetTimers.map(asset => {
                if (asset.remainingMs <= 0) {
                    return { ...asset, isClosed: true };
                }

                const newRemainingMs = asset.remainingMs - 1000;
                return {
                    ...asset,
                    remainingMs: newRemainingMs,
                    timeComponents: this.getTimeComponents(newRemainingMs),
                    timeStatus: this.getTimeStatus(newRemainingMs)
                };
            });
        }
    },

    mounted() {
        this.timer = setInterval(this.updateTimers, 1000);
    },

    beforeDestroy() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    }
};
</script>
