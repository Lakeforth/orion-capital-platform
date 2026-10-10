import React from "react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from "@/components/ui/table";
import useDarkModeStore from "@/store/darkModeStore";
import type { Trade } from "@/store/tradeStore";
import { formatTradePrice } from "@/lib/price-format";
// import useUserStore from "@/store/userStore.ts";
import useAssetStore from "@/store/assetStore";
import { NotebookPen } from "lucide-react";

interface DesktopPositionTableProps {
  positions: Trade[];
  isLoading: boolean;
  error: string | null;
  activeTab: "active" | "history";
  loadMoreRef: React.RefObject<HTMLDivElement | null>;
  handleClosePosition?: (order: Trade) => void;
}

export function DesktopPositionTable({
  positions,
  isLoading,
  error,
  activeTab,
  loadMoreRef,
  handleClosePosition,
}: DesktopPositionTableProps) {
  // const user = useUserStore((state) => state.user);
  const setActiveAsset = useAssetStore((state) => state.setActiveAsset);
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);

  // Provide default empty array if positions is undefined
  const safePositions = positions || [];

  // assets is read imperatively (not rendered) -- pulling it via getState()
  // instead of subscribing avoids re-rendering this table on every
  // websocket price tick for every symbol on the platform, not just ones
  // relevant to open positions here.
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

  return (
    <div className="h-[calc(200px-38px)] w-full overflow-hidden flex flex-col">
      <div className="w-full overflow-x-auto overflow-y-auto flex-1">
        <Table
          className={`min-w-[800px] w-full ${
            isDarkMode ? "border-slate-600" : "border-slate-200"
          }`}
        >
          <TableHeader
            className={`sticky top-0 z-10 ${
              isDarkMode
                ? "bg-slate-900 border-slate-600"
                : "bg-white border-slate-200"
            } border`}
          >
            <TableRow
              className={`border-b ${
                isDarkMode ? "border-slate-600" : "border-slate-300"
              } hover:bg-transparent`}
            >
              <TableHead
                className={`h-6 text-[11px] font-medium ${
                  isDarkMode
                    ? "text-slate-200 border-slate-600"
                    : "text-slate-900 border-slate-300"
                } w-[11%] border-r-1 px-1`}
              >
                Order
              </TableHead>
              <TableHead
                className={`h-6 text-[11px] font-medium ${
                  isDarkMode
                    ? "text-slate-200 border-slate-600"
                    : "text-slate-900 border-slate-300"
                } w-[11%] border-r-1 px-1`}
              >
                Symbol
              </TableHead>
              <TableHead
                className={`h-6 text-[11px] font-medium ${
                  isDarkMode
                    ? "text-slate-200 border-slate-600"
                    : "text-slate-900 border-slate-300"
                } w-[11%] border-r-1 px-1`}
              >
                Type
              </TableHead>
              <TableHead
                className={`h-6 text-[11px] font-medium ${
                  isDarkMode
                    ? "text-slate-200 border-slate-600"
                    : "text-slate-900 border-slate-300"
                } w-[11%] border-r-1 px-1`}
              >
                Volume
              </TableHead>
              <TableHead
                className={`h-6 text-[11px] font-medium ${
                  isDarkMode
                    ? "text-slate-200 border-slate-600"
                    : "text-slate-900 border-slate-300"
                } w-[11%] border-r-1 px-1`}
              >
                Open Time
              </TableHead>
              {activeTab === "history" && (
                <TableHead
                  className={`h-6 text-[11px] font-medium ${
                    isDarkMode
                      ? "text-slate-200 border-slate-600"
                      : "text-slate-900 border-slate-300"
                  } w-[11%] border-r-1 px-1`}
                >
                  Close Time
                </TableHead>
              )}
              <TableHead
                className={`h-6 text-[11px] font-medium ${
                  isDarkMode
                    ? "text-slate-200 border-slate-600"
                    : "text-slate-900 border-slate-300"
                } w-[11%] border-r-1 px-1`}
              >
                TP
              </TableHead>
              <TableHead
                className={`h-6 text-[11px] font-medium ${
                  isDarkMode
                    ? "text-slate-200 border-slate-600"
                    : "text-slate-900 border-slate-300"
                } w-[11%] border-r-1 px-1`}
              >
                SL
              </TableHead>
              <TableHead
                className={`h-6 text-[11px] font-medium ${
                  isDarkMode
                    ? "text-slate-200 border-slate-600"
                    : "text-slate-900 border-slate-300"
                } w-[11%] border-r-1 px-1`}
              >
                {activeTab === "active" ? "Price" : "Close Price"}
              </TableHead>
              <TableHead
                className={`h-6 text-[11px] font-medium ${
                  isDarkMode
                    ? "text-slate-200 border-slate-600"
                    : "text-slate-900 border-slate-300"
                } text-right w-[11%] border-r-1 px-1`}
              >
                P/L
              </TableHead>
              <TableHead
                className={`h-6 text-[11px] font-medium ${
                  isDarkMode
                    ? "text-slate-200 border-slate-600"
                    : "text-slate-900 border-slate-300"
                } text-right w-[11%] border-r-1 px-1`}
              >
                Commission
              </TableHead>
              <TableHead
                className={`h-6 text-[11px] font-medium ${
                  isDarkMode
                    ? "text-slate-200 border-slate-600"
                    : "text-slate-900 border-slate-300"
                } text-right w-[11%] border-r-1 px-1`}
              >
                Swap
              </TableHead>
              {activeTab === "active" && handleClosePosition && (
                <TableHead
                  className={`h-6 text-[11px] font-medium ${
                    isDarkMode ? "text-slate-200" : "text-slate-900"
                  } w-[11%] px-1`}
                >
                  Close
                </TableHead>
              )}
            </TableRow>
          </TableHeader>
          <TableBody
            className={`border-b ${
              isDarkMode ? "border-slate-600" : "border-slate-300"
            }`}
          >
            {safePositions.length === 0 && !isLoading ? (
              <TableRow>
                <TableCell
                  colSpan={13}
                  className={`h-6 py-4 text-center ${
                    isDarkMode ? "text-slate-500" : "text-slate-400"
                  }`}
                >
                  No {activeTab === "active" ? "active" : "historical"}{" "}
                  positions found
                </TableCell>
              </TableRow>
            ) : (
              safePositions.map((position) => (
                <TableRow
                  key={position.id}
                  className={`border-b ${
                    isDarkMode
                      ? "border-slate-600 bg-slate-900"
                      : "border-slate-300 bg-white"
                  } hover:bg-[#0866C6] group transition-colors`}
                >
                  <TableCell
                    className={`h-6 py-0.5 px-1 text-[11px] font-medium border-r-1 group-hover:text-white transition-colors ${
                      isDarkMode
                        ? "border-slate-600 text-slate-200"
                        : "border-slate-300 text-slate-900"
                    }`}
                  >
                    <div className="flex gap-2 items-center">
                      <NotebookPen size={12} />
                      {position.trade_id}
                    </div>
                  </TableCell>
                  <TableCell
                    onClick={() => handleAssetClick(position.asset_symbol)}
                    className={`h-6 py-0.5 px-1 text-[11px] font-medium cursor-pointer hover:text-primary transition-colors border-r-1 group-hover:text-white ${
                      isDarkMode
                        ? "border-slate-600 text-slate-200"
                        : "border-slate-300 text-slate-900"
                    }`}
                  >
                    {position.asset_symbol}
                  </TableCell>
                  <TableCell
                    className={cn(
                      "h-6 py-0.5 px-1 text-[11px] font-medium border-r-1 border-slate-300 group-hover:text-white transition-colors",
                      position.trade_type === "buy"
                        ? "text-green-500"
                        : "text-red-500",
                    )}
                  >
                    {position.trade_type.toUpperCase()}
                  </TableCell>
                  <TableCell
                    className={`h-6 py-0.5 px-1 text-[11px] border-r-1 group-hover:text-white transition-colors ${
                      isDarkMode
                        ? "border-slate-600 text-slate-200"
                        : "border-slate-300 text-slate-900"
                    }`}
                  >
                    {(position.volume_lots ?? position.volume).toFixed(2)}
                  </TableCell>
                  <TableCell
                    className={`h-6 py-0.5 px-1 text-[11px] border-r-1 group-hover:text-white transition-colors ${
                      isDarkMode
                        ? "border-slate-600 text-slate-200"
                        : "border-slate-300 text-slate-900"
                    }`}
                  >
                    {position.open_time}
                  </TableCell>
                  {activeTab === "history" && (
                    <TableCell
                      className={`h-6 py-0.5 px-1 text-[11px] border-r-1 group-hover:text-white transition-colors ${
                        isDarkMode
                          ? "border-slate-600 text-slate-200"
                          : "border-slate-300 text-slate-900"
                      }`}
                    >
                      {position.close_time}
                    </TableCell>
                  )}
                  <TableCell
                    className={`h-6 py-0.5 px-1 text-[11px] border-r-1 group-hover:text-white transition-colors ${
                      isDarkMode
                        ? "border-slate-600 text-slate-200"
                        : "border-slate-300 text-slate-900"
                    }`}
                  >
                    {position.take_profit || "-"}
                  </TableCell>
                  <TableCell
                    className={`h-6 py-0.5 px-1 text-[11px] border-r-1 group-hover:text-white transition-colors ${
                      isDarkMode
                        ? "border-slate-600 text-slate-200"
                        : "border-slate-300 text-slate-900"
                    }`}
                  >
                    {position.stop_loss || "-"}
                  </TableCell>
                  <TableCell
                    className={`h-6 py-0.5 px-1 text-[11px] border-r-1 group-hover:text-white transition-colors font-mono tabular-nums w-[76px] ${
                      isDarkMode
                        ? "border-slate-600 text-slate-200"
                        : "border-slate-300 text-slate-900"
                    }`}
                  >
                    {formatTradePrice(position.closing_price, position.asset_category, position.asset_symbol)}
                  </TableCell>
                  <TableCell
                    className={`h-6 py-0.5 px-1 text-[11px] text-right border-r-1 group-hover:text-white transition-colors ${
                      isDarkMode
                        ? "border-slate-600 text-slate-200"
                        : "border-slate-300 text-slate-900"
                    }`}
                  >
                    {position.pnl.toFixed(2)}
                  </TableCell>
                  <TableCell
                    className={`h-6 py-0.5 px-1 text-[11px] text-right border-r-1 group-hover:text-white transition-colors ${
                      isDarkMode
                        ? "border-slate-600 text-slate-200"
                        : "border-slate-300 text-slate-900"
                    }`}
                  >
                    {position.paid_commission.toFixed(2)}
                  </TableCell>
                  <TableCell
                    className={`h-6 py-0.5 px-1 text-[11px] text-right border-r-1 group-hover:text-white transition-colors ${
                      isDarkMode
                        ? "border-slate-600 text-slate-200"
                        : "border-slate-300 text-slate-900"
                    }`}
                  >
                    0.00
                  </TableCell>
                  {activeTab === "active" && handleClosePosition && (
                    <TableCell className="h-6 py-0.5 px-1 text-[11px]">
                      <Button
                        variant="ghost"
                        size="icon"
                        className={`h-4 w-4 p-0 ${
                          isDarkMode
                            ? "text-slate-200 hover:text-white"
                            : "text-slate-900 hover:text-black"
                        }`}
                        onClick={() => handleClosePosition(position)}
                      >
                        ✕
                      </Button>
                    </TableCell>
                  )}
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      <div ref={loadMoreRef} className="w-full h-0" />
    </div>
  );
}

export default DesktopPositionTable;
