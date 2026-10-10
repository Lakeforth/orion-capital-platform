// Single source of truth for brand facts, outbound links and disclosure text.
//
// ===========================================================================
//  UNVERIFIED PLACEHOLDER DATA — READ BEFORE PUBLISHING
//
//  Everything in `compliance`, `contact` and `stats` below is placeholder content
//  written so the site reads as finished for review. NONE of it is this firm's
//  verified regulatory position. The on-page markers that used to flag it have been
//  removed at the client's request, so this comment and the DEPLOY.md go-live
//  checklist are now the ONLY things standing between placeholder data and
//  publication. Replace every value here before the site goes live.
// ===========================================================================

export const site = {
  name: 'Orion Capital',
  legalName: 'Orion Capital Ltd',       // TODO: replace with the registered entity name
  tagline: 'Markets on your terms',    // original line; not a competitor's slogan
  domain: 'orion-capital.inc',
  description:
    'Trade forex, indices, commodities, shares and crypto CFDs with Orion Capital. ' +
    'Transparent pricing, fast execution and a browser platform that needs no install.',
};

// Every outbound funnel link lives here. Change once, changes site-wide.
export const links = {
  clientArea: 'https://client-area.orion-capital.inc/',
  register:   'https://client-area.orion-capital.inc/',
  login:      'https://client-area.orion-capital.inc/',
  demo:       'https://client-area.orion-capital.inc/',
  dashboard:  'https://client-area.orion-capital.inc/main/dashboard',
  webtrader:  'https://webtrader.orion-capital.inc/',
};

// TODO: placeholder contact details — replace before launch.
export const contact = {
  email:   'support@orion-capital.inc',
  phone:   '+44 20 0000 0000',
  hours:   'Monday to Friday, 24 hours, from market open on Sunday evening to Friday close.',
  address: '1 Example Street, London, EC1A 1AA, United Kingdom',   // TODO: real registered office
  social: {
    facebook: '#', x: '#', instagram: '#', linkedin: '#', youtube: '#', telegram: '#',
  },
};

// ---------------------------------------------------------------------------
// Disclosure text.
//
// Written for Orion Capital. Deliberately NOT the reference site's wording — its
// house line names products ("FX Options") this firm does not offer, and copying a
// competitor's disclaimer is both a copyright and an accuracy problem.
// The long form follows the standard regulatory pattern because that pattern is
// mandated, but the phrasing here is our own.
// ---------------------------------------------------------------------------
export const compliance = {
  // Markers are no longer rendered. The values below are still unverified placeholders.
  sampleData: false,

  riskWarningShort:
    'Trading leveraged products carries significant risk and can lead to losses greater than you expect.',

  riskWarningLong:
    'CFDs are leveraged products. They carry a high risk of rapid loss because a small move in the ' +
    'underlying market produces a much larger move in your position. Most people who trade them lose ' +
    'money. Make sure you understand how these products work, and never risk funds you cannot afford ' +
    'to lose.',

  // TODO: must be this firm's own audited number before publication.
  lossPercentage:
    '74% of retail investor accounts lose money when trading CFDs with this provider.',

  // TODO: placeholder entities — the structure is right, the details are not real.
  entities: [
    {
      name: 'Orion Capital Ltd',
      regulator: 'Financial Services Authority (Seychelles)',
      licenceNo: 'SD-000000',
      jurisdiction: 'Seychelles',
      role: 'Principal operating entity for international clients.',
    },
    {
      name: 'Orion Capital (Europe)',
      regulator: 'Example European competent authority',
      licenceNo: 'EU-000000',
      jurisdiction: 'European Economic Area',
      role: 'Entity serving clients resident in the EEA.',
    },
  ],

  // Leverage is jurisdiction- and account-dependent; never present one global number.
  maxLeverage: {
    retailEea:  '1:30 on major currency pairs, lower on other asset classes',
    retailIntl: 'up to 1:400 depending on instrument and account type',
    note: 'Caps vary by regulator, instrument and client classification.',
  },

  segregation:
    'Client money is held in segregated accounts with regulated banking partners, separately from ' +
    'the firm\'s own funds.',
  negativeBalance:
    'Retail clients are covered by negative balance protection, so you cannot lose more than the ' +
    'balance of your trading account.',
};

// Third-party proof shown on the homepage. Each block renders only while its value is set,
// so nothing here can publish a rating, endorsement or award the firm has not earned.
// The base44 reference carried AvaTrade's own Trustpilot score and award badges; those
// were deliberately NOT copied over. Fill these with this firm's evidence.
export const proof = {
  // e.g. { score: '4.6', reviews: '1,240', url: 'https://www.trustpilot.com/review/orion-capital.inc' }
  trustpilot: null,
  // TODO: confirm this badge is an award Orion Capital actually holds before launch.
  heroAward: { src: '/images/home/hero-award.webp', alt: 'Most Trusted Broker 2025, International Investor Awards' },
  // Single wide strip, scrolled as a marquee. TODO: only awards this firm holds.
  awardsStrip: { src: '/images/home/awards-strip.webp', alt: 'A row of award badges, each naming an award, the awarding body and the year' },
  // Generic trust marks (SSL, verification) — not third-party brand logos.
  trustedStrip: { src: '/images/home/trusted-strip.webp', alt: 'Trust marks: identity verified, threat protected, bank-grade security, trusted service, and 256-bit SSL encryption' },
};

// Marketing proof points. TODO: placeholder values — only publish what can be evidenced.
export const stats = {
  instruments:   '1,000+',
  jurisdictions: '2',
  clients:       '50,000+',
  partners:      null,           // e.g. '1,200' — the partner pitch omits the figure while null
  yearsActive:   '8',
  sampleData: false,
};
