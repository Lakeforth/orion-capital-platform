import { useState, useEffect, useMemo } from "react";
import { BadgeCheck, Search } from "lucide-react";
import useAssetStore, { type Asset } from "@/store/assetStore";
import useWatchlistStore from "@/store/watchlistStore";
import { useMarketWatchSyntheticTicker } from "@/hooks/useMarketWatchSyntheticTicker";
import { normalizeSymbolQuery } from "@/lib/symbol-search";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

export default function MarketWatch() {
  const groupedAssets = useAssetStore((state) => state.groupedAssets);
  const fetchAssets = useAssetStore((state) => state.fetchAssets);
  const setActiveAsset = useAssetStore((state) => state.setActiveAsset);
  const addPair = useAssetStore((state) => state.addPair);
  const { watchlist } = useWatchlistStore();
  const [searchQuery, setSearchQuery] = useState("");
  const [time, setTime] = useState(new Date());

  const normalizedQuery = useMemo(() => normalizeSymbolQuery(searchQuery), [searchQuery]);

  const filteredWatchlist = useMemo(
    () =>
      watchlist.filter(
        (asset) =>
          !normalizedQuery ||
          normalizeSymbolQuery(asset.symbol_display).includes(normalizedQuery) ||
          normalizeSymbolQuery(asset.name).includes(normalizedQuery)
      ),
    [normalizedQuery, watchlist]
  );

  const filteredMarketAssets = useMemo(
    () =>
      Object.entries(groupedAssets).flatMap(([, typeAssets]) =>
        typeAssets.filter(
          (asset) =>
            !watchlist.some((w) => w.symbol_display === asset.symbol_display) &&
            (!normalizedQuery ||
              normalizeSymbolQuery(asset.symbol_display).includes(normalizedQuery) ||
              normalizeSymbolQuery(asset.name).includes(normalizedQuery))
        )
      ),
    [groupedAssets, normalizedQuery, watchlist]
  );

  const visibleAssets = useMemo<Asset[]>(
    () => [...filteredWatchlist, ...filteredMarketAssets],
    [filteredMarketAssets, filteredWatchlist]
  );
  const { getDisplayQuote } = useMarketWatchSyntheticTicker(visibleAssets);

  useEffect(() => {
    fetchAssets();
    const interval = setInterval(() => {
      setTime(new Date());
    }, 1000);
    return () => clearInterval(interval);
  }, [fetchAssets]);

  return (
    <div className="h-full flex flex-col bg-[#1C2030]">
      {/* Market Watch Header - Fixed */}
      <div className="bg-slate-700 border-b border-[#2A3038] sticky top-0 z-10">
        <div className="p-2">
          <div className="flex items-center justify-between">
            <span className="text-[10px] text-gray-400">
              Market Watch: {time.toLocaleTimeString()}
            </span>
          </div>
          <div className="relative mt-2">
            <Search className="w-3 h-3 absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search Market"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full bg-[#1C2030] rounded px-7 py-0.5 text-[8px] text-white placeholder:text-[10px] placeholder:text-gray-400"
            />
          </div>
        </div>

        {/* Table Header */}
        <Table className="border-y border-[#2A3038]">
          <TableHeader className="bg-[#2A3038]">
            <TableRow className="border-0 hover:bg-transparent">
              <TableHead className="h-7 text-[10px] text-gray-400 font-normal border-r border-[#2A3038]">
                Symbol
              </TableHead>
              <TableHead className="h-7 text-[10px] text-gray-400 font-normal text-right border-r border-[#2A3038]">
                Bid
              </TableHead>
              <TableHead className="h-7 text-[10px] text-gray-400 font-normal text-right">
                Ask
              </TableHead>
            </TableRow>
          </TableHeader>
        </Table>
      </div>

      {/* Table Body - Scrollable */}
      <div className="flex-1 min-h-0 overflow-auto">
        <Table>
          <TableBody>
            {/* Watchlist Assets on Top */}
            {filteredWatchlist.map((asset) => {
                const { buyPrice, sellPrice, changePercent } = getDisplayQuote(asset);
                const isPositive = changePercent >= 0;

                return (
                  <TableRow
                    key={`watchlist-${asset.id}`}
                    translate="no"
                    onClick={() => {
                      // Convert watchlist asset to assetStore asset type
                      const assetStore = useAssetStore.getState();
                      const found = assetStore.getAssetBySymbol(
                        asset.symbol_display
                      );
                      if (found) {
                        setActiveAsset(found);
                      }
                      addPair(asset.symbol_display);
                    }}
                    className="notranslate h-6 cursor-pointer border-b border-[#2A3038] hover:bg-[#2A3038]"
                  >
                    <TableCell className="py-0 text-[11px] border-r border-[#2A3038]">
                      <div className="flex items-center space-x-1">
                        <div
                          className={`w-1 h-1 rounded-full ${
                            isPositive ? "bg-green-500" : "bg-red-500"
                          }`}
                        />
                        <span className="text-white font-bold">
                          {asset.symbol_display}
                        </span>
                        <span>
                          <BadgeCheck size={16} strokeWidth={0.5} />
                        </span>
                      </div>
                    </TableCell>
                  <TableCell
                    className={`py-0 text-[11px] text-right border-r border-[#2A3038] ${
                      isPositive ? "text-green-400" : "text-red-400"
                    }`}
                  >
                    {buyPrice.toFixed(3)}
                  </TableCell>
                  <TableCell
                    className={`py-0 text-[11px] text-right ${
                      isPositive ? "text-green-400" : "text-red-400"
                    }`}
                  >
                    {sellPrice.toFixed(3)}
                  </TableCell>
                  </TableRow>
                );
              })}

            {/* Remaining Market Assets */}
            {filteredMarketAssets.map((asset) => {
                const { buyPrice, sellPrice, changePercent } = getDisplayQuote(asset);
                const isPositive = changePercent >= 0;

                return (
                  <TableRow
                    key={asset.id}
                    translate="no"
                    onClick={() => {
                      setActiveAsset(asset);
                      addPair(asset.symbol_display);
                    }}
                    className="notranslate h-6 cursor-pointer border-b border-[#2A3038] hover:bg-[#2A3038]"
                  >
                    <TableCell className="py-0 text-[11px] border-r border-[#2A3038]">
                      <div className="flex items-center space-x-1">
                        <div
                          className={`w-1 h-1 rounded-full ${
                            isPositive ? "bg-green-500" : "bg-red-500"
                          }`}
                        />
                        <span className="text-white">
                          {asset.symbol_display}
                        </span>
                      </div>
                    </TableCell>
                    <TableCell
                      className={`py-0 text-[11px] text-right border-r border-[#2A3038] ${
                        isPositive ? "text-green-400" : "text-red-400"
                      }`}
                    >
                      {buyPrice.toFixed(3)}
                    </TableCell>
                    <TableCell
                      className={`py-0 text-[11px] text-right ${
                        isPositive ? "text-green-400" : "text-red-400"
                      }`}
                    >
                      {sellPrice.toFixed(3)}
                    </TableCell>
                  </TableRow>
                );
              })}
          </TableBody>
        </Table>
      </div>
    </div>
  );
}
