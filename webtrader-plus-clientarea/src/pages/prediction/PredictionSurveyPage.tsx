import { useState, useEffect, useMemo, useRef } from "react";
import { useParams } from "react-router-dom";
import ReactCountryFlag from "react-country-flag";
import { Target, UserCheck, Zap, type LucideIcon } from "lucide-react";
import { PANEL_IMAGE_URL, useCampaignLogo } from "./predictionBrand";
import { isValidPhoneNumber } from "libphonenumber-js";
import countriesData from "@/config/countries.json";
import FunnelLangSwitch from "@/components/FunnelLangSwitch";
import usePredictionFunnelStore from "@/store/predictionFunnelStore";
import {
  applyCampaignFavicon,
  applyCampaignTrackingScript,
  detectIpAndGeo,
  fetchCampaign,
  getBrowserType,
  getDeviceType,
  postCapture,
  postSurvey,
  readWhatsApp,
} from "./predictionApi";
import { useFunnelLanguage, withLang } from "./predictionLanguage";
import { currencySymbolFor } from "./predictionPricing";

/**
 * PredictionSurveyPage.tsx
 * -----------------------------------------------------------------------------
 * Entry step of the Prediction funnel. Mirrors the Quantum survey flow:
 *   1. Lead capture — first/last name, email, phone (with country dial dropdown)
 *      → POST /c/:uuid/capture
 *   2. The exact same personalisation questions used by the Quantum survey
 *      (src/pages/CampaignSurveyPage.tsx) → POST /c/:uuid/survey
 * then hands off to the /prediction landing page carrying ?uuid=.
 *
 * Reached at /prediction-survey/:uuid. Without a uuid (bare /prediction-survey)
 * every backend call is skipped so the page still renders as a design preview.
 *
 * Like Quantum, every request swallows its error and the flow advances anyway.
 */

const HEAD_FONT = { fontFamily: "'Poppins', system-ui, sans-serif" } as const;
const BODY_FONT = { fontFamily: "'Open Sans', system-ui, sans-serif" } as const;

/** Reassurance cards shown on the desktop-only promo panel. */
const PANEL_CARDS: { icon: LucideIcon; title: string; body: string }[] = [
  {
    icon: Target,
    title: "94% Prediction Accuracy",
    body: "AI reads global events, markets and patterns in real time.",
  },
  {
    icon: Zap,
    title: "True Hands-Free Earning",
    body: "No charts to study. The system runs in the background.",
  },
  {
    icon: UserCheck,
    title: "Dedicated Account Manager",
    body: "A real person guides your setup, start to finish.",
  },
];

type Country = { name: string; dial_code: string; code: string };

type Question = {
  id: string;
  text: string;
  type?: "select";
  options: string[];
};

const QUESTIONS: Question[] = [
  {
    id: "country_of_residence",
    text: "What is your current country of Residence?",
    type: "select",
    options: (countriesData as Country[]).map((c) => c.name),
  },
  {
    id: "language",
    text: "Do you speak German?",
    options: ["Yes", "No"],
  },
  {
    id: "earn_intent",
    text: "How much do you want to earn from this annually?",
    options: [
      "$1,000 - $10,000",
      "$10,000 - $50,000",
      "$50,000 - $100,000",
      "$100,000 and above",
    ],
  },
  {
    id: "income_source",
    text: "Is this going to be a primary or secondary source of income?",
    options: ["Primary", "Secondary"],
  },
  {
    id: "income",
    text: "What is your Annual / Yearly Income?",
    options: [
      "Less than $10,000",
      "$10,000 - $50,000",
      "$50,000 - $100,000",
      "$100,000 - Above",
    ],
  },
  {
    id: "age_bracket",
    text: "Choose your age bracket",
    options: ["18-24", "25-34", "35-64", "65 and above"],
  },
  {
    id: "occupation",
    text: "What is your occupation?",
    type: "select",
    options: [
      "Office & Administrative Support",
      "Sales & Related Occupation",
      "Food Preparation & Serving",
      "Transportation & Material Moving",
      "Production/Manufacturing services",
      "Cleaning & Maintenance services",
      "Security & Protective Services",
      "Healthcare Support & Nursing Services",
      "Customer Service Representative",
      "Education & Teaching Professional",
      "Others",
    ],
  },
  {
    id: "occupation_level",
    text: "What is your occupation level?",
    type: "select",
    options: ["Entry", "Mid", "Senior"],
  },
  {
    id: "experience",
    text: "What is your investment experience level?",
    options: ["Beginner", "Intermediate", "Experienced"],
  },
  {
    id: "goal",
    text: "What is your primary investment goal?",
    options: ["Short-term gains", "Long-term wealth", "Passive income"],
  },
  {
    id: "invest_intent",
    text: "Choose the amount you intend to invest",
    options: [
      "$1,000 - $10,000",
      "$10,000 - $50,000",
      "$50,000 - $100,000",
      "$100,000 and above",
    ],
  },
];

/**
 * The money brackets above are authored in dollars. German leads see the same
 * figures quoted in euros — only the symbol changes, the amounts do not.
 */
const withCurrency = (questions: Question[], symbol: string): Question[] => {
  if (symbol === "$") return questions;
  return questions.map((q) =>
    q.options.some((o) => o.includes("$"))
      ? { ...q, options: q.options.map((o) => o.split("$").join(symbol)) }
      : q,
  );
};

export default function PredictionSurveyPage() {
  const { uuid } = useParams<{ uuid?: string }>();
  const lang = useFunnelLanguage();
  const patchFunnelData = usePredictionFunnelStore((s) => s.patchFunnelData);

  const [phase, setPhase] = useState<"capture" | "questions">("capture");
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Lead capture
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [error, setError] = useState("");

  // Geo / environment, filled on mount
  const [detectedIP, setDetectedIP] = useState("");
  const [detectedCountry, setDetectedCountry] = useState("");

  const [dialOpen, setDialOpen] = useState(false);
  const [dialSearch, setDialSearch] = useState("");
  const [selectedDial, setSelectedDial] = useState<Country>({
    name: "United States",
    dial_code: "+1",
    code: "US",
  });
  const [filteredCountries, setFilteredCountries] = useState<Country[]>(
    countriesData as Country[],
  );
  const dropRef = useRef<HTMLDivElement>(null);

  // Questions
  const [qIndex, setQIndex] = useState(0);
  const [answers, setAnswers] = useState<Record<string, string>>({});

  useEffect(() => {
    const q = dialSearch.toLowerCase();
    setFilteredCountries(
      q
        ? (countriesData as Country[]).filter(
            (c) =>
              c.name.toLowerCase().includes(q) ||
              c.dial_code.includes(q) ||
              c.code.toLowerCase().includes(q),
          )
        : (countriesData as Country[]),
    );
  }, [dialSearch]);

  useEffect(() => {
    function handleClick(e: MouseEvent) {
      if (dropRef.current && !dropRef.current.contains(e.target as Node)) {
        setDialOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, []);

  // Campaign lookup — sets the tab title, like the Quantum survey.
  useEffect(() => {
    if (!uuid) return;
    fetchCampaign(uuid).then((c) => {
      const name = readWhatsApp(c).campaignName;
      if (name) document.title = name;
      applyCampaignFavicon(c);
      applyCampaignTrackingScript(c);
      // Capture the campaign's own logo for every downstream funnel page.
      if (c?.logo) patchFunnelData({ logo: c.logo });
    });
  }, [uuid, patchFunnelData]);

  // IP + country detection; preselects the dial code.
  useEffect(() => {
    detectIpAndGeo().then((geo) => {
      setDetectedIP(geo.ip);
      setDetectedCountry(geo.countryName);
      const found = (countriesData as Country[]).find(
        (c) => c.code === geo.countryCode,
      );
      if (found) setSelectedDial(found);
    });
  }, []);

  const isPhoneValid =
    phone.length >= 4 &&
    isValidPhoneNumber(
      selectedDial.dial_code + phone,
      selectedDial.code as never,
    );

  // Same questions, currency symbol driven by ?lang= (German quotes euros).
  const questions = useMemo(
    () => withCurrency(QUESTIONS, currencySymbolFor(lang)),
    [lang],
  );

  const q = questions[qIndex];
  const progress =
    phase === "capture"
      ? 8
      : Math.round(8 + ((qIndex + 1) / questions.length) * 92);

  const goToQuestions = async () => {
    setError("");
    const nameRe = /^[A-Za-zÀ-ɏ'\-]{2,}(\s[A-Za-zÀ-ɏ'\-]{1,})*$/;
    if (!firstName || !nameRe.test(firstName))
      return setError("Please enter a valid first name (at least 2 letters).");
    if (!lastName || !nameRe.test(lastName))
      return setError("Please enter a valid last name (at least 2 letters).");
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
      return setError("Please enter a valid email address.");
    if (!isPhoneValid) return setError("Please enter a valid phone number.");

    if (uuid) {
      setIsSubmitting(true);
      const captured = await postCapture(uuid, {
        first_name: firstName,
        last_name: lastName,
        email,
        phone: selectedDial.dial_code + phone,
        country: selectedDial.name || detectedCountry,
        ip_address: detectedIP,
        device_type: getDeviceType(),
        browser_type: getBrowserType(),
        language: "EN",
      });
      // Store email/name unconditionally -- they come from the form we just
      // validated above, not from the capture response. The capture endpoint's
      // response shape has changed before and will likely change again; never
      // gate lead-critical state (especially email, which the hot-lead status
      // pings on the next pages require) on parsing it successfully.
      const wa = readWhatsApp(captured);
      patchFunnelData({
        whatsapp_number: wa.number,
        whatsapp_message: wa.message,
        campaign_name: wa.campaignName,
        email: email || null,
        full_name: `${firstName} ${lastName}`.trim() || null,
        // Best-effort: for auto-login campaigns the backend returns an
        // `/auto-login?token=...` URL here that checkout forwards to.
        redirect_url: captured?.redirect_url ?? null,
        ...(captured?.logo ? { logo: captured.logo } : {}),
      });
      setIsSubmitting(false);
    }

    // Advance regardless of whether the capture succeeded.
    setPhase("questions");
  };

  const answerQuestion = async (id: string, value: string) => {
    const nextAnswers = { ...answers, [id]: value };
    setAnswers(nextAnswers);

    if (qIndex < questions.length - 1) {
      setQIndex((i) => i + 1);
      return;
    }

    // Last question: submit the survey, then hand off to the landing page.
    setIsSubmitting(true);
    try {
      if (uuid) await postSurvey(uuid, { email, answers: nextAnswers });
    } finally {
      setIsSubmitting(false);
      window.location.href = withLang(
        uuid ? `/prediction?uuid=${uuid}` : "/prediction",
        lang,
      );
    }
  };

  const back = () => {
    if (qIndex === 0) setPhase("capture");
    else setQIndex((i) => i - 1);
  };

  const inputCls =
    "w-full border border-slate-200 rounded-xl px-4 py-3 text-[15px] text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#4f46e5]/30 focus:border-[#4f46e5] bg-white";
  const btnPrimary =
    "font-semibold inline-flex items-center justify-center gap-2 text-white bg-gradient-to-r from-[#4f46e5] to-[#818cf8] hover:from-[#4338ca] hover:to-[#4f46e5] shadow-lg shadow-indigo-500/40 hover:shadow-indigo-500/55 rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none";

  return (
    <div
      style={BODY_FONT}
      className="min-h-screen w-full flex flex-col lg:flex-row bg-gradient-to-br from-[#0a1a30] via-[#0d2447] to-[#091626] text-slate-100"
    >
      {/* Mobile-only backdrop. On desktop the promo panel carries the image
          instead, and this would bleed behind the white form column. */}
      <div
        className="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat pointer-events-none lg:hidden"
        style={{ backgroundImage: `url('${PANEL_IMAGE_URL}')` }}
      >
        <div className="absolute inset-0 bg-gradient-to-br from-[#0a1a30]/92 via-[#0d2447]/85 to-[#091626]/92" />
      </div>

      <div className="absolute inset-0 bg-[radial-gradient(700px_340px_at_82%_18%,rgba(129,140,248,0.22),transparent_62%)] pointer-events-none lg:hidden" />

      {/* FORM COLUMN */}
      <main className="relative z-10 w-full lg:w-1/2 flex items-center justify-center px-6 py-10 lg:bg-gradient-to-b lg:from-white lg:via-white lg:to-[#eef2ff]">
        <div className="w-full max-w-md bg-white text-slate-700 rounded-2xl shadow-2xl shadow-black/40 overflow-visible lg:rounded-none lg:shadow-none lg:bg-transparent">
          <div className="p-8 lg:px-0">
            <div className="mb-6 flex items-center justify-between">
              <Logo />
              <FunnelLangSwitch />
            </div>

            {/* Progress */}
            <div className="h-1.5 bg-slate-100 overflow-hidden rounded-full mb-7">
              <div
                className="h-full rounded-full bg-gradient-to-r from-[#4f46e5] via-indigo-400 to-violet-400 shadow-[0_0_12px_rgba(129,140,248,0.6)] transition-all duration-500"
                style={{ width: `${Math.min(progress, 100)}%` }}
              />
            </div>

            {phase === "capture" ? (
              <div>
                <h1
                  style={HEAD_FONT}
                  className="font-extrabold text-[24px] text-slate-900 tracking-tight mb-1"
                >
                  Get Started
                </h1>
                <p className="text-slate-500 text-[14px] mb-6">
                  Enter your details to continue
                </p>

                {error && (
                  <p className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-[13px]">
                    {error}
                  </p>
                )}

                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-3">
                    <input
                      type="text"
                      placeholder="First Name"
                      value={firstName}
                      onChange={(e) => setFirstName(e.target.value)}
                      className={inputCls}
                    />
                    <input
                      type="text"
                      placeholder="Last Name"
                      value={lastName}
                      onChange={(e) => setLastName(e.target.value)}
                      className={inputCls}
                    />
                  </div>

                  <input
                    type="email"
                    placeholder="Email Address"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className={inputCls}
                  />

                  {/* Phone with country dial dropdown */}
                  <div className="flex border border-slate-200 rounded-xl overflow-visible focus-within:ring-2 focus-within:ring-[#4f46e5]/30">
                    <div className="relative shrink-0" ref={dropRef}>
                      <button
                        type="button"
                        onClick={() => setDialOpen((v) => !v)}
                        className="bg-slate-50 px-3 h-full flex items-center gap-1.5 border-r border-slate-200 rounded-l-xl focus:outline-none"
                      >
                        <ReactCountryFlag
                          countryCode={selectedDial.code}
                          svg
                          style={{ width: "1.25em", height: "1.25em" }}
                        />
                        <span className="text-[13px] font-semibold tracking-wide text-slate-700">
                          {selectedDial.code}
                        </span>
                        <span className="text-[14px] font-medium text-slate-800">
                          {selectedDial.dial_code}
                        </span>
                        <svg
                          className="w-3 h-3 text-slate-400 ml-0.5"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M19 9l-7 7-7-7"
                          />
                        </svg>
                      </button>

                      {dialOpen && (
                        <div className="absolute z-50 left-0 top-full mt-1 w-72 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden">
                          <div className="p-2 border-b border-slate-100">
                            <input
                              type="text"
                              placeholder="Search country..."
                              value={dialSearch}
                              onChange={(e) => setDialSearch(e.target.value)}
                              className="w-full px-3 py-2 text-[12px] text-slate-900 placeholder-slate-400 bg-slate-50 rounded-lg border border-slate-100 focus:outline-none"
                              autoFocus
                            />
                          </div>
                          <div className="overflow-y-auto max-h-56">
                            {filteredCountries.map((c) => (
                              <button
                                key={c.code}
                                type="button"
                                onClick={() => {
                                  setSelectedDial(c);
                                  setDialOpen(false);
                                  setDialSearch("");
                                }}
                                className="w-full text-left px-4 py-2 hover:bg-[#eef2ff] transition-colors border-b border-slate-50 last:border-0"
                              >
                                <span className="text-[12px] text-slate-400 block leading-tight">
                                  {c.name}
                                </span>
                                <span className="text-[14px] font-medium leading-tight flex items-center gap-1.5 text-slate-800">
                                  <ReactCountryFlag
                                    countryCode={c.code}
                                    svg
                                    style={{ width: "1.1em", height: "1.1em" }}
                                  />
                                  <span className="text-[12px] font-semibold tracking-wide text-slate-600">
                                    {c.code}
                                  </span>
                                  {c.dial_code}
                                </span>
                              </button>
                            ))}
                          </div>
                        </div>
                      )}
                    </div>

                    <div className="relative flex-1">
                      <input
                        type="tel"
                        placeholder="Phone number"
                        value={phone}
                        onChange={(e) => {
                          const val = e.target.value.replace(/\D/g, "");
                          if (val.length <= 15) setPhone(val);
                        }}
                        className="w-full border-0 px-4 py-3 pr-10 text-[15px] text-slate-900 placeholder-slate-400 bg-white focus:outline-none focus:ring-0 rounded-r-xl"
                      />
                      {phone.length > 0 && (
                        <span
                          className={`absolute right-3 top-1/2 -translate-y-1/2 ${
                            isPhoneValid ? "text-emerald-500" : "text-red-400"
                          }`}
                        >
                          {isPhoneValid ? (
                            <svg
                              className="w-4 h-4"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke="currentColor"
                              strokeWidth={2.5}
                            >
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M5 13l4 4L19 7"
                              />
                            </svg>
                          ) : (
                            <svg
                              className="w-4 h-4"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke="currentColor"
                              strokeWidth={2.5}
                            >
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                              />
                            </svg>
                          )}
                        </span>
                      )}
                    </div>
                  </div>
                </div>

                <button
                  onClick={goToQuestions}
                  disabled={
                    isSubmitting ||
                    !firstName ||
                    !lastName ||
                    !email ||
                    !isPhoneValid
                  }
                  style={HEAD_FONT}
                  className={`mt-6 w-full ${btnPrimary} py-4 text-[15px] shadow-lg shadow-indigo-600/25`}
                >
                  {isSubmitting ? (
                    "Processing..."
                  ) : (
                    <>
                      Next <span>&raquo;</span>
                    </>
                  )}
                </button>
              </div>
            ) : (
              <div>
                <h1
                  style={HEAD_FONT}
                  className="font-extrabold text-[24px] text-slate-900 tracking-tight mb-1"
                >
                  Quick Questions
                </h1>
                <p className="text-slate-500 text-[14px] mb-6">
                  Help us personalise your experience
                </p>

                <p className="text-[13px] font-semibold text-slate-800 mb-2">
                  <span className="text-[#4f46e5]">Question {qIndex + 1}</span>{" "}
                  of {questions.length}
                </p>
                <p className="text-[18px] font-medium text-slate-900 mb-4">
                  {q.text}
                </p>

                {q.type === "select" ? (
                  <SelectStep
                    question={q}
                    value={answers[q.id] ?? ""}
                    onChange={(v) => setAnswers((a) => ({ ...a, [q.id]: v }))}
                    onContinue={() => answerQuestion(q.id, answers[q.id] ?? "")}
                    btnPrimary={btnPrimary}
                    isLast={qIndex === questions.length - 1}
                    isSubmitting={isSubmitting}
                  />
                ) : (
                  <div className="flex flex-col gap-2.5">
                    {q.options.map((o) => (
                      <button
                        key={o}
                        onClick={() => answerQuestion(q.id, o)}
                        disabled={isSubmitting}
                        className={`flex items-center gap-3 text-left font-semibold text-[15px] px-4 py-3.5 rounded-xl border transition disabled:opacity-60 ${
                          answers[q.id] === o
                            ? "bg-gradient-to-r from-[#4f46e5] to-indigo-400 border-transparent text-white shadow-lg shadow-indigo-500/30"
                            : "bg-white hover:bg-[#eef2ff] border-slate-200 hover:border-[#4f46e5] text-slate-700 hover:shadow-md hover:shadow-indigo-500/10"
                        }`}
                      >
                        {o}
                      </button>
                    ))}
                  </div>
                )}

                <button
                  onClick={back}
                  className="mt-5 text-[13.5px] text-slate-400 hover:text-slate-600"
                >
                  ← Back
                </button>
              </div>
            )}
          </div>
        </div>
      </main>

      {/* PROMO PANEL — desktop only. Sticky so it holds still while the form
          advances through the questions. */}
      <aside className="hidden lg:flex lg:w-1/2 relative overflow-hidden lg:sticky lg:top-0 lg:h-screen">
        <div
          className="absolute inset-0 bg-cover bg-center bg-no-repeat"
          style={{ backgroundImage: `url('${PANEL_IMAGE_URL}')` }}
        />
        {/* Purple-tinted wash rather than a flat black scrim — it lets the
            artwork through and carries the brand colour. */}
        <div className="absolute inset-0 bg-gradient-to-br from-[#0a1a30]/75 via-[#4f46e5]/40 to-[#091626]/80" />
        <div className="absolute inset-0 bg-[radial-gradient(600px_400px_at_25%_20%,rgba(129,140,248,0.35),transparent_65%)]" />
        <div className="absolute inset-0 bg-[radial-gradient(500px_320px_at_85%_85%,rgba(167,139,250,0.22),transparent_60%)]" />

        <div className="relative z-10 flex flex-col justify-center w-full max-w-md mx-auto px-12">
          <h2
            style={HEAD_FONT}
            className="font-extrabold text-white text-[34px] leading-tight tracking-tight mb-3 drop-shadow"
          >
            Predict Smarter.{" "}
            <span className="bg-gradient-to-r from-indigo-300 to-violet-300 bg-clip-text text-transparent">
              Earn Passively.
            </span>
          </h2>
          <p className="text-indigo-100/90 text-[15px] mb-10">
            Award-winning AI prediction system.
          </p>

          <div className="flex flex-col gap-4">
            {PANEL_CARDS.map((c) => (
              <PanelCard key={c.title} {...c} />
            ))}
          </div>
        </div>
      </aside>
    </div>
  );
}

/* ------------------------------- promo card -------------------------------- */

const PanelCard = ({
  icon: Icon,
  title,
  body,
}: {
  icon: LucideIcon;
  title: string;
  body: string;
}) => (
  <div className="flex items-start gap-4 rounded-2xl bg-white/95 px-5 py-4 shadow-xl shadow-indigo-950/20 ring-1 ring-white/40 backdrop-blur transition hover:bg-white">
    <span className="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-gradient-to-br from-[#4f46e5] to-indigo-400 text-white shadow-md shadow-indigo-500/30">
      <Icon size={20} strokeWidth={2.2} />
    </span>
    <div className="min-w-0">
      <div
        style={HEAD_FONT}
        className="font-bold text-[15px] text-slate-900 mb-0.5"
      >
        {title}
      </div>
      <p className="text-[13px] leading-relaxed text-slate-600">{body}</p>
    </div>
  </div>
);

/* --------------------------- select-type question -------------------------- */

const SelectStep = ({
  question,
  value,
  onChange,
  onContinue,
  btnPrimary,
  isLast,
  isSubmitting,
}: {
  question: Question;
  value: string;
  onChange: (v: string) => void;
  onContinue: () => void;
  btnPrimary: string;
  isLast: boolean;
  isSubmitting: boolean;
}) => (
  <div>
    <select
      value={value}
      onChange={(e) => onChange(e.target.value)}
      className="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-[#4f46e5] focus:ring-2 focus:ring-[#4f46e5]/20 outline-none text-[15px] text-slate-900 bg-white"
    >
      <option value="" disabled>
        Select an option…
      </option>
      {question.options.map((o) => (
        <option key={o} value={o}>
          {o}
        </option>
      ))}
    </select>
    <button
      onClick={onContinue}
      disabled={!value || isSubmitting}
      style={HEAD_FONT}
      className={`w-full ${btnPrimary} py-3.5 mt-4 shadow-lg shadow-indigo-600/25`}
    >
      {isSubmitting ? (
        "Processing..."
      ) : (
        <>
          {isLast ? "See My Picks" : "Continue"} <span>&raquo;</span>
        </>
      )}
    </button>
  </div>
);

const Logo = () => {
  const logo = useCampaignLogo();
  return (
    <img
      src={logo}
      alt="Prediction Profits AI"
      className="h-[56px] w-auto object-contain"
    />
  );
};
