/**
 * TrivonFundedLanding.tsx
 * -----------------------------------------------------------------------------
 * Single-file brokerage landing page for Trivon Funded. Cloned
 * from PredictionLanding.tsx — same design language (navy + #1583F0 brand-blue
 * accent, Poppins/Open Sans, rounded cards, Trustpilot stars, phone-framed
 * testimonials) with Trivon's brokerage-focused content.
 *
 * FRONTEND ONLY: no backend, no i18n. English/USD, static pricing. Every CTA
 * navigates to /trivon-checkout.
 *
 * Fonts: Poppins (headings) + Open Sans (body) via inline font stacks. Add once
 * to your <head> for the exact look:
 *   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
 */

import { useEffect, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { AnimatePresence, motion, useReducedMotion } from "framer-motion";
import { SCREENSHOTS, useCampaignLogo } from "@/pages/trivon/trivonBrand";
import { PRICING, PROGRAMS, REAL_COST_ROWS } from "@/pages/trivon/trivonPricing";
import FunnelLangSwitch from "@/components/FunnelLangSwitch";
import { useApplyLangParam } from "@/lib/funnelLanguage";

const HEAD_FONT = { fontFamily: "'Poppins', system-ui, sans-serif" } as const;
const BODY_FONT = { fontFamily: "'Open Sans', system-ui, sans-serif" } as const;

/**
 * Hero video. The real video isn't ready yet, so the hero renders the poster
 * image with a play button on top and the button stays inert. Drop the embed
 * URL in here and the section becomes a live player — that is the whole change,
 * nothing else in this file needs touching.
 */
const TRIVON_VIDEO_URL: string | null = null;

/**
 * Both Figma frames are the same page in a light and a dark scheme, and neither
 * carries a theme control — so the visitor's OS setting decides, with nothing on
 * the page to override it.
 */
function useLandingTheme(): boolean {
  const [isDark, setIsDark] = useState<boolean>(() => {
    if (typeof window === "undefined") return true;
    return window.matchMedia?.("(prefers-color-scheme: dark)").matches ?? true;
  });

  useEffect(() => {
    if (typeof window === "undefined") return;
    const mq = window.matchMedia("(prefers-color-scheme: dark)");
    const onChange = (e: MediaQueryListEvent) => setIsDark(e.matches);
    mq.addEventListener("change", onChange);
    return () => mq.removeEventListener("change", onChange);
  }, []);

  return isDark;
}

/**
 * Surface + text tokens taken straight off the two Figma frames, so each theme
 * uses one consistent palette instead of ad-hoc colours per section.
 *
 *   dark   page #0A1728 · card #0E1E35 · inset #0B1A2E · navy accent #0D2447
 *   light  page #FFFFFF · alt #F8FAFC  · inset #F1F5F9 · navy accent #0D2447
 *
 * "How to get started" and the footer are dark bands in BOTH themes, so they
 * deliberately don't use these.
 */
const SURFACE_PAGE = "bg-white dark:bg-[#0A1728]";
const SURFACE_ALT = "bg-[#F8FAFC] dark:bg-[#0A1728]";
const SURFACE_CARD = "bg-white dark:bg-[#0E1E35]";
const SURFACE_INSET = "bg-[#F1F5F9] dark:bg-[#0B1A2E]";
const TEXT_HEAD = "text-[#0F172B] dark:text-white";
const TEXT_BODY = "text-[#45556C] dark:text-[#90A1B9]";
const TEXT_MUTED = "text-[#62748E] dark:text-[#90A1B9]";
const BORDER_SOFT = "border-slate-200 dark:border-white/10";


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
      className="absolute rounded-2xl bg-gradient-to-r from-[#1583F0] to-[#6CC5F7] dark:to-[#0E1E35]"
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

export interface TrivonFundedLandingProps {
  /** Registration slots claimed this cycle (drives the scarcity bar). */
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
    title: "Account activated",
    sub: `${m.name} opened a ${FUNDED_TIERS[i % FUNDED_TIERS.length]} trading account`,
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
    title: "Trading access enabled",
    sub: `${m.name} joined Trivon Funded · ${m.place}`,
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
      "Watched my cousin lose a chunk of his account all in a day years ago. That fear kept me out of trading completely until now.",
    name: "Ashley Monroe",
    role: "Ottawa, Canada",
    initials: "AM",
    avBg: "#1583F0",
    rating: 4.5,
  },
  {
    quote:
      "I wanted a straightforward way to access the markets without juggling several platforms. Registration was simple, the dashboard is clear, and support helped me get set up quickly.",
    name: "Nick Zavitz",
    role: "Seattle, US",
    initials: "NZ",
    avBg: "#059669",
    rating: 4.6,
  },
  {
    quote:
      "I wanted a platform that made account management and market access simple. Everything I need is in one place, and the support team is available when I need help.",
    name: "Brandon Cole",
    role: "San Diego, US",
    initials: "BC",
    avBg: "#7c3aed",
    rating: 4.4,
  },
  {
    quote:
      "I registered, completed my account setup, and could access the trading platform without unnecessary steps. The whole experience felt straightforward.",
    name: "Jonas Richter",
    role: "Munich, Germany",
    initials: "JR",
    avBg: "#d97706",
    rating: 4.5,
  },
  {
    quote: "Zero effort on my end. That's exactly what I was told it'd be.",
    name: "J.K. Isaacson",
    role: "Dundee, Scotland",
    initials: "JI",
    avBg: "#3D9BF7",
    rating: 4.3,
  },
  {
    quote:
      "Every time I looked into trading it was candlesticks and indicators I didn't understand. It's funny how I profit from trading without any understanding of charts.",
    name: "Petra Lindemann",
    role: "Cologne, Germany",
    initials: "PL",
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

export default function TrivonFundedLanding({
  slotsClaimed = 167,
  totalSlots = 200,
  showActivity = true,
  onCtaClick,
}: TrivonFundedLandingProps) {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const uuid = searchParams.get("uuid");
  const isDark = useLandingTheme();

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
      navigate(uuid ? `/trivon-checkout?uuid=${uuid}` : "/trivon-checkout"));

  return (
    <div
      style={BODY_FONT}
      className={`${isDark ? "dark " : ""}text-slate-600 bg-white dark:bg-[#0A1728] dark:text-[#90A1B9]`}
    >
      {/* NAV */}
      <header className="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-100 dark:bg-[#0A1728]/90 dark:border-white/5">
        {/* Figma only specifies the 1440 layout: one row, 80px tall. Below sm
            the logo + language + CTA can't fit on one line, so the CTA drops to
            its own full-width row rather than shrinking the lockup. */}
        <div className="max-w-6xl mx-auto px-6 py-3 flex flex-wrap items-center gap-x-4 gap-y-3 sm:h-20 sm:py-0 sm:flex-nowrap sm:gap-8">
          <div className="flex items-center mr-auto">
            <Logo className="h-10 sm:h-[60px]" />
          </div>
          <nav className="hidden lg:flex gap-7 text-[14px] text-slate-500 dark:text-[#90A1B9]">
            <a href="#benefits" className="hover:text-slate-900 dark:hover:text-white">
              Benefits
            </a>
            <a href="#how" className="hover:text-slate-900 dark:hover:text-white">
              How it works
            </a>
            <a href="#pricing" className="hover:text-slate-900 dark:hover:text-white">
              Pricing
            </a>
            <a href="#reviews" className="hover:text-slate-900 dark:hover:text-white">
              Testimonials
            </a>
          </nav>
          <FunnelLangSwitch />
          <CtaButton
            onClick={cta}
            block
            wrapperClassName="order-last w-full sm:order-none sm:w-auto"
            className="px-4 py-2 text-[13px] whitespace-nowrap sm:w-auto"
          />
        </div>
      </header>

      {/* HERO */}
      <section className="relative overflow-hidden bg-gradient-to-br from-white via-slate-50 to-[#EFF7FE] dark:from-[#0B1B30] dark:via-[#0A192B] dark:to-[#0A1728]">
        <div className="absolute inset-0 bg-[radial-gradient(700px_340px_at_82%_18%,rgba(21,131,240,0.14),transparent_62%)]" />
        <div className="absolute inset-0 bg-[radial-gradient(500px_260px_at_95%_60%,rgba(59,130,246,0.10),transparent_60%)]" />
        <div className="relative max-w-6xl mx-auto px-6 pt-10 pb-16 grid lg:grid-cols-2 gap-10 items-center">
          <div>
            {/* Scarcity tagline — the slot numbers count up on mount. */}
            <motion.div
              initial={{ opacity: 0, y: -8 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5 }}
              className="inline-flex items-center gap-2.5 rounded-full border border-[#1583F0]/25 bg-white shadow-sm pl-3 pr-4 py-1.5 mb-6 dark:bg-[#0E1E35]"
              aria-label={`${slotsClaimed} of ${totalSlots} slots available`}
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
                slots available this cycle
              </span>
            </motion.div>

            <h1
              style={HEAD_FONT}
              className="font-extrabold text-slate-900 dark:text-white text-[32px] sm:text-[42px] leading-[1.08] tracking-tight mb-4"
            >
              Trade Global Markets With{" "}
              <span className="underline decoration-[#1583F0] decoration-[3px] underline-offset-[6px]">
                Confidence
              </span>{" "}
              For You.
            </h1>
            <p
              style={HEAD_FONT}
              className="font-semibold text-[#1583F0] text-[17px] sm:text-[19px] mb-5"
            >
              100% Autopilot | 0% Manual Trading
            </p>
            <p className="text-slate-600 dark:text-[#90A1B9] text-[16px] leading-relaxed max-w-xl mb-8">
              <strong className="font-semibold text-slate-900 dark:text-white">
                Trivon Funded
              </strong>
              ,
              is a secure brokerage platform built to give you straightforward
              access to global financial markets. Manage your account, follow
              live prices, use professional trading tools, and get dedicated
              support from one connected experience.
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
          <TrivonFloatingWorkspace />
        </div>
      </section>

      {/* FEATURES / BENEFITS */}
      {/* Figma vertical rhythm is measured off the frame, not the declared
          auto-layout padding, which disagrees on a few sections. */}
      <section id="benefits" className={`py-14 sm:py-[57px] px-6 ${SURFACE_PAGE}`}>
        <div className="max-w-6xl mx-auto grid md:grid-cols-4 gap-8">
          {[
            {
              title: "Access Global Markets",
              body: "Trade forex, indices, commodities, stocks and digital assets from one secure brokerage account.",
              icon: <IconBars />,
            },
            {
              title: "Manage Your Account Securely",
              body: "Monitor balances, deposits, withdrawals and trading activity through a clear, connected client portal.",
              icon: <IconZap />,
            },
            {
              title: "Trade With Professional Tools",
              body: "Use live market pricing, advanced charting and practical risk controls designed for informed trading decisions.",
              icon: <IconUser />,
            },
            {
              title: "Backed by a Dedicated Account Manager",
              body: "You're never on your own. A dedicated account manager supports you from registration through your ongoing trading journey.",
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
              <p className={`text-[13.5px] leading-relaxed ${TEXT_BODY}`}>
                {f.body}
              </p>
            </div>
          ))}
        </div>
      </section>

      {/* FURTHER BENEFIT / UNDER-CAPITALIZATION */}
      <section
        id="assessment"
        className={`py-16 px-6 border-y ${SURFACE_ALT} ${BORDER_SOFT}`}
      >
        <div className="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
          <div className="relative">
            <img
              src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1784206757/Trivon_Funded_software_box_design_202607161305_1_fbhjiu.png"
              alt="Trivon Funded trader dashboard mockup"
              className="w-full max-w-[320px] h-auto mx-auto drop-shadow-2xl"
            />
          </div>
          <div>
            <h2
              style={HEAD_FONT}
              className={`font-extrabold text-[30px] sm:text-[36px] leading-tight tracking-tight mb-4 ${TEXT_HEAD}`}
            >
              One Brokerage Platform. Global Market Access.
            </h2>
            <p className={`text-[15.5px] leading-relaxed mb-7 ${TEXT_BODY}`}>
              Trading should not require juggling disconnected tools and
              services. Trivon brings market access, account controls,
              professional trading tools and dedicated support together in one
              secure, easy-to-use platform.
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
                  title: "Register",
                  body: `Get started for as little as ${PRICING.entryPrice} and begin setting up your Trivon brokerage account.`,
                  icon: <StepCheck />,
                },
                {
                  title: "Verify Your Account",
                  body: "Complete the secure onboarding and identity-verification steps required to activate your account.",
                  icon: <StepDoc />,
                },
                {
                  title: "Go Live",
                  body: "Fund your brokerage account and access live markets through the Trivon trading platform.",
                  icon: <StepGear />,
                },
                {
                  title: "Manage and Trade",
                  body: "Track your portfolio, manage funds and place trades from your connected client area and web trader.",
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
        <section className={`py-16 px-6 ${SURFACE_PAGE}`}>
          <div
            className={`max-w-3xl mx-auto rounded-2xl border shadow-sm overflow-hidden ${BORDER_SOFT}`}
          >
            <div
              className={`flex items-center gap-2.5 px-6 py-4 border-b ${BORDER_SOFT} bg-[#F8FAFC] dark:bg-[#0B1A2E]`}
            >
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
                className={`font-semibold text-[14px] ${TEXT_HEAD}`}
              >
                Live activity
              </span>
              <span className={`ml-auto text-[12.5px] ${TEXT_MUTED}`}>
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
                  className={`flex items-center gap-4 px-6 py-4 border-b last:border-0 border-slate-100 dark:border-white/5 ${SURFACE_CARD}`}
                >
                  <span
                    className="w-9 h-9 flex-none rounded-lg flex items-center justify-center text-[15px]"
                    style={{ background: row.chipBg, color: row.chipFg }}
                  >
                    {row.icon}
                  </span>
                  <div className="min-w-0">
                    <div className={`font-semibold text-[14px] ${TEXT_HEAD}`}>
                      {row.title}
                    </div>
                    <div className={`text-[13px] ${TEXT_BODY}`}>{row.sub}</div>
                  </div>
                  <span
                    className={`ml-auto flex-none text-[12.5px] whitespace-nowrap ${TEXT_MUTED}`}
                  >
                    {ACTIVITY_TIMES[i]}
                  </span>
                </motion.div>
              ))}
            </AnimatePresence>
          </div>
        </section>
      )}

      {/* BROKERAGE ACCESS — the two programme tiers */}
      <section id="pricing" className={`py-24 px-6 ${SURFACE_PAGE}`}>
        <div className="max-w-6xl mx-auto">
          <h2
            style={HEAD_FONT}
            className={`text-center font-extrabold text-[28px] sm:text-[36px] leading-tight tracking-tight mb-3 ${TEXT_HEAD}`}
          >
            Choose Your Trading Access
          </h2>
          <p
            className={`text-center text-[16px] leading-relaxed max-w-3xl mx-auto mb-14 ${TEXT_BODY}`}
          >
            Start with secure access to global markets, then move to expanded
            trading infrastructure when your needs grow.
          </p>

          <div className="grid lg:grid-cols-2 gap-7 items-start">
            {PROGRAMS.map((p) => (
              <div
                key={p.title}
                className={`rounded-2xl border p-8 h-full flex flex-col ${
                  p.highlighted
                    ? "bg-[#0D2447] border-[#1583F0]"
                    : `${SURFACE_INSET} border-slate-200 dark:border-[#142945]`
                }`}
              >
                <div
                  style={HEAD_FONT}
                  className={`text-[11px] font-bold tracking-wider mb-3 ${
                    p.highlighted ? "text-[#1583F0]" : TEXT_MUTED
                  }`}
                >
                  {p.eyebrow}
                </div>
                <h3
                  style={HEAD_FONT}
                  className={`font-bold text-[22px] mb-3 ${
                    p.highlighted ? "text-white" : TEXT_HEAD
                  }`}
                >
                  {p.title}
                </h3>
                <p
                  className={`text-[15.5px] leading-[25px] mb-6 ${
                    p.highlighted ? "text-[#A6B8CC]" : TEXT_BODY
                  }`}
                >
                  {p.body}
                </p>

                <div className="flex items-baseline gap-3 mb-6">
                  <span
                    style={HEAD_FONT}
                    className={`font-extrabold text-[52px] sm:text-[58px] leading-none tracking-tight ${
                      p.highlighted ? "text-white" : TEXT_HEAD
                    }`}
                  >
                    {p.price}
                  </span>
                  <span
                    className={`text-[15px] ${
                      p.highlighted ? "text-[#A6B8CC]" : TEXT_BODY
                    }`}
                  >
                    {p.priceNote}
                  </span>
                </div>

                <ul className="flex flex-col gap-3 mb-8">
                  {p.features.map((f) => (
                    <li key={f} className="flex items-start gap-3">
                      <span
                        aria-hidden="true"
                        className={`mt-0.5 flex h-5 w-5 flex-none items-center justify-center rounded-full ${
                          p.highlighted
                            ? "bg-[#1583F0]/20 text-[#8FD5FA]"
                            : "bg-[#1583F0]/10 text-[#1583F0]"
                        }`}
                      >
                        <IconCheck />
                      </span>
                      <span
                        className={`text-[15.5px] leading-[25px] ${
                          p.highlighted ? "text-[#A6B8CC]" : TEXT_BODY
                        }`}
                      >
                        {f}
                      </span>
                    </li>
                  ))}
                </ul>

                {/* Both tiers route to the same checkout as every other CTA. */}
                <button
                  type="button"
                  onClick={cta}
                  style={HEAD_FONT}
                  className={`mt-auto w-full rounded-lg py-3.5 text-[16px] font-bold transition ${
                    p.highlighted
                      ? "bg-[#0B1A2E] border border-[#142945] text-[#A6B8CC] hover:bg-[#0E1E35]"
                      : "bg-[#1583F0] text-white shadow-lg shadow-[#1583F0]/25 hover:bg-[#0E6FD9]"
                  }`}
                >
                  {p.ctaLabel}
                </button>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* THE REAL COST OF DOING IT YOURSELF */}
      <section
        id="real-cost"
        className={`pt-16 pb-20 sm:pt-20 sm:pb-[122px] px-6 border-y ${SURFACE_ALT} ${BORDER_SOFT}`}
      >
        <div className="max-w-6xl mx-auto">
          <h2
            style={HEAD_FONT}
            className={`font-extrabold text-[28px] sm:text-[36px] tracking-tight text-center max-w-3xl mx-auto mb-12 ${TEXT_HEAD}`}
          >
            The Real Cost of Doing It Yourself
          </h2>
          <div className="grid lg:grid-cols-[1.15fr_0.85fr] gap-7 items-start">
            <div
              className={`rounded-2xl border shadow-sm px-7 py-3 ${SURFACE_CARD} ${BORDER_SOFT}`}
            >
              {REAL_COST_ROWS.map((v) => (
                <div
                  key={v.label}
                  className={`flex items-center justify-between gap-4 py-4 border-b border-slate-100 dark:border-white/5`}
                >
                  <span className={`text-[15px] ${TEXT_BODY}`}>{v.label}</span>
                  <span
                    style={HEAD_FONT}
                    className="font-bold text-[16px] text-red-500 dark:text-[#FF6467] whitespace-nowrap"
                  >
                    {v.value}
                  </span>
                </div>
              ))}
              <div className="flex items-center justify-between py-5">
                <span
                  style={HEAD_FONT}
                  className={`font-bold text-[17px] ${TEXT_HEAD}`}
                >
                  Trivon Brokerage Access:
                </span>
                <span
                  style={HEAD_FONT}
                  className="font-extrabold text-[22px] text-emerald-600 dark:text-[#00BC7D]"
                >
                  {PRICING.entryPrice}{" "}
                  <span className="text-[14px] font-semibold">(One-Time)</span>
                </span>
              </div>
            </div>
            <div
              className={`lg:sticky lg:top-24 rounded-2xl border-2 border-[#1583F0] shadow-xl shadow-[#DCEEFD] dark:shadow-[#1583F0]/10 p-8 text-center ${SURFACE_CARD}`}
            >
              <div className={`text-[13.5px] mb-1 ${TEXT_BODY}`}>
                Standard price{" "}
                <span className={`line-through ${TEXT_MUTED}`}>
                  {PRICING.totalValue}
                </span>
              </div>
              <div
                style={HEAD_FONT}
                className={`font-semibold text-[15px] ${TEXT_BODY}`}
              >
                Get Started at
              </div>
              <div
                style={HEAD_FONT}
                className={`font-extrabold text-[58px] leading-none tracking-tight mb-5 ${TEXT_HEAD}`}
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
                Limited Slots Available
              </div>
              <p className={`text-[13px] leading-relaxed text-left mb-4 ${TEXT_BODY}`}>
                To keep the system running seamlessly for every active user, we
                limit the number of new registrations accepted each cycle. Once
                this cycle&apos;s slots are filled, new sign-ups must wait for
                the next one.
              </p>
              <div className="flex items-center justify-between mb-2">
                <span className={`text-[13px] ${TEXT_BODY}`}>
                  <b className={TEXT_HEAD}>{slotsClaimed}</b> / {totalSlots}{" "}
                  Slots Available
                </span>
              </div>
              <div
                className={`h-2.5 rounded-full overflow-hidden ${SURFACE_INSET}`}
              >
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
      <section className={`py-20 px-6 ${SURFACE_PAGE}`}>
        <div className="max-w-4xl mx-auto">
          <h2
            style={HEAD_FONT}
            className={`text-center font-extrabold text-[26px] sm:text-[34px] tracking-tight mb-2 ${TEXT_HEAD}`}
          >
            Straight From Their Phones
          </h2>
          <p className={`text-center text-[15px] mb-12 ${TEXT_BODY}`}>
            Feedback from traders using the Trivon platform.
          </p>
          <div className="grid md:grid-cols-3 gap-6 max-w-3xl mx-auto">
            {SCREENSHOTS.map((url) => (
              <div
                key={url}
                className={`aspect-[9/19] w-full max-w-[260px] mx-auto md:max-w-none rounded-[2rem] border-[6px] border-slate-900 dark:border-[#0E1E35] shadow-xl overflow-hidden relative ${SURFACE_INSET}`}
              >
                <img
                  src={url}
                  alt="Screenshot from a Trivon Funded trader's phone"
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
        className={`pb-20 px-6 border-y ${SURFACE_ALT} ${BORDER_SOFT}`}
      >
        <div className="max-w-6xl mx-auto">
          <div className="text-center mb-12">
            <h2
              style={HEAD_FONT}
              className={`font-extrabold text-[26px] sm:text-[36px] tracking-tight mb-4 ${TEXT_HEAD}`}
            >
              Real People. Real Satisfaction.
            </h2>
            <div className={`inline-flex flex-wrap items-center justify-center gap-2.5 text-[14px] ${TEXT_BODY}`}>
              <Stars rating={AVG_RATING} />
              <span>
                Rated <b className={TEXT_HEAD}>{trustLabel(AVG_RATING)}</b>{" "}
                on Trustpilot ·{" "}
                <span className={TEXT_MUTED}>
                  {AVG_RATING.toFixed(1)} out of 5, based on 128 reviews
                </span>
              </span>
            </div>
          </div>
          <div className="[column-count:1] md:[column-count:2] lg:[column-count:3] [column-gap:1.25rem]">
            {REVIEWS.map((r) => (
              <div
                key={r.name}
                className={`[break-inside:avoid] mb-5 rounded-xl border shadow-sm p-5 ${SURFACE_CARD} ${BORDER_SOFT}`}
              >
                <div className="flex items-center gap-2 mb-3">
                  <Stars
                    rating={r.rating}
                    size={16}
                    gap="gap-0.5"
                    text="text-[9px]"
                  />
                  <span className={`text-[11px] font-semibold ${TEXT_BODY}`}>
                    {r.rating.toFixed(1)}
                  </span>
                  <span className={`text-[11px] ${TEXT_MUTED}`}>
                    Verified · Invited
                  </span>
                </div>
                <p className={`text-[13.5px] leading-relaxed mb-4 ${TEXT_BODY}`}>
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
                    <div className={`font-semibold text-[13.5px] ${TEXT_HEAD}`}>
                      {r.name}
                    </div>
                    <div className={`text-[12px] ${TEXT_BODY}`}>{r.role}</div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* FLOATING STATS */}
      <section className={`py-16 px-6 ${SURFACE_PAGE}`}>
        <div className="max-w-3xl mx-auto grid grid-cols-1 sm:grid-cols-3 gap-10 text-center">
          {[
            { n: "100%", l: "Auto execution" },
            { n: "105.8%", l: "Average annual returns" },
            { n: "24/7", l: "Client support" },
          ].map((s) => (
            <div key={s.l}>
              <div
                style={HEAD_FONT}
                className={`font-extrabold text-[40px] sm:text-[44px] leading-none ${TEXT_HEAD}`}
              >
                {s.n}
              </div>
              <div className={`mt-2 text-[14px] max-w-[200px] mx-auto leading-snug ${TEXT_BODY}`}>
                {s.l}
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* GUARANTEE */}
      <section className={`pb-20 px-6 ${SURFACE_PAGE}`}>
        <div className="max-w-3xl mx-auto rounded-2xl border-2 border-[#1583F0]/20 bg-gradient-to-br from-[#EFF7FE] to-white dark:from-[#0E1E35] dark:to-[#0E1E35] shadow-sm px-8 py-12 text-center">
          <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-[#1583F0] text-white shadow-lg shadow-[#1583F0]/30">
            <GuaranteeShield />
          </div>
          <h2
            style={HEAD_FONT}
            className={`font-extrabold text-[26px] sm:text-[32px] tracking-tight mb-4 ${TEXT_HEAD}`}
          >
            180-Day Money-Back Guarantee
          </h2>
          <p
            className={`text-[15.5px] leading-relaxed max-w-2xl mx-auto ${TEXT_BODY}`}
          >
            If your account does not generate positive returns within your first
            180 days, you receive a refund of your {PRICING.entryPrice} deposit.
            No questions asked.
          </p>
        </div>
      </section>

      {/* FOOTER */}
      <footer className="bg-[#0a1728] px-6 py-12">
        <div className="max-w-6xl mx-auto grid md:grid-cols-[1.6fr_1fr_1fr] gap-8">
          <div>
            <div className="flex items-center mb-4">
              <Logo height={52} />
            </div>
            <p className="text-[13px] leading-relaxed text-slate-400 max-w-sm">
              Trivon Funded provides secure brokerage access, multi-asset
              trading tools, account management and dedicated customer support
              through one connected platform.
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
            guarantee of future results. The 180-day money-back guarantee is
            subject to its terms. Availability varies by jurisdiction. This page
            is a demonstration mockup.
          </p>
          <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
            <p className="text-[11.5px] text-slate-500">
              © 2026 Trivon Funded. All rights reserved.
            </p>
            <p className="text-[11.5px] text-slate-500">
              Powered by:{" "}
              <span className="text-slate-400">Trivon Funded</span>
            </p>
          </div>
        </div>
      </footer>
    </div>
  );
}

/* ----------------------------- small components ---------------------------- */

/** Sized by height; the width follows the artwork's aspect ratio. */
/**
 * Sized by height; the width follows the artwork's aspect ratio (550x137, so it
 * renders ~4x as wide as it is tall). Pass `className` instead of `height` where
 * the height needs to change per breakpoint.
 */
const Logo = ({
  height = 60,
  className,
}: {
  height?: number;
  className?: string;
}) => {
  const logo = useCampaignLogo();
  return (
    <img
      src={logo}
      alt="Trivon Funded"
      style={className ? undefined : { height }}
      className={`w-auto object-contain ${className ?? ""}`}
    />
  );
};

/**
 * Trading workspace photo that bobs gently, with two stat chips pinned to its
 * corners. The chips drift on a slower, offset loop so they read as sitting in
 * front of the card rather than glued to it. Mirrors the Prediction funnel's
 * hero visual, re-themed for the funded offer.
 */
// TODO: swap for Trivon artwork once it's ready — reuses the Prediction photo
// for now.
const WORKSPACE_IMAGE_URL =
  "https://res.cloudinary.com/dyp8gtllq/image/upload/v1784206757/hero_1_wkt3ij.png";

const TrivonFloatingWorkspace = () => {
  const bob = { duration: 6, repeat: Infinity, ease: "easeInOut" } as const;
  const [playing, setPlaying] = useState(false);

  return (
    <div className="relative mx-auto w-full max-w-[620px] px-6 py-10">
      <motion.div
        animate={{ y: [0, -14, 0] }}
        transition={bob}
        className="rounded-3xl bg-white dark:bg-[#0E1E35] p-2 shadow-[0_30px_60px_-15px_rgba(10,26,48,0.35)]"
      >
        <div className="relative overflow-hidden rounded-2xl">
          {playing && TRIVON_VIDEO_URL ? (
            <iframe
              src={TRIVON_VIDEO_URL}
              title="Trivon brokerage platform"
              className="block aspect-[492/328] w-full rounded-2xl"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowFullScreen
            />
          ) : (
            <>
              <img
                src={WORKSPACE_IMAGE_URL}
                alt="Trivon brokerage platform across trading screens"
                className="w-full h-auto rounded-2xl object-cover"
                loading="lazy"
              />
              {/* Play control sits dead-centre on the poster, per Figma. It only
                  becomes interactive once TRIVON_VIDEO_URL is filled in. */}
              <button
                type="button"
                onClick={() => setPlaying(true)}
                disabled={!TRIVON_VIDEO_URL}
                aria-label={
                  TRIVON_VIDEO_URL
                    ? "Play the Trivon brokerage platform video"
                    : "Video coming soon"
                }
                className="absolute left-1/2 top-1/2 flex h-10 w-10 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-xl bg-black/25 backdrop-blur-sm transition enabled:hover:bg-black/40 enabled:hover:scale-105 disabled:cursor-default"
              >
                <svg
                  width="31"
                  height="33"
                  viewBox="0 0 24 24"
                  className="ml-0.5 fill-white/80"
                  aria-hidden="true"
                >
                  <path d="M8 5v14l11-7z" />
                </svg>
              </button>
            </>
          )}
        </div>
      </motion.div>

      <motion.div
        animate={{ y: [0, -20, 0] }}
        transition={{ ...bob, delay: 0.4 }}
        className="absolute left-0 sm:-left-20 top-[7%] flex items-center gap-2 sm:gap-3 rounded-2xl bg-white dark:bg-[#0E1E35] px-3 py-2 sm:px-4 sm:py-3 shadow-[0_18px_35px_-10px_rgba(10,26,48,0.3)]"
      >
        <span className="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-[#0B1A2E] dark:text-[#009966]">
          <IconTrendUp />
        </span>
        <div>
          <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-[#90A1B9]">
            Auto Execution
          </div>
          <div
            style={HEAD_FONT}
            className="font-bold text-[17px] leading-tight text-slate-900 dark:text-[#90A1B9]"
          >
            100%
          </div>
        </div>
      </motion.div>

      <motion.div
        animate={{ y: [0, -20, 0] }}
        transition={{ ...bob, delay: 1.2 }}
        className="absolute right-0 sm:-right-6 bottom-[8%] flex items-center gap-2 sm:gap-3 rounded-2xl bg-[#0d2447] px-3 py-2 sm:px-4 sm:py-3 shadow-[0_18px_35px_-10px_rgba(10,26,48,0.45)]"
      >
        <span className="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-[#1583F0]/25 text-[#8FD5FA]">
          <IconZap />
        </span>
        <div>
          <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-[#90A1B9]">
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

const IconCheck = () => (
  <svg
    width="12"
    height="12"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="3"
    strokeLinecap="round"
    strokeLinejoin="round"
    aria-hidden="true"
  >
    <path d="M20 6L9 17l-5-5" />
  </svg>
);

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
