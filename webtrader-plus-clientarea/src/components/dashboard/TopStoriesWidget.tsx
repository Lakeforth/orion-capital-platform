import { useEffect, useRef } from "react";

export function TopStoriesWidget() {
  const containerRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    const container = containerRef.current;
    if (!container) return;

    const widgetContainer = document.createElement("div");
    const widgetElement = document.createElement("div");
    const copyrightElement = document.createElement("div");

    widgetContainer.className = "tradingview-widget-container h-full";
    widgetElement.className = "tradingview-widget-container__widget h-full";
    copyrightElement.className = "tradingview-widget-copyright";

    const copyrightLink = document.createElement("a");
    copyrightLink.href = "https://www.tradingview.com/";
    copyrightLink.rel = "noopener nofollow";
    copyrightLink.target = "_blank";

    const spanElement = document.createElement("span");
    spanElement.className = "blue-text";
    spanElement.textContent = "Track all markets on TradingView";

    copyrightLink.appendChild(spanElement);
    copyrightElement.appendChild(copyrightLink);

    widgetContainer.appendChild(widgetElement);
    widgetContainer.appendChild(copyrightElement);

    const script = document.createElement("script");
    script.type = "text/javascript";
    script.async = true;
    script.src =
      "https://s3.tradingview.com/external-embedding/embed-widget-timeline.js";
    script.innerHTML = JSON.stringify({
      feedMode: "all_symbols",
      isTransparent: true,
      displayMode: "adaptive",
      width: "100%",
      height: "100%",
      colorTheme: "dark",
      locale: "en",
    });

    widgetContainer.appendChild(script);

    container.innerHTML = "";
    container.appendChild(widgetContainer);

    return () => {
      container.innerHTML = "";
    };
  }, []);

  return <div ref={containerRef} className="h-[420px] w-full" />;
}
