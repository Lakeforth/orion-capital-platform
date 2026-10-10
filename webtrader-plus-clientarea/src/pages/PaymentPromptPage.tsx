import { useEffect } from "react";
import { useSearchParams, useNavigate } from "react-router-dom";
import { motion } from "framer-motion";
import { CheckCircle2, ArrowRight, ShieldCheck, Zap } from "lucide-react";
import Logo from "@/components/Logo";
import useFunnelStore from "@/store/funnelStore";

export default function PaymentPromptPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const uuid = searchParams.get("uuid") || searchParams.get("campaign");
  const waNumber = searchParams.get("whatsapp_number") || "";
  const waMessage = searchParams.get("whatsapp_message") || "";
  const email = useFunnelStore((state) => state.data?.email);

  // The "Almost Paid" hot-lead ping used to fire from QuantumLanding.tsx on
  // mount, but that's the very first touchpoint -- email is never known that
  // early, so it silently never fired. Moved here, where the survey has
  // already run and email is guaranteed to be in the store.
  useEffect(() => {
    if (!uuid || !email) return;
    fetch(`https://secure.quantumaiprofits.com/c/${uuid}/status`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, status: "landing_page" }),
    }).catch((e) => console.error("status landing_page failed", e));
  }, [uuid, email]);

  const handleConnect = () => {
    if (uuid && email) {
      fetch(`https://secure.quantumaiprofits.com/c/${uuid}/status`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, status: "checkout" }),
      }).catch((e) => console.error("status checkout failed", e));
    }
    const params = new URLSearchParams();
    if (uuid) params.set("uuid", uuid);
    if (waNumber) params.set("whatsapp_number", waNumber);
    if (waMessage) params.set("whatsapp_message", waMessage);
    navigate(`/thank-you?${params.toString()}`);
  };

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
        .glass-card {
          background: rgba(255, 255, 255, 0.95);
          backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.2);
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        @keyframes shine {
          0% { transform: translateX(-100%); }
          100% { transform: translateX(100%); }
        }
        @keyframes button-pulse {
          0% { transform: scale(1); box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2); }
          50% { transform: scale(1.05); box-shadow: 0 15px 30px rgba(37, 99, 235, 0.4); }
          100% { transform: scale(1); box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2); }
        }
        .animate-button-pulse {
          animation: button-pulse 2s infinite ease-in-out;
        }
        @keyframes pulse-soft {
          0%, 100% { transform: scale(1); opacity: 1; }
          50% { transform: scale(1.02); opacity: 0.9; }
        }
        .animate-pulse-soft {
          animation: pulse-soft 3s infinite ease-in-out;
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
          <div className="absolute inset-0 bg-gradient-to-br from-[#0a0f1e]/90 via-[#0a0f1e]/60 to-[#0a0f1e]/90" />
        </div>

        {/* Animated Background Shapes */}
        <div className="absolute inset-0 z-0 pointer-events-none overflow-hidden">
          <motion.div
            animate={{
              scale: [1, 1.2, 1],
              opacity: [0.1, 0.15, 0.1],
            }}
            transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
            className="absolute -top-1/4 -left-1/4 w-1/2 h-1/2 bg-blue-500/20 rounded-full blur-[120px]"
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
            className="absolute -bottom-1/4 -right-1/4 w-1/2 h-1/2 bg-brand/20 rounded-full blur-[120px]"
          />
        </div>

        <motion.div
          variants={containerVariants}
          initial="hidden"
          animate="visible"
          className="glass-card relative z-10 rounded-3xl px-6 py-10 sm:px-8 sm:py-12 flex flex-col items-center gap-8 w-full max-w-lg text-center"
        >
          <motion.div
            variants={itemVariants}
            className="flex flex-col items-center gap-4"
          >
            <div className="bg-blue-600/10 p-3 rounded-2xl">
              <Zap className="text-blue-600" size={32} fill="currentColor" />
            </div>
            <h1 className="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight leading-tight">
              I'm ready to pay now
            </h1>
          </motion.div>

          <div className="space-y-4">
            <motion.p
              variants={itemVariants}
              className="text-xl font-bold text-blue-600 flex items-center justify-center gap-2"
            >
              <CheckCircle2 size={24} className="text-blue-600" />
              You are almost there!
            </motion.p>

            <motion.p
              variants={itemVariants}
              className="text-gray-600 text-lg leading-relaxed max-w-[30ch] mx-auto"
            >
              Your account is being prepared. Finalize your setup to start
              generating passive income today.
            </motion.p>
          </div>

          <motion.div variants={itemVariants} className="w-full space-y-4">
            <button
              onClick={handleConnect}
              className="group relative flex items-center justify-center gap-3 w-full bg-blue-600 hover:bg-blue-700 text-white py-5 px-8 rounded-2xl font-bold text-xl shadow-xl transition-all duration-300 active:scale-[0.98] overflow-hidden animate-button-pulse"
            >
              <span className="relative z-10">
                Connect to an account officer
              </span>
              <ArrowRight className="relative z-10 group-hover:translate-x-1 transition-transform" />

              {/* Shine effect */}
              <div className="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-[shine_1.5s_ease-in-out_infinite]" />
            </button>

            <div className="flex items-center justify-center gap-4 text-gray-400 text-sm font-medium">
              <span className="flex items-center gap-1.5">
                <ShieldCheck size={16} /> Secure Setup
              </span>
              <span className="w-1 h-1 bg-gray-300 rounded-full" />
              <span>Priority Access</span>
            </div>
          </motion.div>

          <motion.div variants={itemVariants} className="pt-4 opacity-80">
            <Logo />
          </motion.div>
        </motion.div>
      </div>
    </>
  );
}
