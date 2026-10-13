<template>
    <div class="tradingview-widget-container">
        <div class="tradingview-widget-container__widget"></div>
    </div>
</template>

<script>
export default {
    name: 'MiniChart',
    props: {
        symbol: {
            type: String,
            default: 'FX:EURUSD'
        },
        width: {
            type: String,
            default: "100%"
        },
        height: {
            type: String,
            default: "100%"
        },
        locale: {
            type: String,
            default: 'en'
        },
        colorTheme: {
            type: String,
            default: 'dark'
        },
        isTransparent: {
            type: Boolean,
            default: false
        },
        autosize: {
            type: Boolean,
            default: false
        }
    },
    mounted() {
        this.loadTradingViewScript();
    },
    methods: {
        loadTradingViewScript() {
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.async = true;
            script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-mini-symbol-overview.js';

            // Create the widget configuration
            script.innerHTML = JSON.stringify({
                symbol: this.symbol,
                width: this.width,
                height: this.height,
                locale: this.locale,
                dateRange: "12M",
                colorTheme: this.colorTheme,
                isTransparent: this.isTransparent,
                autosize: this.autosize,
                largeChartUrl: ""
            });

            // Add the script to the container
            this.$el.appendChild(script);
        }
    },
    beforeUnmount() {
        // Clean up any scripts when component is destroyed
        const scripts = this.$el.getElementsByTagName('script');
        for (let script of scripts) {
            script.remove();
        }
    }
}
</script>

<style scoped>
.tradingview-widget-container {
    position: relative;
}
</style>
