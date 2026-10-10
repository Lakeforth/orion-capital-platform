import { useEffect, useState } from "react";
import FunnelLangSwitch from "@/components/FunnelLangSwitch";
import { useApplyLangParam } from "@/lib/funnelLanguage";

/**
 * BrokerageProfitsLanding.tsx
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
 * Usage:  <BrokerageProfitsLanding onCtaClick={() => ...} />
 */

const HEAD_FONT = { fontFamily: "'Poppins', system-ui, sans-serif" } as const;
const BODY_FONT = { fontFamily: "'Open Sans', system-ui, sans-serif" } as const;

export interface BrokerageProfitsLandingProps {
  seatsFilled?: number;
  totalSeats?: number;
  showActivity?: boolean;
  /** Called by every "Get Started" / assessment CTA. Defaults to opening the quiz. */
  onCtaClick?: () => void;
  whatsappUrl?: string;
}

type Activity = {
  icon: string;
  chipBg: string;
  chipFg: string;
  title: string;
  sub: string;
  time: string;
};
type ValueItem = { name: string; value: string };
type Review = {
  quote: string;
  name: string;
  role: string;
  initials: string;
  avBg: string;
};
type Question = {
  key: string;
  title: string;
  options: { label: string; value: string }[];
};

const ACTIVITY: Activity[] = [
  {
    icon: "🚀",
    chipBg: "#ecfdf5",
    chipFg: "#059669",
    title: "New brokerage deployed",
    sub: "James Whitmore onboarded · Toronto, Canada",
    time: "3 min ago",
  },
  {
    icon: "⬆",
    chipBg: "#eff6ff",
    chipFg: "#0d98ba",
    title: "Brokerage upgrade completed",
    sub: "Meridian FX · Vienna, Austria",
    time: "11 min ago",
  },
  {
    icon: "🚀",
    chipBg: "#ecfdf5",
    chipFg: "#059669",
    title: "New brokerage deployed",
    sub: "Rachel Nguyen onboarded · Sydney, Australia",
    time: "24 min ago",
  },
  {
    icon: "⬆",
    chipBg: "#eff6ff",
    chipFg: "#0d98ba",
    title: "Brokerage upgrade completed",
    sub: "Crestline Capital Markets · Dubai, UAE",
    time: "38 min ago",
  },
  {
    icon: "🚀",
    chipBg: "#ecfdf5",
    chipFg: "#059669",
    title: "New brokerage deployed",
    sub: "Derek Callahan onboarded · Houston, USA",
    time: "52 min ago",
  },
];

const VALUE_ITEMS: ValueItem[] = [
  { name: "Brokerage Technology Infrastructure", value: "$85,000" },
  { name: "AI Optimisation Engine", value: "$45,000" },
  { name: "Liquidity Access & Integration", value: "$24,000" },
  { name: "Regulatory & Compliance Framework", value: "$20,000" },
  { name: "Dedicated Account Manager", value: "$12,000" },
  { name: "Setup & Deployment", value: "$18,000" },
];

const REVIEWS: Review[] = [
  {
    quote:
      "Honestly? My old setup was a joke. Slow execution, clients leaving left and right, and I knew exactly why, but had no clue how to fix it. These guys rebuilt the whole back end without me losing a single day of trading. Three months later, my retention numbers don't even look like the same business.",
    name: "Stefan Kovač",
    role: "Brokerage Owner · Vienna, Austria",
    initials: "SK",
    avBg: "#0d98ba",
  },
  {
    quote:
      "Came from real estate. Brokerages felt like a different planet to me. Turns out I just needed the right people.",
    name: "Diana Fernandez",
    role: "Real Estate Investor · Miami, USA",
    initials: "DF",
    avBg: "#059669",
  },
  {
    quote:
      "Two years of cobbling my own systems together. Two years of clients complaining about lag. One day with these guys fixed what I couldn't fix in two years. Make of that what you will.",
    name: "J. J. Park",
    role: "Brokerage Owner · Seoul, South Korea",
    initials: "JP",
    avBg: "#7c3aed",
  },
  {
    quote: "The most hands-off business I own, and I own a few.",
    name: "Thomas Reilly",
    role: "Commercial Real Estate Broker · Dublin, Ireland",
    initials: "TR",
    avBg: "#d97706",
  },
  {
    quote:
      "I spent two years trying to sort a brokerage out myself. Licensing, liquidity, the whole circus. These Brokerage Profits guys handed me the entire thing in 24 hours. Somehow ridiculous, if I'm honest, in the best way.",
    name: "Liam Fletcher",
    role: "Entrepreneur · Perth, Australia",
    initials: "LF",
    avBg: "#0891b2",
  },
  {
    quote:
      "I run a small import business out of Charleston, and I just wanted something that runs itself. Brokerage Profits built my brokerage to run itself with AI-optimised trading tech. It's so easy, and it's just more money coming in than my other business.",
    name: "Brandon Hale",
    role: "Import Business Owner · Charleston, USA",
    initials: "BH",
    avBg: "#dc2626",
  },
];

const QUIZ: Question[] = [
  {
    key: "own",
    title: "Do you currently own a brokerage?",
    options: [
      { label: "Yes, I already own one", value: "yes" },
      { label: "No, I want a brand-new build", value: "no" },
    ],
  },
  {
    key: "volume",
    title: "Expected monthly client volume?",
    options: [
      { label: "Under 50 clients", value: "s" },
      { label: "50 – 250 clients", value: "m" },
      { label: "250+ clients", value: "l" },
    ],
  },
  {
    key: "capital",
    title: "Capital ready to deploy?",
    options: [
      { label: "Under $10k", value: "a" },
      { label: "$10k – $50k", value: "b" },
      { label: "$50k or more", value: "c" },
    ],
  },
  {
    key: "goal",
    title: "Your main priority right now?",
    options: [
      { label: "Launch as fast as possible", value: "launch" },
      { label: "Higher margins on every trade", value: "margin" },
      { label: "Less manual, hands-off work", value: "auto" },
    ],
  },
];

const Stars = ({
  size = 24,
  gap = "gap-1",
  text = "text-[13px]",
}: {
  size?: number;
  gap?: string;
  text?: string;
}) => (
  <div className={`flex ${gap}`}>
    {Array.from({ length: 5 }).map((_, i) => (
      <span
        key={i}
        className={`inline-flex items-center justify-center rounded-sm bg-[#00b67a] text-white ${text}`}
        style={{ width: size, height: size }}
      >
        ★
      </span>
    ))}
  </div>
);

export default function BrokerageProfitsLanding({
  seatsFilled = 572,
  totalSeats = 600,
  showActivity = true,
  onCtaClick,
}: BrokerageProfitsLandingProps) {
  const [quizOpen, setQuizOpen] = useState(false);
  const [step, setStep] = useState(0);
  const [done, setDone] = useState(false);
  const [answers, setAnswers] = useState<Record<string, string>>({});
  const [seatPct, setSeatPct] = useState(0);

  // Honor an incoming `?lang=` deep link (e.g. a German ad link).
  useApplyLangParam();

  useEffect(() => {
    const t = setTimeout(
      () => setSeatPct(Math.min(100, (seatsFilled / totalSeats) * 100)),
      200,
    );
    return () => clearTimeout(t);
  }, [seatsFilled, totalSeats]);

  const openQuiz = () => {
    if (onCtaClick) return onCtaClick();
    setStep(0);
    setDone(false);
    setAnswers({});
    setQuizOpen(true);
  };
  const answer = (key: string, value: string) => {
    setAnswers((a) => ({ ...a, [key]: value }));
    if (step >= QUIZ.length - 1) setDone(true);
    else setStep((s) => s + 1);
  };
  const owns = answers.own === "yes";
  const progress = done ? 100 : Math.round((step / QUIZ.length) * 100) + 8;
  const q = QUIZ[step];

  const btnPrimary =
    "font-semibold inline-flex items-center gap-2 text-white bg-[#0d98ba] hover:bg-[#0b7f9c] rounded-lg transition";

  return (
    <div style={BODY_FONT} className="text-slate-600 bg-white">
      {/* NAV */}
      <header className="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-100">
        <div className="max-w-6xl mx-auto px-6 h-16 flex items-center gap-8">
          <div className="flex items-center gap-2.5 mr-auto">
            <Logo />
            <span
              style={HEAD_FONT}
              className="font-bold text-[17px] text-slate-900"
            >
              Brokerage Profits
            </span>
          </div>
          <nav className="hidden md:flex gap-7 text-[14px] text-slate-500">
            <a href="#benefits" className="hover:text-slate-900">
              Benefits
            </a>
            <a href="#how" className="hover:text-slate-900">
              Get started
            </a>
            <a href="#reviews" className="hover:text-slate-900">
              Testimonials
            </a>
          </nav>
          <FunnelLangSwitch />
          <button
            onClick={openQuiz}
            style={HEAD_FONT}
            className={`${btnPrimary} px-4 py-2 text-[14px]`}
          >
            Get Started
          </button>
        </div>
      </header>

      {/* HERO */}
      <section className="relative overflow-hidden bg-gradient-to-br from-[#0a1a30] via-[#0d2447] to-[#091626]">
        <div className="absolute inset-0 bg-[radial-gradient(700px_340px_at_82%_18%,rgba(45,212,191,0.28),transparent_62%)]" />
        <div className="absolute inset-0 bg-[radial-gradient(500px_260px_at_95%_60%,rgba(59,130,246,0.22),transparent_60%)]" />
        <div className="relative max-w-6xl mx-auto px-6 py-16 grid lg:grid-cols-2 gap-10 items-center">
          <div>
            <div className="inline-flex items-center gap-3 text-[12.5px] text-slate-200 border border-white/15 bg-white/5 rounded-full px-4 py-1.5 mb-6">
              <span className="flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-400" />
                No technical setup
              </span>
              <span className="w-px h-3 bg-white/20" />
              <span>Live and earning in 24 hours</span>
            </div>
            <h1
              style={HEAD_FONT}
              className="font-extrabold text-white text-[38px] sm:text-[46px] leading-[1.08] tracking-tight mb-5"
            >
              Own a Brokerage Engineered to{" "}
              <span className="underline decoration-cyan-400 decoration-[3px] underline-offset-[6px]">
                Outperform.
              </span>
              <br />
              Powered by AI. Built in{" "}
              <span className="underline decoration-cyan-400 decoration-[3px] underline-offset-[6px]">
                24 Hours.
              </span>
            </h1>
            <div className="flex flex-wrap gap-3">
              <button
                onClick={openQuiz}
                style={HEAD_FONT}
                className={`${btnPrimary} px-7 py-3.5 shadow-lg shadow-blue-900/40`}
              >
                Get Started <span>&raquo;</span>
              </button>
              <button
                onClick={openQuiz}
                style={HEAD_FONT}
                className="font-semibold inline-flex items-center gap-2 text-white bg-white/10 hover:bg-white/15 border border-white/20 px-6 py-3.5 rounded-lg transition"
              >
                Take the 60-sec assessment
              </button>
            </div>
          </div>
          {/* VSL */}
          <div className="relative">
            <div className="relative aspect-video rounded-2xl overflow-hidden border border-white/15 bg-[repeating-linear-gradient(135deg,#0f2036_0_14px,#0c1b2e_14px_28px)] shadow-2xl shadow-black/40">
              <div className="absolute inset-0 bg-[radial-gradient(360px_200px_at_50%_45%,rgba(59,130,246,0.25),transparent_70%)]" />
              <div className="absolute inset-0 flex flex-col items-center justify-center gap-3">
                <button
                  onClick={openQuiz}
                  className="w-[70px] h-[70px] rounded-full bg-[#0d98ba] hover:bg-[#0b7f9c] flex items-center justify-center shadow-lg shadow-blue-900/50 transition"
                >
                  <span className="w-0 h-0 border-l-[18px] border-l-white border-y-[12px] border-y-transparent ml-1.5" />
                </button>
                <span
                  style={HEAD_FONT}
                  className="font-semibold tracking-[0.28em] text-[11px] text-slate-300"
                >
                  VSL VIDEO
                </span>
              </div>
              <span className="absolute top-3 left-4 text-[10px] font-mono text-slate-400">
                [ drop sales video here ]
              </span>
            </div>
          </div>
        </div>
      </section>

      {/* FEATURES */}
      <section id="benefits" className="py-20 px-6 bg-white">
        <div className="max-w-6xl mx-auto grid md:grid-cols-4 gap-8">
          {[
            {
              title: "Live in 24 Hours",
              body: "No building from scratch. No hiring quant experts. We hand you a fully operational, AI-optimised brokerage — ready to generate revenue from day one.",
              icon: <IconClock />,
            },
            {
              title: "AI-Optimised Performance",
              body: "Our proprietary AI continuously fine-tunes your spread pricing, liquidity routing, and risk exposure, ensuring your brokerage remains consistently competitive without requiring manual intervention.",
              icon: <IconChip />,
            },
            {
              title: "Three Revenue Streams",
              body: "Your brokerage earns from spreads every time a client executes a position, commissions on every transaction processed, and client deposits sitting within your brokerage infrastructure.",
              icon: <IconBars />,
            },
            {
              title: "Direct Tier-One Liquidity",
              body: "No intermediaries, no markup layers eating into your spreads. Your brokerage connects directly to the same liquidity providers institutional trading desks rely on.",
              icon: <IconNodes />,
            },
          ].map((f) => (
            <div key={f.title}>
              <div className="flex items-center gap-3 mb-4">
                <div className="text-[#0d98ba] flex-shrink-0">{f.icon}</div>
                <h3
                  style={HEAD_FONT}
                  className="font-bold text-[16px] text-[#0d98ba]"
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

      {/* ASSESSMENT SPLIT */}
      <section className="py-16 px-6 ">
        <div className="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
          <div className="relative">
            <img
              src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1783352083/Brokerage_Profits_Phone_Laptop_Screen_Mockups_yevweg.png"
              alt="Brokerage Profits Dashboard Mockup"
              className="w-full h-auto drop-shadow-2xl"
            />
          </div>
          <div>
            <h2
              style={HEAD_FONT}
              className="font-extrabold text-slate-900 text-[30px] sm:text-[36px] leading-tight tracking-tight mb-4"
            >
              Discover How You Qualify For An AI-Optimised Brokerage.
            </h2>
            <p className="text-[15.5px] leading-relaxed text-slate-500 mb-7">
              Owning a truly profitable brokerage could be more possible than
              you think. Take this 60-second assessment about your brokerage
              business goals and current setup, and let's determine your precise
              need — new build or full upgrade.
            </p>
            <button
              onClick={openQuiz}
              style={HEAD_FONT}
              className={`${btnPrimary} px-7 py-3.5 shadow-lg shadow-cyan-600/25`}
            >
              Get Free Assessment <span>&raquo;</span>
            </button>
          </div>
        </div>
      </section>

      {/* 4 STEPS (NAVY) */}
      <section
        id="how"
        className="py-24 px-6 bg-gradient-to-b from-[#0b1b30] to-[#0a1728]"
      >
        <div className="max-w-7xl mx-auto">
          <h2
            style={HEAD_FONT}
            className="text-center font-extrabold text-white text-[30px] sm:text-[42px] tracking-tight mb-20"
          >
            Get started in 4 easy steps
          </h2>
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
                  title: "Choose Your Plan",
                  body: "Pick the package that matches your goals and budget, from a lean landing page to a full enterprise build. Not sure which fits? We'll help you decide.",
                  icon: <StepCheck />,
                },
                {
                  title: "We Get to Work",
                  body: "Once you're onboard, our team hits the ground running. You don't need to micromanage, we handle the heavy lifting while keeping you in the loop.",
                  icon: <StepDoc />,
                },
                {
                  title: "Watch It Develop",
                  body: "Track your project in real time at every stage. No guesswork, no radio silence, just full visibility into what's being built and when.",
                  icon: <StepGear />,
                },
                {
                  title: "Launch and Support",
                  body: "We go live when everything is tight. After launch, we stay on with 24/7 support to keep your site fast, secure, and working for your business around the clock.",
                  icon: <StepBolt />,
                },
              ].map((s) => (
                <div
                  key={s.title}
                  className="text-center flex flex-col items-center"
                >
                  <div className="w-24 h-24 rounded-full bg-white flex items-center justify-center text-[#0d98ba] mb-8 shadow-xl">
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
              <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.18)]" />
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
            {ACTIVITY.map((row, i) => (
              <div
                key={i}
                className="flex items-center gap-4 px-6 py-4 border-b border-slate-50 last:border-0"
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
                  {row.time}
                </span>
              </div>
            ))}
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
            Everything Inside Brokerage Profits, And What It's Actually Worth
          </h2>
          <div className="grid lg:grid-cols-[1.15fr_0.85fr] gap-7 items-start">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm px-7 py-3">
              {VALUE_ITEMS.map((v) => (
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
                  $204,000
                </span>
              </div>
            </div>
            <div className="lg:sticky lg:top-24 bg-white rounded-2xl border-2 border-[#0d98ba] shadow-xl shadow-cyan-100 p-8 text-center">
              <div className="text-[13.5px] text-slate-500 mb-1">
                Total real value{" "}
                <span className="line-through text-slate-400">$204,000</span>
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
                $1,500
              </div>
              <button
                onClick={openQuiz}
                style={HEAD_FONT}
                className={`w-full ${btnPrimary} justify-center py-3.5 shadow-lg shadow-cyan-600/25 mb-6`}
              >
                Get Started at $1,500
              </button>
              <div className="flex items-center justify-between mb-2">
                <span
                  style={HEAD_FONT}
                  className="font-semibold text-[12px] tracking-wider uppercase text-[#0d98ba]"
                >
                  Limited Availability
                </span>
                <span className="text-[13px] text-slate-500">
                  <b className="text-slate-900">{seatsFilled}</b> / {totalSeats}{" "}
                  filled
                </span>
              </div>
              <div className="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                <div
                  className="h-full rounded-full bg-gradient-to-r from-[#0d98ba] to-cyan-400 transition-[width] duration-1000"
                  style={{ width: `${seatPct}%` }}
                />
              </div>
              <p className="mt-3 text-[12.5px] text-slate-400">
                {seatsFilled} out of {totalSeats} seats have already been
                filled.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* WHATSAPP TESTIMONIALS */}
      <section className="py-20 px-6 bg-white">
        <div className="max-w-4xl mx-auto">
          <h2
            style={HEAD_FONT}
            className="text-center font-extrabold text-slate-900 text-[26px] sm:text-[34px] tracking-tight mb-2"
          >
            Straight From Their Phones
          </h2>
          <p className="text-center text-slate-500 text-[15px] mb-12">
            Unedited messages from new brokerage owners.
          </p>
          <div className="grid md:grid-cols-3 gap-6 max-w-3xl mx-auto">
            {[
              "https://res.cloudinary.com/dyp8gtllq/image/upload/v1783373864/1_razbz9.jpg",
              "https://res.cloudinary.com/dyp8gtllq/image/upload/v1783373892/2_i1lqmn.jpg",
              "https://res.cloudinary.com/dyp8gtllq/image/upload/v1783373906/3_euwp59.jpg",
            ].map((url, i) => (
              <div
                key={i}
                className="aspect-[9/19] bg-slate-100 rounded-[2rem] border-[6px] border-slate-900 shadow-xl overflow-hidden relative"
              >
                <img
                  src={url}
                  alt={`Phone Screen Chat ${i + 1}`}
                  className="w-full h-full object-cover"
                />
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* TRUSTPILOT */}
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
            <div className="inline-flex items-center gap-2.5 text-[14px] text-slate-600">
              <Stars />
              <span>
                Rated <b className="text-slate-900">Excellent</b> on Trustpilot
                · <span className="text-slate-500">based on 128 reviews</span>
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
                  <Stars size={16} gap="gap-0.5" text="text-[9px]" />
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

      {/* STATS */}
      <section className="py-8 px-6 bg-white">
        <div className="max-w-6xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
          {[
            { n: "5,000+", l: "Custom enterprise-level websites delivered" },
            { n: "200+", l: "Technologists & industry specialists" },
            { n: "40%", l: "Traffic boost for our designed websites" },
            { n: "98%", l: "Client satisfaction rate" },
          ].map((s) => (
            <div key={s.l}>
              <div
                style={HEAD_FONT}
                className="font-extrabold text-[32px] text-slate-900 leading-none"
              >
                {s.n}
              </div>
              <div className="mt-1 text-[13px] text-slate-500 max-w-[180px] mx-auto leading-tight">
                {s.l}
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* GUARANTEE */}
      <section className="pb-20 px-6 bg-white">
        <div className="max-w-4xl mx-auto flex justify-center">
          <img
            src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1781701659/cc98aea1-0147-40c0-ac4b-40f5603a5df2_ihdjh4.png"
            alt="180-Day Money Back Guarantee"
            className="w-full h-auto object-contain"
          />
        </div>
      </section>

      {/* FINAL CTA (NAVY) */}
      <section className="px-6 pb-20 bg-white">
        <div className="max-w-6xl mx-auto relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0b1b30] to-[#0d2447] px-10 py-14">
          <div className="absolute inset-0 bg-[radial-gradient(500px_240px_at_88%_30%,rgba(45,212,191,0.22),transparent_62%)]" />
          <div className="relative flex flex-wrap items-center gap-6 justify-between">
            <h2
              style={HEAD_FONT}
              className="font-extrabold text-white text-[28px] sm:text-[36px] leading-tight tracking-tight max-w-xl"
            >
              Your brokerage could be live and earning by this time tomorrow.
            </h2>
            <button
              onClick={openQuiz}
              style={HEAD_FONT}
              className={`${btnPrimary} px-8 py-4 shadow-lg shadow-blue-900/40 whitespace-nowrap`}
            >
              Get Started <span>&raquo;</span>
            </button>
          </div>
        </div>
      </section>

      {/* FOOTER */}
      <footer className="bg-[#0a1728] px-6 py-12">
        <div className="max-w-6xl mx-auto grid md:grid-cols-[1.6fr_1fr_1fr] gap-8">
          <div>
            <div className="flex items-center gap-2.5 mb-4">
              <Logo dark />
              <span
                style={HEAD_FONT}
                className="font-bold text-[16px] text-white"
              >
                Brokerage Profits
              </span>
            </div>
            <p className="text-[13px] leading-relaxed text-slate-400 max-w-sm">
              We hand ambitious operators a fully AI-optimised brokerage —
              deployed, compliant, and earning within 24 hours.
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
            <button
              onClick={openQuiz}
              style={HEAD_FONT}
              className={`${btnPrimary} px-4 py-2 text-[13px]`}
            >
              Free Assessment
            </button>
          </div>
        </div>
        <div className="max-w-6xl mx-auto mt-10 pt-6 border-t border-white/10">
          <p className="text-[11px] leading-relaxed text-slate-500 mb-2">
            Trading involves risk. Figures shown are illustrative and not a
            guarantee of future results. Regulatory licensing timelines vary by
            jurisdiction. This page is a demonstration mockup.
          </p>
          <p className="text-[11.5px] text-slate-500">
            © 2026 Brokerage Profits. All rights reserved.
          </p>
        </div>
      </footer>

      {/* QUIZ MODAL */}
      {quizOpen && (
        <div
          className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-6"
          onClick={() => setQuizOpen(false)}
        >
          <div
            onClick={(e) => e.stopPropagation()}
            className="w-full max-w-lg bg-white rounded-2xl shadow-2xl p-8"
          >
            <div className="flex items-center gap-3 mb-6">
              <div className="flex items-center gap-2 mr-auto">
                <Logo />
                <span
                  style={HEAD_FONT}
                  className="font-bold text-[14px] text-slate-900"
                >
                  Free Assessment
                </span>
              </div>
              <button
                onClick={() => setQuizOpen(false)}
                className="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 transition"
              >
                ✕
              </button>
            </div>
            <div className="h-1.5 rounded-full bg-slate-100 overflow-hidden mb-6">
              <div
                className="h-full bg-gradient-to-r from-[#0d98ba] to-cyan-400 transition-all duration-300"
                style={{ width: `${progress}%` }}
              />
            </div>
            {!done ? (
              <div>
                <div className="text-[12.5px] text-slate-400 mb-2">
                  Question {step + 1} of {QUIZ.length}
                </div>
                <h3
                  style={HEAD_FONT}
                  className="font-bold text-[22px] text-slate-900 tracking-tight mb-6"
                >
                  {q.title}
                </h3>
                <div className="flex flex-col gap-2.5">
                  {q.options.map((o) => (
                    <button
                      key={o.value}
                      onClick={() => answer(q.key, o.value)}
                      className="text-left font-semibold text-[15px] text-slate-700 bg-slate-50 hover:bg-[#e6f6fa] border border-slate-200 hover:border-[#0d98ba] px-4 py-3.5 rounded-xl transition"
                    >
                      {o.label}
                    </button>
                  ))}
                </div>
                {step > 0 && (
                  <button
                    onClick={() => setStep((s) => Math.max(0, s - 1))}
                    className="mt-4 text-[13.5px] text-slate-400 hover:text-slate-600"
                  >
                    ← Back
                  </button>
                )}
              </div>
            ) : (
              <div className="text-center">
                <div className="w-14 h-14 mx-auto mb-4 rounded-full bg-emerald-50 border border-emerald-300 flex items-center justify-center text-emerald-600 text-[26px]">
                  ✓
                </div>
                <div
                  style={HEAD_FONT}
                  className="font-semibold text-[12.5px] tracking-widest uppercase text-emerald-600 mb-2.5"
                >
                  You Qualify
                </div>
                <h3
                  style={HEAD_FONT}
                  className="font-extrabold text-[26px] text-slate-900 tracking-tight mb-3"
                >
                  {owns ? "Full Upgrade Recommended" : "New Build Recommended"}
                </h3>
                <p className="text-[15px] leading-relaxed text-slate-500 max-w-sm mx-auto mb-6">
                  {owns
                    ? "Based on your answers, your existing brokerage is a strong fit for a full AI-optimised upgrade — we migrate your setup and switch on AI pricing without downtime."
                    : "Based on your answers, a fresh AI-optimised build is your fastest path to a live, revenue-generating brokerage — deployed within 24 hours."}
                </p>
                <button
                  onClick={() => setQuizOpen(false)}
                  style={HEAD_FONT}
                  className={`w-full ${btnPrimary} justify-center py-3.5 rounded-xl shadow-lg shadow-cyan-600/25 mb-3`}
                >
                  Claim My Brokerage — $1,500
                </button>
                <button
                  onClick={() => {
                    setStep(0);
                    setDone(false);
                    setAnswers({});
                  }}
                  className="text-[13.5px] text-slate-400 hover:text-slate-600"
                >
                  Retake assessment
                </button>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

/* ----------------------------- small components ---------------------------- */

const Logo = ({ dark = false }: { dark?: boolean }) => (
  <svg
    width={dark ? 24 : 26}
    height={dark ? 24 : 26}
    viewBox="0 0 24 24"
    fill="none"
  >
    <path
      d="M12 3a9 9 0 1 0 9 9"
      stroke={dark ? "#0d98ba" : "#0d98ba"}
      strokeWidth="2.4"
      strokeLinecap="round"
    />
    <path
      d="M12 8a4 4 0 1 0 4 4"
      stroke={dark ? "#22d3ee" : "#06b6d4"}
      strokeWidth="2.4"
      strokeLinecap="round"
    />
  </svg>
);

const IconClock = () => (
  <svg
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.9"
  >
    <circle cx="12" cy="12" r="9" />
    <path d="M12 7v5l3.5 2" />
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
const IconNodes = () => (
  <svg
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.9"
  >
    <circle cx="6" cy="12" r="2.5" />
    <circle cx="18" cy="6" r="2.5" />
    <circle cx="18" cy="18" r="2.5" />
    <path d="M8.2 11l7.6-4M8.2 13l7.6 4" />
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
