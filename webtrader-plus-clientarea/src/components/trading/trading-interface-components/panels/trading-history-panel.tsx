import { useState, useEffect, useRef, useCallback } from "react";
import { ChevronDown, X, Loader2 } from "lucide-react";
import useTradeStore from "@/store/tradeStore";
import type { Trade } from "@/store/tradeStore";
import useAssetStore from "@/store/assetStore";
import { Button } from "@/components/ui/button";
import { formatTradePrice } from "@/lib/price-format";
import { amountTone, isZeroAmount } from "@/lib/amount-tone";


export default function TradingHistoryPanel() {
  const {
    closedTrades,
    isLoadingClosed,
    errorClosed,
    fetchMoreClosedTrades,
    hasMoreClosedTrades,
  } = useTradeStore();

  const observerRef = useRef<IntersectionObserver | null>(null);
  const loadMoreRef = useRef<HTMLDivElement>(null);

  // Setup intersection observer for infinite scrolling
  const lastElementRef = useCallback(() => {
    if (isLoadingClosed) return;

    if (observerRef.current) observerRef.current.disconnect();

    observerRef.current = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && hasMoreClosedTrades()) {
        fetchMoreClosedTrades();
      }
    });

    if (loadMoreRef.current) {
      observerRef.current.observe(loadMoreRef.current);
    }
  }, [isLoadingClosed, fetchMoreClosedTrades, hasMoreClosedTrades]);

  // Set up the observer when component mounts
  useEffect(() => {
    lastElementRef();
    return () => {
      if (observerRef.current) {
        observerRef.current.disconnect();
      }
    };
  }, [lastElementRef, closedTrades]);

  return (
    <div className="h-full bg-background flex flex-col">
      <div className="flex items-center justify-between p-4 border-b border-border">
        <h2 className="text-sm font-medium">Trading History</h2>
        <button className="text-muted-foreground hover:text-foreground">
          <X className="h-4 w-4" />
        </button>
      </div>

      <div className="flex-1 overflow-auto">
        {errorClosed && (
          <div className="text-red-500 text-center p-2">{errorClosed}</div>
        )}

        {closedTrades.length === 0 && !isLoadingClosed ? (
          <div className="text-center text-muted-foreground py-4">
            No trading history found
          </div>
        ) : (
          <div className="space-y-px">
            {closedTrades.map((trade) => (
              <TradeHistoryItem key={trade.id} trade={trade} />
            ))}

            {/* Loading indicator and intersection observer target */}
            <div ref={loadMoreRef} className="py-2 text-center">
              {isLoadingClosed && (
                <div className="flex justify-center items-center py-2">
                  <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

function TradeHistoryItem({ trade }: { trade: Trade }) {
  const [isExpanded, setIsExpanded] = useState(false);

  const formattedPnl = trade.pnl.toFixed(2);

  const setActiveAsset = useAssetStore((state) => state.setActiveAsset);

  // assets is read imperatively (not rendered) -- pulling it via getState()
  // instead of subscribing avoids re-rendering every history row on every
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
    <div className="p-3 border-b border-slate-700 hover:bg-muted/30">
      <div className="flex justify-between items-start">
        <div>
          <div className="flex items-center gap-1">
            <span className="text-xs font-medium">{trade?.open_time}</span>
            {/*<span className="text-xs text-muted-foreground">{date}</span>*/}
          </div>
          <Button
            variant="ghost"
            className="flex items-center gap-2 mt-1 pl-0"
            onClick={() => handleAssetClick(trade.asset_symbol)}
          >
            <CurrencyFlag
              assetId={trade.asset_id}
              symbol={trade.asset_symbol}
            />
            <span className="text-xs">{trade.asset_symbol}</span>
          </Button>
          <div className="text-xs text-muted-foreground mt-1">
            {trade.asset_id.split("-")[0].toUpperCase()}
          </div>
          <button
            className="text-xs text-muted-foreground flex items-center mt-1"
            onClick={() => setIsExpanded(!isExpanded)}
          >
            More{" "}
            <ChevronDown
              className={`h-3 w-3 ml-1 transition-transform ${
                isExpanded ? "rotate-180" : ""
              }`}
            />
          </button>
        </div>

        <div className="text-right">
          <div
            className={`text-xs font-medium ${
              amountTone(trade.pnl)
            }`}
          >
            {/* No "+" on a zero: a plus sign says the trade is ahead. */}
            {isZeroAmount(trade.pnl)
              ? `$${formattedPnl}`
              : trade.pnl > 0
                ? `+$${formattedPnl}`
                : `-$${Math.abs(Number.parseFloat(formattedPnl))}`}
          </div>
          <div className="text-xs text-muted-foreground mt-1">
            {(trade.volume_lots ?? trade.volume).toFixed(2)} {trade.asset_name}
          </div>
          <div className="text-xs text-muted-foreground mt-1">
            {formatTradePrice(trade.opening_price, trade.asset_category, trade.asset_symbol)} → {formatTradePrice(trade.closing_price, trade.asset_category, trade.asset_symbol)}
          </div>
        </div>
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
            <span className="text-muted-foreground">Open Time : </span>
            <span className="font-mono">{trade.open_time}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Close Time :</span>
            <span className="font-mono">{trade.close_time}</span>
          </div>

          <div className="flex justify-between">
            <span className="text-muted-foreground">Margin:</span>
            <span>${trade.margin.toFixed(2)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Leverage:</span>
            <span>x{trade.leverage}</span>
          </div>

          <div className="flex justify-between">
            <span className="text-muted-foreground">Open Price:</span>
            <span className="font-mono tabular-nums">{formatTradePrice(trade.opening_price, trade.asset_category, trade.asset_symbol)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Closed Price:</span>
            <span className="font-mono tabular-nums">{formatTradePrice(trade.closing_price, trade.asset_category, trade.asset_symbol)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Take Profit:</span>
            <span>{trade.take_profit > 0 ? trade.take_profit : "-"}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Stop Loss:</span>
            <span>{trade.stop_loss > 0 ? trade.stop_loss : "-"}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Volume:</span>
            <span>{(trade.volume_lots ?? trade.volume).toFixed(2)}</span>
          </div>
        </div>
      )}
    </div>
  );
}

function CurrencyFlag({
  assetId,
  symbol,
}: {
  assetId: string;
  symbol: string;
}) {
  const type = assetId.split("-")[0];

  if (type === "crypto") {
    return (
      <div className="h-4 w-4 rounded-full bg-[#F7931A] flex items-center justify-center">
        <svg
          width="8"
          height="8"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M23.6408 14.9193L20.5295 7.0493C19.1168 3.5383 15.5068 1.7143 11.9955 2.6493L4.1255 5.0443C2.3368 5.5943 0.8995 6.9323 0.2555 8.6443C-0.3888 10.3568 -0.0328 12.2318 1.1048 13.7438L5.3908 20.2438C6.5285 21.7558 8.3175 22.6908 10.1748 22.6908C10.8188 22.6908 11.4628 22.5818 12.0715 22.3638L19.9415 19.2528C21.7302 18.7028 23.1675 17.3648 23.8115 15.6528C24.4555 13.9408 24.0995 12.0658 22.9618 10.5538L21.6335 8.6443"
            stroke="white"
            strokeWidth="1.5"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
          <path
            d="M17.4776 14.3438C17.4776 14.3438 16.1493 15.8558 13.9296 16.5L11.9956 13.5273L9.99561 10.4053C11.9956 9.7613 13.4326 10.4053 13.4326 10.4053"
            stroke="white"
            strokeWidth="1.5"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
          <path
            d="M10.0651 16.5C8.9271 16.9408 7.5991 16.5 7.5991 16.5"
            stroke="white"
            strokeWidth="1.5"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
          <path
            d="M14.3672 10.4053C15.5052 9.9643 16.8332 10.4053 16.8332 10.4053"
            stroke="white"
            strokeWidth="1.5"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </svg>
      </div>
    );
  } else if (symbol.includes("Gold") || symbol.includes("XAU")) {
    return (
      <div className="h-5 w-5 rounded-full bg-[#FFD700] flex items-center justify-center text-black text-xs font-bold">
        Au
      </div>
    );
  } else {
    // For forex pairs
    const firstCurrency = symbol.split("/")[0];
    let color = "bg-blue-500";

    if (firstCurrency === "AUD") color = "bg-blue-500";
    else if (firstCurrency === "CAD") color = "bg-red-500";
    else if (firstCurrency === "EUR") color = "bg-blue-600";
    else if (firstCurrency === "USD") color = "bg-green-500";
    else if (firstCurrency === "GBP") color = "bg-purple-500";

    return (
      <div
        className={`h-5 w-5 rounded-full ${color} flex items-center justify-center text-xs text-white`}
      >
        {firstCurrency.charAt(0)}
      </div>
    );
  }
}
