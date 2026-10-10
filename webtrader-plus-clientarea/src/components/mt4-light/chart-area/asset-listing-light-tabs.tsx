import { useCallback, useEffect, useRef, useState } from "react";
import { Button } from "@/components/ui/button";
import { X, MoreHorizontal, ChevronLeft, ChevronRight } from "lucide-react";
import { CurrencyFlag } from "../../trading/trading-interface-components/header";
import useAssetStore from "@/store/assetStore";
import { useShallow } from "zustand/react/shallow";
import useDarkModeStore from "@/store/darkModeStore";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
// import AddAsset from "./add-asset";

interface AssetListingLightTabsProps {
  initialPairs?: string[];
  onPairChange?: (pair: string) => void;
}

export function AssetListingLightTabs({
  // initialPairs = ["BTC/USD"],
  onPairChange,
}: AssetListingLightTabsProps) {
  const tabsListRef = useRef<HTMLDivElement>(null);
  const activeTabRef = useRef<HTMLDivElement>(null);
  const [isOpen, setIsOpen] = useState(false);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);

  const {
    assets,
    activePairs,
    activePair,
    setActivePair,
    // addPair,
    removePair,
  } = useAssetStore(
    useShallow((state) => ({
      assets: state.assets,
      activePairs: state.activePairs,
      activePair: state.activePair,
      setActivePair: state.setActivePair,
      removePair: state.removePair,
    })),
  );

  const checkScroll = useCallback(() => {
    if (tabsListRef.current) {
      const { scrollLeft, scrollWidth, clientWidth } = tabsListRef.current;
      setCanScrollLeft(scrollLeft > 0);
      setCanScrollRight(scrollLeft < scrollWidth - clientWidth);
    }
  }, []);

  // Check scroll on mount and when active pairs change
  useEffect(() => {
    checkScroll();
    window.addEventListener("resize", checkScroll);
    return () => window.removeEventListener("resize", checkScroll);
  }, [checkScroll, activePairs]);

  const scroll = (direction: "left" | "right") => {
    if (tabsListRef.current) {
      const scrollAmount = 200; // Adjust this value as needed
      const newScrollLeft =
        tabsListRef.current.scrollLeft +
        (direction === "left" ? -scrollAmount : scrollAmount);
      tabsListRef.current.scrollTo({
        left: newScrollLeft,
        behavior: "smooth",
      });
    }
  };

  // Scroll active tab into view when it changes
  useEffect(() => {
    if (activeTabRef.current && tabsListRef.current) {
      const tabElement = activeTabRef.current;
      const container = tabsListRef.current;
      const tabLeft = tabElement.offsetLeft;
      const tabRight = tabLeft + tabElement.offsetWidth;
      const containerLeft = container.scrollLeft;
      const containerRight = containerLeft + container.offsetWidth;

      if (tabLeft < containerLeft) {
        container.scrollTo({ left: tabLeft - 10, behavior: "smooth" });
      } else if (tabRight > containerRight) {
        container.scrollTo({
          left: tabRight - container.offsetWidth + 10,
          behavior: "smooth",
        });
      }
    }
  }, [activePair]);
  const handlePairClick = (pair: string) => {
    setActivePair(pair);
    if (onPairChange) {
      onPairChange(pair);
    }
  };

  const removeCurrencyPair = (pair: string, e: React.MouseEvent) => {
    e.stopPropagation();
    removePair(pair);
    if (onPairChange && activePair === pair) {
      onPairChange(activePairs[0]);
    }
  };

  // const addCurrencyPair = (pair: string) => {
  //   addPair(pair);
  //   if (onPairChange) {
  //     onPairChange(pair);
  //   }
  // };

  return (
    <div
      className={`flex items-center ${
        isDarkMode ? "bg-[#1C2030]" : "bg-slate-100"
      } p-1 rounded-sm w-full`}
    >
      <DropdownMenu open={isOpen} onOpenChange={setIsOpen}>
        <div
          onMouseEnter={() => setIsOpen(true)}
          onMouseLeave={() => setIsOpen(false)}
        >
          <DropdownMenuTrigger asChild>
            <Button
              variant="outline"
              size="icon"
              className={`h-8 w-8 ${
                isDarkMode ? "hover:bg-[#2A3447]" : "hover:bg-slate-200"
              }`}
            >
              <MoreHorizontal
                className={`h-4 w-4 ${
                  isDarkMode ? "text-slate-200" : "text-slate-900"
                }`}
              />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent
            className={`${
              isDarkMode
                ? "bg-[#1B2331] border-[#2A3447]"
                : "bg-white border-slate-200"
            }`}
          >
            {activePairs.map((pair) => {
              const asset = assets.find((a) => a.symbol_display === pair);
              const isActive = activePair === pair;

              return (
                <DropdownMenuItem
                  key={pair}
                  onClick={() => handlePairClick(pair)}
                  className={`flex items-center gap-2 ${
                    isDarkMode ? "hover:bg-[#2A3447]" : "hover:bg-slate-200"
                  } cursor-pointer relative ${
                    isActive
                      ? "after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-[#F7931A]"
                      : ""
                  }`}
                >
                  <CurrencyFlag pair={pair} />
                  <div className="flex flex-col">
                    <span
                      className={`text-sm ${
                        isDarkMode ? "text-white" : "text-slate-900"
                      }`}
                    >
                      {pair}
                    </span>
                    <span className="text-xs text-muted-foreground">
                      {asset?.type
                        ? asset.type.charAt(0).toUpperCase() +
                          asset.type.slice(1)
                        : "Unknown"}
                    </span>
                  </div>
                </DropdownMenuItem>
              );
            })}
          </DropdownMenuContent>
        </div>
      </DropdownMenu>{" "}
      <div className="flex items-center mx-2 flex-1 min-w-0">
        {canScrollLeft && (
          <Button
            variant="outline"
            size="icon"
            className={`h-8 w-8 ${
              isDarkMode ? "hover:bg-[#2A3447]" : "hover:bg-slate-200"
            } shrink-0`}
            onClick={() => scroll("left")}
          >
            <ChevronLeft className="h-4 w-4" />
          </Button>
        )}

        <div
          ref={tabsListRef}
          className="flex gap-2 overflow-x-hidden mx-2 scroll-smooth flex-1 md:max-w-[calc(100vw-700px)] md:[&::-webkit-scrollbar]:hidden md:[&::-webkit-scrollbar-track]:bg-transparent md:[&::-webkit-scrollbar-thumb]:bg-transparent md:hover:overflow-x-auto"
          onScroll={checkScroll}
        >
          {activePairs.map((pair) => {
            const asset = assets.find((a) => a.symbol_display === pair);
            const isActive = activePair === pair;

            return (
              <div
                key={pair}
                ref={isActive ? activeTabRef : null}
                className={`relative flex items-center gap-2 px-4 py-2 rounded cursor-pointer border ${
                  isDarkMode
                    ? "border-[#2A3447] hover:bg-[#2A3447]"
                    : "border-slate-200 hover:bg-slate-200"
                } w-[150px] shrink-0 ${
                  isActive
                    ? "after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-[#F7931A]"
                    : ""
                }`}
                onClick={() => handlePairClick(pair)}
              >
                <CurrencyFlag pair={pair} />
                <div className="flex flex-col">
                  <span
                    className={`text-sm ${
                      isDarkMode ? "text-white" : "text-slate-900"
                    }`}
                  >
                    {pair}
                  </span>
                  <span className="text-xs text-muted-foreground">
                    {asset?.type
                      ? asset.type.charAt(0).toUpperCase() + asset.type.slice(1)
                      : "..."}
                  </span>
                </div>
                <button
                  className={`absolute right-1 top-1 rounded-full p-1 ${
                    isDarkMode
                      ? "bg-[#2A3447] hover:bg-[#3A4457]"
                      : "bg-slate-200 hover:bg-slate-300"
                  }`}
                  onClick={(e) => removeCurrencyPair(pair, e)}
                >
                  <X className="h-3 w-3" />
                </button>
              </div>
            );
          })}{" "}
        </div>

        {canScrollRight && (
          <Button
            variant="outline"
            size="icon"
            className={`h-8 w-8 ${
              isDarkMode ? "hover:bg-[#2A3447]" : "hover:bg-slate-200"
            } shrink-0`}
            onClick={() => scroll("right")}
          >
            <ChevronRight className="h-4 w-4" />
          </Button>
        )}
      </div>
      {/* <AddAsset /> */}
    </div>
  );
}
