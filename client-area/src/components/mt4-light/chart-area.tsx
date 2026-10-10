// import { AssetListingTabs } from "../mt4/header/asset-listing-tabs";
// import TradingViewWidget from "../trading/trading-interface-components/trading-view-widget";
// import BuySellFloatingButtons from "../mt4/main-content/buy-sell-floating-buttons";
import { ChartTabs } from "./chart-area/ChartTabs";
import Mt4PriceChart from "./chart-area/Mt4PriceChart";
import useDarkModeStore from "@/store/darkModeStore";
import AITradingOverlay from "@/components/trading/trading-interface-components/ai-trading-overlay";
import { useAiTradeToggle } from "@/hooks/useAiTradeToggle";
// import TradingViewLightWidget from "./chart-area/TradingViewLightWidget";

export function ChartArea() {
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);
  const aiTrade = useAiTradeToggle();

  return (
    <div
      className={`flex-1 flex flex-col ${
        isDarkMode ? "bg-slate-900" : "bg-gray-50"
      } border-b ${isDarkMode ? "border-slate-600" : "border-slate-400"}`}
    >
      <div className="flex-1 relative">
        <Mt4PriceChart />
        <AITradingOverlay isActive={aiTrade.available && aiTrade.active} containerType="chart" />
      </div>
      <div className="w-full shrink-0 overflow-x-auto relative z-30">
        <ChartTabs />
      </div>
    </div>
  );
}
