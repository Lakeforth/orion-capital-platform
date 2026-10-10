import { useState, useEffect, useRef, type ComponentType } from "react";
import { useParams } from "react-router-dom";
import ReactCountryFlag from "react-country-flag";
import { isValidPhoneNumber } from "libphonenumber-js";
import Logo from "@/components/Logo";
import countriesData from "@/config/countries.json";
import useFunnelStore from "@/store/funnelStore";
import PredictionSurveyPage from "./prediction/PredictionSurveyPage";
import TrivonSurveyPage from "./trivon/TrivonSurveyPage";

const BASE_URL = "https://secure.quantumaiprofits.com";

// Some campaigns have a purpose-built funnel page instead of this generic
// survey UI. The CRM always generates a /c/:uuid link, and that link is what
// gets shared/put into ads -- so these render in place at that same URL
// rather than redirecting to a separate route.
const DEDICATED_FUNNEL_PAGES: Record<string, ComponentType> = {
  "a233ec68-0137-4dbc-b907-54e74c8a0264": PredictionSurveyPage,
  "a245c7db-f23f-4c2d-8f18-4468aac9491e": TrivonSurveyPage,
};

const QUESTIONS = [
  {
    id: "country_of_residence",
    text: "What is your current country of Residence?",
    type: "select",
    options: countriesData.map((c) => c.name),
  },
  {
    id: "language",
    text: "Do you speak English?",
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

function getDeviceType() {
  return /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent)
    ? "mobile"
    : window.innerWidth < 1024
      ? "tablet"
      : "desktop";
}

function getBrowserType() {
  const ua = navigator.userAgent;
  if (/Edg\//.test(ua)) return "Edge";
  if (/OPR\/|Opera/.test(ua)) return "Opera";
  if (/Chrome\//.test(ua) && !/Chromium/.test(ua)) return "Chrome";
  if (/Firefox\//.test(ua)) return "Firefox";
  if (/Safari\//.test(ua) && !/Chrome/.test(ua)) return "Safari";
  if (/MSIE|Trident/.test(ua)) return "IE";
  return "Other";
}

type Country = { name: string; dial_code: string; code: string };

export default function CampaignSurveyPage() {
  const { uuid } = useParams<{ uuid: string }>();
  const setFunnelData = useFunnelStore((state) => state.setFunnelData);

  const [step, setStep] = useState<1 | 2>(1);
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [error, setError] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const [dialOpen, setDialOpen] = useState(false);
  const [dialSearch, setDialSearch] = useState("");
  const [selectedDial, setSelectedDial] = useState<Country>({
    name: "United States",
    dial_code: "+1",
    code: "US",
  });
  const [filteredCountries, setFilteredCountries] =
    useState<Country[]>(countriesData);

  const [campaignLogo, setCampaignLogo] = useState<string | null>(null);
  const [detectedIP, setDetectedIP] = useState("");
  const [detectedCountry, setDetectedCountry] = useState("");

  const [answers, setAnswers] = useState<Record<string, string>>({});
  const dropRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (uuid) {
      fetch(`${BASE_URL}/api/v1/leads/campaigns/${uuid}`)
        .then((r) => r.json())
        .then((data) => {
          if (data?.data?.name) {
            document.title = data.data.name;
          }
          if (data?.data?.logo) {
            setCampaignLogo(data.data.logo);
          }
          if (data?.data?.favicon) {
            let link = document.querySelector<HTMLLinkElement>(
              "link[rel='icon']",
            );
            if (!link) {
              link = document.createElement("link");
              link.rel = "icon";
              document.head.appendChild(link);
            }
            link.href = data.data.favicon;
          }
          if (data?.data?.trackingScript) {
            // innerHTML strips <script> tags from executing, so each one is
            // recreated as a real script element; anything else (e.g. a
            // pixel's <noscript><img> fallback) is inserted as-is.
            const container = document.createElement("div");
            container.innerHTML = data.data.trackingScript;
            Array.from(container.childNodes).forEach((node) => {
              if (node.nodeName === "SCRIPT") {
                const oldScript = node as HTMLScriptElement;
                const newScript = document.createElement("script");
                Array.from(oldScript.attributes).forEach((attr) =>
                  newScript.setAttribute(attr.name, attr.value),
                );
                newScript.text = oldScript.text;
                document.head.appendChild(newScript);
              } else {
                document.head.appendChild(node);
              }
            });
          }
        })
        .catch(() => {});
    }
  }, [uuid]);

  useEffect(() => {
    fetch("https://api4.ipify.org?format=json")
      .then((r) => r.json())
      .then((d: { ip?: string }) => setDetectedIP(d.ip ?? ""))
      .catch(() => {});

    fetch("https://ipapi.co/json/")
      .then((r) => r.json())
      .then(
        (data: {
          ip?: string;
          country_name?: string;
          country_code?: string;
        }) => {
          if (!detectedIP) setDetectedIP(data.ip ?? "");
          setDetectedCountry(data.country_name ?? "");
          const found = countriesData.find((c) => c.code === data.country_code);
          if (found) setSelectedDial(found);
        },
      )
      .catch(() => {});
  }, []);

  useEffect(() => {
    const q = dialSearch.toLowerCase();
    setFilteredCountries(
      q
        ? countriesData.filter(
            (c) => c.name.toLowerCase().includes(q) || c.dial_code.includes(q),
          )
        : countriesData,
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

  async function handleNext() {
    setError("");
    const nameRe = /^[A-Za-zÀ-ɏ'\-]{2,}(\s[A-Za-zÀ-ɏ'\-]{1,})*$/;
    if (!firstName || !nameRe.test(firstName)) {
      setError("Please enter a valid first name (at least 2 letters).");
      return;
    }
    if (!lastName || !nameRe.test(lastName)) {
      setError("Please enter a valid last name (at least 2 letters).");
      return;
    }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setError("Please enter a valid email address.");
      return;
    }

    if (
      !phone ||
      !isValidPhoneNumber(
        selectedDial.dial_code + phone,
        selectedDial.code as never,
      )
    ) {
      setError("Please enter a valid phone number.");
      return;
    }

    setIsSubmitting(true);
    try {
      const payload = {
        first_name: firstName,
        last_name: lastName,
        email,
        phone: phone ? selectedDial.dial_code + phone : "",
        country: selectedDial.name || detectedCountry,
        ip_address: detectedIP,
        device_type: getDeviceType(),
        browser_type: getBrowserType(),
        language: "EN",
      };
      console.log("Capture Request Payload:", payload);
      const resp = await fetch(`${BASE_URL}/c/${uuid}/capture`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(payload),
      });
      const data = await resp.json();
      console.log("Capture Response Content:", data);

      if (data?.data) {
        setFunnelData({
          whatsapp_number: data.data.whatsapp_number || data.data.phone || null,
          whatsapp_message:
            data.data.whatsapp_message || data.data.message || null,
          campaign_name: data.data.campaign_name || data.data.campaign || null,
          email: email || null,
        });
      }
    } catch (error) {
      console.error("Capture Error:", error);
      // continue to step 2 regardless
    } finally {
      setIsSubmitting(false);
    }

    setStep(2);
  }

  async function handleSurveyNext() {
    if (currentQuestionIndex < QUESTIONS.length - 1) {
      setCurrentQuestionIndex((prev) => prev + 1);
    } else {
      setIsSubmitting(true);
      try {
        const payload = {
          email, // Including email to help match the lead if needed
          answers: answers,
        };
        console.log("Survey Request Payload:", payload);
        const resp = await fetch(`${BASE_URL}/c/${uuid}/survey`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify(payload),
        });
        const data = await resp.json();
        console.log("Survey Response Content:", data);
      } catch (e) {
        console.error("Survey submission failed", e);
      } finally {
        setIsSubmitting(false);
        window.location.href = `/landing?uuid=${uuid}`;
      }
    }
  }

  const currentQuestion = QUESTIONS[currentQuestionIndex];

  const isPhoneValid =
    phone.length >= 4 &&
    isValidPhoneNumber(
      selectedDial.dial_code + phone,
      selectedDial.code as never,
    );

  if (uuid && DEDICATED_FUNNEL_PAGES[uuid]) {
    const DedicatedPage = DEDICATED_FUNNEL_PAGES[uuid];
    return <DedicatedPage />;
  }

  return (
    <>
      <style>{`
        @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .fade-up { animation: fadeUp 0.3s ease forwards; }
      `}</style>

      <div className="min-h-screen flex items-center justify-center p-4 bg-[#0a0f1e] relative">
        <div
          className="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat"
          style={{
            backgroundImage: `url('https://res.cloudinary.com/dyp8gtllq/image/upload/v1781017295/photo_2026-06-09_16-00-51_ac3zdm.jpg')`,
          }}
        >
          <div
            className="absolute inset-0"
            style={{
              background:
                "linear-gradient(to bottom right, rgba(10,15,30,0.85), rgba(10,15,30,0.50), rgba(10,15,30,0.85))",
            }}
          />
        </div>

        <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-visible fade-up relative z-10">
          <div className="flex justify-center pt-6 pb-2 px-8">
            {campaignLogo ? (
              <img
                src={campaignLogo}
                alt=""
                className="w-auto h-8 rounded"
              />
            ) : (
              <Logo />
            )}
          </div>

          <div className="h-1 bg-gray-100 overflow-hidden">
            <div
              className="h-1 bg-blue-600 transition-all duration-500 ease-out"
              style={{
                width:
                  step === 1
                    ? "15%"
                    : `${15 + ((currentQuestionIndex + 1) / QUESTIONS.length) * 85}%`,
              }}
            />
          </div>

          <div className="p-8">
            {step === 1 ? (
              <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-1">
                  Get Started
                </h2>
                <p className="text-gray-500 text-sm mb-6">
                  Enter your details to continue
                </p>

                {error && (
                  <p className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
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
                      className="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
                    />
                    <input
                      type="text"
                      placeholder="Last Name"
                      value={lastName}
                      onChange={(e) => setLastName(e.target.value)}
                      className="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
                    />
                  </div>

                  <input
                    type="email"
                    placeholder="Email Address"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
                  />

                  <div className="flex border border-gray-200 rounded-xl overflow-visible focus-within:ring-2 focus-within:ring-blue-500">
                    <div className="relative shrink-0" ref={dropRef}>
                      <button
                        type="button"
                        onClick={() => setDialOpen((v) => !v)}
                        className="bg-gray-50 px-3 h-full flex items-center gap-1.5 border-r border-gray-200 rounded-l-xl focus:outline-none"
                      >
                        <ReactCountryFlag
                          countryCode={selectedDial.code}
                          svg
                          style={{ width: "1.25em", height: "1.25em" }}
                        />
                        <span className="text-sm font-medium text-gray-800">
                          {selectedDial.dial_code}
                        </span>
                        <svg
                          className="w-3 h-3 text-gray-400 ml-0.5"
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
                        <div className="absolute z-50 left-0 top-full mt-1 w-72 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                          <div className="p-2 border-b border-gray-100">
                            <input
                              type="text"
                              placeholder="Search country..."
                              value={dialSearch}
                              onChange={(e) => setDialSearch(e.target.value)}
                              className="w-full px-3 py-2 text-xs text-gray-900 placeholder-gray-400 bg-gray-50 rounded-lg border border-gray-100 focus:outline-none"
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
                                className="w-full text-left px-4 py-2 hover:bg-blue-50 transition-colors border-b border-gray-50 last:border-0"
                              >
                                <span className="text-xs text-gray-400 block leading-tight">
                                  {c.name}
                                </span>
                                <span className="text-sm font-medium leading-tight flex items-center gap-1.5">
                                  <ReactCountryFlag
                                    countryCode={c.code}
                                    svg
                                    style={{ width: "1.1em", height: "1.1em" }}
                                  />
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
                        className="w-full border-0 px-4 py-3 pr-10 text-sm text-gray-900 placeholder-gray-400 bg-white focus:outline-none focus:ring-0 rounded-r-xl"
                      />
                      {phone.length > 0 && (
                        <span
                          className={`absolute right-3 top-1/2 -translate-y-1/2 ${isPhoneValid ? "text-green-500" : "text-red-400"}`}
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
                  onClick={handleNext}
                  disabled={
                    isSubmitting ||
                    !firstName ||
                    !lastName ||
                    !email ||
                    !isPhoneValid
                  }
                  className="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-xl transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isSubmitting ? "Processing..." : "Next →"}
                </button>
              </div>
            ) : (
              <div className="fade-up">
                <h2 className="text-2xl font-bold text-gray-900 mb-1">
                  Quick Questions
                </h2>
                <p className="text-gray-500 text-sm mb-6">
                  Help us personalise your experience
                </p>

                <div className="space-y-6">
                  <div key={currentQuestion.id}>
                    <p className="text-sm font-semibold text-gray-800 mb-4">
                      Question {currentQuestionIndex + 1} of {QUESTIONS.length}
                    </p>
                    <p className="text-lg font-medium text-gray-900 mb-4">
                      {currentQuestion.text}
                    </p>
                    <div className="space-y-4">
                      {currentQuestion.type === "text" ? (
                        <input
                          type="text"
                          value={answers[currentQuestion.id] || ""}
                          onChange={(e) =>
                            setAnswers((prev) => ({
                              ...prev,
                              [currentQuestion.id]: e.target.value,
                            }))
                          }
                          className="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
                          placeholder="Type your answer here..."
                          autoFocus
                        />
                      ) : currentQuestion.type === "select" ? (
                        <select
                          value={answers[currentQuestion.id] || ""}
                          onChange={(e) =>
                            setAnswers((prev) => ({
                              ...prev,
                              [currentQuestion.id]: e.target.value,
                            }))
                          }
                          className="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                        >
                          <option value="" disabled>
                            Select an option...
                          </option>
                          {currentQuestion.options?.map((opt) => (
                            <option key={opt} value={opt}>
                              {opt}
                            </option>
                          ))}
                        </select>
                      ) : (
                        currentQuestion.options?.map((opt) => (
                          <label
                            key={opt}
                            className={`flex items-center gap-3 px-4 py-3 border rounded-xl cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-colors ${
                              answers[currentQuestion.id] === opt
                                ? "bg-blue-50 border-blue-500"
                                : "border-gray-200"
                            }`}
                          >
                            <input
                              type="radio"
                              name={`survey_${currentQuestion.id}`}
                              value={opt}
                              checked={answers[currentQuestion.id] === opt}
                              onChange={() =>
                                setAnswers((prev) => ({
                                  ...prev,
                                  [currentQuestion.id]: opt,
                                }))
                              }
                              className="text-blue-600 focus:ring-blue-500 shrink-0"
                            />
                            <span className="text-sm text-gray-700">{opt}</span>
                          </label>
                        ))
                      )}
                    </div>
                  </div>
                </div>

                <div className="mt-8 flex gap-3">
                  <button
                    onClick={() => {
                      if (currentQuestionIndex === 0) {
                        setStep(1);
                      } else {
                        setCurrentQuestionIndex((v) => v - 1);
                      }
                    }}
                    className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 rounded-xl transition-colors text-sm"
                  >
                    ← Back
                  </button>
                  <button
                    onClick={handleSurveyNext}
                    disabled={
                      isSubmitting || !answers[currentQuestion.id]?.trim()
                    }
                    className="flex-[2] bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {isSubmitting
                      ? "Processing..."
                      : currentQuestionIndex === QUESTIONS.length - 1
                        ? "Get Access →"
                        : "Next →"}
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
