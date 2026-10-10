import React from "react";
import { Button } from "@/components/ui/button";
import { ChevronRight, Loader2 } from "lucide-react";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { cn } from "@/lib/utils";
import type { Trade } from "@/store/tradeStore";
import MobileOrderCard from "./MobileOrderCard";

interface MobileSheetProps {
  activeTab: "active" | "history";
  setActiveTab: (tab: "active" | "history") => void;
  setShowFilters: (show: boolean) => void;
  openTrades: Trade[];
  closedTrades: Trade[];
  isLoadingOpen: boolean;
  isLoadingClosed: boolean;
  expandedOrderId: string | null;
  toggleOrderExpand: (orderId: string) => void;
  loadMoreRef: React.RefObject<HTMLDivElement | null>; // Updated to allow null
  handleClosePosition: (order: Trade) => void;
  handleEditTpSl?: (order: Trade) => void;
}

export function MobileSheet({
  activeTab,
  setActiveTab,
  //   setShowFilters,
  openTrades,
  closedTrades,
  isLoadingOpen,
  isLoadingClosed,
  expandedOrderId,
  toggleOrderExpand,
  loadMoreRef,
  handleClosePosition,
  handleEditTpSl,
}: MobileSheetProps) {
  return (
    <Sheet>
      <SheetTrigger asChild>
        <Button
          variant="ghost"
          size="sm"
          className="text-xs text-muted-foreground flex items-center gap-1"
        >
          View all <ChevronRight className="h-3 w-3" />
        </Button>
      </SheetTrigger>
      <SheetContent side="bottom" className="h-[80vh] p-0">
        <div className="flex flex-col h-full">
          <div className="p-4 border-b border-border flex justify-between items-center">
            <h3 className="font-medium">
              {activeTab === "active" ? "Active Orders" : "Order History"}
            </h3>
            <div className="flex items-center gap-2">
              {/* <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8"
                                onClick={() => setShowFilters(true)}
                            >
                                <Filter className="h-4 w-4" />
                            </Button> */}
            </div>
          </div>
          <div className="flex border-b border-border">
            <button
              className={cn(
                "flex-1 py-2 text-center text-sm",
                activeTab === "active"
                  ? "border-b-2 border-primary text-primary"
                  : "text-muted-foreground"
              )}
              onClick={() => setActiveTab("active")}
            >
              ACTIVE ORDERS
            </button>
            <button
              className={cn(
                "flex-1 py-2 text-center text-sm",
                activeTab === "history"
                  ? "border-b-2 border-primary text-primary"
                  : "text-muted-foreground"
              )}
              onClick={() => setActiveTab("history")}
            >
              ORDERS HISTORY
            </button>
          </div>
          <div className="flex-1 overflow-auto p-4">
            {(activeTab === "active" ? openTrades : closedTrades).map(
              (trade) => (
                <MobileOrderCard
                  key={trade.id}
                  order={trade}
                  isHistory={activeTab === "history"}
                  expandedOrderId={expandedOrderId}
                  toggleOrderExpand={toggleOrderExpand}
                  handleClosePosition={
                    activeTab === "active" ? handleClosePosition : undefined
                  }
                  handleEditTpSl={
                    activeTab === "active" ? handleEditTpSl : undefined
                  }
                />
              )
            )}

            {/* Loading indicator and intersection observer target */}
            <div ref={loadMoreRef} className="py-2 text-center">
              {(activeTab === "active" ? isLoadingOpen : isLoadingClosed) && (
                <div className="flex justify-center items-center py-2">
                  <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                </div>
              )}
            </div>
          </div>
        </div>
      </SheetContent>
    </Sheet>
  );
}

export default MobileSheet;
