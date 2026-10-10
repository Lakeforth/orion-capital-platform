import { ChevronDown, ChevronUp, X } from "lucide-react";
import { useState, useRef, useCallback, useEffect } from "react";
import useTradeStore from "@/store/tradeStore";
import type { Trade } from "@/store/tradeStore";
import { cn } from "@/lib/utils";
import useDarkModeStore from "@/store/darkModeStore";
// import { useMobile } from "@/hooks/use-mobile";
import { toast } from "sonner";
import axiosInstance from "@/lib/axios";
import DesktopPositionTable from "../mt4/position-display/desktop-positionTable";
import ClosePositionDialog from "../mt4/position-display/close-position-dialog";
import useUserStore from "@/store/userStore";

export default function PositionDisplayLight() {
  // UI state
  const [activeTab, setActiveTab] = useState<"active" | "history">("active");
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState<Trade | null>(null);
  const [showCloseDialog, setShowCloseDialog] = useState(false);
  const [closingTrade, setClosingTrade] = useState(false);
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);

  // const isMobile = useMobile(768);

  // Get trade data from the store
  const {
    openTrades,
    closedTrades,
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
    setClosingTrade(true);
    try {
      const response = await axiosInstance.post(
        "/trade/" + selectedOrder?.id + "/close"
      );
      console.log("Trade closed successfully:", response.data);

      // The close endpoint returns no balance -- it's recomputed async
      // server-side. accountSummary.balance derives from useUserStore's
      // cached user.balance, which nothing else on this screen refreshes,
      // so pull it before fetchOpenTrades() recomputes accountSummary.
      // fetchClosedTrades() runs alongside it so the just-closed trade
      // shows up immediately instead of waiting on the .trade.updated
      // websocket broadcast round trip.
      await useUserStore.getState().getCurrentUser();
      await Promise.all([fetchOpenTrades(), fetchClosedTrades()]);

      toast.success("Position successfully closed", {
        position: "bottom-left",
        duration: 3000,
      });
    } catch (error) {
      console.error("Error closing trade:", error);
      toast.error("Failed to close trade", {
        description:
          "There was an error processing your request. Please try again.",
        position: "top-right",
        duration: 5000,
      });
    } finally {
      setClosingTrade(false);
      setShowCloseDialog(false);
    }
  };

  return (
    <div className="flex flex-col text-slate-900 w-full relative">
      <div className="relative">
        {/* Expanded view with slide animation */}
        <div
          className={cn(
            "transform transition-all duration-300 ease-in-out",
            isCollapsed ? "h-0 opacity-0" : "h-[190px] opacity-100"
          )}
        >
          <div
            className={`h-[190px] flex relative ${
              isDarkMode ? "bg-slate-900" : "bg-white"
            } shadow-lg backdrop-blur-sm border-t border-[#70707066]`}
          >
            {/* Terminal Vertical Bar */}
            <div className={`w-[25px] border-r border-[#70707066] flex flex-col items-center pt-1 ${isDarkMode ? "bg-slate-800" : "bg-[#F1F1F1]"}`}>
              <div className="cursor-pointer text-[#707070] mb-4">
                 <X size={14} />
              </div>
              <div className="flex-1 flex items-center justify-center">
                <span className={`[writing-mode:vertical-lr] rotate-180 text-[11px] font-bold ${isDarkMode ? "text-slate-300" : "text-[#2D3748]"}`}>
                  Terminal
                </span>
              </div>
            </div>

            <div className="flex-1 relative overflow-hidden">
              <div className="absolute inset-0 overflow-auto">
                <DesktopPositionTable
                  positions={activeTab === "active" ? openTrades : closedTrades}
                  isLoading={
                    activeTab === "active" ? isLoadingOpen : isLoadingClosed
                  }
                  error={activeTab === "active" ? errorOpen : errorClosed}
                  activeTab={activeTab}
                  loadMoreRef={loadMoreRef}
                  handleClosePosition={
                    activeTab === "active" ? handleClosePosition : undefined
                  }
                />
              </div>
            </div>

            {/* control tabs */}
            <div
              className={`flex justify-between items-center absolute bottom-0 left-0 right-0 z-20 h-[30px] border-t border-[#70707066] pl-1 ${
                isDarkMode ? "bg-slate-800" : "bg-[#F1F1F1]"
              }`}
            >
              <div className="flex items-center h-full">
                <button
                  className={cn(
                    "px-3 h-[26px] flex items-center mt-[-5px] text-[12px] font-bold transition-all",
                    activeTab === "active"
                      ? isDarkMode
                        ? "bg-slate-900 text-slate-200 border-x border-b border-[#70707066] shadow-[0_2px_4px_rgba(0,0,0,0.05)] z-20"
                        : "bg-white text-slate-900 border-x border-b border-[#70707066] shadow-[0_2px_4px_rgba(0,0,0,0.05)] z-20"
                      : isDarkMode
                      ? "bg-transparent text-slate-400 border-transparent"
                      : "bg-transparent text-gray-600 border-transparent hover:text-slate-900"
                  )}
                  onClick={() => setActiveTab("active")}
                >
                  Trade
                </button>
                <button
                  className={cn(
                    "px-3 h-[26px] flex items-center mt-[-5px] text-[12px] font-bold transition-all",
                    activeTab === "history"
                      ? isDarkMode
                        ? "bg-slate-900 text-slate-200 border-x border-b border-[#70707066] shadow-[0_2px_4px_rgba(0,0,0,0.05)] z-20"
                        : "bg-white text-slate-900 border-x border-b border-[#70707066] shadow-[0_2px_4px_rgba(0,0,0,0.05)] z-20"
                      : isDarkMode
                      ? "bg-transparent text-slate-400 border-transparent"
                      : "bg-transparent text-gray-600 border-transparent hover:text-slate-900"
                  )}
                  onClick={() => setActiveTab("history")}
                >
                  Account History
                </button>
              </div>
              <div className="flex items-center">
                <button
                  onClick={() => setIsCollapsed(true)}
                  className={`p-1 ${
                    isDarkMode
                      ? "text-slate-500 hover:text-slate-400"
                      : "text-slate-400 hover:text-slate-300"
                  } transition-colors flex items-center`}
                >
                  <ChevronDown className="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Collapsed view with slide animation */}
        <div
          className={cn(
            "transform transition-all duration-300 ease-in-out absolute bottom-0 left-0 right-0",
            isCollapsed
              ? "translate-y-0 opacity-100"
              : "translate-y-full opacity-0"
          )}
        >
          <div
            className={`flex justify-between items-center relative z-10 h-[30px] border-t border-[#70707066] pl-1 ${
              isDarkMode ? "bg-slate-800" : "bg-[#F1F1F1]"
            }`}
          >
            <div className="flex items-center h-full">
              <button
                className={cn(
                  "px-3 h-[26px] flex items-center mt-[-5px] text-[12px] font-bold transition-all",
                  activeTab === "active"
                    ? isDarkMode
                      ? "bg-slate-900 text-slate-200 border-x border-b border-[#70707066] shadow-[0_2px_4px_rgba(0,0,0,0.05)] z-20"
                      : "bg-white text-slate-900 border-x border-b border-[#70707066] shadow-[0_2px_4px_rgba(0,0,0,0.05)] z-20"
                    : isDarkMode
                    ? "bg-transparent text-slate-400 border-transparent"
                    : "bg-transparent text-gray-600 border-transparent hover:text-slate-900"
                )}
              >
                Trade
              </button>
              <button
                className={cn(
                  "px-3 h-[26px] flex items-center mt-[-5px] text-[12px] font-bold transition-all",
                  activeTab === "history"
                    ? isDarkMode
                      ? "bg-slate-900 text-slate-200 border-x border-b border-[#70707066] shadow-[0_2px_4px_rgba(0,0,0,0.05)] z-20"
                      : "bg-white text-slate-900 border-x border-b border-[#70707066] shadow-[0_2px_4px_rgba(0,0,0,0.05)] z-20"
                    : isDarkMode
                    ? "bg-transparent text-slate-400 border-transparent"
                    : "bg-transparent text-gray-600 border-transparent hover:text-slate-900"
                )}
              >
                Account History
              </button>
            </div>
            <div className="flex items-center">
              <button
                onClick={() => setIsCollapsed(false)}
                className={`p-1 ${
                  isDarkMode
                    ? "text-slate-500 hover:text-slate-400"
                    : "text-slate-400 hover:text-slate-300"
                } transition-colors flex items-center gap-2`}
              >
                <span
                  className={`text-xs ${
                    isDarkMode ? "text-slate-200" : "text-slate-900"
                  }`}
                >
                  Show Positions
                </span>
                <ChevronUp className="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <ClosePositionDialog
        open={showCloseDialog}
        onOpenChange={setShowCloseDialog}
        selectedOrder={selectedOrder}
        onConfirm={confirmClose}
        isClosing={closingTrade}
      />
    </div>
  );
}
