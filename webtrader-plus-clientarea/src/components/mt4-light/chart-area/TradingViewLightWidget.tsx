import { chartTimeZone } from "@/lib/chart-timezone";
import { useEffect, useRef, memo } from "react";
import useAssetStore from "@/store/assetStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import TradingViewLoadingOverlay from "@/components/trading/TradingViewLoadingOverlay";
import useDarkModeStore from "@/store/darkModeStore";

interface TradingViewLightWidgetProps {
  interval?: string;
}

function TradingViewLightWidget({
  interval = "1",
}: TradingViewLightWidgetProps) {
  const container = useRef<HTMLDivElement>(null);
  const tvSymbol =
    useAssetStore((state) => state.activeAsset?.tv_symbol) || "OANDA:EURUSD";
  const livetraderStatus = useSiteSettingsStore(
    (state) => state.settings?.livetrader_status ?? true,
  );
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);

  useEffect(() => {
    if (!livetraderStatus) return;

    const script = document.createElement("script");
    script.src =
      "https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js";
    script.type = "text/javascript";
    script.async = true;
    script.innerHTML = JSON.stringify({
      width: "100%",
      height: "100%",
      allow_symbol_change: true,
      calendar: false,
      details: false,
      hide_side_toolbar: true,
      hide_top_toolbar: false,
      hide_legend: false,
      hide_volume: true,
      hotlist: false,
      interval: interval,
      locale: "en",
      save_image: true,
      style: "1",
      symbol: tvSymbol,
      theme: isDarkMode ? "dark" : "light",
      timezone: chartTimeZone(),
      backgroundColor: isDarkMode ? "#131722" : "#f8f8f8",
      gridColor: isDarkMode
        ? "rgba(255, 255, 255, 0.06)"
        : "rgba(46, 46, 46, 0.06)",
      watchlist: [],
      withdateranges: false,
      compareSymbols: [],
      studies: [],
      autosize: true,
    });

    if (container.current) {
      container.current.innerHTML = "";
      container.current.appendChild(script);
    }

    const containerElement = container.current;
    return () => {
      if (containerElement) {
        containerElement.innerHTML = "";
      }
    };
  }, [tvSymbol, isDarkMode, interval, livetraderStatus]);

  return (
    <div style={{ height: "100%", width: "100%", position: "relative" }}>
      {!livetraderStatus && (
        <TradingViewLoadingOverlay theme={isDarkMode ? "dark" : "light"} />
      )}
      <div
        className="tradingview-widget-container"
        ref={container}
        style={{ height: "100%", width: "100%" }}
      >
        <div
          className="tradingview-widget-container__widget"
          style={{ height: "calc(100% - 32px)", width: "100%" }}
        />
        <div className="tradingview-widget-copyright">
          <a
            href={`https://www.tradingview.com/symbols/${tvSymbol}/?exchange=OANDA`}
            rel="noopener noreferrer"
            target="_blank"
            className={isDarkMode ? "text-slate-200" : "text-slate-900"}
          >
            <span className={isDarkMode ? "text-blue-400" : "blue-text"}>
              {tvSymbol.replace("OANDA:", "")} Chart by TradingView
            </span>
          </a>
        </div>
      </div>
    </div>
  );
}

export default memo(TradingViewLightWidget);
