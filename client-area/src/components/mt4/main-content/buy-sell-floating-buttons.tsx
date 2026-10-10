import type { PointerEvent } from "react";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import TradingInterface from "../trading-interface-copy";
import useAssetStore from "@/store/assetStore";
import { formatTradePrice, spreadQuote } from "@/lib/price-format";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { toast } from "sonner";

const AUTO_TRADER_LOCK_MESSAGE =
  "Auto trader is currently enabled on your account please contact support....";

export default function BuySellFloatingButtons() {
  const activeAsset = useAssetStore((state) => state.activeAsset);
  const user = useUserStore((state) => state.user);
  const enableAutotrader = useSiteSettingsStore(
    (state) => state.settings?.enable_autotrader === true
  );
  const isAutoTraderLocked = enableAutotrader && Boolean(user?.autotrader);
  const buyPrice = activeAsset?.buy_price ?? 0;
  const sellPrice = activeAsset?.sell_price ?? 0;

  // The between-the-buttons spread indicator, in the instrument's own pips or
  // points. It used to render the raw price difference through a price
  // formatter, which on a 5dp forex pair printed a 3.8-pip spread as
  // "0.00038" -- a number with no unit that no client can act on. No cash
  // figure here: this sits on the chart, where there is no order size yet.
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
    <div className=" flex gap-2">
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            variant="ghost"
            className={`bg-transparent hover:bg-[#234134] text-[#4CD080] px-4 py-1 h-auto border-1 border-[#4CD080] shadow-none font-medium text-base w-[120px] ${
              isAutoTraderLocked
                ? "opacity-60 cursor-not-allowed hover:bg-transparent"
                : ""
            }`}
            onPointerDown={handleTriggerPointerDown}
          >
            <span className="flex flex-col items-center gap-0.5 w-full">
              <span className="text-sm">BUY</span>
              <span className="text-xs opacity-90 tabular-nums">
                {formatTradePrice(buyPrice, activeAsset?.type, activeAsset?.sy ?? "")}
              </span>
            </span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent className="p-0 bg-[#1C2030] w-[250px]">
          <TradingInterface type="buy" />
        </DropdownMenuContent>
      </DropdownMenu>

      {spread && spread.units > 0 && (
        <div className="flex items-center justify-center">
          <p className="text-xs">
            {spread.units.toFixed(1)} {spread.unitLabel}
          </p>
        </div>
      )}
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            className={`bg-transparent hover:bg-[#4a2828] text-[#FF4D4D] px-4 py-1 h-auto shadow-none font-medium text-base border-1 border-[#FF4D4D] w-[120px] ${
              isAutoTraderLocked
                ? "opacity-60 cursor-not-allowed hover:bg-transparent"
                : ""
            }`}
            onPointerDown={handleTriggerPointerDown}
          >
            <span className="flex flex-col items-center gap-0.5 w-full">
              <span className="text-sm">SELL</span>
              <span className="text-xs opacity-90 tabular-nums">
                {formatTradePrice(sellPrice, activeAsset?.type, activeAsset?.sy ?? "")}
              </span>
            </span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent className="p-0 bg-[#1C2030] w-[250px]">
          <TradingInterface type="sell" />
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
