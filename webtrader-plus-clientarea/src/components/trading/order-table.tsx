import { useState, useEffect, useRef, useCallback } from "react";
import { Button } from "@/components/ui/button";
import { ChevronDown, ChevronUp } from "lucide-react";
import { cn } from "@/lib/utils";
import { useMobile } from "@/hooks/use-mobile";
import useTradeStore from "@/store/tradeStore";
import type { Trade } from "@/store/tradeStore";
import useAssetStore from "@/store/assetStore";

import DesktopTradeTable from "@/components/trading/partials/DesktopTradeTable";
import AccountSummary from "@/components/trading/partials/AccountSummary";
import ClosePositionDialog from "@/components/trading/partials/ClosePositionDialog";
import MobileFilterDialog from "@/components/trading/partials/MobileFilterDialog";
import MobileSheet from "@/components/trading/partials/MobileSheet";
import { TakeProfitStopLossModal } from "@/components/trading/trading-interface-components/take-profit-stop-loss-modal";
import axiosInstance from "@/lib/axios.ts";
import {toast} from "sonner";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";

interface OrderTableProps {
  isFloating?: boolean;
}

export default function OrderTable({ isFloating = false }: OrderTableProps) {
  // UI state
  const [activeTab, setActiveTab] = useState<"active" | "history">("active");
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [showCloseDialog, setShowCloseDialog] = useState(false);
  const [closingTrade, setClosingTrade] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState<Trade | null>(null);
  const [expandedOrderId, setExpandedOrderId] = useState<string | null>(null);
  const [showFilters, setShowFilters] = useState(false);
  const [editingTrade, setEditingTrade] = useState<Trade | null>(null);
  const [savingTpSl, setSavingTpSl] = useState(false);
  const [closingAll, setClosingAll] = useState(false);
  // A per-tenant module, not a permission: some brokers simply do not want
  // the one-click bulk action offered. Closing positions one at a time is
  // never gated by it, and there is no bulk endpoint behind the button --
  // it loops the ordinary per-trade close -- so there is nothing to gate
  // server side either.
  const canCloseAllTrades = useSiteSettingsStore(
    (state) => state.settings?.close_all_trades_enabled === true,
  );
  // const [showAccountDetails, setShowAccountDetails] = useState(false);

  const isMobile = useMobile(768);

  useEffect(() => {
    if (isFloating) setIsCollapsed(false);
  }, [isFloating]);

  // Get trade data directly from the store
  const {
    openTrades,
    closedTrades,
    accountSummary,
    isLoadingOpen,
    isLoadingClosed,
    errorOpen,
    errorClosed,
      fetchOpenTrades,
    fetchClosedTrades,
    fetchMoreOpenTrades,
    fetchMoreClosedTrades,
    hasMoreOpenTrades,
    hasMoreClosedTrades,
  } = useTradeStore();

  const activeAsset = useAssetStore((state) => state.activeAsset);


  const observerRef = useRef<IntersectionObserver | null>(null);
  const loadMoreRef = useRef<HTMLDivElement>(null);

  // Setup intersection observer for infinite scrolling
  const lastElementRef = useCallback(() => {
    const isLoading = activeTab === "active" ? isLoadingOpen : isLoadingClosed;
    const hasMore =
      activeTab === "active" ? hasMoreOpenTrades() : hasMoreClosedTrades();
    const fetchMore =
      activeTab === "active" ? fetchMoreOpenTrades : fetchMoreClosedTrades;

    if (isLoading) return;

    if (observerRef.current) observerRef.current.disconnect();

    observerRef.current = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && hasMore) {
        fetchMore();
      }
    });

    if (loadMoreRef.current) {
      observerRef.current.observe(loadMoreRef.current);
    }
  }, [
    activeTab,
    isLoadingOpen,
    isLoadingClosed,
    hasMoreOpenTrades,
    hasMoreClosedTrades,
    fetchMoreOpenTrades,
    fetchMoreClosedTrades,
  ]);

  // Set up the observer when component mounts or tab changes
  useEffect(() => {
    lastElementRef();
    return () => {
      if (observerRef.current) {
        observerRef.current.disconnect();
      }
    };
  }, [lastElementRef, activeTab, openTrades, closedTrades]);

  const handleClosePosition = (order: Trade) => {
    setSelectedOrder(order);
    setShowCloseDialog(true);
  };

  const confirmClose = async () => {
    // Logic to close the position would go here
    // In a real implementation, you would call an API endpoint

    setClosingTrade(true)
    try {

      const response = await axiosInstance.post("/trade/"+selectedOrder?.id+"/close");

      console.log("Trade closed successfully:", response.data);

      // playSuccessSound();

      // The close endpoint returns no balance -- it's recomputed async
      // server-side. accountSummary.balance derives from useUserStore's
      // cached user.balance, which nothing else on this screen refreshes,
      // so pull it before fetchOpenTrades() recomputes accountSummary.
      // fetchClosedTrades() is called here too (not just left to the
      // .trade.updated websocket broadcast) so the just-closed trade shows
      // up in the Closed tab immediately -- waiting on the broadcast round
      // trip was the actual gap behind "closed trades aren't reflected
      // without delay", since the open-trades/balance refresh above was
      // already synchronous but the closed-trades list wasn't.
      await useUserStore.getState().getCurrentUser();
      await Promise.all([fetchOpenTrades(), fetchClosedTrades()]);

      setClosingTrade(false);

      // Show success toast notification
      toast.success(`Position successfully closed`, {
        // description: `${}`,
        position: "bottom-left",
        duration: 3000,
      });

    } catch (error) {
      console.error("Error executing trade:", error);
      // playErrorSound();
      toast.error("Failed to close trade", {
        description:
            "There was an error processing your trade. Please try again.",
        position: "top-right",
        duration: 5000,
      });
    } finally {
      setClosingTrade(false);
    }

    setShowCloseDialog(false);
  };

  const closeAllTrades = async () => {
    if (!openTrades.length || closingAll) return;
    if (!window.confirm(`Close all ${openTrades.length} open trade${openTrades.length === 1 ? "" : "s"}?`)) return;

    setClosingAll(true);
    try {
      const results = await Promise.allSettled(
        openTrades.map((trade) => axiosInstance.post(`/trade/${trade.id}/close`)),
      );
      const failed = results.filter((result) => result.status === "rejected").length;

      await useUserStore.getState().getCurrentUser();
      await Promise.all([fetchOpenTrades(), fetchClosedTrades()]);

      if (failed === 0) {
        toast.success("All open trades were closed", { position: "bottom-left" });
      } else {
        toast.error(`${failed} trade${failed === 1 ? "" : "s"} could not be closed`, {
          description: "The other open trades were closed successfully.",
          position: "top-right",
        });
      }
    } catch (error) {
      console.error("Error refreshing trades after close all:", error);
      toast.error("Trades were processed, but the list could not be refreshed", {
        description: "Reload the trade room to confirm the latest positions.",
        position: "top-right",
      });
    } finally {
      setClosingAll(false);
    }
  };

  const handleEditTpSl = (order: Trade) => setEditingTrade(order);

  const editingAsset = editingTrade
    ? useAssetStore.getState().assets.find(
        (a) => a.asset_id === editingTrade.asset_id || a.symbol_display === editingTrade.asset_symbol
      ) ?? null
    : null;

  const saveTpSl = async (takeProfit: number | undefined, stopLoss: number | undefined) => {
    if (!editingTrade) return;
    setSavingTpSl(true);
    try {
      await axiosInstance.post(`/trade/${editingTrade.id}/tp-sl`, {
        take_profit: takeProfit ?? null,
        stop_loss: stopLoss ?? null,
      });
      await fetchOpenTrades();
      setEditingTrade(null);
      toast.success("Take profit / stop loss updated", {
        position: "bottom-left",
        duration: 3000,
      });
    } catch (error: any) {
      toast.error("Could not update take profit / stop loss", {
        description: error?.response?.data?.message || "Please try again.",
        position: "top-right",
        duration: 5000,
      });
    } finally {
      setSavingTpSl(false);
    }
  };

  const toggleOrderExpand = (orderId: string) => {
    setExpandedOrderId(expandedOrderId === orderId ? null : orderId);
  };

  return (
    <div className="flex h-full min-h-0 w-full flex-col border border-muted bg-background text-foreground shadow-sm">
      {!isMobile && (
        <div className={cn("flex min-h-0 flex-col", !isCollapsed && "flex-1")}>
          <div className="border-b border-muted flex justify-between items-center sticky top-0 z-20 bg-background">
            <div className="flex overflow-x-auto scrollbar-hide">
              <button
                className={cn(
                  "rounded-none border-b-2 border-transparent px-4 py-2 whitespace-nowrap text-xs",
                  activeTab === "active"
                    ? "border-primary text-primary"
                    : "text-muted-foreground"
                )}
                onClick={() => setActiveTab("active")}
              >
                ACTIVE ORDERS
              </button>
              <button
                className={cn(
                  "rounded-none border-b-2 border-transparent px-4 py-2 whitespace-nowrap text-xs",
                  activeTab === "history"
                    ? "border-primary text-primary"
                    : "text-muted-foreground"
                )}
                onClick={() => setActiveTab("history")}
              >
                ORDERS HISTORY
              </button>
            </div>
            {!isFloating && <div className="mr-9 flex items-center">
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 mr-2"
                onClick={() => setIsCollapsed((collapsed) => !collapsed)}
                aria-label={isCollapsed ? "Show trade rows" : "Hide trade rows"}
                title={isCollapsed ? "Show trade rows" : "Hide trade rows"}
              >
                {isCollapsed ? (
                  <ChevronUp className="h-4 w-4" />
                ) : (
                  <ChevronDown className="h-4 w-4" />
                )}
              </Button>
            </div>}
          </div>

          {!isCollapsed && (
            <DesktopTradeTable
              trades={activeTab === "active" ? openTrades : closedTrades}
              isLoading={activeTab === "active" ? isLoadingOpen : isLoadingClosed}
              error={activeTab === "active" ? errorOpen : errorClosed}
              activeTab={activeTab}
              loadMoreRef={loadMoreRef}
              handleClosePosition={
                activeTab === "active" ? handleClosePosition : undefined
              }
              handleEditTpSl={activeTab === "active" ? handleEditTpSl : undefined}
            />
          )}
        </div>
      )}

      {/* Account summary section */}
      {!isCollapsed && <div className="sticky bottom-0 z-20 mt-auto shrink-0 bg-background">
        {isMobile
          ? activeAsset && (
              <AccountSummary
                accountData={accountSummary}
                asset={activeAsset}
                isDesktop={false}
              >
                <MobileSheet
                  activeTab={activeTab}
                  setActiveTab={setActiveTab}
                  setShowFilters={setShowFilters}
                  openTrades={openTrades}
                  closedTrades={closedTrades}
                  isLoadingOpen={isLoadingOpen}
                  isLoadingClosed={isLoadingClosed}
                  expandedOrderId={expandedOrderId}
                  toggleOrderExpand={toggleOrderExpand}
                  loadMoreRef={loadMoreRef}
                  handleClosePosition={handleClosePosition}
                  handleEditTpSl={handleEditTpSl}
                />
              </AccountSummary>
            )
          : activeAsset && (
              <AccountSummary
                accountData={accountSummary}
                asset={activeAsset}
                isDesktop={true}
                activeActions={
                  // Not rendered at all with nothing open, rather than
                  // rendered disabled: a permanently greyed-out "Close all
                  // trades" is a button that has never once been usable for a
                  // client who has never traded.
                  activeTab === "active" && canCloseAllTrades && openTrades.length > 0 ? (
                    <Button
                      type="button"
                      variant="destructive"
                      size="sm"
                      className="h-7 text-xs"
                      disabled={closingAll}
                      onClick={closeAllTrades}
                    >
                      {closingAll ? "Closing..." : "Close all trades"}
                    </Button>
                  ) : null
                }
              />
            )}
      </div>}

      <MobileFilterDialog open={showFilters} onOpenChange={setShowFilters} />

      <TakeProfitStopLossModal
        open={!!editingTrade}
        onOpenChange={(open) => {
          if (!open && !savingTpSl) setEditingTrade(null);
        }}
        title={
          editingTrade
            ? `EDIT TP / SL - ${editingTrade.asset_symbol} ${editingTrade.trade_type.toUpperCase()}`
            : "EDIT TP / SL"
        }
        submitLabel={savingTpSl ? "Saving..." : "Save"}
        isSubmitting={savingTpSl}
        currentTakeProfit={editingTrade?.take_profit}
        currentStopLoss={editingTrade?.stop_loss}
        asset={editingAsset}
        currentPrice={editingTrade?.closing_price}
        tradeType={editingTrade?.trade_type}
        onApplyValues={saveTpSl}
      />

      <ClosePositionDialog
        open={showCloseDialog}
        onOpenChange={setShowCloseDialog}
        selectedOrder={selectedOrder}
        isClosing={closingTrade}
        onConfirm={confirmClose}
      />
    </div>
  );
}
