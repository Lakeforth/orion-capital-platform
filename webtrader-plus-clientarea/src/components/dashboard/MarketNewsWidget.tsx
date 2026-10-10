import React, { useEffect, useRef, useState } from 'react';

const MarketNewsWidget: React.FC = () => {
  const containerRef = useRef<HTMLDivElement>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    if (containerRef.current) {
      containerRef.current.innerHTML = '';
      
      const script = document.createElement('script');
      script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-timeline.js';
      script.type = 'text/javascript';
      script.async = true;
      script.innerHTML = JSON.stringify({
        "feedMode": "all_symbols",
        "colorTheme": "light",
        "isTransparent": false,
        "displayMode": "regular",
        "width": "100%",
        "height": 600,
        "locale": "en"
      });
      containerRef.current.appendChild(script);

      const timer = setTimeout(() => setIsLoading(false), 2000);
      return () => clearTimeout(timer);
    }
  }, []);

  return (
    <div className="w-full bg-white rounded-xl p-6 shadow-lg border border-gray-100 overflow-hidden min-h-[650px]">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-[#00234B] text-xl font-bold tracking-tight">Market News</h2>
      </div>

      <div className="relative w-full h-[600px]">
        {isLoading && (
          <div className="absolute inset-0 z-20 bg-white space-y-6">
            {[1, 2, 3, 4, 5].map(i => (
              <div key={i} className="flex gap-4 py-4 border-b border-gray-50">
                <div className="flex-1 space-y-3">
                  <div className="h-4 w-3/4 bg-gray-100 animate-pulse rounded"></div>
                  <div className="h-3 w-full bg-gray-50 animate-pulse rounded"></div>
                  <div className="flex gap-4">
                    <div className="h-3 w-20 bg-gray-50 animate-pulse rounded"></div>
                    <div className="h-3 w-20 bg-gray-50 animate-pulse rounded"></div>
                  </div>
                </div>
                <div className="h-20 w-20 bg-gray-100 animate-pulse rounded-lg flex-shrink-0"></div>
              </div>
            ))}
          </div>
        )}
        
        <div 
          ref={containerRef} 
          className={`tradingview-widget-container transition-opacity duration-500 ${isLoading ? 'opacity-0' : 'opacity-100'}`}
        >
          <div className="tradingview-widget-container__widget"></div>
        </div>
      </div>
    </div>
  );
};

export default MarketNewsWidget;
