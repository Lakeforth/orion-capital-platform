import { motion } from "framer-motion";
import { Phone } from "lucide-react";
import Logo from "@/components/Logo";

export default function ThankYouPage1() {
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
        .phone-ring-1 {
          animation: phone-ring 1.5s cubic-bezier(.36,.07,.19,.97) infinite;
          transform-origin: center;
        }
        .call-wave-1 {
          position: absolute;
          border-radius: 50%;
          background: rgba(16, 185, 129, 0.4);
          animation: call-wave 2s ease-out infinite;
          z-index: 0;
        }
        .data-dot-1 {
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
        .glass-card-1 {
          background: #ffffff;
          border: 1px solid rgba(0, 0, 0, 0.05);
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
      `}</style>

      <div className="relative min-h-screen w-full flex items-center justify-center p-4 overflow-hidden bg-[#0a0f1e]">
        <div
          className="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat"
          style={{
            backgroundImage: `url('https://res.cloudinary.com/dyp8gtllq/image/upload/v1781118612/photo_2026-06-10_20-08-57_canth0.jpg')`,
          }}
        >
          <div className="absolute inset-0 bg-gradient-to-br from-[#0a0f1e]/85 via-[#0a0f1e]/50 to-[#0a0f1e]/85" />
        </div>

        <div className="absolute inset-0 z-0 pointer-events-none overflow-hidden">
          <motion.div
            animate={{ scale: [1, 1.2, 1], opacity: [0.1, 0.2, 0.1] }}
            transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
            className="absolute -top-1/4 -left-1/4 w-1/2 h-1/2 bg-emerald-500/20 rounded-full blur-[120px]"
          />
          <motion.div
            animate={{ scale: [1, 1.3, 1], opacity: [0.1, 0.15, 0.1] }}
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
          className="glass-card-1 relative z-10 rounded-3xl px-6 py-10 sm:px-8 sm:py-12 flex flex-col items-center gap-6 sm:gap-8 w-full max-w-lg text-center"
        >
          <div className="flex flex-col sm:flex-row items-center justify-center gap-6 sm:gap-10 w-full">
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

            <motion.div
              variants={itemVariants}
              className="relative h-2 w-20 sm:w-24 hidden sm:block"
            >
              <div className="absolute top-1/2 left-0 w-full h-[1px] bg-emerald-500/20 -translate-y-1/2" />
              <div className="data-dot-1" style={{ animationDelay: "0s" }} />
              <div className="data-dot-1" style={{ animationDelay: "0.6s" }} />
              <div className="data-dot-1" style={{ animationDelay: "1.2s" }} />
            </motion.div>

            <div className="sm:hidden h-12 w-[1px] bg-gradient-to-b from-emerald-500/50 to-transparent relative">
              <motion.div
                animate={{ y: [0, 40, 0], opacity: [0, 1, 0] }}
                transition={{ duration: 1.5, repeat: Infinity }}
                className="absolute left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-emerald-500 rounded-full shadow-[0_0_8px_#10b981]"
              />
            </div>

            <motion.div
              variants={itemVariants}
              className="relative flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24"
            >
              <div
                className="call-wave-1 w-12 h-12 sm:w-16 sm:h-16"
                style={{ animationDelay: "0s" }}
              />
              <div
                className="call-wave-1 w-12 h-12 sm:w-16 sm:h-16"
                style={{ animationDelay: "0.6s" }}
              />
              <div
                className="call-wave-1 w-12 h-12 sm:w-16 sm:h-16"
                style={{ animationDelay: "1.2s" }}
              />
              <div className="absolute inset-0 bg-emerald-500/10 blur-2xl rounded-full animate-pulse" />
              <div className="relative phone-ring-1 z-10">
                <Phone
                  size={40}
                  className="text-emerald-500 fill-emerald-500/20 sm:size-[48px]"
                  strokeWidth={2.5}
                />
              </div>
            </motion.div>
          </div>

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

          <motion.div variants={itemVariants} className="pt-2">
            <Logo />
          </motion.div>
        </motion.div>
      </div>
    </>
  );
}
