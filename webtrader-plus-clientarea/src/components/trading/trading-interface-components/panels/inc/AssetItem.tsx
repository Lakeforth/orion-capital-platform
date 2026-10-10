import { useState, useEffect, useRef, memo } from "react";
import { Plus, Info } from "lucide-react";
import { cn } from "@/lib/utils";
import type { Asset } from "@/store/assetStore";
import AssetInfoModal from "./AssetInfoModal";
import type { MarketWatchDisplayQuote } from "@/hooks/useMarketWatchSyntheticTicker";
import { formatTradePrice } from "@/lib/price-format";
import { displayAssetName } from "@/lib/asset-display";

interface AssetItemProps {
    asset: Asset;
    isActive: boolean;
    onClick: () => void;
    displayQuote?: MarketWatchDisplayQuote;
}

const AssetItem = memo(({
                            asset,
                            isActive,
                            onClick,
                            displayQuote,
                        }: AssetItemProps) => {
    const [priceColor, setPriceColor] = useState<string>("");
    const [isInfoModalOpen, setIsInfoModalOpen] = useState(false);
    const initialRate = displayQuote?.rate ?? (Number.parseFloat(asset.rate) || 0);
    const previousPriceRef = useRef<number>(initialRate);

    // The two prices a client can actually act on. The percentage that used to
    // sit here said nothing about what a trade would cost: it is the move since
    // the session open, while the sell and buy are the real sides of the book.
    const currentPrice = displayQuote?.rate ?? (Number.parseFloat(asset.rate) || 0);
    const quotedSell = displayQuote?.sellPrice ?? asset.sell_price ?? currentPrice;
    const quotedBuy = displayQuote?.buyPrice ?? asset.buy_price ?? currentPrice;
    // Same rule the order ticket uses: take the quoted two-sided prices when
    // the API really sent two sides, and fall back to the tenant's per-side
    // percentages otherwise. Without the fallback a tenant on the one-sided
    // spread model prints the same number in both columns, which reads as a
    // broken row rather than as a zero spread.
    const hasTwoSides = quotedBuy > quotedSell;
    const percent = (value?: string) => Number.parseFloat(value || "0") || 0;
    const sellPrice = hasTwoSides
        ? quotedSell
        : currentPrice * (1 - percent(asset.sell_spread) / 100);
    const buyPrice = hasTwoSides
        ? quotedBuy
        : currentPrice * (1 + percent(asset.buy_spread) / 100);
    const price = (value: number) => formatTradePrice(value, asset.type, asset.sy ?? "");

    // Check price changes and apply flash effect
    useEffect(() => {
        const prevPrice = previousPriceRef.current;

        // Only flash if we have a previous price to compare with
        if (prevPrice !== currentPrice) {
            // Set color based on price change
            if (currentPrice > prevPrice) {
                setPriceColor("text-green-500");
            } else if (currentPrice < prevPrice) {
                setPriceColor("text-red-500");
            }

            previousPriceRef.current = currentPrice;

            const timer = setTimeout(() => {
                setPriceColor("");
            }, 2200);

            return () => clearTimeout(timer);
        }
    }, [currentPrice]);

    const handleInfoClick = (e: React.MouseEvent) => {
        e.stopPropagation(); // Prevent triggering the parent onClick handler
        setIsInfoModalOpen(true);
    };

    return (
        <>
            <div
                className={cn(
                    "notranslate grid grid-cols-[auto_minmax(0,1fr)_auto] items-center py-2 px-2 hover:bg-slate-700/50 border-b border-slate-700 cursor-pointer gap-2",
                    isActive ? "bg-slate-700/50" : ""
                )}
                translate="no"
                onClick={onClick}
            >
                {/* Asset icon */}
                <div className="flex-shrink-0">
                    {asset.image ? (
                        <img
                            src={asset.image}
                            alt={asset.symbol}
                            className="h-4 w-4 rounded-full"
                        />
                    ) : (
                        <div className="h-4 w-4 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs">
                            {asset.symbol.charAt(0)}
                        </div>
                    )}
                </div>

                <div className="min-w-0">
                    {/* truncate, not wrap: a row that grows to two lines pushes
                        every other instrument down the panel. The full name is
                        on the title. Earlier this said overflow-hidden- and
                        whitespace-nowrap-block, neither of which is a real
                        class, so nothing was ever clipped. */}
                    <span
                        className="block truncate text-xs font-light"
                        title={asset.name}
                    >
                        {displayAssetName(asset)}
                    </span>
                </div>

                {/* pl-1/gap-1, and 4rem a side rather than 4.25: measured at
                    the panel's real 300px, that is what it takes to leave the
                    ticker column enough room for SPX500USD while still fitting
                    a ten-character crypto price like 0.00001234. */}
                <div className="flex pl-1 items-center gap-1 shrink-0">
                    <span className={cn(
                        "w-[4rem] truncate text-right text-xs font-medium tabular-nums transition-colors duration-300",
                        // The flash wins while it lasts, then each side settles
                        // back to the colour of the button that takes it.
                        priceColor || "text-red-400"
                    )}>
                        {price(sellPrice)}
                    </span>
                    <span className={cn(
                        "w-[4rem] truncate text-right text-xs font-medium tabular-nums transition-colors duration-300",
                        priceColor || "text-green-500"
                    )}>
                        {price(buyPrice)}
                    </span>
                    <button
                        className="text-slate-400  hover:text-slate-300 w-3 flex items-center justify-center"
                    >
                        <Plus className="h-3.5 w-3.5" />
                    </button>
                    <button
                        className="text-slate-400 hover:text-slate-300 w-3 flex items-center justify-center cursor-pointer"
                        onClick={handleInfoClick}
                        aria-label="View asset information"
                        title="Asset Information"
                    >
                        <Info className="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>

            <AssetInfoModal
                asset={asset}
                open={isInfoModalOpen}
                onOpenChange={setIsInfoModalOpen}
                onTradeClick={onClick}
            />
        </>
    );
});

AssetItem.displayName = "AssetItem";

export default AssetItem;
