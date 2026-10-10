import { Fragment, useCallback, useEffect, useMemo, useRef, useState } from "react";
import { ChevronDown, ChevronRight, Search } from "lucide-react";
import useAssetStore, { type Asset } from "@/store/assetStore";
import useWatchlistStore from "@/store/watchlistStore";
import useDarkModeStore from "@/store/darkModeStore";
import { useMarketWatchSyntheticTicker } from "@/hooks/useMarketWatchSyntheticTicker";
import { normalizeSymbolQuery } from "@/lib/symbol-search";
import { formatTradePrice } from "@/lib/price-format";
import { FOREX_GROUP_ORDER, forexGroup } from "@/lib/forex-groups";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

const scrollbarStyles = `
  .market-watch-scroll::-webkit-scrollbar {
    width: 17px;
  }
  .market-watch-scroll::-webkit-scrollbar-track {
    background: #F1F1F1;
    border-left: 1px solid #70707066;
  }
  .market-watch-scroll::-webkit-scrollbar-thumb {
    background: #DFDFDD;
    border: 1px solid #70707066;
  }
  .market-watch-scroll::-webkit-scrollbar-button {
    background: #DFDFDD;
    border: 1px solid #70707066;
    height: 17px;
    display: block;
  }
  .market-watch-scroll::-webkit-scrollbar-button:single-button:vertical:decrement {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%23707070'><polygon points='5,2 2,8 8,8'/></svg>");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 8px;
    display: block;
  }
  .market-watch-scroll::-webkit-scrollbar-button:single-button:vertical:increment {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%23707070'><polygon points='5,8 2,2 8,2'/></svg>");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 8px;
    display: block;
  }
`;

// Market Watch groups, in MT4 Symbols-tree order. Forex is split the way MT4
// brokers publish it (Majors / Minors / Exotics, see lib/forex-groups.ts).
const TYPE_GROUPS: { type: Asset["type"]; label: string }[] = [
  { type: "metals", label: "Metals" },
  { type: "commodities", label: "Commodities" },
  { type: "indices", label: "Indices" },
  { type: "stocks", label: "Stocks" },
  { type: "crypto", label: "Crypto" },
];
const GROUPS_STORAGE_KEY = "mt4-market-watch-groups";

type MarketWatchGroup = { key: string; label: string; assets: Asset[] };

function readOpenGroups(): Record<string, boolean> {
  try {
    return JSON.parse(localStorage.getItem(GROUPS_STORAGE_KEY) ?? "{}") ?? {};
  } catch {
    return {};
  }
}

function groupKeyFor(asset: Asset): string {
  return asset.type === "forex" ? `forex:${forexGroup(asset.symbol_display)}` : asset.type;
}

export default function MarketWatchLight() {
  const groupedAssets = useAssetStore((state) => state.groupedAssets);
  const fetchAssets = useAssetStore((state) => state.fetchAssets);
  const setActiveAsset = useAssetStore((state) => state.setActiveAsset);
  const activeAsset = useAssetStore((state) => state.activeAsset);
  const addPair = useAssetStore((state) => state.addPair);
  const { watchlist } = useWatchlistStore();
  const [searchQuery, setSearchQuery] = useState("");
  const [time, setTime] = useState(new Date());
  const [openGroups, setOpenGroups] = useState<Record<string, boolean>>(readOpenGroups);
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);

  const normalizedQuery = useMemo(() => normalizeSymbolQuery(searchQuery), [searchQuery]);
  const matches = useCallback(
    (asset: Asset) =>
      !normalizedQuery ||
      normalizeSymbolQuery(asset.symbol_display).includes(normalizedQuery) ||
      normalizeSymbolQuery(asset.name).includes(normalizedQuery),
    [normalizedQuery],
  );

  const groups = useMemo<MarketWatchGroup[]>(() => {
    const favourites = watchlist.filter(matches);
    const inWatchlist = new Set(watchlist.map((w) => w.symbol_display));
    const all = Object.values(groupedAssets).flat().filter((a) => !inWatchlist.has(a.symbol_display) && matches(a));
    const byKey = new Map<string, Asset[]>();
    all.forEach((a) => byKey.set(groupKeyFor(a), [...(byKey.get(groupKeyFor(a)) ?? []), a]));

    return [
      { key: "favourites", label: "Favourites", assets: favourites },
      ...FOREX_GROUP_ORDER.map((g) => ({ key: `forex:${g}`, label: `Forex ${g}`, assets: byKey.get(`forex:${g}`) ?? [] })),
      ...TYPE_GROUPS.map((g) => ({ key: g.type, label: g.label, assets: byKey.get(g.type) ?? [] })),
    ].filter((g) => g.assets.length > 0);
  }, [groupedAssets, matches, watchlist]);

  // Collapsed by default except Favourites, Forex Majors and the active symbol's
  // group; a search opens every group that has a match.
  const activeGroupKey = activeAsset ? groupKeyFor(activeAsset as Asset) : null;
  const isOpen = (key: string) =>
    Boolean(normalizedQuery) || (openGroups[key] ?? (key === "favourites" || key === "forex:Majors" || key === activeGroupKey));
  const toggleGroup = (key: string) =>
    setOpenGroups((current) => {
      const next = { ...current, [key]: !isOpen(key) };
      try {
        localStorage.setItem(GROUPS_STORAGE_KEY, JSON.stringify(next));
      } catch {
        /* storage unavailable: the toggle still works for this session */
      }
      return next;
    });

  const visibleAssets = useMemo<Asset[]>(
    () => groups.flatMap((g) => (isOpen(g.key) ? g.assets : [])),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [groups, openGroups, normalizedQuery, activeGroupKey],
  );
  const { getDisplayQuote } = useMarketWatchSyntheticTicker(visibleAssets);

  // MT4 colours a quote by its last tick: blue when it rose, red when it fell.
  const lastTicks = useRef(new Map<string, { bid: number; dir: "up" | "down" | null }>());
  const tickDirection = (id: string, bid: number) => {
    const prev = lastTicks.current.get(id);
    const dir = !prev ? null : bid > prev.bid ? "up" : bid < prev.bid ? "down" : prev.dir;
    lastTicks.current.set(id, { bid, dir });
    return dir;
  };
  const priceTone = (dir: "up" | "down" | null) =>
    dir === "up" ? "text-blue-500" : dir === "down" ? "text-red-500" : isDarkMode ? "text-slate-200" : "text-slate-800";
  const showPrice = (price: number, asset: Asset) =>
    Number.isFinite(price) && price > 0 ? formatTradePrice(price, asset.type, asset.sy ?? "") : "—";

  useEffect(() => {
    fetchAssets();
    const interval = setInterval(() => {
      setTime(new Date());
    }, 1000);
    return () => clearInterval(interval);
  }, [fetchAssets]);

  const line = "border-[#70707066]";
  const headText = isDarkMode ? "text-slate-200" : "text-black";

  return (
    <div
      className={`h-full flex flex-col border ${line} ${
        isDarkMode ? "bg-slate-900 text-white" : "bg-white text-black"
      } font-sans`}
    >
      <style>{scrollbarStyles}</style>

      {/* Header */}
      <div
        className={`${
          isDarkMode ? "bg-slate-800" : "bg-[#D2E0EA]"
        } border-b ${line} h-[30px] flex items-center justify-center relative shadow-sm`}
      >
        <span className="font-bold text-[14px]">
          Market Watch: {time.toLocaleTimeString("en-GB", { hour12: false })}
        </span>
      </div>

      {/* Search Area */}
      <div className={`p-1.5 flex items-center gap-1.5 border-b ${line} ${isDarkMode ? "bg-slate-900" : "bg-white"}`}>
        <div className="relative flex-1">
          <input
            type="text"
            placeholder="Search Market"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className={`w-full h-[26px] pl-3 pr-8 text-[12px] border ${line} rounded-sm shadow-[inset_0_1px_3px_rgba(0,0,0,0.1)] outline-none ${
              isDarkMode
                ? "bg-slate-800 text-slate-200 placeholder:text-slate-500"
                : "bg-white text-black placeholder:text-slate-400"
            }`}
          />
          <Search size={14} className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-500" />
        </div>
      </div>

      {/* Table Area */}
      <div className="flex-1 min-h-0 overflow-y-scroll market-watch-scroll relative">
        <Table className="border-collapse border-spacing-0">
          <TableHeader className={`sticky top-0 z-20 ${isDarkMode ? "bg-slate-800" : "bg-white"}`}>
            <TableRow className={`hover:bg-transparent border-b ${line} h-[30px]`}>
              <TableHead className={`text-[13px] font-bold text-center border-r ${line} h-[30px] px-2 ${headText}`}>
                Symbol
              </TableHead>
              <TableHead className={`text-[13px] font-bold text-center border-r ${line} h-[30px] px-2 ${headText}`}>
                Bid
              </TableHead>
              <TableHead className={`text-[13px] font-bold text-center h-[30px] px-2 ${headText}`}>
                Ask
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {groups.map((group) => {
              const open = isOpen(group.key);

              return (
                <Fragment key={group.key}>
                  <TableRow
                    onClick={() => toggleGroup(group.key)}
                    className={`h-[26px] cursor-pointer border-b ${line} ${
                      isDarkMode ? "bg-slate-800/70 hover:bg-slate-800" : "bg-[#EEF3F7] hover:bg-[#E3EBF2]"
                    }`}
                    aria-expanded={open}
                  >
                    <TableCell colSpan={3} className="p-0 pl-2">
                      <div className="flex items-center gap-1.5 text-[12px] font-bold">
                        {open ? <ChevronDown size={14} /> : <ChevronRight size={14} />}
                        <span>{group.label}</span>
                        <span className={`font-normal ${isDarkMode ? "text-slate-400" : "text-slate-500"}`}>({group.assets.length})</span>
                      </div>
                    </TableCell>
                  </TableRow>
                  {open &&
                    group.assets.map((asset) => {
                      // Bid is what the client sells at, Ask what they buy at (Bid < Ask).
                      const { buyPrice: ask, sellPrice: bid } = getDisplayQuote(asset);
                      const isActive = activeAsset?.symbol_display === asset.symbol_display;
                      const tone = priceTone(tickDirection(asset.id, bid));

                      return (
                        <TableRow
                          key={asset.id}
                          translate="no"
                          onClick={() => {
                            setActiveAsset(asset);
                            addPair(asset.symbol_display);
                          }}
                          className={`notranslate h-[35px] cursor-pointer border-b ${line} transition-colors ${
                            isActive
                              ? "bg-[#0866C64D]"
                              : isDarkMode
                              ? "hover:bg-slate-800"
                              : "hover:bg-slate-50"
                          }`}
                        >
                          <TableCell className={`p-0 border-r ${line} pl-6`}>
                            <div className="flex items-center gap-5 pl-2">
                              <div className="relative w-[18px] h-[18px] flex items-center justify-center shrink-0">
                                <img
                                  src={asset.image}
                                  alt={asset.name}
                                  className="w-[18px] h-[18px] object-contain rounded-full"
                                />
                              </div>
                              <span className="font-bold text-[13px] whitespace-nowrap leading-none">
                                {asset.symbol_display}
                              </span>
                            </div>
                          </TableCell>
                          <TableCell className={`p-0 text-center border-r ${line} font-bold text-[13px] tabular-nums leading-none ${tone}`}>
                            {showPrice(bid, asset)}
                          </TableCell>
                          <TableCell className={`p-0 text-center font-bold text-[13px] tabular-nums leading-none ${tone}`}>
                            {showPrice(ask, asset)}
                          </TableCell>
                        </TableRow>
                      );
                    })}
                </Fragment>
              );
            })}
          </TableBody>
        </Table>
      </div>

      {/* Bottom Tabs */}
      <div className={`h-[30px] flex items-center border-t ${line} ${isDarkMode ? "bg-slate-800" : "bg-[#F1F1F1]"} pl-1`}>
        <div className="flex items-center h-full">
           <div className={`border-x border-b ${line} px-3 h-[26px] flex items-center shadow-[0_2px_4px_rgba(0,0,0,0.05)] mt-[-5px] z-20 ${isDarkMode ? "bg-slate-900 text-slate-200" : "bg-white"}`}>
              <span className="text-[12px] font-bold">Symbols</span>
           </div>
        </div>
      </div>
    </div>
  );
}
