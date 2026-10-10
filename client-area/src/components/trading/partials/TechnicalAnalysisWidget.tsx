import { useEffect, useRef, memo } from "react";

interface TechnicalAnalysisWidgetProps {
    symbol: string;
    interval?: string;
    width?: number | string;
    height?: number | string;
    isTransparent?: boolean;
    colorTheme?: "light" | "dark";
    locale?: string;
    showIntervalTabs?: boolean;
    displayMode?: "single" | "compact";
}

function TechnicalAnalysisWidget({
                                     symbol = "NASDAQ:AAPL",
                                     interval = "1m",
                                     width = "100%",
                                     height = "450px",
                                     isTransparent = false,
                                     colorTheme = "dark",
                                     locale = "en",
                                     showIntervalTabs = true,
                                     displayMode = "single",
                                 }: TechnicalAnalysisWidgetProps) {
    const container = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        if (!container.current) return;

        // Store a reference to the container for cleanup
        const currentContainer = container.current;

        // Clear any existing widgets
        currentContainer.innerHTML = "";

        // Create container elements
        const widgetContainer = document.createElement("div");
        widgetContainer.className = "tradingview-widget-container";

        // Set container dimensions
        if (typeof width === "number") {
            widgetContainer.style.width = `${width}px`;
        } else {
            widgetContainer.style.width = width as string;
        }

        if (typeof height === "number") {
            widgetContainer.style.height = `${height}px`;
        } else {
            widgetContainer.style.height = height as string;
        }

        const widgetElement = document.createElement("div");
        widgetElement.className = "tradingview-widget-container__widget";
        widgetElement.style.width = "100%";
        widgetElement.style.height = "calc(100% - 32px)";

        const copyrightElement = document.createElement("div");
        copyrightElement.className = "tradingview-widget-copyright";


        // Append elements to container
        widgetContainer.appendChild(widgetElement);
        widgetContainer.appendChild(copyrightElement);
        currentContainer.appendChild(widgetContainer);

        // Create and load script
        const script = document.createElement("script");
        script.src = "https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js";
        script.type = "text/javascript";
        script.async = true;

        // Set script content (widget configuration)
        script.innerHTML = JSON.stringify({
            interval,
            width: "100%",
            isTransparent,
            height: "100%",
            symbol,
            showIntervalTabs : false,
            displayMode,
            locale,
            colorTheme,
        });

        widgetContainer.appendChild(script);

        // Cleanup function
        return () => {
            if (currentContainer) {
                currentContainer.innerHTML = "";
            }
        };
    }, [symbol, interval, width, height, isTransparent, colorTheme, locale, showIntervalTabs, displayMode]);

    return <div ref={container} style={{ width, height }} />;
}

// Memoize to prevent unnecessary rerenders
export default memo(TechnicalAnalysisWidget);
