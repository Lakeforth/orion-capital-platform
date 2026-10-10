/**
 * ApexFundedLanding.tsx
 * -----------------------------------------------------------------------------
 * Single-file landing page for the Apex Funded prop-firm campaign. Cloned from
 * TrivonFundedLanding / PredictionLanding — same design language (navy +
 * #1583F0 brand-blue accent, Poppins/Open Sans, rounded cards, Trustpilot
 * stars, phone-framed testimonials) with Apex Funded copy from the campaign
 * script.
 *
 * FRONTEND ONLY: no backend, no i18n. English/USD, static pricing. Every CTA
 * navigates to /apex-checkout.
 *
 * Fonts: Poppins (headings) + Open Sans (body) via inline font stacks. Add once
 * to your <head> for the exact look:
 *   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
 */

import { useEffect, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { AnimatePresence, motion, useReducedMotion } from "framer-motion";
import {
  BRAND_NAME,
  PRODUCT_IMAGE_URL,
  SCREENSHOTS,
  WORKSPACE_IMAGE_URL,
  useCampaignLogo,
} from "@/pages/apex/apexBrand";
import { PRICING, REAL_COST_ROWS } from "@/pages/apex/apexPricing";
import FunnelLangSwitch from "@/components/FunnelLangSwitch";
import { useApplyLangParam } from "@/lib/funnelLanguage";

const HEAD_FONT = { fontFamily: "'Poppins', system-ui, sans-serif" } as const;
const BODY_FONT = { fontFamily: "'Open Sans', system-ui, sans-serif" } as const;

/**
 * The page has a single call to action. Every CTA button renders this label and
 * shares the same `cta` handler — keep it that way.
 */
const CtaLabel = () => (
  <>
    Get Started at{" "}
    <span className="line-through opacity-70">{PRICING.totalValue}</span>{" "}
    {PRICING.entryPrice}
  </>
);

const BTN_PRIMARY =
  "font-semibold inline-flex items-center gap-2 text-white bg-[#1583F0] hover:bg-[#0E6FD9] rounded-lg transition";

/**
 * CTA button with a pulsing halo: a gradient layer sitting behind the button
 * that breathes outward and fades on a 3s loop.
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
      className="absolute rounded-2xl bg-gradient-to-r from-[#1583F0] to-[#6CC5F7]"
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

export interface ApexFundedLandingProps {
  /** Capital slots claimed this cycle (drives the scarcity bar). */
  slotsClaimed?: number;
  totalSlots?: number;
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
  initials: string;
  avBg: string;
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

const FUNDED_TIERS = ["$50,000", "$100,000", "$200,000", "$300,000"];
const PAYOUTS = ["$4,120", "$7,540", "$9,860", "$12,300", "$21,450"];

let activityUid = 0;

const evaluationEvent = (i: number): Activity => {
  const m = MEMBERS[i % MEMBERS.length];
  return {
    id: ++activityUid,
    icon: "🎯",
    chipBg: "#eff6ff",
    chipFg: "#1583F0",
    title: "Evaluation passed",
    sub: `${m.name} cleared to a ${FUNDED_TIERS[i % FUNDED_TIERS.length]} account`,
  };
};

const payoutEvent = (i: number): Activity => {
  const m = MEMBERS[(i + 4) % MEMBERS.length];
  return {
    id: ++activityUid,
    icon: "💸",
    chipBg: "#ecfdf5",
    chipFg: "#059669",
    title: "Payout processed",
    sub: `${m.name} withdrew ${PAYOUTS[i % PAYOUTS.length]} · ${m.place}`,
  };
};

const memberEvent = (i: number): Activity => {
  const m = MEMBERS[(i + 7) % MEMBERS.length];
  return {
    id: ++activityUid,
    icon: "🚀",
    chipBg: "#EFF7FE",
    chipFg: "#3D9BF7",
    title: "New trader onboarded",
    sub: `${m.name} started the evaluation · ${m.place}`,
  };
};

/** Cycle through the three event types so the feed stays varied. */
const activityEvent = (tick: number): Activity => {
  const kind = tick % 3;
  const i = Math.floor(tick / 3);
  return kind === 0
    ? memberEvent(i)
    : kind === 1
      ? evaluationEvent(i)
      : payoutEvent(i);
};

const SEED_ACTIVITY: Activity[] = Array.from(
  { length: ACTIVITY_ROWS },
  (_, i) => activityEvent(ACTIVITY_ROWS - 1 - i),
);

const REVIEWS: Review[] = [
  {
    quote:
      "Passed my evaluation on the second try. What actually got my attention was the fee landing back in my account with my first payout; I wasn't expecting that part to be true.",
    name: "Lukas Ferber",
    role: "Cologne, Germany",
    initials: "LF",
    avBg: "#1583F0",
    rating: 4.6,
  },
  {
    quote:
      "I run a small logistics business, so a 91% profit split sounded too generous to be true, until I actually saw a payout hit my account and realized they weren't rounding down anywhere.",
    name: "Moyle Roger",
    role: "Alice Springs, Australia",
    initials: "MR",
    avBg: "#059669",
    rating: 4.5,
  },
  {
    quote:
      "Six years trading my own $8,000 account, one bad week and I'd be starting over. My Apex Funded account absorbed a swing in my second month that would've wiped me out before.",
    name: "Han On",
    role: "Hong Kong, China",
    initials: "HO",
    avBg: "#7c3aed",
    rating: 4.4,
  },
  {
    quote:
      "My account manager walked me through the drawdown reset before I even asked. Small thing, but nobody's ever explained a rule to me before I broke it.",
    name: "Wiler Sean",
    role: "Manchester, UK",
    initials: "WS",
    avBg: "#d97706",
    rating: 4.3,
  },
  {
    quote:
      "My wife still thinks I'm going to lose the house over this. I keep telling her it's not my money on the line, she doesn't believe me either way. Regardless, I'm loving the Apex Funded setup.",
    name: "Dominic O'Shea",
    role: "Utah, US",
    initials: "DO",
    avBg: "#3D9BF7",
    rating: 4.2,
  },
  {
    quote:
      "I've been trading on my own for six years and never once had capital that could actually absorb a bad week. First month funded, I finally understood what people meant by 'room to breathe'.",
    name: "Sylvie Marchand",
    role: "Montréal, Canada",
    initials: "SM",
    avBg: "#dc2626",
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
 * number decelerates as it lands. Respects prefers-reduced-motion.
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
      const eased = 1 - Math.pow(1 - t, 3); // easeOutCubic
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

export default function ApexFundedLanding({
  slotsClaimed = 112,
  totalSlots = 200,
  showActivity = true,
  onCtaClick,
}: ApexFundedLandingProps) {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const uuid = searchParams.get("uuid");

  // Honor an incoming `?lang=` deep link (e.g. a German ad link).
  useApplyLangParam();

  const [slotPct, setSlotPct] = useState(0);
  const claimedCount = useCountUp(slotsClaimed);
  const totalCount = useCountUp(totalSlots);
  const activity = useLiveActivity(showActivity);
  const reduceMotion = useReducedMotion();

  // Animate the scarcity bar on mount.
  useEffect(() => {
    const t = setTimeout(
      () => setSlotPct(Math.min(100, (slotsClaimed / totalSlots) * 100)),
      200,
    );
    return () => clearTimeout(t);
  }, [slotsClaimed, totalSlots]);

  const cta =
    onCtaClick ??
    (() =>
      navigate(uuid ? `/apex-checkout?uuid=${uuid}` : "/apex-checkout"));

  return (
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
      <section className="relative overflow-hidden bg-gradient-to-br from-white via-slate-50 to-[#EFF7FE]">
        <div className="absolute inset-0 bg-[radial-gradient(700px_340px_at_82%_18%,rgba(21,131,240,0.14),transparent_62%)]" />
        <div className="absolute inset-0 bg-[radial-gradient(500px_260px_at_95%_60%,rgba(59,130,246,0.10),transparent_60%)]" />
        <div className="relative max-w-6xl mx-auto px-6 pt-10 pb-16 grid lg:grid-cols-2 gap-10 items-center">
          <div>
            {/* Scarcity tagline — the slot numbers count up on mount. */}
            <motion.div
              initial={{ opacity: 0, y: -8 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5 }}
              className="inline-flex items-center gap-2.5 rounded-full border border-[#1583F0]/25 bg-white shadow-sm pl-3 pr-4 py-1.5 mb-6"
              aria-label={`${slotsClaimed} of ${totalSlots} capital slots claimed`}
            >
              <span
                aria-hidden="true"
                className="relative flex h-2 w-2 flex-none"
              >
                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-70" />
                <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
              </span>
              <span
                aria-hidden="true"
                style={HEAD_FONT}
                className="text-[13px] font-medium text-slate-600"
              >
                <span className="font-bold text-slate-900 tabular-nums">
                  {claimedCount}
                </span>
                <span className="text-slate-400"> / </span>
                <span className="font-bold text-slate-900 tabular-nums">
                  {totalCount}
                </span>{" "}
                capital slots claimed this cycle
              </span>
            </motion.div>

            <h1
              style={HEAD_FONT}
              className="font-extrabold text-slate-900 text-[38px] sm:text-[46px] leading-[1.08] tracking-tight mb-4"
            >
              Stop Trading Small.
              <br />
              <span className="underline decoration-[#1583F0] decoration-[3px] underline-offset-[6px]">
                Start Trading Funded.
              </span>
            </h1>
            <p
              style={HEAD_FONT}
              className="font-semibold text-[#1583F0] text-[17px] sm:text-[19px] mb-5"
            >
              100% Funded | 0% Personal Risk
            </p>
            <p className="text-slate-600 text-[16px] leading-relaxed max-w-xl mb-8">
              <strong className="font-semibold">Apex Funded</strong> gives
              skilled traders access to serious capital, so market volatility
              works for you, not against you. Pass one evaluation, trade with
              real firepower, and keep up to 91% of everything you earn.
            </p>
            <div className="flex flex-wrap gap-3">
              <CtaButton
                onClick={cta}
                arrow
                className="px-7 py-3.5 shadow-lg shadow-[#1583F0]/25"
              />
            </div>
          </div>

          {/* Bobbing workspace photo with floating stat chips. */}
          <ApexFloatingWorkspace />
        </div>
      </section>

      {/* FEATURES / BENEFITS */}
      <section id="benefits" className="py-20 px-6 bg-white">
        <div className="max-w-6xl mx-auto grid md:grid-cols-4 gap-8">
          {[
            {
              title: "Massive Funding to Scale",
              body: "Trade with a capital base built to withstand normal market volatility. Your strategy finally has room to play out the way it was designed to, instead of getting cut short by an account too thin to survive a swing.",
              icon: <IconBars />,
            },
            {
              title: "Keep Up to 91% of Profits",
              body: "This is your edge, executed at scale. When your strategy performs, the vast majority of the upside comes back to you; not diluted, not delayed.",
              icon: <IconZap />,
            },
            {
              title: "Dedicated Account Manager",
              body: "From evaluation to scaling, one account manager stays with you; no generic support line, no starting over with someone new each time.",
              icon: <IconUser />,
            },
            {
              title: "Zero Personal Financial Risk",
              body: "No personal capital on the line, ever. You bring the skill and discipline. We bring the capital that backs it.",
              icon: <IconChip />,
            },
          ].map((f) => (
            <div key={f.title}>
              <div className="flex items-center gap-3 mb-4">
                <div className="text-[#1583F0] flex-shrink-0">{f.icon}</div>
                <h3
                  style={HEAD_FONT}
                  className="font-bold text-[16px] text-[#1583F0]"
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

      {/* FURTHER BENEFIT / UNDER-CAPITALIZATION */}
      <section
        id="assessment"
        className="py-16 px-6 bg-slate-50 border-y border-slate-100"
      >
        <div className="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
          <div className="relative">
            <img
              src={PRODUCT_IMAGE_URL}
              alt="Apex Funded trader dashboard mockup"
              className="w-full max-w-[320px] h-auto mx-auto drop-shadow-2xl"
            />
          </div>
          <div>
            <h2
              style={HEAD_FONT}
              className="font-extrabold text-slate-900 text-[30px] sm:text-[36px] leading-tight tracking-tight mb-4"
            >
              Escape The Under-Capitalization Trap
            </h2>
            <p className="text-[15.5px] leading-relaxed text-slate-500 mb-7">
              A small personal account simply can't absorb harsh market
              conditions. One volatile swing, one wider-than-expected spread,
              and a margin call can wipe out weeks of careful work, simply
              because your account was too thin to survive. Eventually, it's not
              about getting better at trading, but about getting properly
              funded.
            </p>
            <CtaButton
              onClick={cta}
              arrow
              className="px-7 py-3.5 shadow-lg shadow-[#1583F0]/25"
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
                  title: "Sign Up",
                  body: "Sign up on Apex Funded in minutes and get access to your trader dashboard.",
                  icon: <StepCheck />,
                },
                {
                  title: "Pass the Evaluation",
                  body: "Prove your strategy under real market conditions. No gimmicks, just consistent, disciplined execution.",
                  icon: <StepDoc />,
                },
                {
                  title: "Get Funded",
                  body: "Once you pass, your funded account activates and your trading capital goes live.",
                  icon: <StepGear />,
                },
                {
                  title: "Trade, Withdraw, Scale",
                  body: "Start trading, withdraw profits and retain 91%, and unlock larger funding tiers as you prove consistency.",
                  icon: <StepBolt />,
                },
              ].map((s) => (
                <div
                  key={s.title}
                  className="text-center flex flex-col items-center"
                >
                  <div className="w-24 h-24 rounded-full bg-white flex items-center justify-center text-[#1583F0] mb-8 shadow-xl">
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
                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75" />
                <span className="relative inline-flex w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.18)]" />
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
                    <div className="text-[13px] text-slate-500">{row.sub}</div>
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

      {/* PRICE BREAKDOWN */}
      <section
        id="pricing"
        className="py-20 px-6 bg-slate-50 border-y border-slate-100"
      >
        <div className="max-w-6xl mx-auto">
          <h2
            style={HEAD_FONT}
            className="font-extrabold text-slate-900 text-[28px] sm:text-[36px] tracking-tight text-center max-w-3xl mx-auto mb-12"
          >
            The Real Cost of Trading with Your Own Capital
          </h2>
          <div className="grid lg:grid-cols-[1.15fr_0.85fr] gap-7 items-start">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm px-7 py-3">
              {REAL_COST_ROWS.map((v) => (
                <div
                  key={v.label}
                  className="flex items-center justify-between gap-4 py-4 border-b border-slate-100"
                >
                  <span className="text-[15px] text-slate-700">{v.label}</span>
                  <span
                    style={HEAD_FONT}
                    className="font-bold text-[16px] text-red-500 whitespace-nowrap"
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
                  Get Funded:
                </span>
                <span
                  style={HEAD_FONT}
                  className="font-extrabold text-[22px] text-emerald-600"
                >
                  0% Risk
                </span>
              </div>
            </div>
            <div className="lg:sticky lg:top-24 bg-white rounded-2xl border-2 border-[#1583F0] shadow-xl shadow-[#DCEEFD] p-8 text-center">
              <div className="text-[13.5px] text-slate-500 mb-1">
                Standard price{" "}
                <span className="line-through text-slate-400">
                  {PRICING.totalValue}
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
                {PRICING.entryPrice}
              </div>
              <CtaButton
                onClick={cta}
                block
                wrapperClassName="mb-6"
                className="py-3.5 text-[14px] shadow-lg shadow-[#1583F0]/25"
              />
              <div
                style={HEAD_FONT}
                className="font-semibold text-[12px] tracking-wider uppercase text-[#1583F0] text-left mb-2"
              >
                Limited Capital Pool This Cycle
              </div>
              <p className="text-[13px] leading-relaxed text-slate-500 text-left mb-4">
                We allocate a fixed pool of funded capital each cycle to
                maintain risk controls across all active accounts. Once this
                cycle's allocation is fully assigned, new evaluations must wait
                for the next cycle.
              </p>
              <div className="flex items-center justify-between mb-2">
                <span className="text-[13px] text-slate-500">
                  <b className="text-slate-900">{slotsClaimed}</b> /{" "}
                  {totalSlots} Capital Slots Available
                </span>
              </div>
              <div className="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                <div
                  className="h-full rounded-full bg-gradient-to-r from-[#1583F0] to-[#6CC5F7] transition-[width] duration-1000"
                  style={{ width: `${slotPct}%` }}
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
            Unedited messages from funded traders.
          </p>
          <div className="grid md:grid-cols-3 gap-6 max-w-3xl mx-auto">
            {SCREENSHOTS.map((url) => (
              <div
                key={url}
                className="aspect-[9/19] bg-slate-100 rounded-[2rem] border-[6px] border-slate-900 shadow-xl overflow-hidden relative"
              >
                <img
                  src={url}
                  alt="Screenshot from an Apex Funded trader's phone"
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
                Rated <b className="text-slate-900">{trustLabel(AVG_RATING)}</b>{" "}
                on Trustpilot ·{" "}
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
                className="[break-inside:avoid] mb-5 bg-white rounded-xl border border-slate-200 shadow-sm p-5"
              >
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
                <div className="flex items-center gap-3">
                  <span
                    className="w-8 h-8 rounded-full flex items-center justify-center text-[12px] font-bold text-white"
                    style={{ background: r.avBg }}
                  >
                    {r.initials}
                  </span>
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

      {/* FLOATING STATS */}
      <section className="py-16 px-6 bg-white">
        <div className="max-w-4xl mx-auto grid grid-cols-2 lg:grid-cols-4 gap-10 text-center">
          {[
            { n: "$300M+", l: "Paid out to funded traders" },
            { n: "$300K+", l: "Funded per trader" },
            { n: "91%", l: "Profit split available" },
            { n: "0%", l: "Trading risk for you" },
          ].map((s) => (
            <div key={s.l}>
              <div
                style={HEAD_FONT}
                className="font-extrabold text-[40px] sm:text-[44px] text-slate-900 leading-none"
              >
                {s.n}
              </div>
              <div className="mt-2 text-[14px] text-slate-500 max-w-[200px] mx-auto leading-snug">
                {s.l}
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* GUARANTEE */}
      <section className="pb-20 px-6 bg-white">
        <div className="max-w-3xl mx-auto rounded-2xl border-2 border-[#1583F0]/20 bg-gradient-to-br from-[#EFF7FE] to-white shadow-sm px-8 py-12 text-center">
          <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-[#1583F0] text-white shadow-lg shadow-[#1583F0]/30">
            <GuaranteeShield />
          </div>
          <h2
            style={HEAD_FONT}
            className="font-extrabold text-slate-900 text-[26px] sm:text-[32px] tracking-tight mb-4"
          >
            Your Evaluation Fee, Refunded 100%.
          </h2>
          <p className="text-[15.5px] leading-relaxed text-slate-500 max-w-2xl mx-auto">
            Your evaluation fee is credited back to you in full with your first
            payout. You're not paying to play. You're paying to prove your
            strategy, and getting that cost back the moment you do.
          </p>
        </div>
      </section>

      {/* FOOTER */}
      <footer className="bg-[#0a1728] px-6 py-12">
        <div className="max-w-6xl mx-auto grid md:grid-cols-[1.6fr_1fr_1fr] gap-8">
          <div>
            <div className="flex items-center mb-4">
              <Logo height={52} tone="light" />
            </div>
            <p className="text-[13px] leading-relaxed text-slate-400 max-w-sm">
              Apex Funded gives skilled traders access to serious capital —
              pass one evaluation, trade with real firepower, and keep up to
              91% of everything you earn, with zero personal risk.
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
            Trading involves risk. Figures shown are illustrative and not a
            guarantee of future results. The evaluation-fee refund is subject to
            its terms. Availability varies by jurisdiction. This page is a
            demonstration mockup.
          </p>
          <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
            <p className="text-[11.5px] text-slate-500">
              © 2026 Apex Funded. All rights reserved.
            </p>
            <p className="text-[11.5px] text-slate-500">
              Powered by:{" "}
              <span className="text-slate-400">Apex Funded</span>
            </p>
          </div>
        </div>
      </footer>
    </div>
  );
}

/* ----------------------------- small components ---------------------------- */

/** Sized by height; falls back to a wordmark when no logo URL is set. */
const Logo = ({
  height = 60,
  tone = "dark",
}: {
  height?: number;
  tone?: "dark" | "light";
}) => {
  const logo = useCampaignLogo();
  if (!logo) {
    return (
      <span
        style={{ ...HEAD_FONT, fontSize: Math.round(height * 0.42) }}
        className={`font-extrabold tracking-tight ${
          tone === "light" ? "text-white" : "text-slate-900"
        }`}
      >
        {BRAND_NAME}
      </span>
    );
  }
  return (
    <img
      src={logo}
      alt={BRAND_NAME}
      style={{ height }}
      className="w-auto object-contain"
    />
  );
};

/**
 * Trading workspace photo that bobs gently, with two stat chips pinned to its
 * corners. The chips drift on a slower, offset loop so they read as sitting in
 * front of the card rather than glued to it. Mirrors the Prediction funnel's
 * hero visual, re-themed for the funded offer.
 */
const ApexFloatingWorkspace = () => {
  const bob = { duration: 6, repeat: Infinity, ease: "easeInOut" } as const;

  return (
    <div className="relative mx-auto w-full max-w-[620px] px-6 py-10">
      <motion.div
        animate={{ y: [0, -14, 0] }}
        transition={bob}
        className="rounded-3xl bg-white p-2 shadow-[0_30px_60px_-15px_rgba(10,26,48,0.35)]"
      >
        <img
          src={WORKSPACE_IMAGE_URL}
          alt="Trader managing an Apex Funded account across multiple screens"
          className="w-full h-auto rounded-2xl object-cover"
          loading="lazy"
        />
      </motion.div>

      <motion.div
        animate={{ y: [0, -20, 0] }}
        transition={{ ...bob, delay: 0.4 }}
        className="absolute -left-2 sm:-left-20 top-[7%] flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-[0_18px_35px_-10px_rgba(10,26,48,0.3)]"
      >
        <span className="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
          <IconTrendUp />
        </span>
        <div>
          <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
            Profit Split
          </div>
          <div
            style={HEAD_FONT}
            className="font-bold text-[17px] leading-tight text-slate-900"
          >
            91%
          </div>
        </div>
      </motion.div>

      <motion.div
        animate={{ y: [0, -20, 0] }}
        transition={{ ...bob, delay: 1.2 }}
        className="absolute -right-2 sm:-right-6 bottom-[8%] flex items-center gap-3 rounded-2xl bg-[#0d2447] px-4 py-3 shadow-[0_18px_35px_-10px_rgba(10,26,48,0.45)]"
      >
        <span className="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-[#1583F0]/25 text-[#8FD5FA]">
          <IconZap />
        </span>
        <div>
          <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
            Funded Capital
          </div>
          <div
            style={HEAD_FONT}
            className="font-bold text-[17px] leading-tight text-white"
          >
            $100,000
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

const GuaranteeShield = () => (
  <svg
    width="30"
    height="30"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.9"
    strokeLinecap="round"
    strokeLinejoin="round"
  >
    <path d="M12 2l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V5z" />
    <path d="M9 12l2 2 4-4" />
  </svg>
);

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
