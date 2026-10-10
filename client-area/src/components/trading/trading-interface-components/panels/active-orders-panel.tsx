import { useState, useEffect, useRef, useCallback } from "react";
import { ChevronDown, X, Loader2 } from "lucide-react";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import useTradeStore from "@/store/tradeStore";
import type { Trade } from "@/store/tradeStore";
import { formatTradePrice, formatVolume } from "@/lib/price-format";
import useAssetStore from "@/store/assetStore";
import { Button } from "@/components/ui/button";
import { amountTone, isZeroAmount } from "@/lib/amount-tone";

export default function ActiveOrdersPanel() {
  const [activeTab, setActiveTab] = useState("active");
  const {
    openTrades,
    isLoadingOpen,
    errorOpen,
    fetchMoreOpenTrades,
    hasMoreOpenTrades,
  } = useTradeStore();

  const observerRef = useRef<IntersectionObserver | null>(null);
  const loadMoreRef = useRef<HTMLDivElement>(null);

  // Setup intersection observer for infinite scrolling
  const lastElementRef = useCallback(() => {
    if (isLoadingOpen) return;

    if (observerRef.current) observerRef.current.disconnect();

    observerRef.current = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && hasMoreOpenTrades()) {
        fetchMoreOpenTrades();
      }
    });

    if (loadMoreRef.current) {
      observerRef.current.observe(loadMoreRef.current);
    }
  }, [isLoadingOpen, fetchMoreOpenTrades, hasMoreOpenTrades]);

  // Set up the observer when component mounts
  useEffect(() => {
    lastElementRef();
    return () => {
      if (observerRef.current) {
        observerRef.current.disconnect();
      }
    };
  }, [lastElementRef, openTrades]);

  // Group trades by asset for better organization
  const groupedTrades = openTrades.reduce<Record<string, Trade[]>>(
    (acc, trade) => {
      if (!acc[trade.asset_symbol]) {
        acc[trade.asset_symbol] = [];
      }
      acc[trade.asset_symbol].push(trade);
      return acc;
    },
    {}
  );

  return (
    <div className="h-full bg-background flex flex-col">
      <div className="flex items-center justify-between p-4 border-b border-border">
        <h2 className="text-sm font-medium">Active Orders</h2>
        <button className="text-muted-foreground hover:text-foreground">
          <X className="h-4 w-4" />
        </button>
      </div>

      <Tabs
        value={activeTab}
        onValueChange={setActiveTab}
        className="flex-1 flex flex-col"
      >
        <TabsList className="bg-transparent h-10 p-0 border-b border-border rounded-none">
          <TabsTrigger
            value="active"
            className="flex-1 rounded-none data-[state=active]:border-b-2 data-[state=active]:border-primary data-[state=active]:shadow-none data-[state=active]:bg-transparent h-full"
          >
            <span
              className={`text-xs font-medium w-full ${
                activeTab === "active"
                  ? ""
                  : "text-muted-foreground"
              }`}
            >
              ACTIVE
            </span>
          </TabsTrigger>
          {/*<TabsTrigger*/}
          {/*  value="pending"*/}
          {/*  className="flex-1 rounded-none data-[state=active]:border-b-2 data-[state=active]:border-primary data-[state=active]:shadow-none data-[state=active]:bg-transparent h-full"*/}
          {/*>*/}
          {/*  <span*/}
          {/*    className={`text-xs font-medium ${*/}
          {/*      activeTab === "pending"*/}
          {/*        ? "text-primary"*/}
          {/*        : "text-muted-foreground"*/}
          {/*    }`}*/}
          {/*  >*/}
          {/*    PENDING*/}
          {/*  </span>*/}
          {/*</TabsTrigger>*/}
        </TabsList>

        <div className="p-3 overflow-y-auto">
          {activeTab === "active" ? (
            <div className="space-y-3">
              {errorOpen && (
                <div className="text-red-500 text-center p-2">{errorOpen}</div>
              )}

              {Object.entries(groupedTrades).length === 0 && !isLoadingOpen && (
                <div className="text-center text-muted-foreground py-4">
                  No active orders found
                </div>
              )}

              {Object.entries(groupedTrades).map(([assetSymbol, trades]) => (
                <div key={assetSymbol} className="mb-1">
                  <div className="bg-slate-700 p-1 mb-2">
                    <span className="text-xs font-medium">{assetSymbol}</span>
                  </div>

                  <div className="space-y-1">
                    {trades.map((trade) => (
                      <TradeItem key={trade.id} trade={trade} />
                    ))}
                  </div>
                </div>
              ))}

              {/* Loading indicator and intersection observer target */}
              <div ref={loadMoreRef} className="py-2 text-center">
                {isLoadingOpen && (
                  <div className="flex justify-center items-center py-2">
                    <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                  </div>
                )}
              </div>
            </div>
          ) : (
            <div className="text-center text-muted-foreground py-4">
              No pending orders available
            </div>
          )}
        </div>
      </Tabs>
    </div>
  );
}

function TradeItem({ trade }: { trade: Trade }) {
  const [isExpanded, setIsExpanded] = useState(false);

  const formattedPnl = trade.pnl.toFixed(2);

  const setActiveAsset = useAssetStore((state) => state.setActiveAsset);

  // assets is read imperatively (not rendered) -- pulling it via getState()
  // instead of subscribing avoids re-rendering every order row on every
  // websocket price tick for every symbol on the platform.
  const handleAssetClick = (assetSymbol: string) => {
    const asset = useAssetStore
      .getState()
      .assets.find((a) => a.symbol_display === assetSymbol);
    if (asset) {
      setActiveAsset(asset);
    }
  };

  return (
    <div className="border-b border-border pb-3 text-xs">
      <div className="flex items-center justify-between">
        <Button
          variant="ghost"
          className="flex items-center gap-2 pl-0"
          onClick={() => handleAssetClick(trade.asset_symbol)}
        >
          <CryptoIcon type={trade.asset_id} />
          <span className="">{trade.asset_name}</span>
        </Button>
        <span
          className={`font-medium ${
            amountTone(trade.pnl)
          }`}
        >
          {/* No "+" on a zero: a plus sign says the trade is ahead. */}
          {isZeroAmount(trade.pnl)
            ? `$${formattedPnl}`
            : trade.pnl > 0
              ? `+$${formattedPnl}`
              : `-$${Math.abs(Number.parseFloat(formattedPnl))}`}
        </span>
      </div>

      <div className="flex items-center justify-between mt-1">
        <button
          className=" text-muted-foreground flex items-center"
          onClick={() => setIsExpanded(!isExpanded)}
        >
          Show more{" "}
          <ChevronDown
            className={`h-3 w-3 ml-1 transition-transform ${
              isExpanded ? "rotate-180" : ""
            }`}
          />
        </button>
        <span className="text-xs text-muted-foreground">{trade.open_time}</span>
      </div>

      {isExpanded && (
        <div className="mt-2 pt-2 border-t border-border/50 grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
          <div className="flex justify-between">
            <span className="text-muted-foreground">ID:</span>
            <span className="font-mono">{trade.trade_id}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Type:</span>
            <span
              className={
                trade.trade_type === "buy" ? "text-green-500" : "text-red-500"
              }
            >
              {trade.trade_type.toUpperCase()}
            </span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Margin:</span>
            <span>${trade.margin.toFixed(2)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Volume:</span>
            <span>{formatVolume(trade.volume_lots ?? trade.volume)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Open Price:</span>
            <span className="font-mono tabular-nums">{formatTradePrice(trade.opening_price, trade.asset_category, trade.asset_symbol)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Current Price:</span>
            <span className="font-mono tabular-nums">{formatTradePrice(trade.closing_price, trade.asset_category, trade.asset_symbol)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Leverage:</span>
            <span>x{trade.leverage}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Take Profit:</span>
            <span>{trade.take_profit > 0 ? trade.take_profit : "-"}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Stop Loss:</span>
            <span>{trade.stop_loss > 0 ? trade.stop_loss : "-"}</span>
          </div>
        </div>
      )}
    </div>
  );
}

function CryptoIcon({ type }: { type: string }) {
  let bgColor = "bg-gray-500";
  let content = type.charAt(0).toUpperCase();

  switch (type.toLowerCase()) {
    case "crypto":
      bgColor = "bg-orange-500";
      content = "₿";
      break;
    case "forex":
      bgColor = "bg-blue-500";
      content = "F";
      break;
    case "stocks":
      bgColor = "bg-green-500";
      content = "S";
      break;
  }

  return (
    <div
      className={`h-4 w-4 rounded-full ${bgColor} flex items-center justify-center text-[10px] text-white font-medium`}
    >
      {content}
    </div>
  );
}
