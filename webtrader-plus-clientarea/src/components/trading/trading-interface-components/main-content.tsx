import type { ActiveView } from "./trading-platform";
import MarketWatchPanel from "./panels/market-watch-panel";
import ActiveOrdersPanel from "./panels/active-orders-panel";
import TradingHistoryPanel from "./panels/trading-history-panel";
import CalendarPanel from "./panels/calendar-panel";
import MarketNewsPanel from "./panels/market-news-panel";
import { useMobile } from "@/hooks/use-mobile";
import TradingViewWidget from "./trading-view-widget";
// import useAssetStore from "@/store/assetStore";
import TradingInterface from "../trading-interface";
import OrderTable from "../order-table";
import AITradingOverlay from "./ai-trading-overlay";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import FloatablePanel from "../partials/FloatablePanel";
import { useState } from "react";

interface MainContentProps {
  sidebarExpanded: boolean;
  activeView: ActiveView;
  activePair: string;
  addCurrencyPair: (pair: string) => void;
}

export default function MainContent({
  // sidebarExpanded,
  activeView,
  addCurrencyPair,
}: MainContentProps) {
  const isMobile = useMobile();
  const isLargeScreen = useMobile(1024);
  const [ordersFloating, setOrdersFloating] = useState(false);
  const aiTrade = useUserStore((state) => state.user?.ai_trade);
  const enabledAiTrade = useSiteSettingsStore(
    (state) => state.settings?.enabled_ai_trade === true,
  );

  // const { activeAsset } = useAssetStore();
  // console.log("sidebarExtended", sidebarExpanded);

  // Log when active asset changes
  // useEffect(() => {
  //   console.log("MainContent - Active asset changed:", activeAsset);
  // }, [activeAsset]);

  return (
    <div className="flex flex-col flex-1 overflow-hidden">
      {/* Main content area */}
      <div className="flex flex-1 overflow-hidden">
        {/* Left panel - shows based on active view (desktop only) */}
        {activeView && !isMobile && (
          <div className="w-[300px] border-r border-border overflow-y-auto hidden md:block">
            {activeView === "market-watch" && (
              <MarketWatchPanel addCurrencyPair={addCurrencyPair} />
            )}
            {activeView === "active-orders" && <ActiveOrdersPanel />}
            {activeView === "trading-history" && <TradingHistoryPanel />}
            {activeView === "calendar" && <CalendarPanel />}
            {activeView === "market-news" && <MarketNewsPanel />}
          </div>
        )}

        {/* Center - Chart and Orders */}
        <div className="flex-1 flex flex-col gap-2 overflow-hidden w-full relative">
          {/* Chart and Trading Interface */}
          <div className="flex flex-1 border-b border-border bg-background w-full relative">
            {/* Chart area */}
            <div className="flex-1 bg-background border-muted border-2 w-full relative overflow-hidden">
              <AITradingOverlay
                isActive={Boolean(enabledAiTrade && aiTrade)}
                containerType="chart"
              />
              <TradingViewWidget />
            </div>

            {/* Trading Interface */}
            {!isLargeScreen && (
              <FloatablePanel
                label="order entry"
                initialPosition={{ x: Math.max(8, window.innerWidth - 340), y: 120 }}
                className="w-[300px] border-2 border-border overflow-y-auto"
                floatingClassName="w-[320px] max-h-[75vh]"
              >
                <TradingInterface />
              </FloatablePanel>
            )}
          </div>

          {/* Orders table */}
          {isLargeScreen && (
            <div className="border-b-2 border-border w-full">
              <TradingInterface />
            </div>
          )}
          <FloatablePanel
            label="orders window"
            initialPosition={{ x: 140, y: Math.max(80, window.innerHeight - 360) }}
            className="h-auto overflow-auto w-full"
            floatingClassName="w-[min(1100px,calc(100vw-32px))] h-[340px] min-w-[620px] min-h-[240px] max-w-[calc(100vw-16px)] max-h-[calc(100vh-16px)]"
            resizable
            onFloatingChange={setOrdersFloating}
          >
            <OrderTable isFloating={ordersFloating} />
          </FloatablePanel>
        </div>
      </div>
    </div>
  );
}
