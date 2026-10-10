import { useEffect } from "react";
import { useSearchParams } from "react-router-dom";
import { motion } from "framer-motion";
import { Phone } from "lucide-react";
import Logo from "@/components/Logo";
import { useCampaignQuery } from "@/services/campaigns/campaign-queries";
import useFunnelStore from "@/store/funnelStore";

const WhatsAppIcon = () => (
  <svg viewBox="0 0 24 24" fill="currentColor" className="w-6 h-6">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
  </svg>
);

export default function ThankYouPage() {
  const [searchParams] = useSearchParams();
  const funnelStoreData = useFunnelStore((state) => state.data);

  const uuid =
    searchParams.get("uuid") ||
    searchParams.get("campaign") ||
    funnelStoreData?.campaign_name ||
    null;
  const { data } = useCampaignQuery(uuid);

  const waNumber =
    searchParams.get("whatsapp_number") ||
    funnelStoreData?.whatsapp_number ||
    data?.data?.whatsappNumber ||
    "";
  const waMessage =
    searchParams.get("whatsapp_message") ||
    funnelStoreData?.whatsapp_message ||
    data?.data?.whatsappMessage ||
    "";
  useEffect(() => {
    if (uuid) {
      console.log("Fetching campaign data for UUID:", uuid);
    }
  }, [uuid]);
  useEffect(() => {
    if (data) {
      console.log("Campaign Retrieval Response:", data);
    }
  }, [data]);

  const waLink =
    waNumber && waMessage
      ? `https://wa.me/${waNumber.replace(/\D/g, "")}?text=${encodeURIComponent(waMessage)}`
      : "";

  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1,
        delayChildren: 0.2,
      },
    },
  };

  const itemVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0 },
  };

  return (
    <>
      <style>{`
        @keyframes draw-check {
          from { stroke-dashoffset: 100; }
          to { stroke-dashoffset: 0; }
        }
        @keyframes phone-ring {
          0%, 100% { transform: rotate(0deg) scale(1); }
          10%, 30%, 50%, 70%, 90% { transform: rotate(-10deg) scale(1.05); }
          20%, 40%, 60%, 80% { transform: rotate(10deg) scale(1.05); }
        }
        @keyframes call-wave {
          0% { transform: scale(1); opacity: 0.6; }
          100% { transform: scale(2.5); opacity: 0; }
        }
        @keyframes data-flow {
          0% { left: 0%; opacity: 0; }
          20% { opacity: 1; }
          80% { opacity: 1; }
          100% { left: 100%; opacity: 0; }
        }
        .phone-ring {
          animation: phone-ring 1.5s cubic-bezier(.36,.07,.19,.97) infinite;
          transform-origin: center;
        }
        .call-wave {
          position: absolute;
          border-radius: 50%;
          background: rgba(16, 185, 129, 0.4);
          animation: call-wave 2s ease-out infinite;
          z-index: 0;
        }
        .data-dot {
          width: 6px;
          height: 6px;
          background: #10b981;
          border-radius: 50%;
          position: absolute;
          top: 50%;
          transform: translateY(-50%);
          animation: data-flow 2s linear infinite;
          box-shadow: 0 0 10px #10b981;
        }
        .glass-card {
          background: #ffffff;
          border: 1px solid rgba(0, 0, 0, 0.05);
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        @keyframes shine {
          0% { transform: translateX(-100%); }
          100% { transform: translateX(100%); }
        }
        @keyframes button-pulse {
          0% { transform: scale(1); box-shadow: 0 10px 20px rgba(37, 211, 102, 0.2); }
          50% { transform: scale(1.05); box-shadow: 0 15px 30px rgba(37, 211, 102, 0.4); }
          100% { transform: scale(1); box-shadow: 0 10px 20px rgba(37, 211, 102, 0.2); }
        }
        .animate-button-pulse {
          animation: button-pulse 2s infinite ease-in-out;
        }
      `}</style>

      <div className="relative min-h-screen w-full flex items-center justify-center p-4 overflow-hidden bg-[#0a0f1e]">
        {/* Background Image with Overlay */}
        <div
          className="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat"
          style={{
            backgroundImage: `url('https://res.cloudinary.com/dyp8gtllq/image/upload/v1781017295/photo_2026-06-09_16-00-51_ac3zdm.jpg')`,
          }}
        >
          {/* Lighter overlay to make image visible */}
          <div className="absolute inset-0 bg-gradient-to-br from-[#0a0f1e]/85 via-[#0a0f1e]/50 to-[#0a0f1e]/85" />
        </div>

        {/* Animated Background Shapes */}
        <div className="absolute inset-0 z-0 pointer-events-none overflow-hidden">
          <motion.div
            animate={{
              scale: [1, 1.2, 1],
              opacity: [0.1, 0.2, 0.1],
            }}
            transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
            className="absolute -top-1/4 -left-1/4 w-1/2 h-1/2 bg-emerald-500/20 rounded-full blur-[120px]"
          />
          <motion.div
            animate={{
              scale: [1, 1.3, 1],
              opacity: [0.1, 0.15, 0.1],
            }}
            transition={{
              duration: 10,
              repeat: Infinity,
              ease: "easeInOut",
              delay: 1,
            }}
            className="absolute -bottom-1/4 -right-1/4 w-1/2 h-1/2 bg-blue-500/20 rounded-full blur-[120px]"
          />
        </div>

        <motion.div
          variants={containerVariants}
          initial="hidden"
          animate="visible"
          className="glass-card relative z-10 rounded-3xl px-6 py-10 sm:px-8 sm:py-12 flex flex-col items-center gap-6 sm:gap-8 w-full max-w-lg text-center"
        >
          {/* Status Indicators Section */}
          <div className="flex flex-col sm:flex-row items-center justify-center gap-6 sm:gap-10 w-full">
            {/* Agent Image instead of Checkmark */}
            <motion.div variants={itemVariants} className="relative">
              <div className="w-20 h-20 sm:w-24 sm:h-24 rounded-full border-4 border-emerald-500/30 overflow-hidden shadow-[0_0_20px_rgba(16,185,129,0.2)]">
                <img
                  src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1780941470/1ddcd02b-e1da-4a35-89f8-192c4b28a04e_jjkmia.png"
                  alt="Agent"
                  className="w-full h-full object-cover"
                />
              </div>
              <div className="absolute -bottom-1 -right-1 bg-emerald-500 rounded-full p-1 border-2 border-[#0a0f1e]">
                <div className="w-2 h-2 bg-white rounded-full animate-pulse" />
              </div>
            </motion.div>

            {/* Data Flow Animation */}
            <motion.div
              variants={itemVariants}
              className="relative h-2 w-20 sm:w-24 hidden sm:block"
            >
              <div className="absolute top-1/2 left-0 w-full h-[1px] bg-emerald-500/20 -translate-y-1/2" />
              <div className="data-dot" style={{ animationDelay: "0s" }} />
              <div className="data-dot" style={{ animationDelay: "0.6s" }} />
              <div className="data-dot" style={{ animationDelay: "1.2s" }} />
            </motion.div>

            {/* Vertical Flow for Mobile */}
            <div className="sm:hidden h-12 w-[1px] bg-gradient-to-b from-emerald-500/50 to-transparent relative">
              <motion.div
                animate={{ y: [0, 40, 0], opacity: [0, 1, 0] }}
                transition={{ duration: 1.5, repeat: Infinity }}
                className="absolute left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-emerald-500 rounded-full shadow-[0_0_8px_#10b981]"
              />
            </div>

            {/* Animated Phone Section */}
            <motion.div
              variants={itemVariants}
              className="relative flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24"
            >
              {/* Animated Waves */}
              <div
                className="call-wave w-12 h-12 sm:w-16 sm:h-16"
                style={{ animationDelay: "0s" }}
              />
              <div
                className="call-wave w-12 h-12 sm:w-16 sm:h-16"
                style={{ animationDelay: "0.6s" }}
              />
              <div
                className="call-wave w-12 h-12 sm:w-16 sm:h-16"
                style={{ animationDelay: "1.2s" }}
              />

              <div className="absolute inset-0 bg-emerald-500/10 blur-2xl rounded-full animate-pulse" />
              <div className="relative phone-ring z-10">
                <Phone
                  size={40}
                  className="text-emerald-500 fill-emerald-500/20 sm:size-[48px]"
                  strokeWidth={2.5}
                />
              </div>
            </motion.div>
          </div>

          {/* Text Content */}
          <div className="space-y-3">
            <motion.h1
              variants={itemVariants}
              className="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight"
            >
              Expect a call from a dedicated agent any moment from now
            </motion.h1>

            <motion.p
              variants={itemVariants}
              className="text-emerald-600 text-lg font-bold flex items-center justify-center gap-2"
            >
              <span className="relative flex h-3 w-3">
                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span>
                <span className="relative inline-flex rounded-full h-3 w-3 bg-emerald-600"></span>
              </span>
              Agent is Connecting Now...
            </motion.p>
          </div>

          <motion.div
            variants={itemVariants}
            className="bg-gray-50/50 border border-gray-200 rounded-2xl p-5 sm:p-6 w-full space-y-4 shadow-inner"
          >
            <p className="text-gray-600 text-sm sm:text-base leading-relaxed">
              Please stay on the line. A dedicated agent will be calling you
              shortly to finalize your setup.
              <span className="block mt-2 font-bold text-gray-900 text-lg italic">
                Do not close this page.
              </span>
            </p>
          </motion.div>

          {/* WhatsApp Button Section */}
          {waLink && (
            <motion.div variants={itemVariants} className="w-full">
              <a
                href={waLink}
                target="_blank"
                rel="noopener noreferrer"
                className="group relative flex items-center justify-center gap-3 w-full bg-[#25D366] hover:bg-[#22c35e] text-white py-4 px-8 rounded-2xl font-bold text-lg shadow-[0_10px_20px_rgba(37,211,102,0.2)] transition-all duration-300 active:scale-[0.98] overflow-hidden animate-button-pulse"
              >
                {/* Floating-like Ping Animation */}
                <span className="absolute inset-0 rounded-2xl bg-[#25D366] animate-ping opacity-20 group-hover:opacity-30" />

                <motion.div
                  animate={{ scale: [1, 1.2, 1] }}
                  transition={{ duration: 2, repeat: Infinity }}
                  className="relative z-10"
                >
                  <WhatsAppIcon />
                </motion.div>
                <span className="relative z-10">
                  Chat With an Account Manager Now!
                </span>

                {/* Shine effect */}
                <div className="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-[shine_1.5s_ease-in-out_infinite]" />
              </a>
            </motion.div>
          )}

          {/* Logo at the bottom */}
          <motion.div variants={itemVariants} className="pt-2">
            <Logo />
          </motion.div>
        </motion.div>
      </div>
    </>
  );
}
