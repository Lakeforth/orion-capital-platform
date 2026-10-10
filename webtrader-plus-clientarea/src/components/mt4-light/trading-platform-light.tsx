"use client";

import { useEffect, useState } from "react";
import { TitleBar } from "./title-bar";
import { Toolbar } from "./toolbar";
// import { AITradingPanel } from "./ai-trading-panel";
import { ChartArea } from "./chart-area";
import useDarkModeStore from "@/store/darkModeStore";
// import { TradingHistory } from "./trading-history";
// import { StatusBar } from "./status-bar";
import MarketWatch from "./market-watch-light";
import PositionDisplayLight from "./position-display-light";
import AlgoTraderLight from "./algo-trader-light";
import TotalPortfolioLight from "./total-portfolio-light";
import AutomatedTrading from "../mt4/right-panels/automated-trading";
import useOverlayStore from "@/store/overlayStore";
import { Sheet, SheetContent } from "@/components/ui/sheet";
import { useMobile } from "@/hooks/use-mobile";
import useSiteSettingsStore from "@/store/siteSettingStore";
import useTradeStore from "@/store/tradeStore";
import AssetPriceStreamInitializer from "@/components/trading/AssetPriceStreamInitializer";

export function TradingPlatformLight() {
  const { automatedTrading, selectedAdvisorId } = useOverlayStore();
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);
  const isMobile = useMobile();
  const [isSheetOpen, setIsSheetOpen] = useState(false);
  const settings = useSiteSettingsStore((state) => state.settings);
  // Selectors, not the whole store: every price tick on an open trade updates tradeStore, and
  // re-rendering this shell on each tick is what kept rebuilding Market Watch.
  const fetchOpenTrades = useTradeStore((state) => state.fetchOpenTrades);
  const fetchClosedTrades = useTradeStore((state) => state.fetchClosedTrades);

  // Unlike the default theme's TradingPlatform, this shell previously had no
  // mount-time load of open/closed trades -- PositionDisplayLight only
  // refetches after a trade opens/closes in this session. Existing open
  // trades masked the gap (the order-entry form refreshes fetchOpenTrades()
  // on every new trade), but pre-existing closed trades never appeared until
  // one closed live in the same session, since nothing else calls
  // fetchClosedTrades() on initial load -- reported as the Web Trader
  // History tab being empty on webtrader, which mounts this shell whenever
  // a tenant's is_mt4 setting is on and never mounts NotificationListener
  // (the component that papered over this for the client-area).
  useEffect(() => {
    fetchOpenTrades();
    fetchClosedTrades();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // A JSX value, not a component declared inside render: an inner `const Sidebar = () => ...`
  // is a new component type on every render, so React unmounted and remounted Market Watch
  // (wiping a search in progress and refetching assets) whenever this shell re-rendered.
  const sidebar = (
    <div
      className={`w-full md:w-96 ${
        isDarkMode
          ? "bg-slate-900 border-slate-600"
          : "bg-white border-slate-300"
      } border-r flex flex-col h-full`}
    >
      <div
        className={`${
          settings?.expert_advisor ? "h-[75%]" : "h-full"
        } overflow-auto`}
      >
        <MarketWatch />
      </div>
      {settings?.expert_advisor && (
        <div className="h-[25%] overflow-auto">
          <AlgoTraderLight />
        </div>
      )}
    </div>
  );

  return (
    <div
      className={`h-screen ${
        isDarkMode ? "bg-slate-950" : "bg-slate-100"
      } flex flex-col font-sans text-sm`}
    >
      <AssetPriceStreamInitializer />
      <TitleBar />
      <Toolbar
        isMobile={isMobile}
        onToggleSidebar={() => setIsSheetOpen(true)}
      />

      <div className="flex flex-1 overflow-hidden">
        {/* Sheet for Mobile */}
        {isMobile ? (
          <Sheet open={isSheetOpen} onOpenChange={setIsSheetOpen}>
            <SheetContent side="left" className="p-0 w-full sm:w-96 z-">
              {sidebar}
            </SheetContent>
          </Sheet>
        ) : (
          sidebar
        )}

        {/* Main Content Area */}
        <div
          className={`flex-1 flex flex-col relative ${
            isDarkMode ? "bg-slate-900" : "bg-white"
          }`}
        >
          <ChartArea />
          {/* Automated Trading Panel */}
          {automatedTrading && (
            <div
              className={`top-0 left-0 z-50 absolute ${
                isDarkMode ? "bg-slate-900" : "bg-white"
              }`}
            >
              <AutomatedTrading advisorId={selectedAdvisorId} />
            </div>
          )}
        </div>
      </div>

      {/* <TradingHistory /> */}
      <PositionDisplayLight />
      <TotalPortfolioLight />

      {/* <StatusBar /> */}
    </div>
  );
}
