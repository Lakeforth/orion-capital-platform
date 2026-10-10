/**
 * predictionPricing.ts
 * -----------------------------------------------------------------------------
 * Currency-aware price strings for the Prediction landing page.
 *
 * The funnel's language rides on `?lang=` (see predictionLanguage.ts), which
 * drives the Google Translate widget. That widget rewrites words, not money —
 * so a German visitor would otherwise read translated copy quoting US dollars.
 * German therefore gets its own set of pre-converted price strings.
 *
 * Amounts are authored, not computed: these are marketing price points, so they
 * are checked by eye rather than derived from a live exchange rate.
 */

export type ValueItem = { name: string; value: string };

export type Pricing = {
  /** Struck-through "standard price". */
  totalValue: string;
  /** The actual entry price. */
  entryPrice: string;
  /** The hero's floating "Total Profit" stat chip. */
  profitChip: string;
  /** The value stack. Must sum to `totalValue`. */
  valueItems: ValueItem[];
};

const USD: Pricing = {
  totalValue: "$8,100",
  entryPrice: "$250",
  profitChip: "+$14,284.50",
  valueItems: [
    { name: "AI Quant System Access", value: "$3,500" },
    { name: "Real-Time Global Event & Data Analysis", value: "$2,200" },
    {
      name: "Multi-Category Prediction Access (crypto, weather, geopolitics & more)",
      value: "$1,100",
    },
    { name: "Dedicated Account Manager", value: "$900" },
    { name: "Onboarding & System Setup", value: "$400" },
  ],
};

/**
 * Converted at ~0.8747. The line items are rounded to stay legible, chosen so
 * they still sum to exactly €7,085 — rounding each one straight off the rate
 * lands a euro short of the total.
 */
const EUR: Pricing = {
  totalValue: "€7,085",
  entryPrice: "€200",
  profitChip: "+€12,494.50",
  valueItems: [
    { name: "AI Quant System Access", value: "€3,060" },
    { name: "Real-Time Global Event & Data Analysis", value: "€1,925" },
    {
      name: "Multi-Category Prediction Access (crypto, weather, geopolitics & more)",
      value: "€960",
    },
    { name: "Dedicated Account Manager", value: "€790" },
    { name: "Onboarding & System Setup", value: "€350" },
  ],
};

/** Only German switches to euros; every other language keeps USD. */
export const pricingFor = (lang: string | null): Pricing =>
  lang === "de" ? EUR : USD;

/**
 * Bare currency symbol for copy that quotes amounts without re-pricing them —
 * e.g. the survey's income brackets, where only the symbol changes.
 */
export const currencySymbolFor = (lang: string | null): string =>
  lang === "de" ? "€" : "$";

/** Fallback for components rendered outside a provider. */
export const DEFAULT_PRICING = USD;
