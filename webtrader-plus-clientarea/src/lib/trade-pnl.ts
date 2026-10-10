type PricedAsset = {
  base: string;
  symbol: string;
  type: string;
  rate: string;
  quote_rate?: number;
};

// Asset.base is the quote currency. Match the backend's USD conversion,
// using live forex rates when available and the server factor otherwise.
export function quoteToUsd(asset: PricedAsset, assets: PricedAsset[]): number | null {
  if (asset.base === "USD") return 1;
  for (const fx of assets) {
    if (fx.type !== "forex") continue;
    const rate = Number(fx.rate);
    if (!Number.isFinite(rate) || rate <= 0) continue;
    if (fx.symbol === asset.base && fx.base === "USD") return rate;
    if (fx.symbol === "USD" && fx.base === asset.base) return 1 / rate;
  }
  const factor = Number(asset.quote_rate);
  return Number.isFinite(factor) && factor > 0 ? factor : null;
}

export function calculateUsdPnl(
  direction: "buy" | "sell", openingPrice: number, closingPrice: number,
  contractUnits: number, quoteRate: number,
): number {
  const difference = direction === "buy" ? closingPrice - openingPrice : openingPrice - closingPrice;
  return difference * contractUnits * quoteRate;
}
