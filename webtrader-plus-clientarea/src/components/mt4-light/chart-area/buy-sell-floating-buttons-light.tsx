import type { PointerEvent } from "react";
import TradingInterface from "@/components/mt4/trading-interface-copy";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import useAssetStore from "@/store/assetStore";
import { formatTradePrice, spreadQuote } from "@/lib/price-format";
import useDarkModeStore from "@/store/darkModeStore";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { toast } from "sonner";

const AUTO_TRADER_LOCK_MESSAGE =
  "Auto trader is currently enabled on your account please contact support....";

export default function BuySellFloatingButtonsLight() {
  const activeAsset = useAssetStore((state) => state.activeAsset);
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);
  const user = useUserStore((state) => state.user);
  const enableAutotrader = useSiteSettingsStore(
    (state) => state.settings?.enable_autotrader === true
  );
  const isAutoTraderLocked = enableAutotrader && Boolean(user?.autotrader);
  const buyPrice = activeAsset?.buy_price ?? 0;
  const sellPrice = activeAsset?.sell_price ?? 0;

  // Pips/points, not a unitless price difference -- see the same fix in
  // components/mt4/main-content/buy-sell-floating-buttons.tsx.
  const spread = activeAsset
    ? spreadQuote({
        price: Number.parseFloat(activeAsset.rate || "0"),
        buySpreadPercent: Number.parseFloat(activeAsset.buy_spread || "0"),
        sellSpreadPercent: Number.parseFloat(activeAsset.sell_spread || "0"),
        category: activeAsset.type,
        symbol: activeAsset.sy ?? "",
        contractSize: activeAsset.contract_size,
        volumeLots: 0,
        pipSizeOverride: activeAsset.pip_size,
        quotedSpread: activeAsset.buy_price - activeAsset.sell_price,
      })
    : null;

  const handleTriggerPointerDown = (event: PointerEvent<HTMLButtonElement>) => {
    if (isAutoTraderLocked) {
      event.preventDefault();
      toast.error(AUTO_TRADER_LOCK_MESSAGE);
    }
  };

  return (
    <div className="relative flex items-center w-fit">
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            className={`bg-[#48BB78] hover:bg-[#38A169] text-white pr-7 h-7 shadow-none font-bold text-[10px] w-[100px] rounded-l-md rounded-r-none border-none ${
              isAutoTraderLocked
                ? "opacity-60 cursor-not-allowed hover:bg-[#48BB78]"
                : ""
            }`}
            onPointerDown={handleTriggerPointerDown}
          >
            <span className="flex flex-col items-start gap-0 w-full leading-tight">
              <span className="text-[10px]">BUY</span>
              <span className="text-[10px] font-medium tabular-nums">
                {formatTradePrice(buyPrice, activeAsset?.type, activeAsset?.sy ?? "")}
              </span>
            </span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          className={`p-0 w-[250px] ${
            isDarkMode ? "bg-[#1C2030]" : "bg-white"
          }`}
        >
          <TradingInterface type="buy" />
        </DropdownMenuContent>
      </DropdownMenu>

      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger asChild>
            <div
              className="absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2 rounded-sm z-10 bg-black text-white flex items-center justify-center px-1.5 h-5 border border-slate-700"
            >
              <p className="text-[10px] font-bold tabular-nums">
                {spread ? `${spread.units.toFixed(1)} ${spread.unitLabel}` : "-"}
              </p>
            </div>
          </TooltipTrigger>
          <TooltipContent
            className={`${
              isDarkMode
                ? "bg-slate-800 text-white border-slate-700"
                : "bg-white text-slate-900 border-slate-200"
            }`}
          >
            <p className="text-xs">Spread</p>
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            className={`bg-[#F56565] hover:bg-[#E53E3E] text-white pl-7 h-7 shadow-none font-bold text-[10px] w-[100px] rounded-r-md rounded-l-none border-none ${
              isAutoTraderLocked
                ? "opacity-60 cursor-not-allowed hover:bg-[#F56565]"
                : ""
            }`}
            onPointerDown={handleTriggerPointerDown}
          >
            <span className="flex flex-col items-end gap-0 w-full leading-tight">
              <span className="text-[10px]">SELL</span>
              <span className="text-[10px] font-medium tabular-nums">
                {formatTradePrice(sellPrice, activeAsset?.type, activeAsset?.sy ?? "")}
              </span>
            </span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          className={`p-0 w-[250px] ${
            isDarkMode ? "bg-[#1C2030]" : "bg-white"
          }`}
        >
          <TradingInterface type="sell" />
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
