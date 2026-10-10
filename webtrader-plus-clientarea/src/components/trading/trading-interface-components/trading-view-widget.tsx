import { chartTimeZone } from "@/lib/chart-timezone";
import { useEffect, useRef, memo } from "react";
import useAssetStore from "@/store/assetStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import TradingViewLoadingOverlay from "@/components/trading/TradingViewLoadingOverlay";

function TradingViewWidget() {
  const container = useRef<HTMLDivElement | null>(null);
  // Only use the tv_symbol property from activeAsset to prevent unnecessary re-renders
  const tvSymbol = useAssetStore((state) => state.activeAsset?.tv_symbol);
  const livetraderStatus = useSiteSettingsStore(
    (state) => state.settings?.livetrader_status ?? true
  );

  useEffect(() => {
    if (!container.current || !tvSymbol || !livetraderStatus) return;

    // Store a reference to the container for cleanup
    const currentContainer = container.current;
    const currentSymbol = tvSymbol;

    // Clear any existing widgets
    currentContainer.innerHTML = "";

    // Create container elements
    const widgetContainer = document.createElement("div");
    widgetContainer.className = "tradingview-widget-container";
    widgetContainer.style.height = "100%";
    widgetContainer.style.width = "100%";

    const widgetElement = document.createElement("div");
    widgetElement.className = "tradingview-widget-container__widget";
    widgetElement.style.height = "calc(100% - 32px)";
    widgetElement.style.width = "100%";

    const copyrightElement = document.createElement("div");

    // Append elements to container
    widgetContainer.appendChild(widgetElement);
    widgetContainer.appendChild(copyrightElement);
    currentContainer.appendChild(widgetContainer);

    // Create and load script
    const script = document.createElement("script");
    script.src =
      "https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js";
    script.type = "text/javascript";
    script.async = true;

    // Set script content (widget configuration)
    script.innerHTML = JSON.stringify({
      autosize: true,
      symbol: currentSymbol,
      interval: "1",
      timezone: chartTimeZone(),
      theme: "dark",
      style: "1",
      locale: "en",
      isTransparent: true,
      withdateranges: true,
      hide_side_toolbar: false,
      allow_symbol_change: false,
      support_host: "https://www.tradingview.com",
    });

    widgetContainer.appendChild(script);

    // Cleanup function
    return () => {
      if (currentContainer) {
        currentContainer.innerHTML = "";
      }
    };
  }, [tvSymbol, livetraderStatus]); // Re-run when tvSymbol or livetraderStatus changes

  return (
    <div style={{ height: "100%", width: "100%", position: "relative" }}>
      {!livetraderStatus && <TradingViewLoadingOverlay theme="dark" />}
      <div ref={container} style={{ height: "100%", width: "100%" }} />
    </div>
  );
}

// Memoize to prevent unnecessary rerenders
export default memo(TradingViewWidget);
