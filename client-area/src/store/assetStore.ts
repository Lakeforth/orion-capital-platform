import { create } from "zustand";
import { persist } from "zustand/middleware";
import axiosInstance from "@/lib/axios";
import { AssetUpdate } from "@/hooks/useAssetWebsocket";
import useUserStore from "@/store/userStore.ts";
import useDataStore, {AssetCat} from "@/store/dataStore.ts";

export interface Asset {
  id: string;
  asset_id: string;
  name: string;
  tv_symbol: string;
  symbol: string;
  sy: string;
  symbol_display: string;
  rate: string;
  price_low: string;
  price_high: string;
  volume: string | null;
  market_cap: string | null;
  change: string | null;
  change_percent: string | null;
  /** Per-side spread PERCENTAGE as configured, e.g. "0.01" == 0.01%. */
  buy_spread: string;
  sell_spread: string;
  base: string;
  leverage: number;
  spread: number;
  is_active: number;
  image: string;
  sym_key: string;
  type: "forex" | "crypto" | "stocks" | "indices" | "commodities" | "metals";
  category: "forex" | "cryptos" | "stocks" | "indices" | "commodities" | "metals";
  buy_price: number;
  sell_price: number;
  created_at: string;
  updated_at: string;
  contract_size: number;
  /** One pip (forex/metals/commodities) or point (indices/stocks/cryptos). */
  pip_size?: number;
  price_precision?: number;
  position: number;
  /**
   * The multiplier to use for MARGIN, not `rate` -- for any forex pair not
   * quoted directly against USD (USD-base like USDJPY, or a true cross like
   * GBPJPY/EURGBP), `rate` is denominated in the wrong currency and using it
   * for margin overcharges by ~150x on JPY pairs, undercharges on others.
   * Equal to `rate` for every non-forex asset and every USD-quoted pair.
   */
  margin_rate: number;
  /** USD value of one unit of the quote currency; applies to every category. */
  quote_rate?: number;
}

interface AssetStore {
  assets: Asset[];
  activeAsset: Asset | null;
  isLoading: boolean;
  error: string | null;
  groupedAssets: Record<string, Asset[]>;
  activePairs: string[];
  activePair: string | null;
  lastWebsocketUpdateByAsset: Record<string, number>;

  fetchAssets: () => Promise<void>;
  setActiveAsset: (asset: Asset) => void;
  getAssetById: (id: string) => Asset | undefined;
  getAssetBySymbol: (symbol: string) => Asset | undefined;
  updateAssetFromWebsocket: (updates: AssetUpdate | AssetUpdate[]) => void;
  setActivePair: (pair: string) => void;
  addPair: (pair: string) => void;
  applyDefaultPairs: (pairs: string[]) => void;
  removePair: (pair: string) => void;
  getActiveLeverage: () => number;
}

const DEFAULT_ASSET_SYMBOL = "BITSTAMP:BTCUSD";
// Every new trader starts with these 5 charts already open (Gold, Bitcoin,
// EUR/USD, Brent Crude, Apple) -- values are each instrument's real
// symbol_display, matching what setActiveAsset/addPair store for a
// search-click so a later click on the same instrument can never produce a
// second, differently-keyed entry for it (see the double-chart-tab bug this
// mismatch caused elsewhere).
const STARTING_PAIRS = ["BTCUSD"];
const DEFAULT_PAIR = STARTING_PAIRS[0];
const MIN_PRICE = Number.EPSILON;

const pairKey = (pair?: string | null) =>
  (pair ?? "").toUpperCase().replace(/[^A-Z0-9]/g, "");

function deduplicatePairs(pairs: string[], includeFallback = true): string[] {
  const preferredPairs = new Map(
    STARTING_PAIRS.map((pair) => [pairKey(pair), pair]),
  );
  const seen = new Set<string>();

  const candidates = includeFallback ? [...pairs, ...STARTING_PAIRS] : pairs;

  return candidates.reduce<string[]>((result, pair) => {
    const key = pairKey(pair);

    if (!key || seen.has(key)) {
      return result;
    }

    seen.add(key);
    result.push(preferredPairs.get(key) ?? pair);
    return result;
  }, []);
}

// Provider tick timestamp of the last update accepted for each asset. The
// socket delivers whatever the feed sends, in whatever order it arrives, and
// every open position's live PnL is computed straight off these prices -- so
// the same two rules the backend applies to Asset.rate have to apply here
// too, or the trade room happily prices positions off a tick the database
// would have refused.
const acceptedTickAt = new Map<string, number>();

function isUsableTick(update: AssetUpdate): boolean {
  if (!Number.isFinite(update.price) || update.price <= 0) {
    return false;
  }

  if (!update.lastUpdated) {
    return true;
  }

  const tickAt = Date.parse(update.lastUpdated);

  if (Number.isNaN(tickAt)) {
    return true;
  }

  const previous = acceptedTickAt.get(update.id);

  // An older response arriving after a newer one would otherwise walk the
  // displayed price -- and the PnL on every open position for that asset --
  // backwards.
  if (previous !== undefined && tickAt <= previous) {
    return false;
  }

  acceptedTickAt.set(update.id, tickAt);

  return true;
}

function toFiniteNumber(value: unknown): number {
  const parsed =
    typeof value === "number"
      ? value
      : typeof value === "string"
        ? Number.parseFloat(value.replace(/,/g, "").trim())
        : Number.NaN;

  return Number.isFinite(parsed) ? parsed : Number.NaN;
}

/**
 * buy_spread / sell_spread arrive as PERCENTAGES, per side, exactly as the
 * tenant configured them -- forex "0.01" means 0.01%, i.e. a 0.0001
 * multiplier. This used to treat any value below 1 as if it were already a
 * ratio and multiply the rate by it directly, which marked a 0.01% forex
 * spread up by a full 1% -- 100x too much, ~191 pips on GBP/AUD instead of
 * 0.19.
 */
function spreadToOffset(nextRate: number, spreadPercent: number): number {
  if (!Number.isFinite(spreadPercent) || spreadPercent <= 0) {
    return 0;
  }

  return nextRate * (spreadPercent / 100);
}

/**
 * Re-price both sides of the quote onto a newer live-feed tick.
 *
 * `nextRate` is a rate-only tick -- the feed does not carry a fresh
 * buy_price/sell_price on every message, only `asset.rate`. Deliberately NOT
 * derived from `asset.buy_price`/`asset.sell_price` (the previous tick's
 * OWN derived output): a value derived from a derivation, repeated over many
 * ticks with no re-anchor to the server, is exactly how a EUR/USD sell price
 * ended up stuck ~116 pips behind the real rate and never self-healed
 * (confirmed live, 2026-08-19) -- ratio-based carrying has the same failure
 * shape as offset-based carrying, just a different rate of drift.
 *
 * Recomputed fresh from buy_spread/sell_spread every tick instead. Spread
 * percentages are static config, not a running derivation, so there is
 * nothing to drift -- every tick computes independently of every other one.
 * The one tradeoff: this does not carry a spread_multiplier between full
 * syncs, so a customer on a non-default multiplier briefly shows the
 * standard spread on intermediate live ticks until the next full asset sync
 * restores their multiplied buy_price/sell_price. Self-correcting every
 * sync, and affects only the rare configured customer -- an unconfigured one
 * already pays normal spread, so this is invisible to them either way.
 */
function deriveBidAsk(asset: Asset, nextRate: number) {
  const safeRate = Math.max(nextRate, MIN_PRICE);
  const buySpread = toFiniteNumber(asset.buy_spread);
  const sellSpread = toFiniteNumber(asset.sell_spread);

  const buyOffset = spreadToOffset(safeRate, buySpread);
  const sellOffset = spreadToOffset(safeRate, sellSpread);

  // BUY is always at or above the market price, SELL always at or below it.
  return {
    buyPrice: safeRate + buyOffset,
    sellPrice: Math.max(safeRate - sellOffset, MIN_PRICE),
  };
}

const useAssetStore = create<AssetStore>()(
  persist(
    (set, get) => ({
      assets: [],
      activeAsset: null,
      isLoading: false,
      error: null,
      groupedAssets: {},
      activePairs: [...STARTING_PAIRS],
      activePair: DEFAULT_PAIR,
      lastWebsocketUpdateByAsset: {},

      fetchAssets: async () => {
        set({ isLoading: true, error: null });
        try {
          const response = await axiosInstance.get("/assets");
          const assets = response.data.data as Asset[];

          const groupedAssets: Record<string, Asset[]> = {};
          assets.forEach((asset) => {
            if (!groupedAssets[asset.type]) {
              groupedAssets[asset.type] = [];
            }
            groupedAssets[asset.type].push(asset);
          });

          const currentActive = get().activeAsset;
          const currentActivePair = get().activePair;

          let defaultAsset = assets.find(
            (a) => a.tv_symbol === DEFAULT_ASSET_SYMBOL
          );

          if (!defaultAsset) {
            defaultAsset = assets.find((a) => a.sy === "AUD/JPY");
          }

          if (!defaultAsset && assets.length > 0) {
            defaultAsset = assets[0];
          }

          const refreshedActiveById = currentActive
            ? assets.find(
                (asset) =>
                  asset.asset_id === currentActive.asset_id ||
                  asset.id === currentActive.id ||
                  asset.symbol_display === currentActive.symbol_display
              )
            : undefined;

          const refreshedActiveByPair = currentActivePair
            ? assets.find(
                (asset) =>
                  asset.symbol_display === currentActivePair ||
                  asset.sy === currentActivePair ||
                  pairKey(asset.symbol_display) === pairKey(currentActivePair) ||
                  pairKey(asset.sy) === pairKey(currentActivePair)
              )
            : undefined;

          const nextActiveAsset =
            refreshedActiveById || refreshedActiveByPair || defaultAsset || null;
          const availablePairs = new Map(
            assets.map((asset) => [pairKey(asset.symbol_display), asset.symbol_display]),
          );
          const refreshedPairs = deduplicatePairs(get().activePairs, false)
            .map((pair) => availablePairs.get(pairKey(pair)))
            .filter((pair): pair is string => Boolean(pair));
          const nextActivePair = nextActiveAsset?.symbol_display || currentActivePair;

          set({
            assets,
            groupedAssets,
            isLoading: false,
            activeAsset: nextActiveAsset,
            activePair: nextActivePair,
            activePairs: refreshedPairs.length > 0
              ? refreshedPairs
              : nextActivePair
                ? [nextActivePair]
                : [],
          });
        } catch (error) {
          console.log(error);
          set({
            isLoading: false,
            error: "Failed to fetch assets. Please try again later.",
          });
        }
      },

      setActiveAsset: (asset) => {
        set((state) => {
          const key = pairKey(asset.symbol_display);
          const hasPair = state.activePairs.some((pair) => pairKey(pair) === key);

          return {
            activeAsset: asset,
            activePair: asset.symbol_display,
            activePairs: hasPair
              ? state.activePairs.map((pair) =>
                  pairKey(pair) === key ? asset.symbol_display : pair,
                )
              : [...state.activePairs, asset.symbol_display],
          };
        });
      },

      getAssetById: (id) => {
        return get().assets.find((asset) => asset.id === id);
      },

      getAssetBySymbol: (symbol) => {
        return get().assets.find((asset) => asset.symbol_display === symbol);
      },

      updateAssetFromWebsocket: (updates) => {
        set((state) => {
          const updateArray = Array.isArray(updates) ? updates : [updates];

          if (updateArray.length === 0) {
            return state;
          }

          const assetsMap = new Map(
            state.assets.map((asset) => [asset.asset_id, asset])
          );

          const updatedAssets = [...state.assets];
          const updatedGroupedAssets = { ...state.groupedAssets };
          const updatedLastWebsocket = { ...state.lastWebsocketUpdateByAsset };
          let updatedActiveAsset = null;
          const now = Date.now();

          updateArray.forEach((update) => {
            const asset = assetsMap.get(update.id);

            if (!asset || !isUsableTick(update)) {
              return;
            }

            const nextRate = update.price;
            const { buyPrice, sellPrice } = deriveBidAsk(asset, nextRate);

            const updatedAsset = {
              ...asset,
              rate: nextRate.toString(),
              ...(update.priceLow24h !== undefined && {
                price_low: update.priceLow24h.toString(),
              }),
              ...(update.priceHigh24h !== undefined && {
                price_high: update.priceHigh24h.toString(),
              }),
              ...(update.change24h !== undefined && {
                change: update.change24h.toString(),
              }),
              ...(update.changePercent24h !== undefined && {
                change_percent: update.changePercent24h.toString(),
              }),
              ...(update.volume24h !== undefined && {
                volume: update.volume24h ? update.volume24h.toString() : null,
              }),
              updated_at: update.lastUpdated,
              buy_price: buyPrice,
              sell_price: sellPrice,
            };

            const assetIndex = updatedAssets.findIndex(
              (a) => a.asset_id === update.id
            );

            if (assetIndex !== -1) {
              updatedAssets[assetIndex] = updatedAsset;
              updatedLastWebsocket[update.id] = now;

              if (
                state.activeAsset &&
                state.activeAsset.asset_id === update.id
              ) {
                updatedActiveAsset = updatedAsset;
              }

              const assetType = asset.type;
              if (updatedGroupedAssets[assetType]) {
                const groupIndex = updatedGroupedAssets[assetType].findIndex(
                  (a) => a.id === asset.id
                );
                if (groupIndex !== -1) {
                  updatedGroupedAssets[assetType][groupIndex] = updatedAsset;
                }
              }
            }
          });
          return {
            ...state,
            assets: updatedAssets,
            groupedAssets: updatedGroupedAssets,
            activeAsset: updatedActiveAsset || state.activeAsset,
            lastWebsocketUpdateByAsset: updatedLastWebsocket,
          };
        });
      },

      setActivePair: (pair) => {
        set((state) => {
          const asset = state.assets.find(
            (candidate) => pairKey(candidate.symbol_display) === pairKey(pair),
          );
          return {
            activePair: asset?.symbol_display ?? pair,
            activeAsset: asset || state.activeAsset,
          };
        });
      },

      addPair: (pair) => {
        set((state) => {
          const key = pairKey(pair);
          const existingPair = state.activePairs.find(
            (activePair) => pairKey(activePair) === key,
          );

          if (existingPair) {
            return { activePair: existingPair };
          }

          return {
            activePairs: [...state.activePairs, pair],
            activePair: pair,
          };
        });
        // Update active asset as well
        const asset = get().assets.find(
          (candidate) => pairKey(candidate.symbol_display) === pairKey(pair),
        );
        if (asset) {
          get().setActiveAsset(asset);
        }
      },

      applyDefaultPairs: (pairs) => {
        const configuredPairs = pairs
          .slice(0, 5)
          .filter((pair) => typeof pair === "string" && Boolean(pairKey(pair)));
        const defaults = configuredPairs.length > 0 ? configuredPairs : [DEFAULT_PAIR];

        set((state) => {
          const availablePairs = new Map(
            state.assets.map((asset) => [pairKey(asset.symbol_display), asset.symbol_display]),
          );
          const availableDefaults = state.assets.length > 0
            ? defaults
                .map((pair) => availablePairs.get(pairKey(pair)))
                .filter((pair): pair is string => Boolean(pair))
            : defaults;
          const effectiveDefaults = availableDefaults.length > 0
            ? availableDefaults
            : [availablePairs.get(pairKey(DEFAULT_PAIR)) ?? state.assets[0]?.symbol_display ?? DEFAULT_PAIR];
          const defaultKeys = new Set(effectiveDefaults.map(pairKey));
          const personalPairs = state.activePairs.filter(
            (pair) => !defaultKeys.has(pairKey(pair)),
          );
          const activePairs = deduplicatePairs(
            [...effectiveDefaults, ...personalPairs],
            false,
          );
          const activePair = activePairs[0] ?? DEFAULT_PAIR;
          const activeAsset = state.assets.find(
            (asset) => pairKey(asset.symbol_display) === pairKey(activePair),
          );

          return {
            activePairs,
            activePair,
            activeAsset: activeAsset ?? state.activeAsset,
          };
        });
      },

      removePair: (pair) => {
        set((state) => {
          if (state.activePairs.length <= 1) {
            return state;
          }
          const newPairs = state.activePairs.filter((p) => p !== pair);
          const newActivePair =
            state.activePair === pair ? newPairs[0] : state.activePair;

          // Update active asset if needed
          if (state.activePair === pair) {
            const newAsset = state.assets.find(
              (a) => a.symbol_display === newPairs[0]
            );
            if (newAsset) {
              return {
                activePairs: newPairs,
                activePair: newPairs[0],
                activeAsset: newAsset,
              };
            }
          }

          return {
            activePairs: newPairs,
            activePair: newActivePair,
          };
        });
      },

      getActiveLeverage: () => {
        const { activeAsset } = get();

        // If no active asset, return default
        if (!activeAsset) return 20;

        try {
          // Get leverage data from dataStore
          const leverageData = useDataStore.getState().leverage;
          const user = useUserStore.getState().user;

          // Get the asset type
          const assetType = activeAsset.category;

          // Use type assertion to make TypeScript happy
          // This is safe since we know assetType is a valid key of AssetCat
          const leverageValue = leverageData[assetType as keyof AssetCat];

          // Return the leverage value with fallbacks
          return (
              (leverageValue) ||
              activeAsset.leverage ||
              user?.account_type?.leverage ||
              20
          );
        } catch (error) {
          console.error("Error retrieving leverage:", error);
          // Fallback to user account leverage or default
          const user = useUserStore.getState().user;
          return user?.account_type?.leverage || 20;
        }
      }

    }),
    {
      name: "asset-storage",
      // Version 3 uses the tenant-configured list, with BTCUSD as the sole
      // fallback. It also canonicalizes old persisted symbols. Earlier builds
      // stored BTC/USD while the API now uses BTCUSD; treating those as
      // different strings produced two tabs for the same instrument.
      version: 3,
      migrate: (persistedState) => {
        const incoming = persistedState as Partial<AssetStore> | undefined;
        const existingPairs = incoming?.activePairs ?? [];
        const mergedPairs = deduplicatePairs(existingPairs);
        const activeKey = pairKey(incoming?.activePair);
        const activePair =
          mergedPairs.find((pair) => pairKey(pair) === activeKey) ?? DEFAULT_PAIR;

        return { ...incoming, activePairs: mergedPairs, activePair };
      },
      partialize: (state) => ({
        activePairs: state.activePairs,
        activePair: state.activePair,
      }),
      merge: (persistedState, currentState) => {
        const incoming = persistedState as Partial<AssetStore>;
        return {
          ...currentState,
          ...incoming,
          // Prevent stale price objects from flashing on reload.
          activeAsset: currentState.activeAsset,
        };
      },
    }
  )
);

export default useAssetStore;
