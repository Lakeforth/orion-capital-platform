import { useEffect } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { motion } from "framer-motion";
import { CheckCircle2, Lock, Shield, ShieldCheck } from "lucide-react";
import usePredictionFunnelStore from "@/store/predictionFunnelStore";
import FunnelLangSwitch from "@/components/FunnelLangSwitch";
import { postStatus } from "./predictionApi";
import { PANEL_IMAGE_URL, PRODUCT_BOX_DE_URL } from "./predictionBrand";
import { DEFAULT_LANG, useFunnelLanguage } from "./predictionLanguage";
import { pricingFor } from "./predictionPricing";

/**
 * PredictionCheckoutPage.tsx
 * -----------------------------------------------------------------------------
 * Checkout step of the Prediction funnel. Presented as an order summary, but no
 * payment is taken here: the single CTA fires the "checkout" status beacon and
 * advances to thank-you, forwarding uuid + WhatsApp details as query params.
 *
 * The name/email fields are DISPLAY ONLY. They echo back what the survey already
 * captured (via the funnel store) as reassurance — they collect nothing, and the
 * CTA never blocks on them.
 *
 * Styled in the Prediction theme (navy gradient + purple #4f46e5 accent,
 * Poppins/Open Sans), reusing framer-motion + lucide.
 */

const HEAD_FONT = { fontFamily: "'Poppins', system-ui, sans-serif" } as const;
const BODY_FONT = { fontFamily: "'Open Sans', system-ui, sans-serif" } as const;

const PRODUCT_NAME = "Prediction Profits AI";
const PRODUCT_IMAGE = PRODUCT_BOX_DE_URL;

const FEATURES = [
  "AI-Powered Market & Event Analysis",
  "Real-Time Signals & Insights",
  "Dedicated Account Manager",
];

const TRUST_BADGES = [
  { icon: Lock, lines: ["Secure", "Checkout"] },
  { icon: ShieldCheck, lines: ["256-Bit SSL", "Encrypted"] },
  { icon: Shield, lines: ["Your Data is", "100% Safe"] },
];

export default function PredictionCheckoutPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const lang = useFunnelLanguage();
  const funnelData = usePredictionFunnelStore((s) => s.data);
  const pricing = pricingFor(lang);

  const uuid = searchParams.get("uuid") ?? searchParams.get("campaign");
  const email = funnelData?.email;
  const fullName = funnelData?.full_name;
  const waNumber =
    searchParams.get("whatsapp_number") ?? funnelData?.whatsapp_number;
  const waMessage =
    searchParams.get("whatsapp_message") ?? funnelData?.whatsapp_message;

  // Tell the backend the lead reached checkout (email is known by this
  // point, unlike on the landing page where it never fires).
  useEffect(() => {
    if (!uuid || !email) return;
    postStatus(uuid, email, "landing_page");
  }, [uuid, email]);

  const handleConnect = () => {
    if (uuid && email) postStatus(uuid, email, "checkout");

    // Auto-login campaigns (Campaign::end_zone === 'platform') hand back an
    // `/auto-login?token=...` URL at capture time. Forward to it so the lead
    // lands in the client area / trade room instead of the thank-you page.
    // Use a full-page load: /auto-login may live on a different origin
    // (front_end_url) and needs to re-init the authenticated app + axios base.
    const redirectUrl = funnelData?.redirect_url;
    if (redirectUrl && redirectUrl.includes("/auto-login")) {
      window.location.href = redirectUrl;
      return;
    }

    const params = new URLSearchParams();
    if (uuid) params.set("uuid", uuid);
    // `en` is the default; forwarding it would only clutter the URL.
    if (lang && lang !== DEFAULT_LANG) params.set("lang", lang);
    if (waNumber) params.set("whatsapp_number", waNumber);
    if (waMessage) params.set("whatsapp_message", waMessage);

    const qs = params.toString();
    navigate(`/prediction-thank-you${qs ? `?${qs}` : ""}`);
  };

  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: { staggerChildren: 0.1, delayChildren: 0.2 },
    },
  };

  const itemVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0 },
  };

  return (
    <>
      <style>{`
        @keyframes shine {
          0% { transform: translateX(-100%); }
          100% { transform: translateX(100%); }
        }
        @keyframes button-pulse {
          0% { transform: scale(1); box-shadow: 0 10px 20px rgba(79, 70, 229, 0.35); }
          50% { transform: scale(1.02); box-shadow: 0 15px 30px rgba(79, 70, 229, 0.55); }
          100% { transform: scale(1); box-shadow: 0 10px 20px rgba(79, 70, 229, 0.35); }
        }
        .animate-button-pulse { animation: button-pulse 2s infinite ease-in-out; }
      `}</style>

      <div
        style={BODY_FONT}
        className="relative min-h-screen w-full overflow-hidden flex items-center justify-center p-4 sm:p-6 bg-gradient-to-br from-[#0a1a30] via-[#0d2447] to-[#091626]"
      >
        {/* Background image — same artwork and purple-tinted wash as the
            survey's promo panel, so the funnel reads as one surface. */}
        <div
          className="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat"
          style={{ backgroundImage: `url('${PANEL_IMAGE_URL}')` }}
        >
          <div className="absolute inset-0 bg-gradient-to-br from-[#0a1a30]/75 via-[#4f46e5]/40 to-[#091626]/80" />
          <div className="absolute inset-0 bg-[radial-gradient(600px_400px_at_25%_20%,rgba(129,140,248,0.35),transparent_65%)]" />
          <div className="absolute inset-0 bg-[radial-gradient(500px_320px_at_85%_85%,rgba(167,139,250,0.22),transparent_60%)]" />
        </div>

        {/* Everything lives inside one white card floating on the artwork. */}
        <motion.div
          variants={containerVariants}
          initial="hidden"
          animate="visible"
          className="relative z-10 w-full max-w-2xl bg-white rounded-2xl shadow-2xl shadow-black/50 px-5 py-6 sm:px-6"
        >
          <div className="absolute right-4 top-4 z-20">
            <FunnelLangSwitch />
          </div>
          {/* Header */}
          <motion.div variants={itemVariants} className="text-center mb-5">
            <h1
              style={HEAD_FONT}
              className="flex items-center justify-center gap-2 text-[20px] sm:text-[24px] font-black text-slate-900 tracking-tight"
            >
              <Lock className="text-[#4f46e5]" size={21} />
              Secure Checkout
            </h1>
            <p className="mt-0.5 text-[12px] text-slate-500">
              Your information is safe and encrypted
            </p>
          </motion.div>

          <div className="grid md:grid-cols-2 gap-6 items-start">
            {/* LEFT — the product */}
            <motion.div variants={itemVariants}>
              <img
                src={PRODUCT_IMAGE}
                alt={`${PRODUCT_NAME} software package`}
                className="w-full max-w-[150px] h-auto mx-auto drop-shadow-xl"
              />

              <h2
                style={HEAD_FONT}
                className="mt-4 font-extrabold text-[15.5px] text-slate-900 tracking-tight"
              >
                {PRODUCT_NAME}
              </h2>
              <p className="mt-1 text-[12px] leading-relaxed text-slate-500">
                A done-for-you AI system that reads global events, crypto,
                weather and geopolitics to surface high-confidence predictions.
              </p>

              <ul className="mt-3.5 space-y-2">
                {FEATURES.map((f) => (
                  <li key={f} className="flex items-center gap-2">
                    <CheckCircle2
                      size={16}
                      className="flex-none text-[#4f46e5]"
                    />
                    <span className="text-[12px] text-slate-700">{f}</span>
                  </li>
                ))}
              </ul>

              <div className="mt-5 flex items-start justify-between max-w-[240px]">
                {TRUST_BADGES.map(({ icon: Icon, lines }) => (
                  <div
                    key={lines.join(" ")}
                    className="flex flex-col items-center text-center"
                  >
                    <Icon size={17} className="text-slate-700" />
                    <span className="mt-1 text-[9.5px] leading-snug text-slate-500">
                      {lines[0]}
                      <br />
                      {lines[1]}
                    </span>
                  </div>
                ))}
              </div>
            </motion.div>

            {/* RIGHT — order summary + locked details */}
            <motion.div
              variants={itemVariants}
              className="rounded-xl border border-slate-200 overflow-hidden"
            >
              <div className="p-4">
                <h3
                  style={HEAD_FONT}
                  className="font-bold text-[15px] text-slate-900 mb-3"
                >
                  Order Summary
                </h3>

                <div className="flex items-center gap-2.5">
                  <img
                    src={PRODUCT_IMAGE}
                    alt=""
                    aria-hidden="true"
                    className="w-8 h-auto flex-none"
                  />
                  <div className="min-w-0">
                    <div
                      style={HEAD_FONT}
                      className="font-bold text-[12.5px] text-slate-900"
                    >
                      {PRODUCT_NAME}
                    </div>
                    <div className="text-[10.5px] text-slate-500">
                      Full Access – One Time Payment
                    </div>
                  </div>
                  <div
                    style={HEAD_FONT}
                    className="ml-auto flex-none font-semibold text-[12.5px] text-slate-900"
                  >
                    {pricing.entryPrice}
                  </div>
                </div>

                <div className="my-3 border-t border-slate-200" />

                <div className="flex items-center justify-between">
                  <span
                    style={HEAD_FONT}
                    className="font-extrabold text-[17px] text-slate-900"
                  >
                    Total
                  </span>
                  <span
                    style={HEAD_FONT}
                    className="font-extrabold text-[19px] text-[#4f46e5]"
                  >
                    {pricing.entryPrice}
                  </span>
                </div>
              </div>

              <div className="border-t border-slate-200 p-4">
                <div className="flex items-center gap-2 mb-1">
                  <Lock className="text-[#4f46e5]" size={16} />
                  <span
                    style={HEAD_FONT}
                    className="font-bold text-[13.5px] text-slate-900"
                  >
                    Secure Information
                  </span>
                </div>
                <p className="flex items-center gap-2 text-[11px] text-slate-500 mb-4">
                  <Lock className="flex-none text-slate-400" size={13} />
                  Your information is encrypted and secure.
                </p>

                {/* Display only — echoes what the survey captured. */}
                <LockedField label="Full Name" value={fullName} />
                <LockedField label="Email Address" value={email} />

                <div className="flex items-start gap-2 mb-4">
                  <Shield
                    className="flex-none mt-0.5 text-emerald-500"
                    size={17}
                  />
                  <p className="text-[11px] leading-relaxed text-slate-500">
                    We respect your privacy. Your information will never be
                    shared or sold.
                  </p>
                </div>

                <button
                  onClick={handleConnect}
                  style={HEAD_FONT}
                  className="group relative w-full overflow-hidden rounded-xl bg-[#4f46e5] hover:bg-[#4338ca] text-white py-3 px-4 shadow-lg transition-all duration-300 active:scale-[0.98] animate-button-pulse"
                >
                  <span className="relative z-10 flex items-center justify-center gap-2 font-bold text-[14.5px]">
                    <Lock size={16} />
                    I&apos;m Ready to Pay {pricing.entryPrice}
                  </span>
                  <span className="relative z-10 block text-[10.5px] font-medium text-indigo-100">
                    Complete Your Purchase Securely
                  </span>
                  <div className="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-[shine_1.5s_ease-in-out_infinite]" />
                </button>

                <div className="mt-2.5 flex items-center justify-center gap-1.5 text-[10.5px] text-slate-400">
                  <ShieldCheck size={13} />
                  <span>Secure • Encrypted • Trusted</span>
                </div>
              </div>
            </motion.div>
          </div>

          {/* Footer */}
          <motion.div
            variants={itemVariants}
            className="mt-6 border-t border-slate-200 pt-4 text-center"
          >
            <div className="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-[11px] text-slate-600">
              <span className="flex items-center gap-1.5">
                <Lock size={12} /> Secure Checkout
              </span>
              <span className="text-slate-300">|</span>
              <span>256-Bit SSL Encrypted</span>
              <span className="text-slate-300">|</span>
              <span>100% Safe &amp; Secure</span>
            </div>
            <p className="mt-1 text-[10px] text-slate-400">
              No credit card details are required on this page.
            </p>
          </motion.div>
        </motion.div>
      </div>
    </>
  );
}

/**
 * A read-only echo of a value the survey already captured. Rendered as a
 * disabled input so it looks like the rest of the funnel's fields, but it
 * submits nothing. Renders nothing at all when the value is unknown — an empty
 * locked box reads as a broken form.
 */
const LockedField = ({
  label,
  value,
}: {
  label: string;
  value: string | null | undefined;
}) => {
  if (!value) return null;
  return (
    <div className="mb-3.5">
      <label className="block text-[11.5px] font-semibold text-slate-700 mb-1">
        {label}
      </label>
      <input
        type="text"
        value={value}
        readOnly
        aria-readonly="true"
        tabIndex={-1}
        className="w-full cursor-default rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-[13px] text-slate-900 focus:outline-none"
      />
    </div>
  );
};
