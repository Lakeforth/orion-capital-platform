// Centralised copy + config for the Quantum AI Profits landing page.

export const SURVEY_URL = "/survey";
export const GET_STARTED_ANCHOR = "/payment-prompt";

// ---- Countdown timer (per visitor) ----
export const TIMER_KEY = "qap_timer_start";
export const TIMER_DURATION_MS = 48 * 60 * 60 * 1000; // 48 hours

// ---- Hero ----
export const TRUST_TAGS = [
  "Zero technical knowledge required",
  "AI Quant-powered",
  "Hands-free passive income",
] as const;

// ---- Floating stats bar ----
export interface Stat {
  value: string;
  label: string;
  money?: boolean;
}
export const STATS: Stat[] = [
  { value: "$8.9M+", label: "Generated for members", money: true },
  { value: "105.88%", label: "Average yearly return" },
];

// ---- Features / benefits ----
export type FeatureIconName = "bolt" | "bank" | "clock";
export interface Feature {
  title: string;
  body: string;
  icon: FeatureIconName;
}
export const FEATURES: Feature[] = [
  {
    title: "Fully Automated. Zero Daily Input",
    body: "Once your account is live, the AI quant runs every trade, every adjustment, every day — without you touching a thing.",
    icon: "bolt",
  },
  {
    title: "Institutional Access. Everyday Entry",
    body: "Strategies once reserved for hedge funds and prop desks, opened up to ordinary people for the price of a dinner out.",
    icon: "bank",
  },
  {
    title: "Your Money, Working Around the Clock",
    body: "Markets never sleep and neither does the system. It compounds while you work, rest, and live your life.",
    icon: "clock",
  },
];

// ---- Who is this for ----
export const AUDIENCE: string[] = [
  "Too busy to actively manage investments",
  "Savings are sitting idle",
  "Tired of unpredictable income",
  "Frustrated with traditional investment returns",
  "First-time passive income seeker",
  "Retirees and near-retirees seeking supplemental income",
];

// ---- How it works steps ----
export interface Step {
  title: string;
  body: string;
}
export const STEPS: Step[] = [
  {
    title: "Create Your Account",
    body: "Sign up in under five minutes. No financial background needed.",
  },
  {
    title: "Fund Your Account",
    body: "Deposit as little as $250. That's your AI Quants' working capital.",
  },
  {
    title: "Our Systems Activate",
    body: "Our AI Quants activate automatically and begin generating returns.",
  },
  {
    title: "Earn Passively",
    body: "Returns flow into your account. Withdraw or compound. You decide.",
  },
];

// ---- Price / value stack ----
export interface ValueItem {
  name: string;
  value: string;
}
export const VALUE_ITEMS: ValueItem[] = [
  { name: "AI Quant System Access", value: "$2,500" },
  { name: "Passive Income Automation", value: "$1,500" },
  { name: "Dedicated Account Manager", value: "$1,000" },
  { name: "Account Setup & Configuration", value: "$1,000" },
  { name: "Ongoing System Updates & Optimisation", value: "$1,200" },
  { name: "Risk Management Framework", value: "$1,000" },
];
export const TOTAL_REAL_VALUE = "$8,200";
export const EARLY_ACCESS_PRICE = "$250";

// ---- Reviews (Trustpilot style) ----
export interface Review {
  name: string;
  role: string;
  country: string;
  text: string;
  rating: number;
}
export const REVIEWS: Review[] = [
  {
    name: "Carlos Delgado Rivera",
    role: "Retail Store Owner",
    country: "San Juan, Puerto Rico",
    text: "I had zero experience with trading, but Quantum AI Profits made it so simple. Within my first month I saw consistent returns I never thought possible running a small shop.",
    rating: 4.0,
  },
  {
    name: "Marisol Ortiz Vega",
    role: "School Teacher",
    country: "Ponce, Puerto Rico",
    text: "On a teacher's salary, every dollar counts. This platform has given me a real passive income stream. I check my dashboard in the morning and the algo already did the work overnight.",
    rating: 4.5,
  },
  {
    name: "Lars van den Berg",
    role: "IT Consultant",
    country: "Amsterdam, Netherlands",
    text: "As someone who works in tech, I was skeptical at first. But the algorithm's logic is solid. My portfolio has grown steadily each week. Highly recommended for busy professionals.",
    rating: 4.0,
  },
  {
    name: "Anouk de Vries",
    role: "Freelance Graphic Designer",
    country: "Rotterdam, Netherlands",
    text: "I was looking for something to supplement my freelance income. Quantum AI Profits delivers exactly that quiet, consistent gains without me having to stare at charts all day.",
    rating: 3.5,
  },
  {
    name: "Klaus Hoffmann",
    role: "Mechanical Engineer",
    country: "Munich, Germany",
    text: "Germans are known for being precise and skeptical — I was both. After two weeks of live trading, I was convinced. The system is disciplined, which is exactly what a good algo should be.",
    rating: 4.5,
  },
  {
    name: "Sabine Müller",
    role: "Physiotherapist",
    country: "Berlin, Germany",
    text: "I never imagined trading was for someone like me. But the setup was easy, the team was supportive, and the results speak for themselves. My savings are finally working for me.",
    rating: 3.5,
  },
  {
    name: "Ryan MacPherson",
    role: "Real Estate Agent",
    country: "Toronto, Ontario, Canada",
    text: "Real estate is my business but the market slowed down. A colleague told me about Quantum AI Profits and I haven't looked back. Great diversification for any income portfolio.",
    rating: 4.0,
  },
  {
    name: "Priya Nair",
    role: "Pharmacy Technician",
    country: "Vancouver, British Columbia, Canada",
    text: "I work long shifts and have no time to trade manually. This platform does everything for you. My only regret is not starting sooner. Absolutely changed my financial outlook.",
    rating: 4.5,
  },
];
