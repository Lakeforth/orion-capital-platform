// ===========================================================================
//  UNVERIFIED PLACEHOLDER PRICING — READ BEFORE PUBLISHING
//  Realistic for a retail CFD broker so the site reads as finished in review, but
//  NOT this firm's live commercial terms. The on-page notices that used to flag
//  this have been removed at the client's request, so only this comment and the
//  DEPLOY.md checklist remain. Replace every figure before publication.
// ===========================================================================

// Kept as a switch so the notices can be turned back on in one edit if needed.
export const pricingIsSample = false;

// Account tiers — the five from the base44 reference's Accounts page (HtmlHiDwBody.jsx),
// which replaced the earlier Standard / Premium / VIP set at the owner's request.
// The first block of fields feeds the summary cards and comparison on Fees & Charges;
// `spec` holds the reference's full specification table for /accounts.
// `leverage` is split retail / professional; VIP Diamond is professional-only.
export const accountTypes = [
  {
    key: 'classic',
    name: 'Classic',
    blurb: 'The starting tier, with full access to every asset.',
    minDeposit: '$/€ 100',
    spreadFrom: '2.5 pips',
    commission: 'None — cost is in the spread',
    maxLeverage: '1:30 retail / 1:400 professional*',
    swapFree: 'On request',
    support: 'Free support',
    featured: false,
    spec: {
      leverage: { retail: '1:30', professional: '1:400' },
      spreads: { 'EUR/USD': '2.5', 'GBP/USD': '2.8', 'USD/JPY': '2.8', 'Crude OIL': '$0.10' },
      swapsDiscount: '-', instruments: 'All Assets', marginCall: '100%',
      stopOut: { retail: '50%', professional: '20%' },
      minVolume: '0.01', maxVolume: '50', negativeBalance: true, freeSupport: true,
    },
  },
  {
    key: 'silver',
    name: 'Silver',
    blurb: 'Classic conditions with a larger balance.',
    minDeposit: '$/€ 2,500',
    spreadFrom: '2.5 pips',
    commission: 'None — cost is in the spread',
    maxLeverage: '1:30 retail / 1:400 professional*',
    swapFree: 'On request',
    support: 'Free support',
    featured: false,
    spec: {
      leverage: { retail: '1:30', professional: '1:400' },
      spreads: { 'EUR/USD': '2.5', 'GBP/USD': '2.8', 'USD/JPY': '2.8', 'Crude OIL': '$0.10' },
      swapsDiscount: '-', instruments: 'All Assets', marginCall: '100%',
      stopOut: { retail: '50%', professional: '20%' },
      minVolume: '0.01', maxVolume: '50', negativeBalance: true, freeSupport: true,
    },
  },
  {
    key: 'gold',
    name: 'Gold',
    blurb: 'Tighter spreads and a swaps discount period.',
    minDeposit: '$/€ 25,000',
    spreadFrom: '1.8 pips',
    commission: 'None — cost is in the spread',
    maxLeverage: '1:30 retail / 1:400 professional*',
    swapFree: 'On request',
    support: 'Free support',
    featured: true,
    spec: {
      leverage: { retail: '1:30', professional: '1:400' },
      spreads: { 'EUR/USD': '1.8', 'GBP/USD': '2.3', 'USD/JPY': '2.3', 'Crude OIL': '$0.08' },
      swapsDiscount: 'Available', instruments: 'All Assets', marginCall: '100%',
      stopOut: { retail: '50%', professional: '20%' },
      minVolume: '0.01', maxVolume: '50', negativeBalance: true, freeSupport: true,
    },
  },
  {
    key: 'vip',
    name: 'VIP',
    blurb: 'Our tightest spreads, by invitation.',
    minDeposit: 'Discretionary',
    spreadFrom: '0.9 pips',
    commission: 'None — cost is in the spread',
    maxLeverage: '1:30 retail / 1:400 professional*',
    swapFree: 'On request',
    support: 'Free support',
    featured: false,
    spec: {
      leverage: { retail: '1:30', professional: '1:400' },
      spreads: { 'EUR/USD': '0.9', 'GBP/USD': '1.4', 'USD/JPY': '1.4', 'Crude OIL': '$0.06' },
      swapsDiscount: 'Available', instruments: 'All Assets', marginCall: '100%',
      stopOut: { retail: '50%', professional: '20%' },
      minVolume: '0.01', maxVolume: '50', negativeBalance: true, freeSupport: true,
    },
  },
  {
    key: 'vip-diamond',
    name: 'VIP Diamond',
    blurb: 'VIP pricing for professional clients, with larger trade sizes.',
    minDeposit: 'Discretionary',
    spreadFrom: '0.9 pips',
    commission: 'None — cost is in the spread',
    maxLeverage: '1:400 (professional)*',
    swapFree: 'On request',
    support: 'Free support',
    featured: false,
    spec: {
      leverage: { professional: '1:400' },
      spreads: { 'EUR/USD': '0.9', 'GBP/USD': '1.4', 'USD/JPY': '1.4', 'Crude OIL': '$0.06' },
      swapsDiscount: 'Available', instruments: 'All Assets', marginCall: '100%',
      stopOut: { professional: '20%' },
      minVolume: '20 (FX/Metals)\n5,000 (Energies)', maxVolume: '100 (FX/Metals)\n100,000 (Energies)',
      negativeBalance: true, freeSupport: true,
    },
  },
];

// Indicative spreads per tier — the four instruments the reference's Accounts page lists.
// Column keys are the tier keys above. Typical values, not guarantees.
export const spreadTable = [
  { market: 'Forex',       instrument: 'EUR/USD',   classic: '2.5',   silver: '2.5',   gold: '1.8',   vip: '0.9',   'vip-diamond': '0.9',   unit: 'pips' },
  { market: 'Forex',       instrument: 'GBP/USD',   classic: '2.8',   silver: '2.8',   gold: '2.3',   vip: '1.4',   'vip-diamond': '1.4',   unit: 'pips' },
  { market: 'Forex',       instrument: 'USD/JPY',   classic: '2.8',   silver: '2.8',   gold: '2.3',   vip: '1.4',   'vip-diamond': '1.4',   unit: 'pips' },
  { market: 'Commodities', instrument: 'Crude Oil', classic: '0.10',  silver: '0.10',  gold: '0.08',  vip: '0.06',  'vip-diamond': '0.06',  unit: 'USD' },
];

// The full cost picture — what a client actually pays, beyond the spread.
export const feeSchedule = [
  {
    name: 'Spread',
    what: 'The difference between the buy and sell price. This is the main cost on most accounts.',
    amount: 'From 0.9 pips, by account type and instrument',
  },
  {
    name: 'Commission',
    what: 'No account tier carries a per-lot commission; every tier is priced spread-only.',
    amount: 'None',
  },
  {
    name: 'Overnight financing (swap)',
    what: 'Applied when a leveraged position is held past the daily rollover. Can be a credit or a debit.',
    amount: 'Varies by instrument and direction',
  },
  {
    name: 'Currency conversion',
    what: 'Applied when a trade settles in a currency other than your account currency.',
    amount: '0.5% of the converted amount',
  },
  {
    name: 'Inactivity fee',
    what: 'Charged after 3 consecutive months with no trading activity, and again after each further 3 months, unless prohibited by law.',
    amount: '$/€/£50 after 3 months of inactivity, and each 3 months after',
  },
  {
    name: 'Administration fee',
    what: 'An annual fee after 12 consecutive months with no account use, unless prohibited by law, to cover keeping the service available.',
    amount: '$/€/£100 after 12 months of inactivity',
  },
  {
    name: 'Deposits',
    what: 'We do not charge to fund an account. Your payment provider may.',
    amount: 'Free',
  },
  {
    name: 'Withdrawals',
    what: 'No charge from us on standard withdrawals to the original funding method.',
    amount: 'Free',
  },
];

// Per-currency amounts for the two account-level fees above — the lists on the Fees &
// Charges page (Figma frame 72:2). Keep in step with the feeSchedule rows.
export const inactivityFee = { USD: '$50', EUR: '€50', GBP: '£50' };
export const adminFee      = { USD: '$100', EUR: '€100', GBP: '£100' };

// The deposit methods on the Withdrawals & Deposits page (Figma frame 73:924). Times are
// how long a deposit takes to reach the account; every method has the same $100 minimum.
export const paymentMethods = [
  { name: 'Visa / Mastercard', time: 'Instant',               min: '$100', type: 'Card' },
  { name: 'Wire transfer',     time: 'Up to 7 business days', min: '$100', type: 'Bank' },
  { name: 'Skrill',            time: 'Within 24 hours',       min: '$100', type: 'E-wallet' },
  { name: 'Neteller',          time: 'Within 24 hours',       min: '$100', type: 'E-wallet' },
  { name: 'WebMoney',          time: 'Within 24 hours',       min: '$100', type: 'E-wallet' },
];

// Minimum first deposit by account currency and funding route.
export const minDeposits = {
  card: { USD: '$100', EUR: '€100', GBP: '£100' },
  wire: { USD: '$100', EUR: '€100', GBP: '£100' },
};
