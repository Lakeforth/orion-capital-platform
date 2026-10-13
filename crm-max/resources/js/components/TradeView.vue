<template>
    <div>
        <div :id="containerId" class="tradingview-widget-container" style="height: 70vh!important; border: 1px solid rgba(0,0,0,0.9)">
        </div>
    </div>
</template>

<script>
export default {
    props: {
        symbol: {
            type: String,
            default: 'NASDAQ:AAPL'
        },
        containerId: {
            type: String,
            default: 'tradingview-wrapper_sm'
        }
    },
    mounted() {
        this.initTradingView();
    },
    methods: {
        initTradingView() {
            const script = document.createElement('script');
            script.src = 'https://s3.tradingview.com/tv.js';
            script.async = true;
            script.onload = () => {
                new TradingView.widget({
                    "autosize": true,
                    "symbol": this.symbol,
                    "interval": "1",
                    "timezone": "Etc/UTC",
                    "theme": "dark",
                    "style": "3",
                    "locale": "en",
                    "enable_publishing": false,
                    "allow_symbol_change": false,
                    "container_id": this.containerId
                });
            };
            document.head.appendChild(script);
        }
    },
    watch: {
        symbol: {
            handler(newSymbol) {
                this.initTradingView();
            }
        }
    }
}
</script>
