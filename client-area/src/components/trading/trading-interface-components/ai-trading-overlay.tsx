import { motion, AnimatePresence } from "framer-motion";
import {
  Bot,
  Cpu,
  Zap,
  Activity,
  ShieldCheck,
  Database,
  Search,
  Minimize2,
  Maximize2,
  type LucideIcon,
} from "lucide-react";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";

interface AITradingOverlayProps {
  isActive: boolean;
  containerType?: "full" | "chart";
}

export default function AITradingOverlay({
  isActive,
  containerType = "full",
}: AITradingOverlayProps) {
  const [displayText, setDisplayText] = useState("AI BOT INITIALIZING...");
  const [progress, setProgress] = useState(0);
  const [isMinimized, setIsMinimized] = useState(false);

  const messages = [
    "SCANNING GLOBAL MARKETS...",
    "ANALYZING ASSET VOLATILITY...",
    "DETECTING PRICE PATTERNS...",
    "CALCULATING ENTRY PROBABILITIES...",
    "OPTIMIZING RISK PARAMETERS...",
    "AI ENGINE RUNNING AT 100%...",
    "EXECUTING NEURAL NETWORK MODELS...",
    "MONITORING REAL-TIME DATA STREAMS...",
  ];

  useEffect(() => {
    if (!isActive) return;

    let messageIndex = 0;
    const interval = setInterval(() => {
      messageIndex = (messageIndex + 1) % messages.length;
      setDisplayText(messages[messageIndex]);
    }, 3000);

    const progressInterval = setInterval(() => {
      setProgress((prev) => (prev >= 100 ? 0 : prev + 1));
    }, 100);

    return () => {
      clearInterval(interval);
      clearInterval(progressInterval);
    };
  }, [isActive]);

  return (
    <AnimatePresence>
      {isActive && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{
            opacity: 1,
            width: isMinimized ? "320px" : "100%",
            height: isMinimized ? "180px" : "100%",
            left: isMinimized ? "24px" : "0px",
            bottom: isMinimized ? "24px" : "0px",
            right: isMinimized ? "auto" : "0px",
            top: isMinimized ? "auto" : "0px",
          }}
          exit={{ opacity: 0 }}
          className={`z-[100] flex items-center justify-center overflow-hidden transition-all duration-500 ease-in-out ${
            containerType === "full" ? "fixed" : "absolute"
          } ${
            isMinimized
              ? "rounded-2xl border-2 border-trading-green/30 bg-black/80 backdrop-blur-md shadow-[0_0_30px_rgba(0,255,136,0.2)] pointer-events-auto"
              : "pointer-events-none"
          }`}
        >
          {/* Controls - Always clickable */}
          <div className="absolute top-6 right-6 z-[110] pointer-events-auto">
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setIsMinimized(!isMinimized)}
              className="text-trading-green hover:bg-trading-green/20 bg-black/40 backdrop-blur-sm rounded-full border border-trading-green/20"
            >
              {isMinimized ? (
                <Maximize2 className="h-5 w-5" />
              ) : (
                <Minimize2 className="h-5 w-5" />
              )}
            </Button>
          </div>

          {/* Futuristic Background Overlay - Only show when NOT minimized */}
          {!isMinimized && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="absolute inset-0 bg-black/40 backdrop-blur-[2px]"
            >
              {/* Grid Pattern */}
              <div
                className="absolute inset-0 opacity-10"
                style={{
                  backgroundImage:
                    "linear-gradient(to right, #00ff88 1px, transparent 1px), linear-gradient(to bottom, #00ff88 1px, transparent 1px)",
                  backgroundSize: "40px 40px",
                }}
              />

              {/* Scanning Line */}
              <motion.div
                animate={{ top: ["0%", "100%"] }}
                transition={{ duration: 4, repeat: Infinity, ease: "linear" }}
                className="absolute left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-trading-green/50 to-transparent shadow-[0_0_15px_#00ff88]"
              />
            </motion.div>
          )}

          {/* Central HUD */}
          <motion.div
            layout
            className={`relative z-10 flex flex-col items-center justify-center w-full ${
              isMinimized ? "p-4" : "p-8 max-w-4xl"
            }`}
          >
            {/* Top Scanning Header - Hide when minimized */}
            {!isMinimized && (
              <motion.div
                initial={{ width: 0, opacity: 0 }}
                animate={{ width: "100%", opacity: 1 }}
                className="absolute top-0 flex items-center justify-between px-4 py-1 border-t border-trading-green/30 font-mono text-[10px] text-trading-green/40 w-full"
              >
                <span>SYSTEM: AI_CORE_v2.0.4</span>
                <div className="flex gap-4">
                  <span>LATENCY: 12ms</span>
                  <span>UPTIME: 99.9%</span>
                </div>
              </motion.div>
            )}

            {/* Rings - Scale down when minimized */}
            {!isMinimized && (
              <div className={containerType === "chart" ? "scale-75" : ""}>
                <motion.div
                  animate={{ rotate: 360 }}
                  transition={{
                    duration: 20,
                    repeat: Infinity,
                    ease: "linear",
                  }}
                  className="absolute w-[400px] h-[400px] border border-trading-green/20 rounded-full border-dashed -translate-x-1/2 -translate-y-1/2 left-1/2 top-1/2"
                />

                <motion.div
                  animate={{ rotate: -360 }}
                  transition={{
                    duration: 15,
                    repeat: Infinity,
                    ease: "linear",
                  }}
                  className="absolute w-[320px] h-[320px] border-2 border-trading-green/10 rounded-full border-t-trading-green/40 -translate-x-1/2 -translate-y-1/2 left-1/2 top-1/2"
                />

                <motion.div
                  animate={{ rotate: 360 }}
                  transition={{
                    duration: 10,
                    repeat: Infinity,
                    ease: "linear",
                  }}
                  className="absolute w-[240px] h-[240px] border border-trading-green/30 rounded-full border-l-transparent border-r-transparent -translate-x-1/2 -translate-y-1/2 left-1/2 top-1/2"
                />
              </div>
            )}

            {/* AI Bot Icon Container */}
            <motion.div
              layout
              className={`relative flex items-center justify-center bg-black/60 rounded-full border border-trading-green/50 shadow-[0_0_30px_rgba(0,255,136,0.2)] ${
                isMinimized
                  ? "w-20 h-20"
                  : containerType === "chart"
                    ? "w-32 h-32"
                    : "w-48 h-48"
              }`}
            >
              <motion.div
                animate={{
                  scale: [1, 1.1, 1],
                  boxShadow: [
                    "0 0 20px rgba(0,255,136,0.2)",
                    "0 0 40px rgba(0,255,136,0.4)",
                    "0 0 20px rgba(0,255,136,0.2)",
                  ],
                }}
                transition={{ duration: 2, repeat: Infinity }}
                className="flex items-center justify-center"
              >
                <Bot
                  className={`text-trading-green ${isMinimized ? "w-10 h-10" : containerType === "chart" ? "w-16 h-16" : "w-24 h-24"}`}
                />
              </motion.div>

              {/* Orbiting Icons - Only show when NOT minimized */}
              {!isMinimized && (
                <>
                  <OrbitingIcon
                    Icon={Cpu}
                    delay={0}
                    distance={containerType === "chart" ? 80 : 110}
                  />
                  <OrbitingIcon
                    Icon={Zap}
                    delay={1.5}
                    distance={containerType === "chart" ? 80 : 110}
                  />
                  <OrbitingIcon
                    Icon={Activity}
                    delay={3}
                    distance={containerType === "chart" ? 80 : 110}
                  />
                  <OrbitingIcon
                    Icon={Database}
                    delay={4.5}
                    distance={containerType === "chart" ? 80 : 110}
                  />
                </>
              )}
            </motion.div>

            {/* Data Readouts */}
            <div className={`${isMinimized ? "mt-4" : "mt-12"} text-center`}>
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="flex flex-col items-center gap-2"
              >
                <div
                  className={`flex items-center gap-2 text-trading-green font-mono tracking-[0.2em] font-bold ${
                    isMinimized ? "text-[10px]" : "text-xl"
                  }`}
                >
                  <span className="w-2 h-2 rounded-full bg-trading-green animate-pulse" />
                  {displayText}
                </div>

                {/* Progress Bar */}
                <div
                  className={`h-1 bg-black/40 rounded-full overflow-hidden border border-trading-green/20 mt-2 ${
                    isMinimized ? "w-40" : "w-64"
                  }`}
                >
                  <motion.div
                    className="h-full bg-trading-green shadow-[0_0_10px_#00ff88]"
                    initial={{ width: "0%" }}
                    animate={{ width: `${progress}%` }}
                    transition={{ ease: "linear" }}
                  />
                </div>

                {/* Detail HUD - Hide when minimized */}
                {!isMinimized && (
                  <div className="grid grid-cols-2 gap-8 mt-6 font-mono text-[10px] text-trading-green/60 tracking-widest uppercase">
                    <div className="flex flex-col items-start gap-1">
                      <div className="flex items-center gap-1">
                        <Search className="w-3 h-3" />
                        <span>Scan Status</span>
                      </div>
                      <div className="text-trading-green font-bold">
                        COMPLETED
                      </div>
                    </div>
                    <div className="flex flex-col items-end gap-1">
                      <div className="flex items-center gap-1">
                        <ShieldCheck className="w-3 h-3" />
                        <span>Secure Link</span>
                      </div>
                      <div className="text-trading-green font-bold">
                        ENCRYPTED
                      </div>
                    </div>
                  </div>
                )}
              </motion.div>
            </div>
          </motion.div>

          {/* Corner HUD Elements - Hide when minimized */}
          {!isMinimized && (
            <>
              <HUDCorner position="top-left" />
              <HUDCorner position="top-right" />
              <HUDCorner position="bottom-left" />
              <HUDCorner position="bottom-right" />

              {/* Side HUD Panels */}
              <SidePanel side="left" />
              <SidePanel side="right" />

              {/* Floating Data Particles */}
              <div className="absolute inset-0 pointer-events-none">
                {[...Array(15)].map((_, i) => (
                  <DataParticle key={i} />
                ))}
              </div>
            </>
          )}
        </motion.div>
      )}
    </AnimatePresence>
  );
}

function OrbitingIcon({
  Icon,
  delay,
  distance,
}: {
  Icon: LucideIcon;
  delay: number;
  distance: number;
}) {
  return (
    <motion.div
      animate={{ rotate: 360 }}
      transition={{ duration: 8, repeat: Infinity, ease: "linear", delay }}
      className="absolute"
      style={{ width: distance * 2, height: distance * 2 }}
    >
      <div className="absolute top-0 left-1/2 -translate-x-1/2 bg-black/80 p-2 rounded-lg border border-trading-green/30 text-trading-green shadow-[0_0_10px_rgba(0,255,136,0.2)]">
        <Icon className="w-4 h-4" />
      </div>
    </motion.div>
  );
}

function HUDCorner({
  position,
}: {
  position: "top-left" | "top-right" | "bottom-left" | "bottom-right";
}) {
  const baseClasses =
    "absolute w-24 h-24 border-trading-green/40 pointer-events-none";
  const posClasses = {
    "top-left": "top-4 left-4 border-t-2 border-l-2 rounded-tl-xl",
    "top-right": "top-4 right-4 border-t-2 border-r-2 rounded-tr-xl",
    "bottom-left": "bottom-4 left-4 border-b-2 border-l-2 rounded-bl-xl",
    "bottom-right": "bottom-4 right-4 border-b-2 border-r-2 rounded-br-xl",
  };

  return (
    <div className={`${baseClasses} ${posClasses[position]}`}>
      <div
        className={`absolute w-2 h-2 bg-trading-green ${
          position === "top-left"
            ? "-top-1 -left-1"
            : position === "top-right"
              ? "-top-1 -right-1"
              : position === "bottom-left"
                ? "-bottom-1 -left-1"
                : "-bottom-1 -right-1"
        }`}
      />
    </div>
  );
}

function SidePanel({ side }: { side: "left" | "right" }) {
  return (
    <motion.div
      initial={{ opacity: 0, x: side === "left" ? -50 : 50 }}
      animate={{ opacity: 1, x: 0 }}
      className={`absolute top-1/2 -translate-y-1/2 ${side === "left" ? "left-8" : "right-8"} hidden lg:flex flex-col gap-8 font-mono text-[10px] text-trading-green/40`}
    >
      {[...Array(4)].map((_, i) => (
        <div
          key={i}
          className={`flex flex-col ${side === "left" ? "items-start" : "items-end"} gap-1`}
        >
          <div className="w-12 h-[1px] bg-trading-green/30" />
          <div className="flex items-center gap-2">
            <span className="text-trading-green font-bold">0{i + 1}</span>
            <span className="animate-pulse">
              {Math.random().toString(16).substring(2, 8).toUpperCase()}
            </span>
          </div>
          <div className="flex gap-1">
            {[...Array(5)].map((_, j) => (
              <div
                key={j}
                className={`w-1 h-1 ${Math.random() > 0.5 ? "bg-trading-green/60" : "bg-trading-green/10"}`}
              />
            ))}
          </div>
        </div>
      ))}
    </motion.div>
  );
}

function DataParticle() {
  const x = Math.random() * 100;
  const duration = 10 + Math.random() * 20;
  const delay = Math.random() * -20;

  return (
    <motion.div
      initial={{ x: `${x}%`, y: "110%", opacity: 0 }}
      animate={{
        y: "-10%",
        opacity: [0, 0.5, 0],
        x: [`${x}%`, `${x + (Math.random() * 10 - 5)}%`],
      }}
      transition={{
        duration,
        repeat: Infinity,
        delay,
        ease: "linear",
      }}
      className="absolute text-[8px] font-mono text-trading-green/40 whitespace-nowrap"
    >
      {Math.random().toString(16).substring(2, 10).toUpperCase()}
    </motion.div>
  );
}
