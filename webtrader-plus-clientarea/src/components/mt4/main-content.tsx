import ChartArea from "./main-content/chart-area";
import { useState } from "react";
import { ChevronLeft, ChevronRight, X } from "lucide-react";
import { cn } from "@/lib/utils";
import { useMobile } from "@/hooks/use-mobile";
import AutomatedTrading from "./right-panels/automated-trading";
import useOverlayStore from "@/store/overlayStore";
import PositionDisplay from "./main-content/position-display";
import RightPanels from "./main-content/right-panels";

// Import panel components
import MarketWatchPanel from "../trading/trading-interface-components/panels/market-watch-panel";
import ActiveOrdersPanel from "../trading/trading-interface-components/panels/active-orders-panel";
import TradingHistoryPanel from "../trading/trading-interface-components/panels/trading-history-panel";
import CalendarPanel from "../trading/trading-interface-components/panels/calendar-panel";
import MarketNewsPanel from "../trading/trading-interface-components/panels/market-news-panel";
import { Card, CardHeader, CardTitle, CardContent } from "../ui/card";
import { Button } from "../ui/button";

export default function MainContent() {
  const [isRightPanelVisible, setIsRightPanelVisible] = useState(true);
  const isMobile = useMobile(768);
  const { automatedTrading, selectedAdvisorId, activePanel, setActivePanel } =
    useOverlayStore();

  const addCurrencyPair = (pair: string) => {
    console.log("Adding currency pair:", pair);
  };

  const renderActivePanel = () => {
    if (!activePanel) return null;

    const panels: Record<string, React.ReactNode> = {
      "market-watch": <MarketWatchPanel addCurrencyPair={addCurrencyPair} />,
      "active-orders": <ActiveOrdersPanel />,
      "trading-history": <TradingHistoryPanel />,
      calendar: <CalendarPanel />,
      "market-news": <MarketNewsPanel />,
    };

    const PanelComponent = panels[activePanel];

    // If it's one of the implemented panels, return it
    if (PanelComponent)
      return (
        <div className="w-[300px] border-r border-border overflow-y-auto">
          {PanelComponent}
        </div>
      );

    // For non-implemented panels, show a generic panel
    return (
      <div className="w-[300px] border-r border-border">
        <Card className="bg-[#1C2030] text-slate-300 border-slate-800">
          <CardHeader className="bg-slate-700 flex flex-row items-center justify-between py-4 px-4 border-b border-slate-800">
            <CardTitle className="text-sm font-medium text-slate-200">
              {activePanel
                .split("-")
                .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                .join(" ")}
            </CardTitle>
            <Button
              variant="ghost"
              size="icon"
              className="h-6 w-6 text-slate-400 hover:text-slate-100"
              onClick={() => setActivePanel(null)}
            >
              <X className="h-4 w-4" />
            </Button>
          </CardHeader>
          <CardContent className="p-4">
            <p className="text-muted-foreground">
              This panel is under development
            </p>
          </CardContent>
        </Card>
      </div>
    );
  };

  return (
    <main className="flex-1 flex flex-col h-full relative">
      {/* Chart and Right Panels Container */}
      <div className="flex flex-1 min-h-0">
        {/* Left Panel from Sidebar */}
        {!isMobile && renderActivePanel()}
        {/* Chart Area with Automated Trading */}
        <div className="flex-1 relative h-full">
          <ChartArea />
          {/* Automated Trading Panel */}
          {automatedTrading && (
            <div className="top-0 left-0 z-50 absolute">
              <AutomatedTrading advisorId={selectedAdvisorId} />
            </div>
          )}

          {/* Mobile Panel Overlay */}
          {isMobile && activePanel && (
            <>
              <div
                className="fixed inset-0 bg-black/50 z-30"
                onClick={() => setActivePanel(null)}
              />
              <div className="fixed left-0 top-0 bottom-0 w-[300px] z-40 bg-background">
                {renderActivePanel()}
              </div>
            </>
          )}
        </div>
        {/* Right Panels */}
        {/* Toggle Button */}
        <button
          onClick={() => setIsRightPanelVisible(!isRightPanelVisible)}
          className="absolute right-0 top-8 z-50 bg-slate-700 hover:bg-slate-600 
            text-slate-300 p-1 rounded-l-md shadow-lg border border-slate-600"
        >
          {isRightPanelVisible ? (
            <ChevronRight className="h-4 w-4" />
          ) : (
            <ChevronLeft className="h-4 w-4" />
          )}
        </button>
        <div
          className={cn(
            "transition-all duration-300 ease-in-out",
            isMobile
              ? "fixed right-0 top-0 bottom-0 z-40" // Mobile overlay
              : "relative", // Desktop inline
            !isRightPanelVisible &&
              (isMobile ? "translate-x-full" : "w-0 opacity-0"),
            isRightPanelVisible &&
              (isMobile ? "translate-x-0" : "w-80 opacity-100")
          )}
        >
          <RightPanels />
        </div>
        {/* Overlay for mobile */}
        {isMobile && isRightPanelVisible && (
          <div
            className="fixed inset-0 bg-black/50 z-30"
            onClick={() => setIsRightPanelVisible(false)}
          />
        )}
      </div>

      {/* Position Display - Full width of main content */}
      <PositionDisplay />
    </main>
  );
}
