import { useEffect, useRef, useState } from "react";
import {
  CandlestickSeries,
  ColorType,
  createChart,
  LineStyle,
  type CandlestickData,
  type IChartApi,
  type IPriceLine,
  type ISeriesApi,
  type UTCTimestamp,
} from "lightweight-charts";
import axiosInstance from "@/lib/axios";
import useAssetStore from "@/store/assetStore";
import useTradeStore from "@/store/tradeStore";
import useDarkModeStore from "@/store/darkModeStore";
import { formatVolume, pricePrecision } from "@/lib/price-format";
import TradingViewLightWidget from "./TradingViewLightWidget";

const TIMEFRAMES = ["M1", "M5", "M15", "M30", "H1", "H4", "D1"] as const;
type Timeframe = (typeof TIMEFRAMES)[number];
const PERIOD_SECONDS: Record<Timeframe, number> = { M1: 60, M5: 300, M15: 900, M30: 1800, H1: 3600, H4: 14400, D1: 86400 };
const TIMEFRAME_KEY = "mt4-chart-timeframe";
const VIEW_KEY = "mt4-chart-view";

type Bar = CandlestickData<UTCTimestamp>;

/** Trades carry "XAU/USD" while assets use "XAU_USD" or "EURUSD": compare letters and digits only. */
const symbolKey = (symbol?: string | null) => (symbol ?? "").toUpperCase().replace(/[^A-Z0-9]/g, "");

function themeOptions(isDarkMode: boolean) {
  const grid = isDarkMode ? "#1e293b" : "#eef2f7";
  return {
    layout: {
      background: { type: ColorType.Solid, color: isDarkMode ? "#0f172a" : "#ffffff" },
      textColor: isDarkMode ? "#cbd5e1" : "#334155",
    },
    grid: { vertLines: { color: grid }, horzLines: { color: grid } },
  } as const;
}

function readSetting<T extends string>(key: string, allowed: readonly T[], fallback: T): T {
  try {
    const value = localStorage.getItem(key) as T | null;
    return value && allowed.includes(value) ? value : fallback;
  } catch {
    return fallback;
  }
}

function saveSetting(key: string, value: string) {
  try {
    localStorage.setItem(key, value);
  } catch {
    /* storage unavailable: the choice still applies for this session */
  }
}

/**
 * MT4-style price chart drawn on the broker's own Bid (not TradingView's feed):
 * history from /assets/{id}/candles, the last candle driven by live ticks, and
 * entry / Stop Loss / Take Profit lines for every open trade on the symbol.
 * Symbols without a history source (stocks) fall back to the TradingView embed,
 * which clients can also pick themselves with the "TradingView" toggle.
 */
export default function Mt4PriceChart() {
  const activeAsset = useAssetStore((state) => state.activeAsset);
  const assetId = activeAsset?.id;
  const liveBid = useAssetStore((state) => {
    const asset = state.assets.find((a) => a.id === assetId);
    return asset ? Number(asset.sell_price) : undefined;
  });
  const openTrades = useTradeStore((state) => state.openTrades);
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);

  const [timeframe, setTimeframe] = useState<Timeframe>(() => readSetting(TIMEFRAME_KEY, TIMEFRAMES, "M1"));
  const [view, setView] = useState<"broker" | "tradingview">(() => readSetting(VIEW_KEY, ["broker", "tradingview"] as const, "broker"));
  const [status, setStatus] = useState<"loading" | "ready" | "unavailable">("loading");

  const container = useRef<HTMLDivElement>(null);
  const chartRef = useRef<IChartApi | null>(null);
  const seriesRef = useRef<ISeriesApi<"Candlestick"> | null>(null);
  const lastBar = useRef<Bar | null>(null);
  // Which symbol/timeframe lastBar belongs to: a tick for a newly selected symbol must never
  // land on the previous symbol's candles while its own history is still loading.
  const loadedKey = useRef<string | null>(null);
  const [reloadNonce, setReloadNonce] = useState(0);
  const tradeLines = useRef<IPriceLine[]>([]);

  // One chart instance for the component's lifetime.
  useEffect(() => {
    if (!container.current) return;
    const chart = createChart(container.current, {
      autoSize: true,
      timeScale: { timeVisible: true, secondsVisible: false, rightOffset: 6 },
      rightPriceScale: { borderVisible: false },
      crosshair: { mode: 0 },
      ...themeOptions(useDarkModeStore.getState().isDarkMode),
    });
    seriesRef.current = chart.addSeries(CandlestickSeries, {
      upColor: "#26a69a",
      downColor: "#ef5350",
      wickUpColor: "#26a69a",
      wickDownColor: "#ef5350",
      borderVisible: false,
    });
    chartRef.current = chart;

    return () => {
      chart.remove();
      chartRef.current = null;
      seriesRef.current = null;
      tradeLines.current = [];
    };
  }, []);

  useEffect(() => {
    chartRef.current?.applyOptions(themeOptions(isDarkMode));
  }, [isDarkMode]);

  // History for the symbol/timeframe, priced like Market Watch.
  useEffect(() => {
    if (!assetId || view !== "broker") return;
    let cancelled = false;
    const key = `${assetId}:${timeframe}`;
    loadedKey.current = null;
    lastBar.current = null;
    setStatus("loading");
    const precision = pricePrecision(activeAsset?.type, activeAsset?.sy ?? "", Number(activeAsset?.sell_price) || Number(activeAsset?.rate) || 0);
    seriesRef.current?.applyOptions({ priceFormat: { type: "price", precision, minMove: 1 / 10 ** precision } });

    axiosInstance
      .get(`/assets/${assetId}/candles`, { params: { timeframe, count: 300 } })
      .then((response) => {
        if (cancelled) return;
        const candles: Bar[] = response.data?.data?.candles ?? [];
        if (!candles.length || !seriesRef.current) {
          setStatus("unavailable");
          return;
        }
        seriesRef.current.setData(candles);
        lastBar.current = candles[candles.length - 1];
        loadedKey.current = key;
        chartRef.current?.timeScale().scrollToRealTime();
        setStatus("ready");
      })
      .catch(() => {
        if (!cancelled) setStatus("unavailable");
      });

    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [assetId, timeframe, view, reloadNonce]);

  // Live ticks move the last candle, or open the next one once its period is over
  // (stepping from the last bar keeps the provider's own alignment, e.g. OANDA's daily close).
  useEffect(() => {
    const series = seriesRef.current;
    const bar = lastBar.current;
    if (status !== "ready" || !series || !bar || !liveBid || !Number.isFinite(liveBid)) return;
    if (loadedKey.current !== `${assetId}:${timeframe}`) return;
    const period = PERIOD_SECONDS[timeframe];
    const now = Math.floor(Date.now() / 1000);
    const next: Bar =
      now < bar.time + period
        ? { ...bar, high: Math.max(bar.high, liveBid), low: Math.min(bar.low, liveBid), close: liveBid }
        : {
            time: (bar.time + period * Math.floor((now - bar.time) / period)) as UTCTimestamp,
            open: bar.close,
            high: Math.max(bar.close, liveBid),
            low: Math.min(bar.close, liveBid),
            close: liveBid,
          };
    try {
      series.update(next);
      lastBar.current = next;
    } catch {
      // The chart refused the tick (e.g. its data was replaced underneath it): reload the history.
      loadedKey.current = null;
      setReloadNonce((n) => n + 1);
    }
  }, [liveBid, status, timeframe, assetId]);

  // Entry, Stop Loss and Take Profit lines for the open trades on this symbol.
  useEffect(() => {
    const series = seriesRef.current;
    if (!series) return;
    tradeLines.current.forEach((line) => {
      try {
        series.removePriceLine(line);
      } catch {
        /* line already gone with replaced series data */
      }
    });
    tradeLines.current = [];
    if (status !== "ready" || !activeAsset) return;

    const add = (price: number, color: string, title: string, lineStyle: LineStyle) => {
      if (Number.isFinite(price) && price > 0) {
        tradeLines.current.push(series.createPriceLine({ price, color, title, lineStyle, lineWidth: 1, axisLabelVisible: true }));
      }
    };
    openTrades
      .filter((trade) => trade.asset_id === activeAsset.id || symbolKey(trade.asset_symbol) === symbolKey(activeAsset.symbol_display))
      .forEach((trade) => {
        const side = trade.trade_type === "buy" ? "BUY" : "SELL";
        const lots = trade.volume_lots != null ? ` ${formatVolume(trade.volume_lots)}` : "";
        add(Number(trade.opening_price), trade.trade_type === "buy" ? "#2563eb" : "#dc2626", `${side}${lots}`, LineStyle.Solid);
        add(Number(trade.stop_loss), "#ef4444", "SL", LineStyle.Dashed);
        add(Number(trade.take_profit), "#16a34a", "TP", LineStyle.Dashed);
      });
  }, [openTrades, activeAsset, status]);

  const chooseTimeframe = (next: Timeframe) => {
    setTimeframe(next);
    saveSetting(TIMEFRAME_KEY, next);
  };
  const chooseView = (next: "broker" | "tradingview") => {
    setView(next);
    saveSetting(VIEW_KEY, next);
  };

  const showTradingView = view === "tradingview" || status === "unavailable";
  const button = (active: boolean) =>
    `h-6 px-2 rounded text-[11px] font-semibold transition-colors ${
      active
        ? "bg-blue-600 text-white"
        : isDarkMode
        ? "text-slate-300 hover:bg-slate-700"
        : "text-slate-600 hover:bg-slate-200"
    }`;

  return (
    <div className="absolute inset-0 flex flex-col">
      <div
        className={`h-8 shrink-0 flex items-center gap-1 px-2 border-b ${
          isDarkMode ? "bg-slate-900 border-slate-700" : "bg-[#F7F9FB] border-slate-300"
        }`}
      >
        <span className={`mr-2 text-[12px] font-bold ${isDarkMode ? "text-slate-100" : "text-slate-800"}`}>
          {activeAsset?.symbol_display ?? ""}
          {!showTradingView && <span className="ml-1 font-normal opacity-60">{timeframe} · Bid</span>}
        </span>
        {!showTradingView &&
          TIMEFRAMES.map((tf) => (
            <button key={tf} type="button" className={button(tf === timeframe)} onClick={() => chooseTimeframe(tf)} aria-pressed={tf === timeframe}>
              {tf}
            </button>
          ))}
        <div className="ml-auto flex items-center gap-1">
          <button type="button" className={button(!showTradingView)} onClick={() => chooseView("broker")} disabled={status === "unavailable" && view === "broker"}>
            Chart
          </button>
          <button type="button" className={button(showTradingView)} onClick={() => chooseView("tradingview")}>
            TradingView
          </button>
        </div>
      </div>
      <div className="relative flex-1 min-h-0">
        <div ref={container} className={`absolute inset-0 ${showTradingView ? "invisible" : ""}`} />
        {status === "loading" && !showTradingView && (
          <div className={`absolute inset-0 flex items-center justify-center text-xs ${isDarkMode ? "text-slate-400" : "text-slate-500"}`}>
            Loading chart…
          </div>
        )}
        {showTradingView && (
          <div className="absolute inset-0">
            {status === "unavailable" && view === "broker" && (
              <div className="absolute top-1 left-1/2 -translate-x-1/2 z-10 rounded bg-amber-100 px-2 py-0.5 text-[11px] text-amber-800">
                No broker price history for this symbol — showing TradingView
              </div>
            )}
            <TradingViewLightWidget />
          </div>
        )}
      </div>
    </div>
  );
}
