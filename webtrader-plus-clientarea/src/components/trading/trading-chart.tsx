// TradingViewWidget.jsx
import { chartTimeZone } from "@/lib/chart-timezone";
import { useEffect, useRef, memo } from "react";

function TradingViewWidget() {
  const container = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    if (container.current) {
      // Check if the script already exists to prevent duplication
      if (
        !container.current.querySelector(
          "script[src='https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js']"
        )
      ) {
        const script = document.createElement("script");
        script.src =
          "https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js";
        script.type = "text/javascript";
        script.async = true;
        script.innerHTML = `
          {
            "autosize": true,
            "symbol": "NASDAQ:AAPL",
            "interval": "1",
            "timezone": "${chartTimeZone()}",
            "theme": "dark",
            "style": "1",
            "locale": "en",
            "withdateranges": true,
            "hide_side_toolbar": false,
            "allow_symbol_change": false,
            "details": true,
            "hide_volume": true,
            "support_host": "https://www.tradingview.com"
          }`;
        container.current.appendChild(script);
      }
    }

    // Cleanup function to remove the script on unmount
    return () => {
      if (container.current) {
        const script = container.current.querySelector(
          "script[src='https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js']"
        );
        if (script) {
          container.current.removeChild(script);
        }
      }
    };
  }, []);

  return (
    <div
      className="tradingview-widget-container"
      ref={container}
      style={{ height: "100%", width: "100%" }}
    >
      <div
        className="tradingview-widget-container__widget"
        style={{ height: "calc(100% - 32px)", width: "100%" }}
      ></div>
      <div className="tradingview-widget-copyright">
        <a
          href="https://www.tradingview.com/"
          rel="noopener nofollow"
          target="_blank"
        >
          <span className="blue-text">Track all markets on TradingView</span>
        </a>
      </div>
    </div>
  );
}

export default memo(TradingViewWidget);
