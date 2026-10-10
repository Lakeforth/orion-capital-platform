export type InvestmentDuration = "12_24" | "36_60";

export type MarketKey = "commodities" | "forex" | "crypto" | "indices" | "stocks";

export type CommodityInstrumentKey =
  | "gold"
  | "silver"
  | "crude_oil"
  | "natural_gas"
  | "copper"
  | "platinum";

export const MARKETS: { key: MarketKey; label: string }[] = [
  { key: "commodities", label: "Commodities" },
  { key: "forex", label: "Forex" },
  { key: "crypto", label: "Crypto" },
  { key: "indices", label: "Indices" },
  { key: "stocks", label: "Stocks" },
];

export const COMMODITY_INSTRUMENTS: { key: CommodityInstrumentKey; label: string }[] = [
  { key: "gold", label: "Gold" },
  { key: "silver", label: "Silver" },
  { key: "crude_oil", label: "Crude Oil" },
  { key: "natural_gas", label: "Natural Gas" },
  { key: "copper", label: "Copper" },
  { key: "platinum", label: "Platinum" },
];

// Recommended minimum funding per market, in USD (the Figma reference design
// quotes these in dollars regardless of the tenant's display currency
// elsewhere -- confirmed intentional, not a units bug). Nick-confirmed
// figures (2026-08-14).
export const MARKET_MINIMUM_FUNDING: Record<MarketKey, number> = {
  commodities: 150_000,
  forex: 50_000,
  crypto: 100_000,
  stocks: 250_000,
  indices: 350_000,
};

export function recommendedFundingFor(selectedMarkets: MarketKey[]): number {
  if (selectedMarkets.length === 0) return MARKET_MINIMUM_FUNDING.commodities;
  return Math.max(...selectedMarkets.map((market) => MARKET_MINIMUM_FUNDING[market]));
}

export function formatUsd(amount: number): string {
  return `$${amount.toLocaleString("en-US")}+`;
}
