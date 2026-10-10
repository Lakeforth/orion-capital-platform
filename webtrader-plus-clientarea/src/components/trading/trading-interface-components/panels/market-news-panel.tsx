
import { useEffect, useRef } from "react";

export default function MarketNewsPanel() {
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    // Create container elements
    const widgetContainer = document.createElement("div");
    const widgetElement = document.createElement("div");
    const copyrightElement = document.createElement("div");

    widgetContainer.className = "tradingview-widget-container h-full";
    widgetElement.className = "tradingview-widget-container__widget h-full";
    copyrightElement.className = "tradingview-widget-copyright";

    // Add copyright link
    const copyrightLink = document.createElement("a");
    copyrightLink.href = "https://www.tradingview.com/";
    copyrightLink.rel = "noopener nofollow";
    copyrightLink.target = "_blank";

    const spanElement = document.createElement("span");
    spanElement.className = "blue-text";
    spanElement.textContent = "Track all markets on TradingView";

    copyrightLink.appendChild(spanElement);
    copyrightElement.appendChild(copyrightLink);

    // Append elements to container
    widgetContainer.appendChild(widgetElement);
    widgetContainer.appendChild(copyrightElement);

    // Clear container and append widget
    if (containerRef.current) {
      containerRef.current.innerHTML = "";
      containerRef.current.appendChild(widgetContainer);
    }

    // Create and load script
    const script = document.createElement("script");
    script.type = "text/javascript";
    script.async = true;
    script.src =
      "https://s3.tradingview.com/external-embedding/embed-widget-timeline.js";

    // Set script content (widget configuration)
    script.innerHTML = JSON.stringify({
      feedMode: "all_symbols",
      isTransparent: false,
      displayMode: "regular",
      width: "100%",
      height: "100%",
      colorTheme: "dark",
      locale: "en",
    });

    // Append script to widget container
    widgetContainer.appendChild(script);

    // Cleanup on unmount
    return () => {
      if (containerRef.current) {
        containerRef.current.innerHTML = "";
      }
    };
  }, []);

  return (
    <div className="h-full bg-background">
      <div className="p-4 border-b border-border">
        <h2 className="text-sm font-medium">Market News</h2>
      </div>
      <div ref={containerRef} className="h-[calc(100%-56px)]"></div>
    </div>
  );
}
