/**
 * Market watch search compares this against the same normalization of the
 * candidate symbol/name, so "eurusd", "EUR/USD" and "eur-usd" all match the
 * same instrument regardless of which separator (if any) the symbol uses.
 */
export function normalizeSymbolQuery(value: string): string {
  return value.toLowerCase().replace(/[^a-z0-9]/g, "");
}
