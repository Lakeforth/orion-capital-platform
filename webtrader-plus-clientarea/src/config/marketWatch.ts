const toNumber = (value: string | undefined, fallback: number) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

export const MARKET_WATCH_SYNTHETIC_TICKS = {
  enabled: import.meta.env.VITE_ENABLE_MARKET_WATCH_SYNTHETIC_TICKS !== "false",
  intervalMs: toNumber(import.meta.env.VITE_MARKET_WATCH_TICK_INTERVAL_MS, 320),
  maxSilenceMs: toNumber(
    import.meta.env.VITE_MARKET_WATCH_MAX_SILENCE_MS,
    2 * 60 * 1000
  ),
  minStepRatio: toNumber(
    import.meta.env.VITE_MARKET_WATCH_MIN_STEP_RATIO,
    0.0000002
  ),
  maxStepRatio: toNumber(
    import.meta.env.VITE_MARKET_WATCH_MAX_STEP_RATIO,
    0.0000018
  ),
  tickProbability: toNumber(
    import.meta.env.VITE_MARKET_WATCH_TICK_PROBABILITY,
    0.45
  ),
} as const;
