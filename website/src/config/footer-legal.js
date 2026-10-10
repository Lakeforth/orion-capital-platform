// ===========================================================================
//  SAMPLE — REPLACE BEFORE LAUNCH
//  Practice values for the footer's contact card and legal block (Footer.astro).
//  Every bracketed field is a placeholder. Fill each one from the firm's own entry on
//  its regulator's public register (e.g. FINRA BrokerCheck / SEC IAPD), word for word,
//  and keep the map pointed at the firm's own office. Never use another firm's
//  address, licence number, regulator memberships or name here.
// ===========================================================================

export const office = {
  name: 'Orion Capital',
  // Contact offices, as supplied by the owner (9 Oct 2026). `map` is the Google Maps query;
  // the footer map opens on the first office and the card can switch between them.
  offices: [
    { label: 'United Kingdom', lines: ['40 Bank Street', 'London, E14 5NR'], map: '40 Bank Street, London E14 5NR' },
    { label: 'Singapore', lines: ['14 Robinson Road', 'Far East Finance Building', 'Singapore 048545'], map: '14 Robinson Road, Singapore 048545' },
  ],
  phone: '+12818641077',
  email: 'support@orion-capital.inc',
  mapZoom: 16,
};

export const legal = {
  firm: 'Orion Capital',
  regulator: 'U.S. Securities and Exchange Commission (SEC) and the Financial Industry Regulatory Authority (FINRA)',
  licenceNo: '801-10970',
  protectionScheme: 'the Securities Investor Protection Corporation (SIPC)',
  registeredAddress: '1055 LPL WAY FORT MILL, SC 29715 UNITED STATES',
  partner: 'Interactive Brokers',
};
