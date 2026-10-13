// Navigation is data, not markup: the same tree feeds the desktop mega-menu, the
// mobile drawer, the footer and the sitemap. Adding a page means adding a node here.
//
// `built: false` entries keep the full broker-scale taxonomy visible (the client wants
// the breadth of the reference site) while routing to an honest "in progress" page
// instead of a 404. Flip to `built: true` as each page lands.

const soon = '/coming-soon';
const L = (label, href, built = false, note = '') => ({ label, href: built ? href : soon, built, note });

export const megaMenu = [
  {
    label: 'Trading',
    columns: [
      {
        heading: 'Products',
        items: [
          L('CFD Trading', '/trading-info/cfd-trading'),
          L('Forex Trading', '/markets/forex', true),
          L('Futures', '/markets/futures', true),
        ],
      },
      {
        heading: 'Conditions',
        items: [
          L('Account Types', '/accounts', true),
          L('Trading Hours', '/trading-info/trading-hours'),
          L('Rollover Dates', '/trading-info/rollover'),
        ],
      },
      {
        heading: 'Funding',
        items: [
          L('Deposits & Withdrawals', '/trading-info/deposits-withdrawals', true),
          L('Payment Methods', '/trading-info/deposits-withdrawals#methods', true),
          L('Islamic Account', '/trading-info/islamic-account'),
        ],
      },
    ],
  },
  {
    label: 'Markets',
    columns: [
      {
        heading: 'Popular',
        items: [
          L('Forex', '/markets/forex', true),
          L('Cryptocurrencies', '/markets/crypto', true),
          L('Stocks & Indices', '/markets/stocks', true),
          L('Commodities', '/markets/commodities', true),
        ],
      },
      {
        heading: 'More Markets',
        items: [
          L('Indices', '/markets/indices', true),
          L('ETFs', '/markets/etfs', true),
          L('Bonds & Treasuries', '/markets/bonds', true),
          L('Energies & Metals', '/markets/commodities', true),
        ],
      },
      {
        heading: 'Top Instruments',
        items: [
          L('Gold', '/markets/commodities/gold-trading', true),
          L('Oil', '/markets/commodities/oil-trading', true),
          L('Bitcoin', '/markets/crypto/bitcoin-trading', true),
          L('EUR/USD', '/markets/forex', true),
        ],
      },
    ],
  },
  {
    label: 'Platforms & Tools',
    columns: [
      {
        heading: 'Platforms',
        items: [
          L('Platforms Overview', '/trading-platforms', true),
          L('WebTrader', '/trading-platforms/webtrader', true),
        ],
      },
      {
        heading: 'Trading Tools',
        items: [
          L('Economic Calendar', '/tools/economic-calendar'),
          L('Trading Calculator', '/tools/trading-calculator'),
          L('Trading Signals', '/tools/trading-signals'),
          L('Market Analysis', '/tools/market-analysis'),
        ],
      },
      {
        heading: 'Automated',
        items: [
          L('Copy Trading', '/trading-platforms/copy-trading'),
          L('Algo Trading', '/trading-platforms/algo-trading'),
          L('Expert Advisors', '/trading-platforms/expert-advisors'),
        ],
      },
    ],
  },
  {
    label: 'Learn',
    columns: [
      {
        heading: 'Education Centre',
        items: [
          L('Education Hub', '/education', true),
          L('Trading for Beginners', '/education#beginners', true),
          L('Trading Strategies', '/education#strategies', true),
          L('Market Terms', '/education#glossary', true),
        ],
      },
      {
        heading: 'Guides',
        items: [
          L('How to Trade Forex', '/markets/forex/what-is-forex', true),
          L('What Are CFDs', '/trading-info/cfd-trading'),
          L('Order Types', '/education#orders', true),
          L('Risk Management', '/education#risk', true),
        ],
      },
      {
        heading: 'Insights',
        items: [
          L('News & Analysis', '/tools/market-analysis'),
          L('Blog', '/blog'),
          L('Webinars', '/education#webinars', true),
        ],
      },
    ],
  },
  {
    label: 'About',
    columns: [
      {
        heading: 'Company',
        items: [
          L('About Us', '/about', true),
          L('Why Orion Capital', '/about#why', true),
          L('Regulation', '/about#regulation', true),
          L('Awards', '/about#awards', true),
        ],
      },
      {
        heading: 'Support',
        items: [
          L('Contact Us', '/contact', true),
          L('Help Centre', '/help'),
          L('Client Area', '/about#client-area', true),
        ],
      },
      {
        heading: 'Legal',
        items: [
          L('Legal Documents', '/legal'),
          L('Privacy Policy', '/legal/privacy'),
          L('Terms & Conditions', '/legal/terms'),
          L('Partners', '/partners'),
        ],
      },
    ],
  },
];

// Footer mirrors the menu for crawlability, the way large broker sites do.
export const footerColumns = [
  {
    heading: 'Markets',
    items: [
      L('Forex', '/markets/forex', true),
      L('Cryptocurrencies', '/markets/crypto', true),
      L('Stocks & Indices', '/markets/stocks', true),
      L('Commodities', '/markets/commodities', true),
      L('ETFs', '/markets/etfs', true),
      L('Bonds', '/markets/bonds', true),
    ],
  },
  {
    heading: 'Platforms',
    items: [
      L('Platforms Overview', '/trading-platforms', true),
      L('WebTrader', '/trading-platforms/webtrader', true),
      L('Copy Trading', '/trading-platforms/copy-trading'),
    ],
  },
  {
    heading: 'Trading',
    items: [
      L('Account Types', '/accounts', true),
      L('Deposits & Withdrawals', '/trading-info/deposits-withdrawals', true),
      L('Trading Hours', '/trading-info/trading-hours'),
      L('CFD Trading', '/trading-info/cfd-trading'),
      L('Islamic Account', '/trading-info/islamic-account'),
    ],
  },
  {
    heading: 'Education',
    items: [
      L('Education Hub', '/education', true),
      L('For Beginners', '/education#beginners', true),
      L('Strategies', '/education#strategies', true),
      L('Market Terms', '/education#glossary', true),
      L('Webinars', '/education#webinars', true),
    ],
  },
  {
    heading: 'Tools',
    items: [
      L('Economic Calendar', '/tools/economic-calendar'),
      L('Trading Calculator', '/tools/trading-calculator'),
      L('Trading Signals', '/tools/trading-signals'),
      L('Market Analysis', '/tools/market-analysis'),
    ],
  },
  {
    heading: 'Company',
    items: [
      L('About Us', '/about', true),
      L('Regulation', '/about#regulation', true),
      L('Partners', '/partners'),
      L('Contact Us', '/contact', true),
      L('Help Centre', '/help'),
    ],
  },
  {
    heading: 'Legal',
    items: [
      L('Legal Documents', '/legal'),
      L('Privacy Policy', '/legal/privacy'),
      L('Terms & Conditions', '/legal/terms'),
      L('Cookie Policy', '/legal/cookies'),
    ],
  },
];

// Pages built so far — used by the sitemap page and the build check.
export const builtPages = [
  '/', '/about', '/contact', '/trading-platforms', '/trading-platforms/webtrader',
  '/markets/forex', '/markets/forex/what-is-forex',
  '/markets/crypto', '/markets/crypto/what-are-cryptocurrencies',
  '/markets/stocks', '/markets/stocks/what-are-stocks',
  '/markets/indices', '/markets/indices/what-are-indices',
  '/markets/etfs', '/markets/etfs/what-are-etfs',
  '/markets/commodities', '/markets/commodities/what-are-commodities',
  '/markets/commodities/gold-trading', '/markets/commodities/gold-trading/how-to-trade-gold',
  '/markets/commodities/oil-trading', '/markets/commodities/oil-trading/how-to-trade-oil',
  '/markets/crypto/bitcoin-trading', '/markets/crypto/bitcoin-trading/what-is-bitcoin',
  '/markets/bonds', '/markets/futures', '/markets/futures/what-are-futures',
  '/accounts',
  '/education',
  '/trading-info/deposits-withdrawals',
];
