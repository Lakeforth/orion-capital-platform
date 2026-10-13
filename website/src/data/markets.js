// Instrument rows for the quote widgets (home page + market pages).
// Figures are the base44 reference's indicative values — never live prices. The fifth
// field is the key into src/config/feed.js `quoteMap`; QuoteFeed.astro overwrites the
// price/change cells for any row whose key it knows once the feed is switched on.
//
// Row shape: [name, ticker, price, change, feedKey, badge?]

// ── Home page: icon tabs, "Most Popular" (last) selected first ─────────────────────────
// Only markets the live feed carries. The ETFs, Bonds and Futures tabs were removed on
// 7 Oct 2026 because the feed has no source for them; their icons are tab-3/6/8.
export const homeTabs = [
  { label: 'Forex', icon: 'tab-1', rows: [
    ['Euro / US Dollar', 'EUR/USD', '1.14704', '-0.07%', 'EUR/USD'],
    ['British Pound / US Dollar', 'GBP/USD', '1.3247', '0.05%', 'GBP/USD'],
    ['US Dollar / Japanese Yen', 'USD/JPY', '157.77', '-0.04%', 'USD/JPY'],
    ['Australian Dollar / US Dollar', 'AUD/USD', '0.6954', '0.02%', 'AUD/USD'],
    ['US Dollar / Canadian Dollar', 'USD/CAD', '1.4251', '0.04%', 'USD/CAD'],
  ]},
  { label: 'Stocks', icon: 'tab-2', rows: [
    ['Apple', 'AAPL', '333.69', '1.02%', 'AAPL'],
    ['Microsoft', 'MSFT', '517.53', '0.92%', 'MSFT'],
    ['Tesla', 'TSLA', '375.39', '2.83%', 'TSLA'],
    ['Amazon', 'AMZN', '251.52', '1.33%', 'AMZN'],
    ['NVIDIA', 'NVDA', '233.95', '1.34%', 'NVDA'],
  ]},
  { label: 'Crypto', icon: 'tab-4', rows: [
    ['Bitcoin', 'BTCUSD', '85,782.40', '5.40%', 'BTC/USD'],
    ['Ethereum', 'ETHUSD', '2,730.03', '1.61%', 'ETH/USD'],
    ['Ripple', 'XRPUSD', '1.5184', '2.17%', 'XRP/USD'],
    ['Litecoin', 'LTCUSD', '70.68', '0.56%', 'LTC/USD'],
    ['Solana', 'SOLUSD', '121.65', '1.72%', 'SOL/USD'],
  ]},
  { label: 'Indices', icon: 'tab-5', rows: [
    ['US_500', 'US_500', '7,815.50', '1.04%', 'US 500'],
    ['US_TECH100', 'US_TECH100', '30,658.25', '2.06%', 'US TECH 100'],
    ['GER_40', 'GER_40', '25,288.20', '1.22%', 'GER 40'],
    ['UK_100', 'UK_100', '10,507.60', '0.34%', 'UK 100'],
    ['JPN_225', 'JPN_225', '69,778.20', '0.25%', 'JPN 225'],
  ]},
  { label: 'Commodities', icon: 'tab-7', rows: [
    ['Gold', 'GOLD', '4,354.48', '-0.42%', 'XAU/USD'],
    ['Silver', 'SILVER', '66.340', '0.04%', 'XAG/USD'],
    ['Crude Oil', 'CrudeOIL', '91.51', '-4.51%', 'WTI'],
    ['Brent Oil', 'BRENT', '106.07', '-0.34%', 'BRENT'],
    ['Natural Gas', 'NATGAS', '2.901', '-0.85%', 'NATGAS'],
  ]},
  { label: 'Most Popular', icon: 'tab-9', rows: [
    ['Gold', 'GOLD', '4,354.48', '-0.42%', 'XAU/USD'],
    ['Silver', 'SILVER', '66.340', '0.04%', 'XAG/USD'],
    ['Crude Oil', 'CrudeOIL', '91.51', '-4.51%', 'WTI'],
    ['US_TECH100', 'US_TECH100', '30,658.25', '2.06%', 'US TECH 100'],
    ['US_500', 'US_500', '7,815.50', '1.04%', 'US 500'],
    ['BTCUSD', 'BTCUSD', '85,782.40', '5.40%', 'BTC/USD'],
  ]},
];

// ── Market pages: one list each ───────────────────────────────────────────────────────────
// Trimmed to instruments the live feed carries. The topic pages show their topic only:
// Bitcoin Trading lists bitcoin instruments, Gold Trading gold, Oil Trading oil.
// etfRows and bondRows have no feed source yet and stay as static figures.
export const forexRows = [
  ['EUR/USD', 'EURUSD', '1.13785', '-0.03%', 'EUR/USD'],
  ['GBP/USD', 'GBPUSD', '1.32351', '-0.02%', 'GBP/USD'],
  ['USD/JPY', 'USDJPY', '157.873', '-0.25%', 'USD/JPY'],
  ['EUR/JPY', 'EURJPY', '179.638', '-0.27%', 'EUR/JPY'],
  ['AUD/USD', 'AUDUSD', '0.70301', '-0.04%', 'AUD/USD'],
  ['EUR/CHF', 'EURCHF', '0.93819', '-0.10%', 'EUR/CHF'],
  ['EUR/GBP', 'EURGBP', '0.85965', '-0.01%', 'EUR/GBP'],
  ['USD/CAD', 'USDCAD', '1.41059', '0.02%', 'USD/CAD'],
];

export const cryptoRows = [
  ['BTCUSD', 'BTCUSD', '83,529.90', '-1.00%', 'BTC/USD'],
  ['ETHEREUM', 'ETHUSD', '2,663.79', '-0.73%', 'ETH/USD'],
  ['RIPPLE', 'XRP', '1.4723', '-1.83%', 'XRP/USD'],
  ['LITECOIN', 'LTCUSD', '68.15', '9.38%', 'LTC/USD'],
  ['SOLANA', 'SOLUSD', '113.56', '-1.11%', 'SOL/USD'],
  ['DOGECOIN', 'DOGEUSD', '0.0894', '0.00%', 'DOGE/USD'],
];

export const bitcoinRows = [
  ['BTCUSD', 'BTCUSD', '83,477.40', '-1.06%', 'BTC/USD'],
  ['ETHEREUM', 'ETHUSD', '2,646.93', '-1.37%', 'ETH/USD'],
  ['RIPPLE', 'XRP', '1.4657', '-2.29%', 'XRP/USD'],
  ['LITECOIN', 'LTCUSD', '66.68', '7.38%', 'LTC/USD'],
  ['SOLANA', 'SOLUSD', '113.18', '-1.45%', 'SOL/USD'],
  ['DOGECOIN', 'DOGEUSD', '0.0894', '0.00%', 'DOGE/USD'],
];

export const stockRows = [
  ['APPLE', '#APPLE', '336.01', '0.00%', 'AAPL'],
  ['GOOGLE', '#GOOGLE', '342.57', '0.00%', 'GOOGL'],
  ['AMAZON', '#AMAZON', '249.38', '0.00%', 'AMZN'],
  ['INTEL', '#INTEL', '127.27', '0.00%', 'INTC'],
  ['MICROSOFT', '#MICROSOFT', '497.43', '0.00%', 'MSFT'],
  ['TESLA', '#TESLA', '378.08', '0.00%', 'TSLA'],
  ['NETFLIX', '#NETFLIX', '71.66', '0.00%', 'NFLX'],
  ['NVIDIA', '#NVIDIA', '238.68', '0.00%', 'NVDA'],
];

export const indexRows = [
  ['US_500', 'US_500', '7,769.00', '-0.19%', 'US 500'],
  ['US_TECH100', 'US_TECH100', '30,645.75', '-0.66%', 'US TECH 100'],
  ['US_30', 'US_30', '51,887', '-0.15%', 'US 30'],
  ['UK_100', 'UK_100', '10,798.0', '-0.04%', 'UK 100'],
  ['GER_40', 'GER_40', '25,288.2', '0.00%', 'GER 40'],
  ['US_2000', 'US_2000', '2,835.8', '-0.41%', 'US 2000'],
  ['FRANCE_40', 'FRANCE_40', '7,756.6', '0.00%', 'FRANCE 40'],
  ['SPAIN 35', 'SPAIN35', '17,611', '0.00%', 'SPAIN 35'],
];

export const etfRows = [
  ['MSCI Emerging Markets Index Fund', '#EEM', '66.59', '-2.03%', 'EEM'],
  ['S&P 500 VIX Short-Term Futures ETN - Series B', '#VXXB', '17.72', '2.48%', 'VXXB'],
  ['MSCI Brazil Index Fund', '#EWZ', '36.35', '-1.18%', 'EWZ'],
  ['Energy Select Sector SPDR', '#XLE', '62.34', '0.59%', 'XLE'],
  ['Market Vectors TR Gold Miners', '#GDX', '88.35', '-5.06%', 'GDX'],
  ['MSCI EAFE Index Fund', '', '104.58', '-0.90%', 'EFA'],
  ['India Earnings Fund', '', '40.92', '-2.03%', 'EPI'],
  ['MSCI Australia Index Fund', '', '28.41', '-0.04%', 'EWA'],
];

export const commodityRows = [
  ['Crude Oil', 'CrudeOIL', '92.67', '-0.66%', 'WTI'],
  ['Brent Oil', 'BRENT', '105.54', '0.00%', 'BRENT'],
  ['Natural Gas', 'NATGAS', '3.070', '0.00%', 'NATGAS'],
  ['Gold', 'GOLD', '4,136.93', '-2.98%', 'XAU/USD'],
  ['Silver', 'SILVER', '61.357', '-3.84%', 'XAG/USD'],
  ['Platinum', 'PLATINUM', '1,749.0', '-1.62%', 'XPT/USD'],
  ['Palladium', 'PALLADIUM', '1,104.6', '0.00%', 'XPD/USD'],
  ['Copper', 'COPPER', '6.5378', '0.00%', 'COPPER'],
  ['Corn', 'CORN', '4.916', '-1.10%', 'CORN'],
  ['Soybean', 'SOYBEAN', '12.992', '-2.39%', 'SOYBEAN'],
  ['Wheat', 'WHEAT', '6.838', '0.00%', 'WHEAT'],
  ['Sugar', 'SUGAR', '0.1967', '0.00%', 'SUGAR'],
];

export const bitcoinTradingRows = [
  ['BTCUSD', 'BTCUSD', '83,903.40', '0.55%', 'BTC/USD'],
  ['Bitcoin Cash', 'BCHUSD', '299.69', '0.00%', 'BCH/USD'],
  ['Wrapped Bitcoin', 'WBTCUSD', '83,267.60', '0.00%', 'WBTC/USD'],
];

export const goldRows = [
  ['Gold', 'XAU/USD', '4,163.08', '0.86%', 'XAU/USD'],
  ['Gold / Euro', 'XAU/EUR', '3,653.75', '0.00%', 'XAU/EUR'],
  ['Gold / Pound', 'XAU/GBP', '3,094.97', '0.00%', 'XAU/GBP'],
  ['Gold / Yen', 'XAU/JPY', '646,774', '0.00%', 'XAU/JPY'],
  ['Gold / Swiss Franc', 'XAU/CHF', '3,403.26', '0.00%', 'XAU/CHF'],
  ['Gold / Australian Dollar', 'XAU/AUD', '5,877.89', '0.00%', 'XAU/AUD'],
  ['Gold / Canadian Dollar', 'XAU/CAD', '5,824.57', '0.00%', 'XAU/CAD'],
  ['Gold / Silver Ratio', 'XAU/XAG', '68.714', '0.00%', 'XAU/XAG'],
];

export const oilRows = [
  ['Crude Oil (WTI)', 'CrudeOIL', '90.10', '0.00%', 'WTI'],
  ['Brent Oil', 'BRENT', '105.54', '0.00%', 'BRENT'],
];

export const bondRows = [
  ['EURO-BUND', 'EURO-BUND', '119.65', '0.16%', 'BUND'],
  ['JAPAN GOVT BOND', 'JAPAN_BOND', '124.15', '0.00%', 'JGB'],
];

// ── Platforms accordion (shared by home + market pages) ─────────────────────────────────
// Images live in public/images/home. Names follow the home page (Social Trading)
// rather than the reference's leftover AvaSocial.
export const platforms = [
  { title: 'WebTrader',                desc: 'Our browser platform. Nothing to install, available today.', img: 'platform-webtrader' },
  { title: 'TradingView',              desc: 'Connect with millions of traders and share strategies.',     img: 'platform-tradingview-app' },
  { title: 'Social Trading',                desc: 'Copy trading. In development, not yet available.',           img: 'platform-social-trading' },
];
