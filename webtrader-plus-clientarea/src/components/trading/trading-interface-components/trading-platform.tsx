import { useState, useEffect } from "react";
import Header from "./header";
import Sidebar from "./sidebar";
import MainContent from "./main-content";
import useAssetStore from "@/store/assetStore";
import useTradeStore from "@/store/tradeStore";
import AssetInitializer from "../asset-initializer";
import AssetPriceStreamInitializer from "@/components/trading/AssetPriceStreamInitializer";
// import { useMobile } from "@/hooks/use-mobile";
import { addClickSound } from "@/lib/addClickSound.ts";
import Footer from "@/components/footer";

export type ActiveView =
  | "market-watch"
  | "active-orders"
  | "trading-history"
  | "calendar"
  | "market-news"
  | null;

export default function TradingPlatform() {
  const [sidebarExpanded, setSidebarExpanded] = useState(true);
  const [activeView, setActiveView] = useState<ActiveView>(() =>
    typeof window !== "undefined" && window.innerWidth < 768
      ? null
      : "market-watch",
  );
  // const isMobile = useMobile(768);
  // console.log("isMobile", isMobile);

  // activePairs/activePair come straight from the persisted assetStore
  // (localStorage["asset-storage"]) instead of a local copy, so the full
  // open-chart layout survives a page refresh rather than resetting to a
  // hardcoded default -- see addPair/removePair/setActivePair below.
  const fetchAssets = useAssetStore((state) => state.fetchAssets);
  const activePairs = useAssetStore((state) => state.activePairs);
  const activePair = useAssetStore((state) => state.activePair);
  const addPair = useAssetStore((state) => state.addPair);
  const removePair = useAssetStore((state) => state.removePair);
  const setActivePair = useAssetStore((state) => state.setActivePair);
  const { fetchOpenTrades, fetchClosedTrades } = useTradeStore();

  useEffect(() => {
    addClickSound();
  }, []);

  // Fetch assets and trades when component mounts
  useEffect(() => {
    // console.log("TradingPlatform - Fetching assets and trades");
    fetchAssets();
    fetchOpenTrades();
    fetchClosedTrades();
  }, [fetchAssets, fetchOpenTrades, fetchClosedTrades]);

  // Log assets when they change
  // useEffect(() => {
  //   console.log("TradingPlatform - Assets loaded:", assets.length);
  // }, [assets]);

  const toggleSidebar = () => {
    setSidebarExpanded(!sidebarExpanded);
  };

  const toggleView = (view: ActiveView) => {
    setActiveView(activeView === view ? null : view);
  };

  const addCurrencyPair = (pair: string) => {
    addPair(pair);
  };

  const removeCurrencyPair = (pair: string) => {
    removePair(pair);
  };

  return (
    <div className="flex flex-col h-screen bg-background text-foreground relative overflow-hidden">
      <AssetInitializer />
      <AssetPriceStreamInitializer />
      <Header
        activePairs={activePairs}
        activePair={activePair ?? activePairs[0] ?? ""}
        setActivePair={setActivePair}
        removeCurrencyPair={removeCurrencyPair}
        toggleView={toggleView}
      />
      <div className="flex flex-1 overflow-hidden">
        {/* Only show sidebar on desktop */}
        {/* {!isMobile && (
          <Sidebar
            expanded={sidebarExpanded}
            toggleSidebar={toggleSidebar}
            activeView={activeView}
            toggleView={toggleView}
            addCurrencyPair={addCurrencyPair}
          />
        )} */}

        <Sidebar
          expanded={sidebarExpanded}
          toggleSidebar={toggleSidebar}
          activeView={activeView}
          toggleView={toggleView}
          addCurrencyPair={addCurrencyPair}
        />
        <MainContent
          sidebarExpanded={sidebarExpanded}
          activeView={activeView}
          activePair={activePair ?? activePairs[0] ?? ""}
          addCurrencyPair={addCurrencyPair}
        />
      </div>
      <Footer />
    </div>
  );
}
