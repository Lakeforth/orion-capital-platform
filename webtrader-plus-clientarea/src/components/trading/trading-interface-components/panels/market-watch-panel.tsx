import { useState, useCallback, useMemo } from "react";
import { Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import useAssetStore from "@/store/assetStore";
import { Button } from "@/components/ui/button";
import type { Asset } from "@/store/assetStore";
import AssetCategory from "@/components/trading/trading-interface-components/panels/inc/AssetCategory.tsx";
import { useMarketWatchSyntheticTicker } from "@/hooks/useMarketWatchSyntheticTicker";
import { normalizeSymbolQuery } from "@/lib/symbol-search";
import { orderCategories, withLeadFirst } from "@/lib/market-watch-order";

interface MarketWatchPanelProps {
    addCurrencyPair: (pair: string) => void;
}

// Main Component
const MarketWatchPanel = ({ addCurrencyPair }: MarketWatchPanelProps) => {
    const [expandedCategories, setExpandedCategories] = useState<string[]>([]);
    const [searchTerm, setSearchTerm] = useState("");

    // Use selectors to prevent unnecessary re-renders
    const groupedAssets = useAssetStore((state) => state.groupedAssets);
    const setActiveAsset = useAssetStore((state) => state.setActiveAsset);
    const activeAssetId = useAssetStore((state) => state.activeAsset?.id);
    const isLoading = useAssetStore((state) => state.isLoading);
    const error = useAssetStore((state) => state.error);

    // Expand categories when they first load
    // useEffect(() => {
    //     const categories = Object.keys(groupedAssets);
    //     if (categories.length > 0 && expandedCategories.length === 0) {
    //         setExpandedCategories(categories);
    //     }
    // }, [groupedAssets, expandedCategories.length]);

    // Toggle category expansion
    const toggleCategory = useCallback((category: string) => {
        setExpandedCategories(prev =>
            prev.includes(category)
                ? prev.filter(c => c !== category)
                : [...prev, category]
        );
    }, []);

    // Handle asset selection
    const handleAssetClick = useCallback((asset: Asset) => {
        setActiveAsset(asset);
        addCurrencyPair(asset.symbol_display);
    }, [setActiveAsset, addCurrencyPair]);

    const isSearching = searchTerm.trim() !== "";

    // Filter assets based on search term
    const filteredAssets = searchTerm.trim() === ""
        ? groupedAssets
        : Object.keys(groupedAssets).reduce((acc, category) => {
            const normalizedQuery = normalizeSymbolQuery(searchTerm);
            const filtered = groupedAssets[category].filter(asset =>
                normalizeSymbolQuery(asset.name).includes(normalizedQuery) ||
                normalizeSymbolQuery(asset.symbol).includes(normalizedQuery) ||
                normalizeSymbolQuery(asset.sy).includes(normalizedQuery)
            );

            if (filtered.length > 0) {
                acc[category] = filtered;
            }
            return acc;
        }, {} as Record<string, Asset[]>);
    const visibleAssets = useMemo(
        () => Object.values(filteredAssets).flat(),
        [filteredAssets]
    );
    const { getDisplayQuote } = useMarketWatchSyntheticTicker(visibleAssets);

    const hasResults = Object.keys(filteredAssets).length > 0;

    if (isLoading) {
        return (
            <div className="h-full bg-background flex items-center justify-center">
                <div className="text-muted-foreground">Loading assets...</div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="h-full bg-background flex items-center justify-center p-4">
                <div className="text-destructive text-center">
                    <p className="mb-2 font-medium">Failed to load assets</p>
                    <p className="text-sm text-muted-foreground">{error}</p>
                    <Button
                        variant="outline"
                        className="mt-4"
                        onClick={() => useAssetStore.getState().fetchAssets()}
                    >
                        Retry
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div className="h-full bg-background">
            <div className="p-2 border-b border-border">
                <div className="mb-4 ml-4 mt-2">
                    <h2 className="text-sm font-medium ">Market Watch</h2>
                </div>
                <div className="relative">
                    <Search className="absolute left-2  top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Search..."
                        className="pl-8"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                </div>
            </div>

            <div className="p-2 overflow-y-auto h-[calc(100%-80px)]">
                {!hasResults ? (
                    <div className="text-center text-muted-foreground py-4">
                        No assets found
                    </div>
                ) : (
                    orderCategories(Object.keys(filteredAssets)).map((category) => (
                        <AssetCategory
                            key={category}
                            category={category}
                            assets={withLeadFirst(category, filteredAssets[category])}
                            // A search shows everything it matched: finding three
                            // of eleven hits and hiding the rest behind a chevron
                            // is not a search result.
                            isExpanded={isSearching || expandedCategories.includes(category)}
                            toggleCategory={() => toggleCategory(category)}
                            handleAssetClick={handleAssetClick}
                            activeAssetId={activeAssetId}
                            getDisplayQuote={getDisplayQuote}
                        />
                    ))
                )}
            </div>
        </div>
    );
};

export default MarketWatchPanel;
