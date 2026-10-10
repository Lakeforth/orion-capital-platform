import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ChevronDown, Pencil, X } from "lucide-react";
import { cn } from "@/lib/utils";
import type { Trade } from "@/store/tradeStore";
import { formatTradePrice } from "@/lib/price-format";
import useAssetStore from "@/store/assetStore";
import useUserStore from "@/store/userStore.ts";
import { amountTone } from "@/lib/amount-tone";


interface MobileOrderCardProps {
  order: Trade;
  isHistory?: boolean;
  expandedOrderId: string | null;
  toggleOrderExpand: (orderId: string) => void;
  handleClosePosition?: (order: Trade) => void;
  handleEditTpSl?: (order: Trade) => void;
}

export function MobileOrderCard({
  order,
  isHistory = false,
  expandedOrderId,
  toggleOrderExpand,
  handleClosePosition,
  handleEditTpSl,
}: MobileOrderCardProps) {
  const isExpanded = expandedOrderId === order.id;

    const setActiveAsset = useAssetStore((state) => state.setActiveAsset);

    const { user } = useUserStore();

    // assets is read imperatively (not rendered) -- pulling it via getState()
    // instead of subscribing avoids re-rendering every order card on every
    // websocket price tick for every symbol on the platform.
    const handleAssetClick = (e: React.MouseEvent, symbol: string) => {
      e.stopPropagation(); // Prevent expanding the card when clicking the symbol
      const asset = useAssetStore
        .getState()
        .assets.find((a) => a.symbol_display === symbol);
      if (asset) {
        setActiveAsset(asset);
      }
    };


  return (
    <div
      className="mb-1 border border-muted rounded-sm overflow-hidden"
      onClick={() => toggleOrderExpand(order.id)}
    >
      <div className="p-2 bg-muted/30 flex justify-between items-center">
        <div className="flex items-center gap-1">
          <Badge
            variant={order.trade_type === "buy" ? "default" : "secondary"}
            className={cn(
              "text-xs px-1.5 py-0.5",
              order.trade_type === "buy" ? "bg-green-500" : "bg-red-500"
            )}
          >
            {order.trade_type.toUpperCase()}
          </Badge>
          <Button
            className="text-sm font-medium "
            variant="ghost"
            onClick={(e) => handleAssetClick(e, order.asset_symbol)}
          >
            {order.asset_symbol}
          </Button>
        </div>
        <div className="flex items-center gap-1">
          <span
            className={cn(
              "text-sm font-medium",
              amountTone(order.pnl)
            )}
          >
            {order.pnl >= 0
              ? `$${order.pnl.toFixed(2)}`
              : `-$${Math.abs(order.pnl).toFixed(2)}`}
          </span>
          <Button variant="ghost" size="icon" className="h-6 w-6">
            <ChevronDown
              className={cn(
                "h-3 w-3 transition-transform",
                isExpanded ? "rotate-180" : ""
              )}
            />
          </Button>
        </div>
      </div>

      {isExpanded && (
        <div className="p-2 space-y-1.5 bg-background">
          <div className="grid grid-cols-2 gap-1.5">
            <div>
              <div className="text-xs text-muted-foreground">ID</div>
              <div className="text-sm">{order.trade_id}</div>
            </div>
            <div>
              <div className="text-xs text-muted-foreground">Volume</div>
              <div className="text-sm">{(order.volume_lots ?? order.volume).toFixed(2)}</div>
            </div>
            <div>
              <div className="text-xs text-muted-foreground">Open Price</div>
              <div className="text-sm font-mono tabular-nums">{formatTradePrice(order.opening_price, order.asset_category, order.asset_symbol)}</div>
            </div>
            <div>
              <div className="text-xs text-muted-foreground">
                {isHistory ? "Close Price" : "Current Price"}
              </div>
              <div className="text-sm font-mono tabular-nums">{formatTradePrice(order.closing_price, order.asset_category, order.asset_symbol)}</div>
            </div>
            <div>
              <div className="text-xs text-muted-foreground">Open Time</div>
              <div className="text-sm">{order.open_time}</div>
            </div>
            {isHistory && (
              <div>
                <div className="text-xs text-muted-foreground">Close Time</div>
                <div className="text-sm">{order.close_time || "-"}</div>
              </div>
            )}
            <div>
              <div className="text-xs text-muted-foreground">Leverage</div>
              <div className="text-sm">x{order.leverage}</div>
            </div>
            <div>
              <div className="text-xs text-muted-foreground">Take Profit</div>
              <div className="text-sm">
                {order.take_profit > 0 ? order.take_profit : "-"}
              </div>
            </div>
            <div>
              <div className="text-xs text-muted-foreground">Stop Loss</div>
              <div className="text-sm">
                {order.stop_loss > 0 ? order.stop_loss : "-"}
              </div>
            </div>
            <div>
              <div className="text-xs text-muted-foreground">Margin</div>
              <div className="text-sm">${order.margin}</div>
            </div>
          </div>

          {!isHistory && (handleClosePosition || handleEditTpSl) && (
            <div className="flex gap-1.5 mt-2">
              {handleEditTpSl && (
                  <Button
                      size="sm"
                      variant="outline"
                      className="flex-1 text-xs h-7 gap-1"
                      onClick={() => handleEditTpSl(order)}
                  >
                    <Pencil className="h-3 w-3" />
                    Edit TP/SL
                  </Button>
              )}
              { user?.autotrader || !handleClosePosition ? null : (
                  <Button
                      size="sm"
                      variant="destructive"
                      className="flex-1 text-xs h-7 gap-1"
                      onClick={() => handleClosePosition(order)}
                  >
                    <X className="h-3 w-3" />
                    Close
                  </Button>
              )}
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default MobileOrderCard;
