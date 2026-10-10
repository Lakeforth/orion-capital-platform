import { useState } from "react";
import {
  X,
  Gem,
  ArrowLeftRight,
  Bitcoin,
  LineChart,
  Building2,
  ChevronUp,
  ChevronDown,
  Info,
  Rocket,
  Check,
  Wallet,
} from "lucide-react";
import { cn } from "@/lib/utils";
import {
  MARKETS,
  COMMODITY_INSTRUMENTS,
  recommendedFundingFor,
  formatUsd,
  type MarketKey,
  type CommodityInstrumentKey,
} from "./constants";

interface Props {
  onBack: () => void;
  onSubmit: (payload: { markets: MarketKey[]; commodityInstruments: CommodityInstrumentKey[] }) => void;
  submitting: boolean;
}

const MARKET_ICONS: Record<MarketKey, typeof Gem> = {
  commodities: Gem,
  forex: ArrowLeftRight,
  crypto: Bitcoin,
  indices: LineChart,
  stocks: Building2,
};

export default function AiMarketSelectionModal({ onBack, onSubmit, submitting }: Props) {
  const [selectedMarkets, setSelectedMarkets] = useState<MarketKey[]>([]);
  const [commodityInstruments, setCommodityInstruments] = useState<CommodityInstrumentKey[]>([]);

  const commoditiesSelected = selectedMarkets.includes("commodities");

  const toggleMarket = (market: MarketKey) => {
    setSelectedMarkets((prev) =>
      prev.includes(market) ? prev.filter((m) => m !== market) : [...prev, market],
    );
  };

  const toggleCommodityInstrument = (instrument: CommodityInstrumentKey) => {
    setCommodityInstruments((prev) =>
      prev.includes(instrument) ? prev.filter((i) => i !== instrument) : [...prev, instrument],
    );
  };

  const canSubmit =
    selectedMarkets.length > 0 &&
    (!commoditiesSelected || commodityInstruments.length > 0) &&
    !submitting;

  const recommended = recommendedFundingFor(selectedMarkets);

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="bg-[#051424] border border-[#1e293b] rounded shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
        <div className="border-b border-[#1e293b] px-6 pt-6 pb-6 flex items-start justify-between shrink-0">
          <div>
            <h2 className="text-[#d4e4fa] text-2xl font-semibold leading-tight">AI Trading Configuration</h2>
            <p className="text-[#bec8d2] text-sm mt-1">Step 2 of 2: Market Selection</p>
          </div>
          <button type="button" onClick={onBack} className="text-[#bec8d2] hover:text-[#d4e4fa]">
            <X className="w-[14px] h-[14px]" />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto px-6 py-6 space-y-4">
          <p className="text-[#d4e4fa] text-sm">
            Select the markets you want the AI to analyze and trade on your behalf.
          </p>

          <div className="flex gap-4 items-start w-full">
            <CommoditiesCard
              selected={commoditiesSelected}
              onToggle={() => toggleMarket("commodities")}
              instruments={commodityInstruments}
              onToggleInstrument={toggleCommodityInstrument}
            />
            <MarketCard market="forex" selected={selectedMarkets.includes("forex")} onToggle={() => toggleMarket("forex")} />
            <MarketCard market="crypto" selected={selectedMarkets.includes("crypto")} onToggle={() => toggleMarket("crypto")} />
          </div>
          <div className="flex gap-4 items-start w-full">
            <MarketCard market="indices" selected={selectedMarkets.includes("indices")} onToggle={() => toggleMarket("indices")} />
            <MarketCard market="stocks" selected={selectedMarkets.includes("stocks")} onToggle={() => toggleMarket("stocks")} />
            <div className="flex-1" />
          </div>

          <div className="bg-[#122131] border border-[#1e293b] rounded px-[17px] pt-[33px] pb-[17px]">
            <div className="flex gap-4">
              <Wallet className="w-[19px] h-[22px] text-[#89ceff] shrink-0" />
              <div className="space-y-1">
                <h3 className="text-[#d4e4fa] text-xl font-semibold">Recommended Funding</h3>
                <p className="flex items-baseline gap-2">
                  <span className="text-[#89ceff] text-4xl font-bold tracking-tight">{formatUsd(recommended)}</span>
                  <span className="text-[#bec8d2] text-xs">minimum</span>
                </p>
                <p className="flex gap-2 text-xs text-[#bec8d2] leading-relaxed pt-1">
                  <Info className="w-[12px] h-[12px] shrink-0 mt-0.5" />
                  <span>
                    These recommended minimums are algorithmically calculated to provide sufficient margin
                    for the AI to diversify positions, minimize drawdown risk, and optimize performance
                    across your selected asset classes.
                  </span>
                </p>
              </div>
            </div>
          </div>
        </div>

        <div className="bg-[#010f1f] border-t border-[#1e293b] px-6 py-6 flex items-center justify-between shrink-0">
          <button
            type="button"
            onClick={onBack}
            className="border border-[#3e4850] text-[#d4e4fa] text-[13px] font-semibold uppercase tracking-wider px-[17px] py-[9px] rounded-sm"
          >
            Back
          </button>
          <button
            type="button"
            disabled={!canSubmit}
            onClick={() => onSubmit({ markets: selectedMarkets, commodityInstruments })}
            className={cn(
              "flex items-center gap-2 bg-[#0ea5e9] text-white text-[13px] font-semibold uppercase tracking-wider px-4 py-2 rounded-sm transition-opacity",
              canSubmit ? "opacity-100 cursor-pointer" : "opacity-50 cursor-not-allowed",
            )}
          >
            {submitting ? "Activating..." : "Activate AI Trading"}
            <Rocket className="w-[15px] h-[15px]" />
          </button>
        </div>
      </div>
    </div>
  );
}

function MarketCard({
  market,
  selected,
  onToggle,
}: {
  market: MarketKey;
  selected: boolean;
  onToggle: () => void;
}) {
  const Icon = MARKET_ICONS[market];
  const label = MARKETS.find((m) => m.key === market)?.label ?? market;

  return (
    <button
      type="button"
      onClick={onToggle}
      className={cn(
        "flex-1 flex flex-col items-center justify-center gap-2 p-[17px] rounded border self-start transition-colors",
        selected
          ? "bg-[rgba(137,206,255,0.08)] border-[#89ceff] border-[1.5px]"
          : "bg-[#0d1c2d] border-[#1e293b] hover:border-[#3e4850]",
      )}
    >
      <Icon className="w-6 h-6 text-[#d4e4fa]" />
      <span className="text-[#d4e4fa] text-[13px] font-semibold tracking-wider">{label}</span>
    </button>
  );
}

function CommoditiesCard({
  selected,
  onToggle,
  instruments,
  onToggleInstrument,
}: {
  selected: boolean;
  onToggle: () => void;
  instruments: CommodityInstrumentKey[];
  onToggleInstrument: (instrument: CommodityInstrumentKey) => void;
}) {
  return (
    <div
      className={cn(
        "flex-1 flex flex-col gap-4 p-4 rounded border self-start transition-colors",
        selected ? "bg-[rgba(137,206,255,0.08)] border-[#89ceff] border-[1.5px]" : "bg-[#0d1c2d] border-[#1e293b]",
      )}
    >
      <button type="button" onClick={onToggle} className="flex flex-col items-center gap-2">
        <Gem className="w-6 h-6 text-[#d4e4fa]" />
        <span className="text-[#d4e4fa] text-[13px] font-semibold tracking-wider">Commodities</span>
        {selected ? <ChevronUp className="w-4 h-4 text-[#d4e4fa]" /> : <ChevronDown className="w-4 h-4 text-[#5a6773]" />}
      </button>

      {selected && (
        <div className="space-y-2">
          <p className="text-[#bec8d2] text-xs font-semibold">Select assets</p>
          <div className="space-y-2">
            {COMMODITY_INSTRUMENTS.map((instrument) => {
              const checked = instruments.includes(instrument.key);
              return (
                <button
                  type="button"
                  key={instrument.key}
                  onClick={() => onToggleInstrument(instrument.key)}
                  className="w-full flex items-center justify-between bg-[#0d1c2d] border border-[#1e293b] rounded px-3 py-2.5"
                >
                  <span className="text-[#d4e4fa] text-[13px] font-semibold tracking-wider">{instrument.label}</span>
                  <span
                    className={cn(
                      "flex items-center justify-center w-[18px] h-[18px] rounded",
                      checked ? "bg-[#89ceff]" : "border border-[#3e4850]",
                    )}
                  >
                    {checked && <Check className="w-3.5 h-3.5 text-[#051424]" />}
                  </span>
                </button>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
