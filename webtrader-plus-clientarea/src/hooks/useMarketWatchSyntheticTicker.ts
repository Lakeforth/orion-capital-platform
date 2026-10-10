import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { MARKET_WATCH_SYNTHETIC_TICKS } from "@/config/marketWatch";
import useAssetStore, { type Asset } from "@/store/assetStore";

interface SyntheticQuote {
  rate: number;
  buyPrice: number;
  sellPrice: number;
  buyOffset: number;
  sellOffset: number;
  changePercent: number;
  baseWsRate: number;
  baseWsChangePercent: number;
  lastWsAt: number;
}

export interface MarketWatchDisplayQuote {
  rate: number;
  buyPrice: number;
  sellPrice: number;
  changePercent: number;
  isSynthetic: boolean;
}

function getBaseQuote(asset: Asset): MarketWatchDisplayQuote {
  const changePercent = Number.parseFloat(asset.change_percent ?? "0") || 0;
  return {
    rate: Number.parseFloat(asset.rate) || 0,
    buyPrice: asset.buy_price,
    sellPrice: asset.sell_price,
    changePercent,
    isSynthetic: false,
  };
}

function getRandomStepRatio() {
  const { minStepRatio, maxStepRatio } = MARKET_WATCH_SYNTHETIC_TICKS;
  return minStepRatio + Math.random() * (maxStepRatio - minStepRatio);
}

const MIN_PRICE = Number.EPSILON;

function parseNumberish(value: unknown): number {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : Number.NaN;
  }

  if (typeof value === "string") {
    const cleaned = value.replace(/,/g, "").trim();
    if (!cleaned) {
      return Number.NaN;
    }
    const parsed = Number.parseFloat(cleaned);
    return Number.isFinite(parsed) ? parsed : Number.NaN;
  }

  return Number.NaN;
}

function getStableBaseQuote(asset: Asset, previous?: SyntheticQuote) {
  const parsedRate = parseNumberish(asset.rate);
  const parsedBuy = parseNumberish(asset.buy_price);
  const parsedSell = parseNumberish(asset.sell_price);

  let wsRate = parsedRate;
  if (!(wsRate > 0)) {
    if (parsedBuy > 0 && parsedSell > 0) {
      wsRate = (parsedBuy + parsedSell) / 2;
    } else if (parsedBuy > 0) {
      wsRate = parsedBuy;
    } else if (parsedSell > 0) {
      wsRate = parsedSell;
    } else if ((previous?.baseWsRate ?? 0) > 0) {
      wsRate = previous!.baseWsRate;
    } else {
      wsRate = MIN_PRICE;
    }
  }

  const rawSpreadHalf =
    parsedBuy > 0 && parsedSell > 0 ? Math.max((parsedBuy - parsedSell) / 2, 0) : 0;
  const fallbackOffset = Math.max(rawSpreadHalf, wsRate * 0.00002, 0.0000001);
  const maxOffset = wsRate * 0.1;

  let buyOffset = (parsedBuy > 0 ? parsedBuy : wsRate) - wsRate;
  let sellOffset = wsRate - (parsedSell > 0 ? parsedSell : wsRate);

  if (!(buyOffset >= 0) || buyOffset > maxOffset) {
    buyOffset = previous?.buyOffset ?? Math.min(fallbackOffset, maxOffset);
  }

  if (!(sellOffset >= 0) || sellOffset > maxOffset) {
    sellOffset = previous?.sellOffset ?? Math.min(fallbackOffset, maxOffset);
  }

  buyOffset = Math.max(Math.min(buyOffset, maxOffset), 0);
  sellOffset = Math.max(Math.min(sellOffset, maxOffset), 0);

  const buyPrice = Math.max(wsRate + buyOffset, MIN_PRICE);
  const sellPrice = Math.max(wsRate - sellOffset, MIN_PRICE);
  const changePercent = parseNumberish(asset.change_percent);

  return {
    wsRate: Math.max(wsRate, MIN_PRICE),
    wsChangePercent: Number.isFinite(changePercent)
      ? changePercent
      : (previous?.changePercent ?? 0),
    buyPrice,
    sellPrice,
    buyOffset,
    sellOffset,
  };
}

export function useMarketWatchSyntheticTicker(assets: Asset[]) {
  const lastWebsocketUpdateByAsset = useAssetStore(
    (state) => state.lastWebsocketUpdateByAsset
  );
  const [syntheticQuotes, setSyntheticQuotes] = useState<
    Record<string, SyntheticQuote>
  >({});

  const assetsById = useMemo(
    () => new Map(assets.map((asset) => [asset.asset_id, asset])),
    [assets]
  );
  const assetsByIdRef = useRef(assetsById);
  const lastWsByAssetRef = useRef(lastWebsocketUpdateByAsset);

  useEffect(() => {
    assetsByIdRef.current = assetsById;
  }, [assetsById]);

  useEffect(() => {
    lastWsByAssetRef.current = lastWebsocketUpdateByAsset;
  }, [lastWebsocketUpdateByAsset]);

  useEffect(() => {
    if (!MARKET_WATCH_SYNTHETIC_TICKS.enabled) {
      setSyntheticQuotes({});
      return;
    }

    const now = Date.now();

    setSyntheticQuotes((prev) => {
      const next: Record<string, SyntheticQuote> = {};

      for (const asset of assets) {
        const lastWsAt = lastWebsocketUpdateByAsset[asset.asset_id];
        if (!lastWsAt) {
          continue;
        }

        if (now - lastWsAt > MARKET_WATCH_SYNTHETIC_TICKS.maxSilenceMs) {
          continue;
        }

        const existing = prev[asset.asset_id];
        if (existing && existing.lastWsAt === lastWsAt) {
          next[asset.asset_id] = existing;
          continue;
        }

        const base = getStableBaseQuote(asset, existing);

        next[asset.asset_id] = {
          rate: base.wsRate,
          buyPrice: base.buyPrice,
          sellPrice: base.sellPrice,
          buyOffset: base.buyOffset,
          sellOffset: base.sellOffset,
          changePercent: base.wsChangePercent,
          baseWsRate: base.wsRate,
          baseWsChangePercent: base.wsChangePercent,
          lastWsAt,
        };
      }

      return next;
    });
  }, [assets, lastWebsocketUpdateByAsset]);

  useEffect(() => {
    if (!MARKET_WATCH_SYNTHETIC_TICKS.enabled) {
      return;
    }

    const interval = window.setInterval(() => {
      setSyntheticQuotes((prev) => {
        if (Object.keys(prev).length === 0) {
          return prev;
        }

        const now = Date.now();
        const next = { ...prev };
        let changed = false;

        for (const [assetId, quote] of Object.entries(prev)) {
          const asset = assetsByIdRef.current.get(assetId);
          const lastWsAt = lastWsByAssetRef.current[assetId];

          if (
            !asset ||
            !lastWsAt ||
            now - lastWsAt > MARKET_WATCH_SYNTHETIC_TICKS.maxSilenceMs
          ) {
            delete next[assetId];
            changed = true;
            continue;
          }

          if (lastWsAt !== quote.lastWsAt) {
            const base = getStableBaseQuote(asset, quote);

            next[assetId] = {
              rate: base.wsRate,
              buyPrice: base.buyPrice,
              sellPrice: base.sellPrice,
              buyOffset: base.buyOffset,
              sellOffset: base.sellOffset,
              changePercent: base.wsChangePercent,
              baseWsRate: base.wsRate,
              baseWsChangePercent: base.wsChangePercent,
              lastWsAt,
            };
            changed = true;
            continue;
          }

          if (Math.random() > MARKET_WATCH_SYNTHETIC_TICKS.tickProbability) {
            continue;
          }

          const stepRatio = getRandomStepRatio();
          const direction = Math.random() > 0.5 ? 1 : -1;
          const nextRate = Math.max(
            quote.rate + quote.rate * stepRatio * direction,
            MIN_PRICE
          );
          const relativeChangePercent =
            quote.baseWsRate > 0
              ? ((nextRate - quote.baseWsRate) / quote.baseWsRate) * 100
              : 0;

          next[assetId] = {
            rate: nextRate,
            buyPrice: Math.max(nextRate + quote.buyOffset, MIN_PRICE),
            sellPrice: Math.max(nextRate - quote.sellOffset, MIN_PRICE),
            buyOffset: quote.buyOffset,
            sellOffset: quote.sellOffset,
            changePercent: quote.baseWsChangePercent + relativeChangePercent,
            baseWsRate: quote.baseWsRate,
            baseWsChangePercent: quote.baseWsChangePercent,
            lastWsAt: quote.lastWsAt,
          };
          changed = true;
        }

        return changed ? next : prev;
      });
    }, MARKET_WATCH_SYNTHETIC_TICKS.intervalMs);

    return () => {
      window.clearInterval(interval);
    };
  }, []);

  const getDisplayQuote = useCallback(
    (asset: Asset): MarketWatchDisplayQuote => {
      if (!MARKET_WATCH_SYNTHETIC_TICKS.enabled) {
        return getBaseQuote(asset);
      }

      const synthetic = syntheticQuotes[asset.asset_id];
      if (!synthetic) {
        return getBaseQuote(asset);
      }

      return {
        rate: synthetic.rate,
        buyPrice: synthetic.buyPrice,
        sellPrice: synthetic.sellPrice,
        changePercent: synthetic.changePercent,
        isSynthetic: true,
      };
    },
    [syntheticQuotes]
  );

  return {
    enabled: MARKET_WATCH_SYNTHETIC_TICKS.enabled,
    getDisplayQuote,
  };
}
