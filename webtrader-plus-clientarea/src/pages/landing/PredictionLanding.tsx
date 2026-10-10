/**
 * PredictionLanding.tsx
 * -----------------------------------------------------------------------------
 * Single-file landing page. React + Tailwind (stock utility classes only —
 * no custom tailwind.config required) + TypeScript. No global CSS: every style
 * is a Tailwind class or an inline style object living in this file.
 *
 * Fonts: uses Poppins (headings) + Open Sans (body) via inline font stacks.
 * For the exact look, add this once to your <head> (or import in your app):
 *   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
 * If you skip it, it falls back to the system UI font gracefully.
 *
 * Usage:  <PredictionLanding onCtaClick={() => ...} />
 */

import { createContext, useContext, useEffect, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { AnimatePresence, motion, useReducedMotion } from "framer-motion";
import FunnelLangSwitch from "@/components/FunnelLangSwitch";
import {
  GUARANTEE_PILL_DE_URL,
  GUARANTEE_SEAL_DE_URL,
  PRODUCT_BOX_DE_URL,
  screenshotsFor,
  useCampaignLogo,
} from "@/pages/prediction/predictionBrand";
import {
  DEFAULT_PRICING,
  pricingFor,
  type Pricing,
} from "@/pages/prediction/predictionPricing";
import {
  useFunnelLanguage,
  withLang,
} from "@/pages/prediction/predictionLanguage";

const HEAD_FONT = { fontFamily: "'Poppins', system-ui, sans-serif" } as const;
const BODY_FONT = { fontFamily: "'Open Sans', system-ui, sans-serif" } as const;

/**
 * Prices depend on `?lang=` (German is quoted in euros), which is only known
 * inside the page component. Context keeps `CtaLabel` and `FloatingWorkspace`
 * — both module-scope — from having to drill a prop through `CtaButton`.
 */
const PricingContext = createContext<Pricing>(DEFAULT_PRICING);
const usePricing = () => useContext(PricingContext);

/**
 * The page has a single call to action. Every CTA button renders this label and
 * shares the same `cta` handler — keep it that way.
 */
const CtaLabel = () => {
  const { totalValue, entryPrice } = usePricing();
  return (
    <>
      Get Started at{" "}
      <span className="line-through opacity-70">{totalValue}</span> {entryPrice}
    </>
  );
};

const BTN_PRIMARY =
  "font-semibold inline-flex items-center gap-2 text-white bg-[#4f46e5] hover:bg-[#4338ca] rounded-lg transition";

/**
 * CTA button with the pulsing halo used on the Quantum landing page (see the
 * `CTA` primitive in ./primitives.tsx): a gradient layer sitting behind the
 * button that breathes outward and fades on a 3s loop.
 */
const CtaButton = ({
  onClick,
  className = "",
  wrapperClassName = "",
  arrow = false,
  block = false,
}: {
  onClick: () => void;
  className?: string;
  wrapperClassName?: string;
  arrow?: boolean;
  block?: boolean;
}) => (
  <div
    className={`relative inline-flex items-center justify-center ${
      block ? "w-full" : ""
    } ${wrapperClassName}`}
  >
    {/* Pulsing halo */}
    <motion.div
      aria-hidden="true"
      animate={{
        top: ["-4px", "-12px", "-4px"],
        left: ["-4px", "-12px", "-4px"],
        right: ["-4px", "-12px", "-4px"],
        bottom: ["-4px", "-12px", "-4px"],
        opacity: [0.5, 0.2, 0.5],
      }}
      transition={{ duration: 3, repeat: Infinity, ease: "easeInOut" }}
      className="absolute rounded-2xl bg-gradient-to-r from-[#4f46e5] to-[#818cf8]"
    />
    <button
      onClick={onClick}
      style={HEAD_FONT}
      className={`relative z-10 ${BTN_PRIMARY} ${
        block ? "w-full justify-center" : ""
      } ${className}`}
    >
      <CtaLabel />
      {arrow && <span>&raquo;</span>}
    </button>
  </div>
);

export interface PredictionLandingProps {
  seatsFilled?: number;
  totalSeats?: number;
  showActivity?: boolean;
  /** Called by every CTA button (see CtaLabel). */
  onCtaClick?: () => void;
}

type Activity = {
  /** Stable key — rows are re-keyed as the queue shifts, never re-used. */
  id: number;
  icon: string;
  chipBg: string;
  chipFg: string;
  title: string;
  sub: string;
};
type Review = {
  quote: string;
  name: string;
  role: string;
  /** Real portrait for the review card avatar. */
  photo: string;
  /** Out of 5. Capped at 4.6 — nothing on this page is a perfect score. */
  rating: number;
};

/**
 * The live feed. Rows enter at the top and the oldest is dropped, so the queue
 * always holds ACTIVITY_ROWS entries. A row's timestamp comes from its position
 * (see ACTIVITY_TIMES) rather than being stored — as a row is pushed down the
 * list it "ages" on its own.
 */
const ACTIVITY_ROWS = 5;
const ACTIVITY_TIMES = [
  "just now",
  "3 min ago",
  "11 min ago",
  "24 min ago",
  "38 min ago",
];
/** How often a new event arrives. */
const ACTIVITY_INTERVAL_MS = 4500;

const MEMBERS: { name: string; place: string }[] = [
  { name: "Stefan B.", place: "Cologne, Germany" },
  { name: "Fabienne R.", place: "Lausanne, Switzerland" },
  { name: "Jonas A.", place: "Graz, Austria" },
  { name: "Anke R.", place: "Leipzig, Germany" },
  { name: "Dominik K.", place: "Linz, Austria" },
  { name: "Corinne B.", place: "Basel, Switzerland" },
  { name: "Jürgen V.", place: "Dresden, Germany" },
  { name: "Elisabeth H.", place: "Klagenfurt, Austria" },
  { name: "Reto Z.", place: "Lucerne, Switzerland" },
  { name: "Kathrin M.", place: "Nuremberg, Germany" },
  { name: "Simon A.", place: "Wels, Austria" },
  { name: "Vreni S.", place: "St. Gallen, Switzerland" },
];

const PREDICTIONS: { category: string; sub: string }[] = [
  { category: "Crypto", sub: "BTC direction call · high confidence" },
  { category: "Geopolitics", sub: "Election outcome · high confidence" },
  { category: "Weather", sub: "Regional storm track · high confidence" },
  { category: "Sports", sub: "Match outcome · high confidence" },
];

let activityUid = 0;

const memberEvent = (i: number): Activity => {
  const m = MEMBERS[i % MEMBERS.length];
  return {
    id: ++activityUid,
    icon: "🚀",
    chipBg: "#eef2ff",
    chipFg: "#4f46e5",
    title: "New member activated",
    sub: `${m.name} onboarded · ${m.place}`,
  };
};

const predictionEvent = (i: number): Activity => {
  const p = PREDICTIONS[i % PREDICTIONS.length];
  return {
    id: ++activityUid,
    icon: "🎯",
    chipBg: "#eff6ff",
    chipFg: "#4f46e5",
    title: `Prediction settled · ${p.category}`,
    sub: p.sub,
  };
};

/** Every third event is a settled prediction; the rest are new members. */
const activityEvent = (tick: number): Activity =>
  tick % 3 === 2
    ? predictionEvent(Math.floor(tick / 3))
    : memberEvent(tick - Math.floor(tick / 3));

const SEED_ACTIVITY: Activity[] = Array.from(
  { length: ACTIVITY_ROWS },
  (_, i) => activityEvent(ACTIVITY_ROWS - 1 - i),
);

const REVIEWS: Review[] = [
  {
    quote:
      "My brother-in-law recommended it. He's usually wrong about everything, not this time.",
    name: "Stefan Bernd",
    role: "Cologne, Germany",
    photo: "/assets/prediction/faces/stefan-bernd.jpg",
    rating: 4.6,
  },
  {
    quote:
      "I still don't fully understand how it works; I honestly don't care for the technical details. I just know that the system works for me.",
    name: "Fabienne Rogé",
    role: "Lausanne, Switzerland",
    photo: "/assets/prediction/faces/fabienne-roge.jpg",
    rating: 4.4,
  },
  {
    quote:
      "I run a bakery, and hardly have hours to sit around reading news about oil prices or elections. I do mind local events, sometimes. Prediction Profits AI just happens in the background while I'm working. My earnings come, and that's very convenient for me.",
    name: "Jonas Albrecht",
    role: "Graz, Austria",
    photo: "/assets/prediction/faces/jonas-albrecht.jpg",
    rating: 4.3,
  },
  {
    quote:
      "My son set it up for me because I kept putting it off. Now he's asking me how it's going. It's delivering, so far. I like it.",
    name: "Werner Tichmann",
    role: "Erfurt, Germany",
    photo: "/assets/prediction/faces/werner-tichmann.jpg",
    rating: 4.1,
  },
  {
    quote:
      "I told my account manager straight up I know nothing about crypto or geopolitics and I wasn't going to pretend otherwise. He didn't try to teach me anything, just said that's the point of the Prediction Profits AI system. Fair enough. I check in maybe once a week, mostly out of habit rather than necessity at this point.",
    name: "Priska Lubeck",
    role: "Bern, Switzerland",
    photo: "/assets/prediction/faces/priska-lubeck.jpg",
    rating: 4.2,
  },
  {
    quote:
      "A colleague mentioned this over lunch, I think mostly to complain that his wife thought it was a scam. I looked into it properly instead of dismissing it outright, and the account manager answered every awkward question I threw at him without dodging any of them, which is more than I can say for most sales calls I've sat through.",
    name: "Matthias Brügelmann",
    role: "Innsbruck, Austria",
    photo: "/assets/prediction/faces/matthias-brugelmann.jpg",
    rating: 4.4,
  },
];

const AVG_RATING =
  REVIEWS.reduce((sum, r) => sum + r.rating, 0) / REVIEWS.length;

/** Trustpilot's own bands: 4.3+ Excellent, 3.8+ Great, 3.0+ Average. */
const trustLabel = (r: number) =>
  r >= 4.3 ? "Excellent" : r >= 3.8 ? "Great" : "Average";

/**
 * Trustpilot-style star row. Each of the 5 tiles fills proportionally, so a
 * rating of 4.6 renders four solid tiles and a 60%-filled fifth.
 */
const Stars = ({
  rating,
  size = 24,
  gap = "gap-1",
  text = "text-[13px]",
}: {
  rating: number;
  size?: number;
  gap?: string;
  text?: string;
}) => (
  <div className={`flex ${gap}`} aria-label={`${rating} out of 5 stars`}>
    {Array.from({ length: 5 }).map((_, i) => {
      const fill = Math.max(0, Math.min(1, rating - i));
      return (
        <span
          key={i}
          className="relative inline-flex items-center justify-center overflow-hidden rounded-sm bg-[#dcdce6]"
          style={{ width: size, height: size }}
        >
          <span
            className="absolute inset-y-0 left-0 bg-[#00b67a]"
            style={{ width: `${fill * 100}%` }}
          />
          <span className={`relative text-white ${text}`}>★</span>
        </span>
      );
    })}
  </div>
);

/**
 * Counts from 0 up to `target` over `duration` ms on mount, easing out so the
 * number decelerates as it lands. Returns 0..target as an integer.
 *
 * Respects prefers-reduced-motion by snapping straight to `target`.
 */
function useCountUp(target: number, duration = 1600) {
  const [value, setValue] = useState(0);

  useEffect(() => {
    const reduced = window.matchMedia(
      "(prefers-reduced-motion: reduce)",
    ).matches;
    if (reduced || target <= 0) {
      setValue(target);
      return;
    }

    let frame = 0;
    let start: number | null = null;

    const tick = (now: number) => {
      if (start === null) start = now;
      const t = Math.min(1, (now - start) / duration);
      // easeOutCubic
      const eased = 1 - Math.pow(1 - t, 3);
      setValue(Math.round(eased * target));
      if (t < 1) frame = requestAnimationFrame(tick);
    };

    frame = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(frame);
  }, [target, duration]);

  return value;
}

/**
 * Drives the live activity queue: every ACTIVITY_INTERVAL_MS a fresh event is
 * unshifted onto the front and the oldest row falls off the end.
 */
function useLiveActivity(enabled: boolean) {
  const [rows, setRows] = useState<Activity[]>(SEED_ACTIVITY);

  useEffect(() => {
    if (!enabled) return;

    let tick = ACTIVITY_ROWS;
    const id = setInterval(() => {
      const next = activityEvent(tick++);
      setRows((prev) => [next, ...prev].slice(0, ACTIVITY_ROWS));
    }, ACTIVITY_INTERVAL_MS);

    return () => clearInterval(id);
  }, [enabled]);

  return rows;
}

export default function PredictionLanding({
  seatsFilled = 79,
  totalSeats = 100,
  showActivity = true,
  onCtaClick,
}: PredictionLandingProps) {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const uuid = searchParams.get("uuid");
  const lang = useFunnelLanguage();

  const [seatPct, setSeatPct] = useState(0);
  const seatsCount = useCountUp(seatsFilled);
  const totalCount = useCountUp(totalSeats);
  const activity = useLiveActivity(showActivity);
  const reduceMotion = useReducedMotion();
  const pricing = pricingFor(lang);

  // Animate the scarcity bar on mount.
  useEffect(() => {
    const t = setTimeout(
      () => setSeatPct(Math.min(100, (seatsFilled / totalSeats) * 100)),
      200,
    );
    return () => clearTimeout(t);
  }, [seatsFilled, totalSeats]);

  const cta =
    onCtaClick ??
    (() =>
      navigate(
        withLang(
          uuid ? `/prediction-checkout?uuid=${uuid}` : "/prediction-checkout",
          lang,
        ),
      ));

  return (
    <PricingContext.Provider value={pricing}>
      <div style={BODY_FONT} className="text-slate-600 bg-white">
        {/* NAV */}
        <header className="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-100">
          <div className="max-w-6xl mx-auto px-6 h-20 flex items-center gap-8">
            <div className="flex items-center mr-auto">
              <Logo height={60} />
            </div>
            <nav className="hidden lg:flex gap-7 text-[14px] text-slate-500">
              <a href="#benefits" className="hover:text-slate-900">
                Benefits
              </a>
              <a href="#how" className="hover:text-slate-900">
                How it works
              </a>
              <a href="#pricing" className="hover:text-slate-900">
                Pricing
              </a>
              <a href="#reviews" className="hover:text-slate-900">
                Testimonials
              </a>
            </nav>
            <FunnelLangSwitch />
            <CtaButton
              onClick={cta}
              className="px-4 py-2 text-[13px] whitespace-nowrap"
            />
          </div>
        </header>

        {/* HERO */}
        <section className="relative overflow-hidden bg-gradient-to-br from-white via-slate-50 to-[#eef2ff]">
          <div className="absolute inset-0 bg-[radial-gradient(700px_340px_at_82%_18%,rgba(79,70,229,0.14),transparent_62%)]" />
          <div className="absolute inset-0 bg-[radial-gradient(500px_260px_at_95%_60%,rgba(59,130,246,0.10),transparent_60%)]" />
          <div className="relative max-w-6xl mx-auto px-6 pt-10 pb-16 grid lg:grid-cols-2 gap-10 items-center">
            <div>
              {/* Scarcity tagline — the seat numbers count up on mount. */}
              <motion.div
                initial={{ opacity: 0, y: -8 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5 }}
                className="inline-flex items-center gap-2.5 rounded-full border border-[#4f46e5]/25 bg-white shadow-sm pl-3 pr-4 py-1.5 mb-6"
                aria-label={`${seatsFilled} of ${totalSeats} seats filled`}
              >
                <span
                  aria-hidden="true"
                  className="relative flex h-2 w-2 flex-none"
                >
                  <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#4f46e5] opacity-70" />
                  <span className="relative inline-flex h-2 w-2 rounded-full bg-[#4f46e5]" />
                </span>
                <span
                  aria-hidden="true"
                  style={HEAD_FONT}
                  className="text-[13px] font-medium text-slate-600"
                >
                  <span className="font-bold text-slate-900 tabular-nums">
                    {seatsCount}
                  </span>
                  <span className="text-slate-400"> / </span>
                  <span className="font-bold text-slate-900 tabular-nums">
                    {totalCount}
                  </span>{" "}
                  seats filled for this cycle
                </span>
              </motion.div>

              <h1
                style={HEAD_FONT}
                className="font-extrabold text-slate-900 text-[38px] sm:text-[46px] leading-[1.08] tracking-tight mb-4"
              >
                Predict Smarter.
                <br />
                <span className="underline decoration-[#4f46e5] decoration-[3px] underline-offset-[6px]">
                  Earn Passively.
                </span>
              </h1>
              <p
                style={HEAD_FONT}
                className="font-semibold text-[#4f46e5] text-[17px] sm:text-[19px] mb-5"
              >
                Award Winning AI Prediction System
              </p>
              <p className="text-slate-600 text-[16px] leading-relaxed max-w-xl mb-8">
                Prediction Profits AI is a done-for-you system that reads global
                events, crypto markets, geopolitical and weather patterns,
                conflicts, and sports outcomes for you to earn consistently from
                these global developments, without lifting a finger.
              </p>
              <div className="flex flex-wrap gap-3">
                <CtaButton
                  onClick={cta}
                  arrow
                  className="px-7 py-3.5 shadow-lg shadow-indigo-500/30"
                />
              </div>
            </div>
            <FloatingWorkspace />
          </div>
        </section>

        {/* FEATURES / BENEFITS */}
        <section id="benefits" className="py-20 px-6 bg-white">
          <div className="max-w-6xl mx-auto grid md:grid-cols-4 gap-8">
            {[
              {
                title: "AI Edge. Zero Manual Input",
                body: "Our AI scans thousands of key indicators in real time, news signals, market pricing, historical patterns, arriving at logical predictions instantly, so you never have to connect the dots yourself.",
                icon: <IconChip />,
              },
              {
                title: "Zero Tech Knowledge Needed",
                body: "No coding, no complex platforms, no learning curve. If you can fill out a form, you can run this system; the AI handles every technical detail behind the scenes.",
                icon: <IconZap />,
              },
              {
                title: "Dedicated Account Manager",
                body: "You're never on your own. A dedicated account manager guides your journey personally, tailoring the system's output to your goals and walking you through every step.",
                icon: <IconUser />,
              },
              {
                title: "True Handsfree Earning",
                body: "No charts to study, no news to monitor. The system runs in the background, surfacing high-confidence predictions across world events, crypto, weather, conflicts, and sports, built for real passive income.",
                icon: <IconBars />,
              },
            ].map((f) => (
              <div key={f.title}>
                <div className="flex items-center gap-3 mb-4">
                  <div className="text-[#4f46e5] flex-shrink-0">{f.icon}</div>
                  <h3
                    style={HEAD_FONT}
                    className="font-bold text-[16px] text-[#4f46e5]"
                  >
                    {f.title}
                  </h3>
                </div>
                <p className="text-[13.5px] leading-relaxed text-black">
                  {f.body}
                </p>
              </div>
            ))}
          </div>
        </section>

        {/* ASSESSMENT */}
        <section
          id="assessment"
          className="py-16 px-6 bg-slate-50 border-y border-slate-100"
        >
          <div className="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
            <div className="relative">
              <img
                src={PRODUCT_BOX_DE_URL}
                alt="Prognose Gewinne KI Produktbox"
                className="w-full max-w-[320px] h-auto mx-auto drop-shadow-2xl"
              />
            </div>
            <div>
              <h2
                style={HEAD_FONT}
                className="font-extrabold text-slate-900 text-[30px] sm:text-[36px] leading-tight tracking-tight mb-4"
              >
                A System Normally Reserved for the Few Now Open to You
              </h2>
              <p className="text-[15.5px] leading-relaxed text-slate-500 mb-7">
                Institutional-grade AI, real-time global event analysis, and a
                dedicated account manager, the kind of access usually locked
                behind elite firms, now within your reach.
              </p>
              <CtaButton
                onClick={cta}
                arrow
                className="px-7 py-3.5 shadow-lg shadow-indigo-500/30"
              />
            </div>
          </div>
        </section>

        {/* HOW TO GET STARTED (NAVY) */}
        <section
          id="how"
          className="py-24 px-6 bg-gradient-to-b from-[#0b1b30] to-[#0a1728]"
        >
          <div className="max-w-7xl mx-auto">
            <h2
              style={HEAD_FONT}
              className="text-center font-extrabold text-white text-[30px] sm:text-[42px] tracking-tight mb-3"
            >
              How to Get Started
            </h2>
            <p className="text-center text-slate-400 text-[16px] mb-20">
              You Only Need to Take 4 Simple Steps
            </p>
            <div className="relative">
              {/* Arrows Layer (Desktop only) */}
              <div className="hidden md:flex absolute top-12 left-0 w-full justify-evenly items-center px-[10%] pointer-events-none">
                {[1, 2, 3].map((i) => (
                  <div key={i} className="flex-1 flex justify-center">
                    <svg
                      width="42"
                      height="12"
                      viewBox="0 0 42 12"
                      fill="none"
                      className="text-white/20"
                    >
                      <path
                        d="M0 6H40M40 6L35 1M40 6L35 11"
                        stroke="currentColor"
                        strokeWidth="1.5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                    </svg>
                  </div>
                ))}
              </div>

              <div className="grid md:grid-cols-4 gap-12 md:gap-8 relative z-10">
                {[
                  {
                    title: "Fund Your Account",
                    body: `Deposit as little as ${pricing.entryPrice} to activate your Prediction Profits AI account.`,
                    icon: <StepCheck />,
                  },
                  {
                    title: "Get System Access",
                    body: "Unlock the entire Prediction Profits AI ecosystem built to predict outcomes on your behalf.",
                    icon: <StepDoc />,
                  },
                  {
                    title: "Let The AI Work",
                    body: "Our quant-trained AI takes over, scanning key metrics and making high-confidence predictions automatically.",
                    icon: <StepGear />,
                  },
                  {
                    title: "Earn Passively",
                    body: "Returns flow into your account. Withdraw any time you decide.",
                    icon: <StepBolt />,
                  },
                ].map((s) => (
                  <div
                    key={s.title}
                    className="text-center flex flex-col items-center"
                  >
                    <div className="w-24 h-24 rounded-full bg-white flex items-center justify-center text-[#4f46e5] mb-8 shadow-xl">
                      {s.icon}
                    </div>
                    <h3
                      style={HEAD_FONT}
                      className="font-bold text-white text-[20px] mb-4"
                    >
                      {s.title}
                    </h3>
                    <p className="text-[15px] leading-relaxed text-slate-400 max-w-[280px]">
                      {s.body}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </section>

        {/* LIVE ACTIVITY */}
        {showActivity && (
          <section className="py-16 px-6 bg-white">
            <div className="max-w-3xl mx-auto rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
              <div className="flex items-center gap-2.5 px-6 py-4 border-b border-slate-100 bg-slate-50">
                {/* Live indicator: a solid dot with a ring that pulses outward. */}
                <span
                  aria-hidden="true"
                  className="relative flex w-2.5 h-2.5 flex-none"
                >
                  <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#4f46e5] opacity-75" />
                  <span className="relative inline-flex w-2.5 h-2.5 rounded-full bg-[#4f46e5] shadow-[0_0_0_4px_rgba(79,70,229,0.18)]" />
                </span>
                <span
                  style={HEAD_FONT}
                  className="font-semibold text-[14px] text-slate-900"
                >
                  Live activity
                </span>
                <span className="ml-auto text-[12.5px] text-slate-400">
                  updating in real time
                </span>
              </div>
              {/* `popLayout` lets the exiting row leave the flow immediately, so
                the rows below slide up instead of waiting for it to finish. */}
              <AnimatePresence initial={false} mode="popLayout">
                {activity.map((row, i) => (
                  <motion.div
                    key={row.id}
                    layout={!reduceMotion}
                    initial={
                      reduceMotion
                        ? { opacity: 0 }
                        : { opacity: 0, y: -28, scale: 0.96 }
                    }
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    exit={
                      reduceMotion
                        ? { opacity: 0 }
                        : { opacity: 0, y: 28, scale: 0.96 }
                    }
                    transition={{ duration: 0.45, ease: "easeOut" }}
                    className="flex items-center gap-4 px-6 py-4 border-b border-slate-50 last:border-0 bg-white"
                  >
                    <span
                      className="w-9 h-9 flex-none rounded-lg flex items-center justify-center text-[15px]"
                      style={{ background: row.chipBg, color: row.chipFg }}
                    >
                      {row.icon}
                    </span>
                    <div className="min-w-0">
                      <div className="font-semibold text-[14px] text-slate-900">
                        {row.title}
                      </div>
                      <div className="text-[13px] text-slate-500">
                        {row.sub}
                      </div>
                    </div>
                    <span className="ml-auto flex-none text-[12.5px] text-slate-400 whitespace-nowrap">
                      {ACTIVITY_TIMES[i]}
                    </span>
                  </motion.div>
                ))}
              </AnimatePresence>
            </div>
          </section>
        )}

        {/* PRICE / VALUE STACK */}
        <section
          id="pricing"
          className="py-20 px-6 bg-slate-50 border-y border-slate-100"
        >
          <div className="max-w-6xl mx-auto">
            <h2
              style={HEAD_FONT}
              className="font-extrabold text-slate-900 text-[28px] sm:text-[36px] tracking-tight text-center max-w-3xl mx-auto mb-12"
            >
              Everything Inside Prediction Profits AI, And Its Actual Worth
            </h2>
            <div className="grid lg:grid-cols-[1.15fr_0.85fr] gap-7 items-start">
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm px-7 py-3">
                {pricing.valueItems.map((v) => (
                  <div
                    key={v.name}
                    className="flex items-center justify-between gap-4 py-4 border-b border-slate-100"
                  >
                    <span className="text-[15px] text-slate-700">{v.name}</span>
                    <span
                      style={HEAD_FONT}
                      className="font-semibold text-[15px] text-slate-400 whitespace-nowrap line-through"
                    >
                      {v.value}
                    </span>
                  </div>
                ))}
                <div className="flex items-center justify-between py-5">
                  <span
                    style={HEAD_FONT}
                    className="font-bold text-[17px] text-slate-900"
                  >
                    Total Real Value
                  </span>
                  <span
                    style={HEAD_FONT}
                    className="font-extrabold text-[24px] text-slate-900"
                  >
                    {pricing.totalValue}
                  </span>
                </div>
              </div>
              <div className="lg:sticky lg:top-24 bg-white rounded-2xl border-2 border-[#4f46e5] shadow-xl shadow-indigo-100 p-8 text-center">
                <div className="text-[13.5px] text-slate-500 mb-1">
                  Total real value{" "}
                  <span className="line-through text-slate-400">
                    {pricing.totalValue}
                  </span>
                </div>
                <div
                  style={HEAD_FONT}
                  className="font-semibold text-[15px] text-slate-500"
                >
                  Get Started at
                </div>
                <div
                  style={HEAD_FONT}
                  className="font-extrabold text-[58px] leading-none tracking-tight text-slate-900 mb-5"
                >
                  {pricing.entryPrice}
                </div>
                <CtaButton
                  onClick={cta}
                  block
                  wrapperClassName="mb-6"
                  className="py-3.5 text-[14px] shadow-lg shadow-indigo-500/30"
                />
                <div
                  style={HEAD_FONT}
                  className="font-semibold text-[12px] tracking-wider uppercase text-[#4f46e5] text-left mb-2"
                >
                  Limited Availability
                </div>
                <p className="text-[13px] leading-relaxed text-slate-500 text-left mb-4">
                  To protect prediction accuracy and give every member direct
                  access to their account manager, we cap enrollment at a small
                  number of active members per cycle.
                </p>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-[13px] text-slate-500">
                    <b className="text-slate-900">{seatsFilled}</b> /{" "}
                    {totalSeats} Seats filled
                  </span>
                </div>
                <div className="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                  <div
                    className="h-full rounded-full bg-gradient-to-r from-[#4f46e5] to-indigo-400 transition-[width] duration-1000"
                    style={{ width: `${seatPct}%` }}
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* WHATSAPP SCREENSHOT TESTIMONIALS */}
        <section className="py-20 px-6 bg-white">
          <div className="max-w-4xl mx-auto">
            <h2
              style={HEAD_FONT}
              className="text-center font-extrabold text-slate-900 text-[26px] sm:text-[34px] tracking-tight mb-2"
            >
              Straight From Their Phones
            </h2>
            <p className="text-center text-slate-500 text-[15px] mb-12">
              Unedited messages from members earning passively.
            </p>
            <div className="grid md:grid-cols-3 gap-6 max-w-3xl mx-auto">
              {screenshotsFor(lang).map((url) => (
                <div
                  key={url}
                  className="aspect-[9/19] bg-slate-100 rounded-[2rem] border-[6px] border-slate-900 shadow-xl overflow-hidden relative"
                >
                  <img
                    src={url}
                    alt="WhatsApp message from a Prediction Profits AI member"
                    className="w-full h-full object-cover"
                  />
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* TRUSTPILOT TESTIMONIALS */}
        <section
          id="reviews"
          className="py-20 px-6 bg-slate-50 border-y border-slate-100"
        >
          <div className="max-w-6xl mx-auto">
            <div className="text-center mb-12">
              <h2
                style={HEAD_FONT}
                className="font-extrabold text-slate-900 text-[26px] sm:text-[36px] tracking-tight mb-4"
              >
                Real People. Real Satisfaction.
              </h2>
              <div className="inline-flex flex-wrap items-center justify-center gap-2.5 text-[14px] text-slate-600">
                <Stars rating={AVG_RATING} />
                <span>
                  Rated{" "}
                  <b className="text-slate-900">{trustLabel(AVG_RATING)}</b> on
                  Trustpilot ·{" "}
                  <span className="text-slate-500">
                    {AVG_RATING.toFixed(1)} out of 5, based on 128 reviews
                  </span>
                </span>
              </div>
            </div>
            <div className="[column-count:1] md:[column-count:2] lg:[column-count:3] [column-gap:1.25rem]">
              {REVIEWS.map((r) => (
                <div
                  key={r.name}
                  className="[break-inside:avoid] mb-5 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden"
                >
                  <img
                    src={r.photo}
                    alt={r.name}
                    className="w-full aspect-[4/3] object-cover object-top"
                    loading="lazy"
                  />
                  <div className="p-5">
                    <div className="flex items-center gap-2 mb-3">
                      <Stars
                        rating={r.rating}
                        size={16}
                        gap="gap-0.5"
                        text="text-[9px]"
                      />
                      <span className="text-[11px] font-semibold text-slate-700">
                        {r.rating.toFixed(1)}
                      </span>
                      <span className="text-[11px] text-slate-400">
                        Verified · Invited
                      </span>
                    </div>
                    <p className="text-[13.5px] leading-relaxed text-slate-600 mb-4">
                      {r.quote}
                    </p>
                    <div className="min-w-0">
                      <div className="font-semibold text-[13.5px] text-slate-900">
                        {r.name}
                      </div>
                      <div className="text-[12px] text-slate-500">{r.role}</div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* STATS */}
        <section className="py-16 px-6 bg-white">
          <div className="max-w-3xl mx-auto grid grid-cols-1 sm:grid-cols-2 gap-10 text-center">
            {[
              { n: "94%", l: "Average prediction accuracy" },
              {
                n: "14,000+",
                l: "Active members earning passively across global events",
              },
            ].map((s) => (
              <div key={s.l}>
                <div
                  style={HEAD_FONT}
                  className="font-extrabold text-[44px] text-slate-900 leading-none"
                >
                  {s.n}
                </div>
                <div className="mt-2 text-[14px] text-slate-500 max-w-[260px] mx-auto leading-snug">
                  {s.l}
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* GUARANTEE — German seal + pill artwork */}
        <section className="pb-20 px-6 bg-white">
          <div className="max-w-4xl mx-auto flex flex-col sm:flex-row items-center justify-center gap-5 sm:gap-8">
            <img
              src={GUARANTEE_SEAL_DE_URL}
              alt="180 Tage Geld-zurück-Garantie"
              className="w-[160px] sm:w-[200px] h-auto object-contain shrink-0"
              loading="lazy"
            />
            <div className="min-w-0 w-full max-w-xl space-y-3 text-center sm:text-left">
              <img
                src={GUARANTEE_PILL_DE_URL}
                alt="180-Tage Geld-zurück-Garantie"
                className="w-full h-auto object-contain"
                loading="lazy"
              />
              <p className="text-[13.5px] sm:text-[15px] leading-relaxed text-slate-600">
                Qualifizierte Bewerber können unser gesamtes KI-Handelssystem
                180 Tage lang testen und wenn Sie nicht mit mindestens $100.000
                finanziert werden,{" "}
                <span className="font-semibold text-slate-900">
                  erhalten Sie eine volle 100% Rückerstattung!
                </span>
              </p>
              <p className="text-[11px] italic text-slate-400">
                *Details siehe AGB.
              </p>
            </div>
          </div>
        </section>

        {/* FOOTER */}
        <footer className="bg-[#0a1728] px-6 py-12">
          <div className="max-w-6xl mx-auto grid md:grid-cols-[1.6fr_1fr_1fr] gap-8">
            <div>
              <div className="flex items-center mb-4">
                <Logo height={52} variant="dark" />
              </div>
              <p className="text-[13px] leading-relaxed text-slate-400 max-w-sm">
                A done-for-you AI system that reads global events, crypto,
                weather, geopolitics and sports — so you can earn passively
                without lifting a finger.
              </p>
            </div>
            <div>
              <div
                style={HEAD_FONT}
                className="font-semibold text-[13px] text-white mb-3"
              >
                Quick Links
              </div>
              <div className="flex flex-col gap-2 text-[13px] text-slate-400">
                <a href="#benefits" className="hover:text-white">
                  Benefits
                </a>
                <a href="#how" className="hover:text-white">
                  How it works
                </a>
                <a href="#pricing" className="hover:text-white">
                  Pricing
                </a>
                <a href="#reviews" className="hover:text-white">
                  Reviews
                </a>
              </div>
            </div>
            <div>
              <div
                style={HEAD_FONT}
                className="font-semibold text-[13px] text-white mb-3"
              >
                Get Started
              </div>
              <CtaButton onClick={cta} className="px-4 py-2 text-[12px]" />
            </div>
          </div>
          <div className="max-w-6xl mx-auto mt-10 pt-6 border-t border-white/10">
            <p className="text-[11px] leading-relaxed text-slate-500 mb-2">
              Predictions involve risk. Figures shown are illustrative and not a
              guarantee of future results. The 180-Tage Geld-zurück-Garantie is
              subject to its terms. Availability varies by jurisdiction. This
              page is a demonstration mockup.
            </p>
            <p className="text-[11.5px] text-slate-500">
              © 2026 Prediction Profits AI. All rights reserved.
            </p>
          </div>
        </footer>
      </div>
    </PricingContext.Provider>
  );
}

/* ----------------------------- small components ---------------------------- */

/**
 * Trading workspace photo that bobs gently, with two stat chips pinned to its
 * corners. The chips drift on a slower, offset loop so they read as sitting in
 * front of the card rather than glued to it.
 */
const FloatingWorkspace = () => {
  const { profitChip } = usePricing();
  const bob = { duration: 6, repeat: Infinity, ease: "easeInOut" } as const;

  return (
    <div className="relative mx-auto w-full max-w-[620px] px-6 py-10">
      <motion.div
        animate={{ y: [0, -14, 0] }}
        transition={bob}
        className="rounded-3xl bg-white p-2 shadow-[0_30px_60px_-15px_rgba(10,26,48,0.35)]"
      >
        <img
          src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1783955716/11_gxtvoa_1_digvef.png"
          alt="Trader monitoring Prediction Profits AI across multiple screens"
          className="w-full h-auto rounded-2xl object-cover"
          loading="lazy"
        />
      </motion.div>

      <motion.div
        animate={{ y: [0, -20, 0] }}
        transition={{ ...bob, delay: 0.4 }}
        className="absolute -left-2 sm:-left-20 top-[7%] flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-[0_18px_35px_-10px_rgba(10,26,48,0.3)]"
      >
        <span className="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
          <IconTrendUp />
        </span>
        <div>
          <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
            Total Profit
          </div>
          <div
            style={HEAD_FONT}
            className="font-bold text-[17px] leading-tight text-slate-900"
          >
            {profitChip}
          </div>
        </div>
      </motion.div>

      <motion.div
        animate={{ y: [0, -20, 0] }}
        transition={{ ...bob, delay: 1.2 }}
        className="absolute -right-2 sm:-right-6 bottom-[8%] flex items-center gap-3 rounded-2xl bg-[#0d2447] px-4 py-3 shadow-[0_18px_35px_-10px_rgba(10,26,48,0.45)]"
      >
        <span className="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-[#4f46e5]/25 text-indigo-300">
          <IconZap />
        </span>
        <div>
          <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
            Execution
          </div>
          <div
            style={HEAD_FONT}
            className="font-bold text-[17px] leading-tight text-white"
          >
            0.04ms
          </div>
        </div>
      </motion.div>
    </div>
  );
};

const IconTrendUp = () => (
  <svg
    width="18"
    height="18"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2.2"
    strokeLinecap="round"
    strokeLinejoin="round"
  >
    <path d="M3 17l6-6 4 4 7-7" />
    <path d="M14 8h6v6" />
  </svg>
);

/** Sized by height; the width follows the artwork's aspect ratio. */
const Logo = ({
  height = 60,
  variant = "light",
}: {
  height?: number;
  variant?: "light" | "dark";
}) => {
  const logo = useCampaignLogo(variant);
  return (
    <img
      src={logo}
      alt="Prediction Profits AI"
      style={{ height }}
      className="w-auto object-contain"
    />
  );
};

const IconChip = () => (
  <svg
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.9"
  >
    <rect x="6" y="6" width="12" height="12" rx="2" />
    <path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3" />
  </svg>
);
const IconZap = () => (
  <svg
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.9"
    strokeLinecap="round"
    strokeLinejoin="round"
  >
    <path d="M13 2L4.5 12.5H11l-1 9.5L19.5 11H13z" />
  </svg>
);
const IconUser = () => (
  <svg
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.9"
    strokeLinecap="round"
    strokeLinejoin="round"
  >
    <circle cx="12" cy="8" r="4" />
    <path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1" />
  </svg>
);
const IconBars = () => (
  <svg
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.9"
  >
    <path d="M5 20V10M12 20V4M19 20v-7" />
  </svg>
);
const StepCheck = () => (
  <svg
    width="26"
    height="26"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.8"
  >
    <path d="M9 11l3 3L22 4" />
    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
  </svg>
);
const StepDoc = () => (
  <svg
    width="26"
    height="26"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.8"
  >
    <rect x="3" y="4" width="18" height="14" rx="2" />
    <path d="M7 20h10M8 8h8M8 12h5" />
  </svg>
);
const StepGear = () => (
  <svg
    width="26"
    height="26"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.8"
  >
    <circle cx="12" cy="12" r="3" />
    <path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1" />
  </svg>
);
const StepBolt = () => (
  <svg
    width="26"
    height="26"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.8"
  >
    <path d="M13 2L4.5 12.5H11l-1 9.5L19.5 11H13z" />
  </svg>
);
