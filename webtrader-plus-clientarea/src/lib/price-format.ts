/**
 * Per-instrument price presentation.
 *
 * Mirrors asset_price_precision() / asset_pip_size() / format_trade_price() in
 * the backend's app/Helpers.php -- keep the two in sync if either changes.
 * They render the same recorded prices on different surfaces (trade room and
 * client area here, back office and PDF statements there), and any drift
 * between them reads to a client as those surfaces disagreeing about a fill.
 *
 * A flat 5-decimal format (see the a639ae8 stability fix) stopped prices
 * jittering on every tick, but QA correctly flagged it as wrong for
 * anything that isn't a standard forex pair: JPY-quoted pairs trade at
 * ~100x the raw magnitude of most other pairs (fractional-pip precision is
 * conventionally 3dp there, not 5 -- the same fractional-pip idea, just two
 * decimals shallower), and crypto/stocks/indices/commodities/metals were
 * never 5-decimal instruments in the first place.
 *
 * The reverse mistake matters more: rounding a forex price to 2dp throws away
 * the entire pip range a trade is measured in (1.910221 -> 1.91), so nothing
 * on the price path may fall back to a money precision.
 */

/** Instruments quoted a decimal deeper than the rest of their category. */
const SILVER_SYMBOLS = ["XAG", "SI", "SIL"];
const NATURAL_GAS_SYMBOLS = ["NG", "NGAS"];

/**
 * The instrument's own ticker, without its quote currency -- "XAU" from an
 * "XAU/USD" symbol, "NG" from "NGUSD". Precision and pip size are
 * per-instrument, not per-category: gold and silver share "metals" but quote
 * to different depths, as do natural gas and the other commodities.
 */
function instrumentKey(symbol: string): string {
  return (symbol ?? "").toUpperCase().split(/[/_]/)[0].trim();
}

function isJpyQuoted(symbol: string): boolean {
  const upper = (symbol ?? "").toUpperCase();
  return upper.endsWith("/JPY") || upper.endsWith("_JPY") || upper.endsWith("JPY");
}

export function pricePrecision(category: string | undefined, symbol: string, price: number): number {
  const sym = instrumentKey(symbol);

  switch (category) {
    case "forex":
      return isJpyQuoted(symbol) ? 3 : 5;
    case "metals":
      // Gold quotes to the cent, silver a decimal deeper.
      return SILVER_SYMBOLS.includes(sym) ? 3 : 2;
    case "commodities":
      // Natural gas is the one commodity quoted to 3dp.
      return NATURAL_GAS_SYMBOLS.includes(sym) ? 3 : 2;
    case "crypto":
      // Majors quote to the cent; lower-priced alts need the extra digits or
      // they round away to a flat line.
      if (price >= 100) return 2;
      if (price >= 1) return 4;
      // Sub-$1 coins: 5 significant digits (0.21966, 0.062658), capped at 6
      // decimals (0.009336) -- a flat 6 printed filler digits like 0.219660.
      return price > 0 ? Math.min(6, 4 - Math.floor(Math.log10(price))) : 6;
    case "stocks":
    case "indices":
      return 2;
    default:
      return 5;
  }
}

/**
 * One pip (forex/metals/commodities) or one point (indices/stocks/cryptos)
 * for this instrument, as a price increment.
 */
export function pipSize(category: string | undefined, symbol: string): number {
  const sym = instrumentKey(symbol);

  switch (category) {
    case "forex":
      return isJpyQuoted(symbol) ? 0.01 : 0.0001;
    case "metals":
      return SILVER_SYMBOLS.includes(sym) ? 0.001 : 0.01;
    case "commodities":
      return 0.01;
    case "stocks":
      return 0.01;
    case "indices":
    case "crypto":
      return 1;
    default:
      return 0.0001;
  }
}

/** Whether this instrument's spread is conventionally quoted in pips or points. */
export function spreadUnitLabel(category: string | undefined, spreadInUnits: number): string {
  const isPips = category === "forex" || category === "metals" || category === "commodities";
  const plural = Math.abs(spreadInUnits) === 1 ? "" : "s";

  return isPips ? `pip${plural}` : `point${plural}`;
}

export function formatTradePrice(price: number, category: string | undefined, symbol: string): string {
  return price.toFixed(pricePrecision(category, symbol, price));
}

export function formatVolume(volumeLots: number | string | null | undefined): string {
  const lots = typeof volumeLots === "string" ? Number.parseFloat(volumeLots) : volumeLots;
  return (lots ?? 0).toFixed(2);
}

export interface SpreadQuote {
  /** Spread as a price increment, e.g. 0.000382 on GBP/AUD. */
  price: number;
  /** That increment expressed in the instrument's pips or points, e.g. 3.8. */
  units: number;
  /** "pips" / "points", already pluralised for `units`. */
  unitLabel: string;
  /** What the spread costs on this order size, in the quote currency. */
  cost: number;
  /** Quote currency the cost is denominated in, e.g. "AUD" on GBP/AUD. */
  costCurrency: string;
}

/**
 * Turn the configured spread percentages into something a client can act on.
 *
 * The order box used to print the raw config value -- "Spread: 0.02000" --
 * which is unitless, and reads as pips to anyone who has used any other
 * platform when it is actually a percent. On GBP/AUD at 1.91003 the real
 * spread is 3.8 pips, nearly 200x what "0.02" suggests.
 *
 *     spread      = price * (totalSpreadPercent / 100)
 *     pips/points = spread / pipSize
 *     cost        = spread * contractSize * volumeLots
 *
 * `buySpreadPercent`/`sellSpreadPercent` are the per-side percentages exactly
 * as configured (forex 0.01 each, so a 0.02% round trip) -- NOT already
 * divided by 100, and not a multiplier.
 *
 * Prefers `quotedSpread` (the live ask minus the live bid) when the server has
 * given us both sides, because those carry this customer's spread_multiplier
 * and the published percentages do not -- a customer on a 3x multiplier must
 * not be quoted the standard spread and then charged triple.
 */
export function spreadQuote(params: {
  price: number;
  buySpreadPercent: number;
  sellSpreadPercent: number;
  category: string | undefined;
  symbol: string;
  contractSize: number;
  volumeLots: number;
  pipSizeOverride?: number | null;
  quotedSpread?: number | null;
}): SpreadQuote {
  const {
    price,
    buySpreadPercent,
    sellSpreadPercent,
    category,
    symbol,
    contractSize,
    volumeLots,
    quotedSpread,
  } = params;

  const totalPercent = (buySpreadPercent || 0) + (sellSpreadPercent || 0);
  const spread =
    Number.isFinite(quotedSpread) && (quotedSpread as number) > 0
      ? (quotedSpread as number)
      : (price || 0) * (totalPercent / 100);

  // The backend publishes pip_size per asset (tenant instrument sets differ);
  // fall back to the local table for surfaces that only carry category+symbol.
  const pip =
    params.pipSizeOverride && params.pipSizeOverride > 0
      ? params.pipSizeOverride
      : pipSize(category, symbol);

  const units = pip > 0 ? spread / pip : 0;
  const cost = spread * (contractSize || 0) * (volumeLots || 0);

  // On "GBP/AUD" the spread is priced in AUD -- the quote side of the pair.
  const parts = (symbol ?? "").toUpperCase().split(/[/_]/);
  const costCurrency = parts.length > 1 ? parts[parts.length - 1] : "USD";

  return {
    price: spread,
    units,
    unitLabel: spreadUnitLabel(category, units),
    cost,
    costCurrency,
  };
}

/**
 * The order box's spread line, e.g. "3.8 pips (3.82 AUD)".
 */
export function formatSpreadQuote(quote: SpreadQuote): string {
  return `${quote.units.toFixed(1)} ${quote.unitLabel} (${quote.cost.toFixed(2)} ${quote.costCurrency})`;
}
