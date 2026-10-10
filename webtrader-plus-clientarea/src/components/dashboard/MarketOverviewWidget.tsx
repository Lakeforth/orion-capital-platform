import React, { useEffect, useRef, useState } from 'react';

interface MarketOverviewWidgetProps {
  height?: number;
}

const MarketOverviewWidget: React.FC<MarketOverviewWidgetProps> = ({ height = 660 }) => {
  const containerRef = useRef<HTMLDivElement>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    if (containerRef.current) {
      // Clear container before adding script
      containerRef.current.innerHTML = '';
      
      const script = document.createElement('script');
      script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-market-overview.js';
      script.type = 'text/javascript';
      script.async = true;
      script.innerHTML = JSON.stringify({
        "colorTheme": "light",
        "dateRange": "12M",
        "showChart": true,
        "locale": "en",
        "width": "100%",
        "height": height,
        "largeChartUrl": "",
        "isTransparent": false,
        "showSymbolLogo": true,
        "showFloatingTooltip": false,
        "tabs": [
          {
            "title": "Forex",
            "symbols": [
              { "s": "FX:EURUSD", "d": "EUR/USD" },
              { "s": "FX:GBPUSD", "d": "GBP/USD" },
              { "s": "FX:USDJPY", "d": "USD/JPY" },
              { "s": "FX:USDCHF", "d": "USD/CHF" },
              { "s": "FX:AUDUSD", "d": "AUD/USD" },
              { "s": "FX:USDCAD", "d": "USD/CAD" }
            ],
            "originalTitle": "Forex"
          },
          {
            "title": "Crypto",
            "symbols": [
              { "s": "BINANCE:BTCUSDT", "d": "BTC/USDT" },
              { "s": "BINANCE:ETHUSDT", "d": "ETH/USDT" },
              { "s": "BINANCE:SOLUSDT", "d": "SOL/USDT" },
              { "s": "BINANCE:BNBUSDT", "d": "BNB/USDT" },
              { "s": "BINANCE:XRPUSDT", "d": "XRP/USDT" },
              { "s": "BINANCE:ADAUSDT", "d": "ADA/USDT" }
            ],
            "originalTitle": "Crypto"
          },
          {
            "title": "Stocks",
            "symbols": [
              { "s": "NASDAQ:AAPL", "d": "Apple" },
              { "s": "NASDAQ:MSFT", "d": "Microsoft" },
              { "s": "NASDAQ:TSLA", "d": "Tesla" },
              { "s": "NASDAQ:AMZN", "d": "Amazon" },
              { "s": "NASDAQ:META", "d": "Meta" },
              { "s": "NASDAQ:GOOGL", "d": "Google" }
            ],
            "originalTitle": "Stocks"
          },
          {
            "title": "Commodities",
            "symbols": [
              { "s": "TVC:GOLD", "d": "Gold" },
              { "s": "TVC:SILVER", "d": "Silver" },
              { "s": "TVC:USOIL", "d": "Crude Oil" },
              { "s": "TVC:UKOIL", "d": "Brent Oil" },
              { "s": "TVC:NG1!", "d": "Natural Gas" }
            ],
            "originalTitle": "Commodities"
          },
          {
            "title": "Indices",
            "symbols": [
              { "s": "FOREXCOM:SPX500", "d": "S&P 500" },
              { "s": "FOREXCOM:NSXUSD", "d": "Nasdaq 100" },
              { "s": "FOREXCOM:DJI", "d": "Dow Jones" },
              { "s": "INDEX:NKY", "d": "Nikkei 225" },
              { "s": "INDEX:DEU40", "d": "DAX" },
              { "s": "FOREXCOM:UKXGBP", "d": "UK 100" }
            ],
            "originalTitle": "Indices"
          }
        ]
      });
      containerRef.current.appendChild(script);

      // Simulate loading completion
      const timer = setTimeout(() => setIsLoading(false), 2000);
      return () => clearTimeout(timer);
    }
  }, [height]);

  return (
    <div 
      className="w-full bg-white rounded-xl p-4 shadow-lg border border-gray-100 overflow-hidden"
      style={{ minHeight: height + 40 }}
    >
      <div className="flex items-center justify-between mb-2">
        <h2 className="text-black text-sm font-bold uppercase tracking-wider">Market Overview</h2>
      </div>

      <div className="relative w-full" style={{ height: height }}>
        {isLoading && (
          <div className="absolute inset-0 z-20 bg-white space-y-6">
            {/* Tabs Skeleton */}
            <div className="flex gap-2 mb-4">
              {[1, 2, 3, 4].map(i => (
                <div key={i} className="h-9 w-24 bg-gray-100 animate-pulse rounded-lg"></div>
              ))}
            </div>
            
            {/* List Rows Skeleton */}
            <div className="space-y-1">
              <div className="flex items-center justify-between py-3 border-b border-gray-50 text-xs font-bold text-gray-400 px-2 uppercase tracking-wider">
                <span>Symbol</span>
                <div className="flex gap-16 px-4">
                  <span>Last</span>
                  <span>Chg</span>
                </div>
              </div>
              {[1, 2, 3, 4, 5, 6].map(i => (
                <div key={i} className="flex items-center justify-between py-4 border-b border-gray-50 px-2">
                  <div className="flex items-center gap-3">
                    <div className="h-8 w-8 bg-gray-100 animate-pulse rounded-full"></div>
                    <div className="space-y-2">
                      <div className="h-4 w-32 bg-gray-100 animate-pulse rounded"></div>
                      <div className="h-3 w-20 bg-gray-50 animate-pulse rounded"></div>
                    </div>
                  </div>
                  <div className="flex gap-12 items-center">
                    <div className="h-5 w-24 bg-gray-100 animate-pulse rounded"></div>
                    <div className="h-6 w-20 bg-gray-100 animate-pulse rounded-md"></div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
        
        <div 
          id="tradingview-market-overview" 
          ref={containerRef} 
          className={`tradingview-widget-container transition-opacity duration-500 ${isLoading ? 'opacity-0' : 'opacity-100'}`}
        >
          <div className="tradingview-widget-container__widget"></div>
        </div>
      </div>
    </div>
  );
};

export default MarketOverviewWidget;
