// MT4-style grouping of forex symbols into Majors / Minors / Exotics.
//
// Majors are the seven USD pairs of the most traded currencies; minors are the
// crosses between those currencies without USD (EURGBP, GBPJPY, ...); anything
// involving another currency (TRY, ZAR, SGD, HKD, ...) is exotic.

export type ForexGroup = "Majors" | "Minors" | "Exotics";

const MAJOR_PAIRS = new Set(["EURUSD", "GBPUSD", "USDJPY", "USDCHF", "AUDUSD", "USDCAD", "NZDUSD"]);
const MAJOR_CURRENCIES = new Set(["EUR", "GBP", "JPY", "CHF", "AUD", "CAD", "NZD"]);

/** "EUR_USD", "EUR/USD" and "EURUSD" all become ["EUR", "USD"]; null when it isn't a 6-letter pair. */
export function currencyPair(symbol: string): [string, string] | null {
  const letters = (symbol ?? "").toUpperCase().replace(/[^A-Z]/g, "");
  return letters.length === 6 ? [letters.slice(0, 3), letters.slice(3)] : null;
}

export function forexGroup(symbol: string): ForexGroup {
  const pair = currencyPair(symbol);
  if (!pair) return "Exotics";
  const [base, quote] = pair;
  if (MAJOR_PAIRS.has(base + quote)) return "Majors";
  if (MAJOR_CURRENCIES.has(base) && MAJOR_CURRENCIES.has(quote)) return "Minors";
  return "Exotics";
}

export const FOREX_GROUP_ORDER: ForexGroup[] = ["Majors", "Minors", "Exotics"];
