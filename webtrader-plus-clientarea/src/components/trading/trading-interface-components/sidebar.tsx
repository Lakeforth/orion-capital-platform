import type React from "react";

import { useState, useEffect } from "react";
import {
  X,
  LineChart,
  Clock,
  BarChart3,
  Calendar,
  Newspaper,
  ArrowUpFromLine,
  ChevronLeft,
  ChevronRight,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { useMobile } from "@/hooks/use-mobile";
import type { ActiveView } from "./trading-platform";
import MarketWatchPanel from "./panels/market-watch-panel";
import ActiveOrdersPanel from "./panels/active-orders-panel";
import TradingHistoryPanel from "./panels/trading-history-panel";
import CalendarPanel from "./panels/calendar-panel";
import MarketNewsPanel from "./panels/market-news-panel";
import WithdrawFunds from "@/components/withdrawal/WithdrawFunds";
import { useWithdrawAction } from "@/hooks/useWithdrawAction";

// Update the SidebarProps interface to include addCurrencyPair
interface SidebarProps {
  expanded: boolean;
  toggleSidebar: () => void;
  activeView: ActiveView;
  toggleView: (view: ActiveView) => void;
  addCurrencyPair: (pair: string) => void;
}

// Then update the function signature to include the new prop
export default function Sidebar({
  expanded,
  toggleSidebar,
  activeView,
  toggleView,
  addCurrencyPair,
}: SidebarProps) {
  const isMobile = useMobile();
  // Withdraw lives here now instead of in the header. It is an action, not a
  // panel, so it sits outside sidebarItems and never takes the active state.
  const { withdraw, isDialogOpen, setDialogOpen } = useWithdrawAction();

  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  // Close mobile menu when switching to desktop
  useEffect(() => {
    if (!isMobile) {
      setIsMobileMenuOpen(false);
    }
  }, [isMobile]);

  const sidebarItems = [
    { id: "market-watch", label: "MARKET WATCH", icon: LineChart },
    { id: "active-orders", label: "ACTIVE ORDERS", icon: BarChart3 },
    { id: "withdraw", label: "WITHDRAW", icon: ArrowUpFromLine, action: withdraw },
    { id: "trading-history", label: "TRADING HISTORY", icon: Clock },
    { id: "calendar", label: "CALENDAR", icon: Calendar },
    { id: "market-news", label: "MARKET NEWS", icon: Newspaper },
  ];

  // Mobile sidebar with Sheet component
  if (isMobile) {
    return (
      <>
        {/* Mobile sidebar - collapsed state (icons only) */}
        <div className="lg:flex flex-col bg-background border-r border-border w-[60px] z-10 hidden">
          <div className="flex flex-col items-center py-4 space-y-6">
            {sidebarItems.map((item) => {
              const Icon = item.icon;
              const isActive = !item.action && activeView === item.id;

              return (
                <button
                  key={item.id}
                  className={cn(
                    "flex flex-col items-center justify-center w-full py-2 px-1 transition-colors",
                    isActive
                      ? "text-primary bg-primary/10"
                      : "text-muted-foreground hover:text-foreground hover:bg-muted/50",
                  )}
                  onClick={() =>
                    item.action ? item.action() : toggleView(item.id as ActiveView)
                  }
                >
                  <Icon className="h-5 w-5" />
                </button>
              );
            })}
          </div>
        </div>

        {/* Mobile sidebar - expanded state (overlay) */}
        {activeView && (
          <div
            className={cn(
              "fixed inset-0 bg-background backdrop-blur-sm z-50 lg:hidden",
              isMobileMenuOpen ? "" : "",
            )}
            onClick={() => toggleView(activeView)}
          >
            <div
              className="absolute right-0 top-0 h-full w-[300px] bg-background border-r border-border overflow-y-auto"
              onClick={(e) => e.stopPropagation()}
            >
              {activeView === "market-watch" && (
                <MobilePanelWrapper
                  title="Market Watch"
                  onClose={() => toggleView(activeView)}
                >
                  <MarketWatchPanel addCurrencyPair={addCurrencyPair} />
                </MobilePanelWrapper>
              )}
              {activeView === "active-orders" && (
                <MobilePanelWrapper
                  title="Active Orders"
                  onClose={() => toggleView(activeView)}
                >
                  <ActiveOrdersPanel />
                </MobilePanelWrapper>
              )}
              {activeView === "trading-history" && (
                <MobilePanelWrapper
                  title="Trading History"
                  onClose={() => toggleView(activeView)}
                >
                  <TradingHistoryPanel />
                </MobilePanelWrapper>
              )}
              {activeView === "calendar" && (
                <MobilePanelWrapper
                  title="Calendar"
                  onClose={() => toggleView(activeView)}
                >
                  <CalendarPanel />
                </MobilePanelWrapper>
              )}
              {activeView === "market-news" && (
                <MobilePanelWrapper
                  title="Market News"
                  onClose={() => toggleView(activeView)}
                >
                  <MarketNewsPanel />
                </MobilePanelWrapper>
              )}
            </div>
          </div>
        )}

        <WithdrawFunds open={isDialogOpen} onOpenChange={setDialogOpen} />
      </>
    );
  }

  // Desktop sidebar
  return (
    <div
      className={cn(
        "flex flex-col bg-background border-r border-border transition-all duration-300 relative",
        expanded ? "w-[92px]" : "w-[60px]",
      )}
    >
      <Button
        variant="ghost"
        size="icon"
        className="absolute -right-3 top-4 h-6 w-6 rounded-full border border-border bg-background z-10"
        onClick={toggleSidebar}
      >
        {expanded ? (
          <ChevronLeft className="h-3 w-3" />
        ) : (
          <ChevronRight className="h-3 w-3" />
        )}
      </Button>

      <div className="flex flex-col items-center py-4 space-y-2 border-r-2 border-secondary h-full">
        {sidebarItems.map((item) => {
          const Icon = item.icon;
          // An action never looks selected -- there is no panel behind it to
          // be looking at.
          const isActive = !item.action && activeView === item.id;

          return (
            <button
              key={item.id}
              className={cn(
                "flex flex-col items-center justify-center w-full py-4 px-1 transition-colors",
                isActive
                  ? "bg-slate-700"
                  : "text-muted-foreground hover:text-foreground hover:bg-muted/50",
              )}
              onClick={() =>
                item.action ? item.action() : toggleView(item.id as ActiveView)
              }
            >
              <Icon className="h-5 w-5 mb-1" />
              <span
                className={cn(
                  "text-[9px] font-medium transition-all",
                  expanded ? "opacity-100" : "opacity-0 h-0",
                )}
              >
                {item.label}
              </span>
            </button>
          );
        })}
      </div>

      {/* The fallback for a session with no client area to be sent to. */}
      <WithdrawFunds open={isDialogOpen} onOpenChange={setDialogOpen} />
    </div>
  );
}

// Helper component for mobile panel headers
function MobilePanelWrapper({
  // title,
  onClose,
  children,
}: {
  title: string;
  onClose: () => void;
  children?: React.ReactNode;
}) {
  return (
    <div className="h-full flex flex-col">
      <div className="flex items-center justify-end px-4 pt-4 border-b border-border">
        {/* <h2 className="text-sm font-medium">{title}</h2> */}
        <Button
          variant="ghost"
          size="icon"
          className="h-7 w-7"
          onClick={onClose}
        >
          <X className="h-4 w-4" />
        </Button>
      </div>
      <div className="flex-1 overflow-auto">{children}</div>
    </div>
  );
}
