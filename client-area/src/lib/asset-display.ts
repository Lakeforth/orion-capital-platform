/**
 * What an instrument is called in the trade room's lists.
 *
 * The ticker, not the descriptive name. The feed's names are inconsistent in a
 * way that only shows up once they are side by side in a 300px panel: forex
 * arrives as "EUR/USD", crypto as "BTC / USD", metals as "Gold", commodities
 * as "Brent Crude Oil", indices as "US SPX 500". Different lengths, different
 * conventions, and the long ones truncated to "Bre..." -- which identifies
 * nothing. The ticker is uniform, short enough to fit whole, and is what the
 * chart tabs and the order ticket already say.
 *
 * `sym_key` is that ticker, with the underscore some categories carry removed:
 * XAU_USD -> XAUUSD, SPX500_USD -> SPX500USD, EURUSD and AAPL unchanged.
 *
 * The descriptive name is not lost -- it is the title on the row and the
 * heading of the asset info dialog.
 */
import type { Asset } from "@/store/assetStore";

export const displayAssetName = (asset: Pick<Asset, "sym_key" | "name">): string =>
  (asset.sym_key || "").replace(/_/g, "") || asset.name;
