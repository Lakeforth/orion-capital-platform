import { memo } from "react";
import { ChevronDown, ChevronUp } from "lucide-react";
import type { Asset } from "@/store/assetStore";
import AssetItem from "./AssetItem";
import type { MarketWatchDisplayQuote } from "@/hooks/useMarketWatchSyntheticTicker";

/**
 * How many instruments a collapsed section still shows.
 *
 * A collapsed section used to show nothing at all, so Market Watch opened as
 * six headings and no prices -- every client had to open a section before the
 * terminal told them anything. One is what the client settled on: six sections
 * with their headline instrument each, and the rest a click away. Which one
 * that is, is decided in lib/market-watch-order.
 */
const PREVIEW_COUNT = 1;

interface AssetCategoryProps {
    category: string;
    assets: Asset[];
    isExpanded: boolean;
    toggleCategory: () => void;
    handleAssetClick: (asset: Asset) => void;
    activeAssetId?: string;
    priceColors?: Record<string, string>;
    getDisplayQuote?: (asset: Asset) => MarketWatchDisplayQuote;
}

const AssetCategory = memo(({
                                category,
                                assets,
                                isExpanded,
                                toggleCategory,
                                handleAssetClick,
                                activeAssetId,
                                getDisplayQuote,
                            }: AssetCategoryProps) => {

    const getCategoryIcon = (category: string) => {
        switch (category.toLowerCase()) {
            case "forex":
                return <span className="text-lg">€</span>;
            case "stocks":
                return (
                    <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
                        <path
                            d="M4 18V8H6V18H4ZM8 18V4H10V18H8ZM12 18V10H14V18H12ZM16 18V13H18V18H16Z"
                            fill="currentColor"
                        />
                    </svg>
                );
            case "indices":
                return (
                    <svg viewBox="0 0 24 24" fill="none" width="20" height="20" className="opacity-70">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20Z"
                            fill="currentColor"
                        />
                        <path
                            d="M6.5 12.5L10 16L13.5 12.5L17 16"
                            stroke="currentColor"
                            strokeWidth="1.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                );
            case "crypto":
                return (
                    <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
                        <path
                            d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                            stroke="currentColor"
                            strokeWidth="2"
                            fillOpacity="0"
                        />
                        <path
                            d="M17 13.5L12.5 16.5L7 13.5M12.5 7.5V16.5"
                            stroke="currentColor"
                            strokeWidth="1.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                );
            case "commodities":
                return (
                    <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
                        <path
                            d="M20 12L12 4L4 12M20 12L12 20M20 12H4"
                            stroke="currentColor"
                            strokeWidth="1.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                );
            case "metals":
                return (
                    <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
                        <path
                            d="M4 5H20V19H4V5Z"
                            stroke="currentColor"
                            strokeWidth="1.5"
                            fillOpacity="0"
                        />
                        <path
                            d="M8 9H16M8 12H16M8 15H12"
                            stroke="currentColor"
                            strokeWidth="1.5"
                            strokeLinecap="round"
                        />
                    </svg>
                );
            default:
                return <span className="text-lg">•</span>;
        }
    };

    return (
        <div className="mb-px">
            {/* Category header */}
            <button
                className="flex items-center justify-between w-full py-1.5 px-3 bg-slate-700 text-white"
                onClick={toggleCategory}
            >
                <div className="flex items-center">
          <span className="w-8 flex items-center justify-start text-slate-400">
            {getCategoryIcon(category)}
          </span>
                    <span className="text-xs font-light">{category.charAt(0).toUpperCase() + category.slice(1)}</span>
                </div>
                <div className="flex items-center">
                    <span className="mr-2 text-slate-400 text-xs">{assets.length}</span>
                    {isExpanded ? (
                        <ChevronUp className="h-4 w-4 text-slate-400" />
                    ) : (
                        <ChevronDown className="h-4 w-4 text-slate-400" />
                    )}
                </div>
            </button>

            {/* Category items: the first few always, the rest once opened. */}
            <div>
                {(isExpanded ? assets : assets.slice(0, PREVIEW_COUNT)).map((asset) => (
                    <AssetItem
                        key={asset.id}
                        asset={asset}
                        isActive={activeAssetId === asset.id}
                        onClick={() => handleAssetClick(asset)}
                        displayQuote={getDisplayQuote?.(asset)}
                    />
                ))}

                {!isExpanded && assets.length > PREVIEW_COUNT && (
                    <button
                        type="button"
                        onClick={toggleCategory}
                        className="w-full py-1.5 text-center text-[11px] text-slate-400 hover:text-slate-200"
                    >
                        Show {assets.length - PREVIEW_COUNT} more
                    </button>
                )}
            </div>
        </div>
    );
});

AssetCategory.displayName = "AssetCategory";

export default AssetCategory;
