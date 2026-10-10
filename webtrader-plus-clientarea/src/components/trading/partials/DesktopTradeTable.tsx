import React from "react";
import { Button } from "@/components/ui/button";
import { Loader2, Pencil, X } from "lucide-react";
import { cn } from "@/lib/utils";
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from "@/components/ui/table";
import type { Trade } from "@/store/tradeStore";
import { formatTradePrice } from "@/lib/price-format";
import useUserStore from "@/store/userStore.ts";
import useAssetStore from "@/store/assetStore";
import { amountTone } from "@/lib/amount-tone";

interface DesktopTradeTableProps {
  trades: Trade[];
  isLoading: boolean;
  error: string | null;
  activeTab: "active" | "history";
  loadMoreRef: React.RefObject<HTMLDivElement | null>; // Updated to allow null
  handleClosePosition?: (order: Trade) => void;
  handleEditTpSl?: (order: Trade) => void;
}

export function DesktopTradeTable({
  trades,
  isLoading,
  error,
  activeTab,
  loadMoreRef,
  handleClosePosition,
  handleEditTpSl,
}: DesktopTradeTableProps) {
  const user = useUserStore((state) => state.user);
  const canClose = !!handleClosePosition && !!user?.can_close_trade;
  const showActions = activeTab === "active" && (canClose || !!handleEditTpSl);
  const setActiveAsset = useAssetStore((state) => state.setActiveAsset);

  // assets is read imperatively (not rendered) -- pulling it via getState()
  // instead of subscribing avoids re-rendering this table on every
  // websocket price tick for every symbol on the platform.
  const handleAssetClick = (assetSymbol: string) => {
    const asset = useAssetStore
      .getState()
      .assets.find((a) => a.symbol_display === assetSymbol);
    if (asset) {
      setActiveAsset(asset);
    }
  };

  if (error) {
    return <div className="w-full p-4 text-center text-red-500">{error}</div>;
  }

  if (trades.length === 0 && !isLoading) {
    return (
      <div className="w-full p-4 text-center text-muted-foreground">
        No {activeTab === "active" ? "active" : "historical"} orders found
      </div>
    );
  }

  return (
    <div className="min-h-0 w-full flex-1 overflow-hidden">
      <div className="relative h-full min-h-[150px] overflow-y-auto">
        <Table>
          <TableHeader className="sticky top-0 z-10 bg-background border-b">
            <TableRow className="hover:bg-muted/30">
              <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                Symbol
              </TableHead>
              <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                ID
              </TableHead>
              <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                Type
              </TableHead>
              <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                Volume
              </TableHead>
              <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                Open Price
              </TableHead>
              <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                Open Time
              </TableHead>
              {activeTab === "history" && (
                <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                  Close Time
                </TableHead>
              )}
              <TableHead className="h-8 text-xs font-medium text-muted-foreground w-9">
                TP
              </TableHead>
              <TableHead className="h-8 text-xs font-medium text-muted-foreground w-9">
                SL
              </TableHead>
              <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                {activeTab === "active" ? "Price" : "Close Price"}
              </TableHead>
              <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                Leverage
              </TableHead>
              <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                Margin
              </TableHead>
              <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                PnL
              </TableHead>
              {showActions && (
                <TableHead className="h-8 text-xs font-medium text-muted-foreground">
                  Actions
                </TableHead>
              )}
            </TableRow>
          </TableHeader>
          <TableBody className="overflow-y-auto">
            {trades.map((trade) => (
              <TableRow key={trade.id} className="h-8 hover:bg-muted/30">
                <TableCell
                  className="py-1 text-xs text-muted-foreground cursor-pointer hover:text-primary"
                  onClick={() => handleAssetClick(trade.asset_symbol)}
                >
                  {trade.asset_symbol}
                </TableCell>
                <TableCell className="py-1 text-xs text-muted-foreground">
                  {trade.trade_id}
                </TableCell>
                <TableCell
                  className={cn(
                    "py-1 ",
                    trade.trade_type === "buy"
                      ? "text-primary"
                      : "text-destructive"
                  )}
                >
                  {trade.trade_type.toUpperCase()}
                </TableCell>
                <TableCell className="py-1 text-xs text-muted-foreground">
                  {(trade.volume_lots ?? trade.volume).toFixed(2)}
                </TableCell>
                <TableCell className="py-1 text-xs text-muted-foreground">
                  {formatTradePrice(trade.opening_price, trade.asset_category, trade.asset_symbol)}
                </TableCell>
                <TableCell className="py-1 text-xs text-muted-foreground">
                  {trade.open_time}
                </TableCell>
                {activeTab === "history" && (
                  <TableCell className="py-1 text-xs text-muted-foreground">
                    {trade.close_time || "-"}
                  </TableCell>
                )}
                <TableCell className="py-1 text-xs text-muted-foreground w-9">
                  {trade.take_profit > 0 ? trade.take_profit : "-"}
                </TableCell>
                <TableCell className="py-1 text-xs text-muted-foreground w-9">
                  {trade.stop_loss > 0 ? trade.stop_loss : "-"}
                </TableCell>
                <TableCell className="py-1 text-xs text-muted-foreground font-mono tabular-nums w-20">
                  {formatTradePrice(trade.closing_price, trade.asset_category, trade.asset_symbol)}
                </TableCell>
                <TableCell className="py-1 text-xs text-muted-foreground">
                  x{trade.leverage}
                </TableCell>
                <TableCell className="py-1 text-xs text-muted-foreground">
                  ${trade.margin}
                </TableCell>
                <TableCell
                  className={cn(
                    "py-1 text-xs",
                    amountTone(trade.pnl)
                  )}
                >
                  {trade.pnl >= 0
                    ? `$${trade.pnl.toFixed(2)}`
                    : `-$${Math.abs(trade.pnl).toFixed(2)}`}
                </TableCell>
                {showActions && (
                    <TableCell className="py-1">
                      <div className="flex gap-1">
                        {handleEditTpSl && (
                          <Button
                            size="sm"
                            variant="outline"
                            className="h-6 text-xs px-2 gap-1"
                            title="Edit take profit / stop loss"
                            onClick={() => handleEditTpSl(trade)}
                          >
                            <Pencil className="h-3 w-3" />
                            TP/SL
                          </Button>
                        )}
                        {canClose && (
                          <Button
                            size="sm"
                            variant="destructive"
                            className="h-6 text-xs px-2 gap-1"
                            onClick={() => handleClosePosition!(trade)}
                          >
                            <X className="h-3 w-3" />
                            Close
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  )}
              </TableRow>
            ))}
          </TableBody>
        </Table>

        {/* Loading indicator and intersection observer target */}
        <div
          ref={loadMoreRef}
          className="sticky bottom-0 py-1 text-center bg-background border-t"
        >
          {isLoading && (
            <div className="flex justify-center items-center py-1">
              <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default DesktopTradeTable;
