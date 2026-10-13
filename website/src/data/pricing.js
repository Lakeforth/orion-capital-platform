// ===========================================================================
//  UNVERIFIED PLACEHOLDER PRICING — READ BEFORE PUBLISHING
//  Realistic for a retail CFD broker so the site reads as finished in review, but
//  NOT this firm's live commercial terms. The on-page notices that used to flag
//  this have been removed at the client's request, so only this comment and the
//  DEPLOY.md checklist remain. Replace every figure before publication.
// ===========================================================================

// Kept as a switch so the notices can be turned back on in one edit if needed.
export const pricingIsSample = false;

// Account tiers — the six from the owner's "Global Investment Account Comparison" sheet
// (account-types.jpg, 12 Oct 2026), which replaced the earlier Classic / Silver / Gold /
// VIP / VIP Diamond set. Investment accounts only. `features` is each card's list on
// /accounts, in the sheet's order. `spreadFrom` is kept only on the tiers other pages
// quote (standard, vip).
export const accountTypes = [
  {
    key: 'standard',
    name: 'Standard',
    minDeposit: '$300',
    spreadFrom: '2.5 pips',
    features: [
      'AI system setup',
      'Official presentation',
      'Standard leverage',
      'Basic market analysis',
      'Standard support',
      'Educational materials',
      'Select trading tools',
    ],
  },
  {
    key: 'bronze',
    name: 'Bronze',
    minDeposit: '$3,000',
    features: [
      'Personal financial specialist',
      'Bronze leverage',
      'Company funding (Bronze level)',
      '3 insured trades',
      'Investment plans access',
      'Personalized market analysis',
      'Priority support',
    ],
  },
  {
    key: 'silver',
    name: 'Silver',
    minDeposit: '$15,000',
    features: [
      'Dedicated financial specialist',
      'Silver leverage',
      'Company funding (Silver level)',
      '5 insured trades',
      'Expanded investment plans',
      'Personalized strategy',
      'In-depth market analysis',
      'Portfolio consultations',
    ],
  },
  {
    key: 'gold',
    name: 'Gold',
    minDeposit: '$30,000',
    features: [
      'Senior financial specialist',
      'Gold leverage',
      'Company funding (Gold level)',
      '7 insured trades',
      'Full access to investment plans',
      'Arbitrage trading',
      'Custom portfolio strategy',
    ],
  },
  {
    key: 'platinum',
    name: 'Platinum',
    minDeposit: '$100,000',
    features: [
      'Dedicated investment specialist',
      'Diamond leverage',
      'Company funding (Diamond level)',
      '15 insured trades',
      'Premium investment plans',
      'ICO/IPO access',
      'Mutual funds',
      'Advanced diversification',
    ],
  },
  {
    key: 'vip',
    name: 'VIP',
    minDeposit: '$300,000',
    spreadFrom: '0.9 pips',
    features: [
      'Personal financial & investment specialist',
      'VIP leverage',
      'Company funding (VIP level)',
      '20 insured trades',
      'Full access to all projects',
      'Arbitrage, ICO/IPO access',
      'Fully customized strategy',
      'Exclusive terms',
    ],
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
  card: { USD: '$300', EUR: '€300', GBP: '£300' },
  wire: { USD: '$300', EUR: '€300', GBP: '£300' },
};
