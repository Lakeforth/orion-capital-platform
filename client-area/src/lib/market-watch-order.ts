/**
 * The order Market Watch opens in, and what leads each section.
 *
 * Both were accidental before: sections appeared in whatever order the /assets
 * response happened to group them, and each section led with whatever row came
 * back first -- which for stocks meant Societe Generale, and for indices meant
 * Spain 35. A collapsed section now shows a single instrument, so that one row
 * is the whole of what a client sees before they open anything, and it has to
 * be the instrument they came looking for.
 *
 * Match is on `sym_key`, exactly. Not a substring: "BTCUSD" is also inside
 * "WBTCUSD", and leading crypto with Wrapped Bitcoin would be its own bug.
 */
import type { Asset } from "@/store/assetStore";

/** Sections, in the order the client asked to read them. */
export const CATEGORY_ORDER = [
  "forex",
  "crypto",
  "metals",
  "commodities",
  "indices",
  "stocks",
] as const;

/**
 * What each section leads with, best first.
 *
 * More than one entry is a fallback chain, not a preference list for display --
 * only the first one actually present is promoted. Commodities carries two
 * because "oil" is Brent on some tenants' feeds and West Texas on others.
 */
const LEADS: Record<string, string[]> = {
  forex: ["EURUSD"],
  crypto: ["BTCUSD"],
  metals: ["XAU_USD"],
  commodities: ["BCO_USD", "WTICO_USD"],
  indices: ["SPX500_USD"],
  stocks: ["AAPL"],
};

/**
 * Put the known sections in order; anything unrecognised keeps its existing
 * relative order and goes to the end, so a new asset type added server-side
 * still appears instead of silently vanishing.
 */
export const orderCategories = (categories: string[]): string[] => {
  const rank = (category: string) => {
    const index = CATEGORY_ORDER.indexOf(category as (typeof CATEGORY_ORDER)[number]);
    return index === -1 ? CATEGORY_ORDER.length : index;
  };

  return [...categories].sort((a, b) => rank(a) - rank(b));
};

/** Promote the section's lead instrument to the front, leaving the rest as they were. */
export const withLeadFirst = (category: string, assets: Asset[]): Asset[] => {
  const wanted = LEADS[category];
  if (!wanted || assets.length < 2) return assets;

  for (const key of wanted) {
    const index = assets.findIndex((asset) => asset.sym_key === key);
    if (index > 0) return [assets[index], ...assets.filter((_, i) => i !== index)];
    if (index === 0) return assets;
  }

  return assets;
};
