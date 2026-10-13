// NOTE: the market-data host is withheld from this handover. The feed is a
// separate service; point baseUrl at whichever quote service you use.
// Live quote feed — configuration and symbol map.
//
// Points at the tenant's own market-data service (the same service the webtrader prices
// itself from), so the marketing site cannot quote a number the platform disagrees with.
//
// The service is anonymous and sends `Access-Control-Allow-Origin: *`, so the browser calls
// it directly — no proxy, no API key in the bundle, nothing to leak. Polling it costs the
// tenant NO upstream vendor quota: the service polls its own providers on its own schedule
// regardless of how often we ask.
//
// ---------------------------------------------------------------------------------------
//  SWITCHING ENDPOINTS IS THIS ONE LINE.
//
//  '<MARKET_DATA_HOST_STAGING>'   — staging. All six categories served by TradingView.
//                                         Currently returns zero assets: the account's
//                                         real-time streaming allowance (1,000/month) is
//                                         exhausted, so the service cannot mint a stream
//                                         token. Resets 28 Oct.
//  '<MARKET_DATA_HOST>'  — production. crypto=CEX.IO, forex/metals/
//                                         commodities/indices=OANDA, stocks=TradingView.
//                                         Live now for everything except stocks.
//
//  NOTE: OANDA's API licence restricts redistribution of its rates to third parties, which
//  is why staging (all-TradingView) is the intended target. Until it has data, the page
//  renders its unavailable state rather than inventing numbers.
// ---------------------------------------------------------------------------------------
export const feed = {
  // ON since 7 Oct 2026, at the owner's request for live prices in the home page table.
  //
  // Pointed at PRODUCTION because staging still returns zero assets until its TradingView
  // streaming quota resets on 28 Oct. Production's forex/metals/commodities/indices come
  // from OANDA, whose licence restricts redistribution (see the note above), so switch
  // baseUrl back to staging once it serves data again.
  enabled: true,

  baseUrl: '<MARKET_DATA_HOST>',

  // Poll cadence. Measured 7 Oct: the service refreshes FX/metals/indices every ~5s and
  // crypto about once a minute. Polling every 2s shows each refresh within 2s of it
  // landing, which is as close to TradingView's tick-by-tick feel as this feed allows;
  // anything faster only adds requests. QuoteFeed skips a poll while one is in flight.
  pollMs: 2000,

  // A quote older than this during an open session is shown as stale rather than current.
  // Generous enough to ride out one missed upstream refresh.
  staleAfterMs: 180000,

  // Give up on a single request after this and keep the previous state.
  timeoutMs: 8000,

  // When the feed cannot supply an instrument, blank the price rather than leaving the
  // figure baked into the HTML on screen. Those figures are design placeholders — gold is
  // currently rendered at 2,041.60 against roughly 4,141 — so falling back to them would
  // publish a wrong price under a label that only says "indicative".
  // Set false to fall back to the static markup instead.
  blankWhenUnavailable: true,
};

// ---------------------------------------------------------------------------------------
// Display symbol -> feed instrument.
//
// `id` is the service's own `symbol` field. `dp` is decimal places and `group` turns on
// thousands separators — both taken from the figures already on the page so live values
// render at the precision the design was built around, not the feed's native precision.
//
// Anything absent from this map is left exactly as server-rendered and never touched.
// ---------------------------------------------------------------------------------------
export const quoteMap = {
  // --- FX ---
  'EUR/USD': { id: 'EURUSD',  dp: 4 },
  'GBP/USD': { id: 'GBPUSD',  dp: 4 },
  'USD/JPY': { id: 'USDJPY',  dp: 2 },
  'AUD/USD': { id: 'AUDUSD',  dp: 4 },
  'USD/CAD': { id: 'USDCAD',  dp: 4 },
  'EUR/JPY': { id: 'EURJPY',  dp: 3 },
  'EUR/CHF': { id: 'EURCHF',  dp: 4 },
  'EUR/GBP': { id: 'EURGBP',  dp: 4 },

  // --- Crypto (the only category carrying changePercent24h) ---
  'BTC/USD': { id: 'BTCUSD',  dp: 0, group: true },
  'ETH/USD': { id: 'ETHUSD',  dp: 2, group: true },
  'XRP/USD': { id: 'XRPUSD',  dp: 4 },
  'LTC/USD': { id: 'LTCUSD',  dp: 2 },
  'SOL/USD': { id: 'SOLUSD',  dp: 2 },
  'DOGE/USD': { id: 'DOGEUSD', dp: 4 },
  'BCH/USD':  { id: 'BCHUSD',  dp: 2 },
  'WBTC/USD': { id: 'WBTCUSD', dp: 0, group: true },

  // --- Indices ---
  // GER 40 maps to the feed's DE30_EUR. Same index: OANDA kept the pre-2021 instrument
  // code after the DAX expanded from 30 constituents to 40.
  'US 500':      { id: 'SPX500_USD', dp: 1, group: true },
  'US TECH 100': { id: 'NAS100_USD', dp: 1, group: true },
  'GER 40':      { id: 'DE30_EUR',   dp: 1, group: true },
  'UK 100':      { id: 'UK100_GBP',  dp: 1, group: true },
  'JPN 225':     { id: 'JP225_USD',  dp: 1, group: true },
  'US 30':       { id: 'US30_USD',   dp: 1, group: true },
  'US 2000':     { id: 'US2000_USD', dp: 1, group: true },
  'FRANCE 40':   { id: 'FR40_EUR',   dp: 1, group: true },
  'SPAIN 35':    { id: 'ESPIX_EUR',  dp: 1, group: true },

  // --- Metals & energy ---
  'XAU/USD': { id: 'XAU_USD',    dp: 2, group: true },
  'XAG/USD': { id: 'XAG_USD',    dp: 2 },
  'WTI':     { id: 'WTICO_USD',  dp: 2 },
  'WTI/USD': { id: 'WTICO_USD',  dp: 2 },
  'BRENT':   { id: 'BCO_USD',    dp: 2 },
  'NATGAS':  { id: 'NATGAS_USD', dp: 3 },
  'XPT/USD': { id: 'XPT_USD',    dp: 2, group: true },
  'XPD/USD': { id: 'XPD_USD',    dp: 2, group: true },
  'COPPER':  { id: 'XCU_USD',    dp: 4 },
  'CORN':    { id: 'CORN_USD',   dp: 3 },
  'SOYBEAN': { id: 'SOYBN_USD',  dp: 3 },
  'WHEAT':   { id: 'WHEAT_USD',  dp: 3 },
  'SUGAR':   { id: 'SUGAR_USD',  dp: 4 },

  // --- Gold in other currencies (Gold Trading page) ---
  'XAU/EUR': { id: 'XAU_EUR', dp: 2, group: true },
  'XAU/GBP': { id: 'XAU_GBP', dp: 2, group: true },
  'XAU/JPY': { id: 'XAU_JPY', dp: 0, group: true },
  'XAU/CHF': { id: 'XAU_CHF', dp: 2, group: true },
  'XAU/AUD': { id: 'XAU_AUD', dp: 2, group: true },
  'XAU/CAD': { id: 'XAU_CAD', dp: 2, group: true },
  'XAU/XAG': { id: 'XAU_XAG', dp: 3 },

  // --- Single-name equities ---
  // Mapped 7 Oct 2026, when production's stocks category was found to carry 87 names.
  // Their timestamps run a few minutes behind (the service's equity refresh is slower),
  // so a stocks-only table can read 'Delayed' in its status line. Real-time US equity
  // display can also need an exchange entitlement; confirm before relying on these.
  'AAPL':  { id: 'AAPL', dp: 2 },
  'GOOGL': { id: 'GOOG', dp: 2 },
  'AMZN':  { id: 'AMZN', dp: 2 },
  'INTC':  { id: 'INTC', dp: 2 },
  'MSFT':  { id: 'MSFT', dp: 2 },
  'TSLA':  { id: 'TSLA', dp: 2 },
  'NFLX':  { id: 'NFLX', dp: 2 },
  'NVDA':  { id: 'NVDA', dp: 2 },
};

// Every distinct instrument id the page needs, deduplicated — one batch request covers all.
export const feedSymbols = [...new Set(Object.values(quoteMap).map((m) => m.id))];
