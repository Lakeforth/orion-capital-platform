import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogTrigger } from "@/components/ui/dialog";
import { Plus, Search, Bell, Star, Info } from "lucide-react";
import useAssetStore from "@/store/assetStore";
import useWatchlistStore from "@/store/watchlistStore";
import { CurrencyFlag } from "../../trading/trading-interface-components/header";
import { Input } from "@/components/ui/input";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

import { Asset } from "@/store/assetStore";
import { formatTradePrice, spreadQuote } from "@/lib/price-format";

/** The instrument's live spread in its own pips or points, e.g. "3.8 pips". */
function spreadUnits(asset: Asset): string {
  const quote = spreadQuote({
    price: Number.parseFloat(asset.rate || "0"),
    buySpreadPercent: Number.parseFloat(asset.buy_spread || "0"),
    sellSpreadPercent: Number.parseFloat(asset.sell_spread || "0"),
    category: asset.type,
    symbol: asset.sy ?? "",
    contractSize: asset.contract_size,
    volumeLots: 0,
    pipSizeOverride: asset.pip_size,
    quotedSpread: asset.buy_price - asset.sell_price,
  });

  return `${quote.units.toFixed(1)} ${quote.unitLabel}`;
}

export default function AddAsset() {
  const [selectedTab, setSelectedTab] = useState<string>("forex");
  const [searchQuery, setSearchQuery] = useState("");
  const groupedAssets = useAssetStore((state) => state.groupedAssets);
  const addPair = useAssetStore((state) => state.addPair);
  const { addToWatchlist, removeFromWatchlist, isInWatchlist, watchlist } =
    useWatchlistStore();
  const [isOpen, setIsOpen] = useState<boolean>(false);

  const categories = [
    { id: "watchlist", label: "Watchlist", count: watchlist.length },
    { id: "forex", label: "Forex", count: groupedAssets["forex"]?.length || 0 },
    {
      id: "stocks",
      label: "Stock",
      count: groupedAssets["stocks"]?.length || 0,
    },
    {
      id: "crypto",
      label: "Crypto",
      count: groupedAssets["crypto"]?.length || 0,
    },
    {
      id: "commodities",
      label: "Commodities",
      count: groupedAssets["commodities"]?.length || 0,
    },
    {
      id: "indices",
      label: "ETFs",
      count: groupedAssets["indices"]?.length || 0,
    },
  ];

  const handleAssetSelect = (symbolDisplay: string) => {
    addPair(symbolDisplay);
    setIsOpen(false);
  };

  const handleWatchlistToggle = (e: React.MouseEvent, asset: Asset) => {
    e.stopPropagation();
    if (isInWatchlist(asset.symbol_display)) {
      removeFromWatchlist(asset.symbol_display);
    } else {
      addToWatchlist(asset);
    }
  };

  const filteredAssets =
    selectedTab === "watchlist"
      ? watchlist.filter(
          (asset) =>
            asset.symbol_display
              .toLowerCase()
              .includes(searchQuery.toLowerCase()) ||
            asset.name.toLowerCase().includes(searchQuery.toLowerCase())
        )
      : groupedAssets[selectedTab]?.filter(
          (asset) =>
            asset.symbol_display
              .toLowerCase()
              .includes(searchQuery.toLowerCase()) ||
            asset.name.toLowerCase().includes(searchQuery.toLowerCase())
        ) || [];

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogTrigger asChild>
        <Button
          variant="outline"
          size="icon"
          className="h-8 w-8 hover:bg-[#2A3447] ml-auto"
        >
          <Plus className="h-4 w-4" />
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[900px] bg-[#1C2030] border-[#2A3447] p-0">
        <div className="flex h-[500px]">
          <div className="w-[160px] border-r border-[#2A3447]">
            {categories.map((category) => (
              <button
                key={category.id}
                onClick={() => setSelectedTab(category.id)}
                className={`w-full flex items-center px-4 py-2 text-sm transition-colors border-l-2 ${
                  selectedTab === category.id
                    ? "bg-[#2A3447] text-white border-[#F7931A]"
                    : "text-muted-foreground hover:bg-[#2A3447] hover:text-white border-transparent"
                }`}
              >
                {category.label}
                <span className="ml-auto rounded-sm bg-[#2A3447] px-1.5 text-xs">
                  {category.count}
                </span>
              </button>
            ))}
          </div>

          <div className="flex-1 flex flex-col min-w-0">
            <div className="p-3 border-b border-[#2A3447]">
              <div className="relative">
                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search assets..."
                  className="pl-8 h-9 bg-[#2A3447] border-0 text-white text-sm"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                />
              </div>
            </div>

            <div className="flex-1 overflow-y-auto">
              <Table className="border-collapse">
                <TableHeader>
                  <TableRow className="hover:bg-transparent border-b border-[#2A3447]">
                    <TableHead className="text-xs text-muted-foreground font-normal px-3 py-2">
                      Symbol
                    </TableHead>
                    <TableHead className="text-xs text-muted-foreground font-normal px-3 py-2 text-right">
                      Sell
                    </TableHead>
                    <TableHead className="text-xs text-muted-foreground font-normal px-3 py-2 text-right">
                      Buy
                    </TableHead>
                    <TableHead className="text-xs text-muted-foreground font-normal px-3 py-2 text-right">
                      Spread
                    </TableHead>
                    <TableHead className="text-xs text-muted-foreground font-normal px-3 py-2 text-right">
                      Change
                    </TableHead>
                    <TableHead className="w-[100px] px-3 py-2"></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredAssets.map((asset) => (
                    <TableRow
                      key={asset.id}
                      translate="no"
                      onClick={() => handleAssetSelect(asset.symbol_display)}
                      className="notranslate hover:bg-[#2A3447] transition-colors cursor-pointer border-b border-[#2A3447]/50"
                    >
                      <TableCell className="px-3 py-2">
                        <div className="flex items-center gap-2">
                          <CurrencyFlag pair={asset.symbol_display} />
                          <div>
                            <div className="text-white">
                              {asset.symbol_display}
                            </div>
                            <div className="text-xs text-muted-foreground">
                              {asset.name}
                            </div>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell className="px-3 py-2 text-right text-white">
                        {formatTradePrice(asset.sell_price, asset.type, asset.sy ?? "")}
                      </TableCell>
                      <TableCell className="px-3 py-2 text-right text-white">
                        {formatTradePrice(asset.buy_price, asset.type, asset.sy ?? "")}
                      </TableCell>
                      <TableCell className="px-3 py-2 text-right text-white">
                        {/* Pips/points, not a flat-5dp price difference --
                            "0.00038" is unreadable next to a Spread heading. */}
                        {spreadUnits(asset)}
                      </TableCell>
                      <TableCell className="px-3 py-2 text-right">
                        <span
                          className={
                            parseFloat(asset.change_percent || "0") >= 0
                              ? "text-green-500"
                              : "text-red-500"
                          }
                        >
                          {parseFloat(asset.change_percent || "0").toFixed(2)}%
                        </span>
                      </TableCell>
                      <TableCell className="px-3 py-2">
                        <div className="flex items-center justify-end space-x-1">
                          <button
                            onClick={(e) => e.stopPropagation()}
                            className="rounded-sm p-0.5 hover:bg-[#3A4457] text-muted-foreground"
                          >
                            <Bell className="h-3.5 w-3.5" />
                          </button>
                          <button
                            onClick={(e) => handleWatchlistToggle(e, asset)}
                            className={`rounded-sm p-0.5 hover:bg-[#3A4457] ${
                              isInWatchlist(asset.symbol_display)
                                ? "text-yellow-500"
                                : "text-muted-foreground"
                            }`}
                          >
                            <Star className="h-3.5 w-3.5" />
                          </button>
                          <button
                            onClick={(e) => e.stopPropagation()}
                            className="rounded-sm p-0.5 hover:bg-[#3A4457] text-muted-foreground"
                          >
                            <Info className="h-3.5 w-3.5" />
                          </button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
