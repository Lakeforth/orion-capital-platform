import { useEffect, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { motion } from "framer-motion";
import { Headphones, ShieldCheck, User } from "lucide-react";
import usePredictionFunnelStore from "@/store/predictionFunnelStore";
import FunnelLangSwitch from "@/components/FunnelLangSwitch";
import WhatsAppButton from "@/components/chat/whatsapp-button";
import {
  applyCampaignFavicon,
  applyCampaignTrackingScript,
  fetchCampaign,
  readWhatsApp,
} from "./predictionApi";
import { PANEL_IMAGE_URL, useCampaignLogo } from "./predictionBrand";
import { useFunnelLanguage } from "./predictionLanguage";

/**
 * PredictionThankYouPage.tsx
 * -----------------------------------------------------------------------------
 * Terminal step of the Prediction funnel: a "we're calling you" confirmation
 * with a ringing agent avatar, the lead's own name/email echoed back, and a
 * WhatsApp CTA.
 *
 * The WhatsApp number/message resolve from (in order) the query params, the
 * prediction funnel store, then a campaign lookup. The CTA only renders once a
 * number is known. Like Quantum, no status beacon fires here.
 *
 * Styled in the Prediction theme (navy gradient + purple #4f46e5 accent,
 * Poppins/Open Sans).
 */

const HEAD_FONT = { fontFamily: "'Poppins', system-ui, sans-serif" } as const;
const BODY_FONT = { fontFamily: "'Open Sans', system-ui, sans-serif" } as const;

/**
 * Prefilled WhatsApp message. Google Translate rewrites the page, but never the
 * `?text=` we hand to wa.me — so the German copy has to be authored here.
 */
const DEFAULT_WA_MESSAGE = "Ich möchte mit Prediction Profits AI durchstarten.";

const defaultWaMessage = () => DEFAULT_WA_MESSAGE;

/**
 * Relative bar heights for the call waveform, read outward from the avatar.
 * Authored rather than random so the left and right sides mirror each other.
 */
const WAVE_BARS = [0.28, 0.5, 0.86, 1, 0.62, 0.94, 0.44, 0.72, 0.34, 0.2];

const WhatsAppIcon = () => (
  <svg viewBox="0 0 24 24" fill="currentColor" className="w-7 h-7">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
  </svg>
);

/**
 * One half of the call waveform. Each bar scales on its own delay, so the row
 * ripples rather than pulsing in unison. `mirrored` flips it for the left side.
 */
const Waveform = ({ mirrored = false }: { mirrored?: boolean }) => (
  <div
    aria-hidden="true"
    className={`flex items-center gap-[3px] sm:gap-1 h-16 ${
      mirrored ? "flex-row-reverse" : ""
    }`}
  >
    {WAVE_BARS.map((h, i) => (
      <span
        key={i}
        className="wave-bar w-[3px] sm:w-1 rounded-full bg-emerald-500"
        style={{
          height: `${h * 100}%`,
          animationDelay: `${i * 0.11}s`,
        }}
      />
    ))}
  </div>
);

export default function PredictionThankYouPage() {
  const [searchParams] = useSearchParams();
  const funnelData = usePredictionFunnelStore((s) => s.data);

  // Terminal stage: nothing to forward on. We still call useFunnelLanguage to
  // apply the ?lang= from the URL to the page.
  useFunnelLanguage();
  const logo = useCampaignLogo();

  const fullName = funnelData?.full_name;
  const leadEmail = funnelData?.email;

  const uuid =
    searchParams.get("uuid") ??
    searchParams.get("campaign") ??
    funnelData?.campaign_name ??
    null;

  // Campaign lookup fills in the WhatsApp details when they weren't passed
  // through the funnel (e.g. the user landed here directly).
  const [campaignWa, setCampaignWa] = useState<{
    number: string | null;
    message: string | null;
  }>({ number: null, message: null });

  useEffect(() => {
    if (!uuid) return;
    let cancelled = false;
    fetchCampaign(uuid).then((c) => {
      if (cancelled) return;
      const wa = readWhatsApp(c);
      setCampaignWa({ number: wa.number, message: wa.message });
      applyCampaignFavicon(c);
      applyCampaignTrackingScript(c);
    });
    return () => {
      cancelled = true;
    };
  }, [uuid]);

  const waNumber =
    searchParams.get("whatsapp_number") ??
    funnelData?.whatsapp_number ??
    campaignWa.number;
  const waMessage =
    searchParams.get("whatsapp_message") ??
    funnelData?.whatsapp_message ??
    campaignWa.message ??
    defaultWaMessage();

  // Only a number makes the CTA actionable; wa.me with no recipient just opens
  // WhatsApp's contact picker.
  const waLink = waNumber
    ? `https://wa.me/${waNumber.replace(/[^\d]/g, "")}?text=${encodeURIComponent(waMessage)}`
    : "";

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
        /* Waveform bars: a vertical bounce, scaled from the centre. */
        @keyframes wave-bar {
          0%, 100% { transform: scaleY(0.28); opacity: 0.65; }
          50%      { transform: scaleY(1);    opacity: 1; }
        }
        .wave-bar {
          transform-origin: center;
          animation: wave-bar 1.1s ease-in-out infinite;
        }
        /* Concentric rings breathing outward from the avatar. */
        @keyframes call-ring {
          0%   { transform: scale(0.85); opacity: 0.55; }
          100% { transform: scale(1.45); opacity: 0; }
        }
        .call-ring {
          position: absolute;
          inset: 0;
          border-radius: 9999px;
          border: 2px solid rgba(79, 70, 229, 0.35);
          animation: call-ring 2.4s ease-out infinite;
        }
        @keyframes shine {
          0%   { transform: translateX(-100%); }
          100% { transform: translateX(100%); }
        }
        @keyframes button-pulse {
          0%   { transform: scale(1);    box-shadow: 0 10px 20px rgba(37, 211, 102, 0.2); }
          50%  { transform: scale(1.02); box-shadow: 0 15px 30px rgba(37, 211, 102, 0.4); }
          100% { transform: scale(1);    box-shadow: 0 10px 20px rgba(37, 211, 102, 0.2); }
        }
        .animate-button-pulse { animation: button-pulse 2s infinite ease-in-out; }

        @media (prefers-reduced-motion: reduce) {
          .wave-bar, .call-ring, .animate-button-pulse { animation: none; }
        }
      `}</style>

      <div
        style={BODY_FONT}
        className="relative min-h-screen w-full flex items-center justify-center p-4 sm:p-6 overflow-hidden bg-gradient-to-br from-[#0a1a30] via-[#0d2447] to-[#091626]"
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

        <motion.div
          variants={containerVariants}
          initial="hidden"
          animate="visible"
          className="relative z-10 w-full max-w-lg bg-white rounded-2xl shadow-2xl shadow-black/50 px-5 py-7 sm:px-8 text-center"
        >
          <div className="absolute right-4 top-4 z-20">
            <FunnelLangSwitch />
          </div>
          <motion.div variants={itemVariants} className="flex justify-center">
            <img
              src={logo}
              alt="Prediction Profits AI"
              className="h-[52px] w-auto object-contain"
            />
          </motion.div>

          <motion.h1
            variants={itemVariants}
            style={HEAD_FONT}
            className="mt-5 text-[22px] sm:text-[28px] font-extrabold text-slate-900 tracking-tight leading-tight"
          >
            Your Purchase is Almost Complete
          </motion.h1>
          <motion.p
            variants={itemVariants}
            className="mt-1.5 text-[13.5px] sm:text-[15px] text-slate-500"
          >
            We are connecting you to finalize your account setup.
          </motion.p>

          {/* Calling animation: waveform · avatar · waveform */}
          <motion.div
            variants={itemVariants}
            className="mt-7 flex items-center justify-center gap-2 sm:gap-4"
          >
            <Waveform mirrored />

            <div className="relative flex-none w-[104px] h-[104px] sm:w-[124px] sm:h-[124px] flex items-center justify-center">
              <span className="call-ring" style={{ animationDelay: "0s" }} />
              <span className="call-ring" style={{ animationDelay: "0.8s" }} />
              <span className="call-ring" style={{ animationDelay: "1.6s" }} />
              <div className="relative z-10 w-[76px] h-[76px] sm:w-[92px] sm:h-[92px] rounded-full bg-indigo-50 ring-8 ring-indigo-100/70 flex items-center justify-center">
                <User
                  className="text-[#4f46e5]"
                  size={44}
                  strokeWidth={0}
                  fill="currentColor"
                />
              </div>
            </div>

            <Waveform />
          </motion.div>

          {/* Who we're calling */}
          <motion.div variants={itemVariants} className="mt-5">
            <p className="text-[14px] text-slate-500">Calling…</p>
            {fullName && (
              <p
                style={HEAD_FONT}
                className="mt-1 text-[20px] sm:text-[23px] font-extrabold text-slate-900"
              >
                {fullName}
              </p>
            )}
            {leadEmail && (
              <p className="mt-0.5 text-[13.5px] text-slate-500 break-all">
                {leadEmail}
              </p>
            )}
          </motion.div>

          {/* Agent notice */}
          <motion.div
            variants={itemVariants}
            className="mt-6 flex items-start gap-4 rounded-xl bg-slate-50 border border-slate-200 px-4 py-4 text-left"
          >
            <span className="flex-none w-11 h-11 rounded-full border-2 border-emerald-500/40 flex items-center justify-center">
              <Headphones className="text-emerald-600" size={20} />
            </span>
            <p className="text-[13.5px] leading-relaxed text-slate-600">
              <span
                style={HEAD_FONT}
                className="block font-bold text-[15px] text-slate-900"
              >
                A finance department agent
              </span>
              will contact you shortly to setup your Account. Please keep your
              phone nearby.
            </p>
          </motion.div>

          {/* WhatsApp CTA.
              TEMPORARY: the `{waLink && (…)}` guard is removed so the button is
              always visible for design review. Restore it before shipping — with
              no number, wa.me just opens WhatsApp's contact picker. */}
          <motion.a
            variants={itemVariants}
            href={waLink || "#"}
            target="_blank"
            rel="noopener noreferrer"
            className="group relative mt-5 flex items-center gap-4 w-full overflow-hidden rounded-xl bg-[#25D366] hover:bg-[#22c35e] text-white px-5 py-4 text-left shadow-[0_10px_20px_rgba(37,211,102,0.2)] transition-all duration-300 active:scale-[0.98] animate-button-pulse"
          >
            <span className="relative z-10 flex-none">
              <WhatsAppIcon />
            </span>
            <span className="relative z-10">
              <span
                style={HEAD_FONT}
                className="block font-bold text-[16px] sm:text-[17px]"
              >
                Continue Account Setup on WhatsApp
              </span>
              <span className="block text-[13px] text-emerald-50">
                Our agent will assist you there
              </span>
            </span>
            <div className="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-[shine_1.5s_ease-in-out_infinite]" />
          </motion.a>

          {/* Privacy note */}
          <motion.div
            variants={itemVariants}
            className="mt-6 flex items-start justify-center gap-3 text-left"
          >
            <ShieldCheck className="flex-none text-emerald-500" size={22} />
            <p className="text-[13px] leading-relaxed text-slate-500">
              Your information is secure and encrypted.
              <br />
              We respect your privacy.
            </p>
          </motion.div>
        </motion.div>
      </div>
      <WhatsAppButton />
    </>
  );
}
