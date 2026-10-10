import { Loader2 } from "lucide-react";

interface TradingViewLoadingOverlayProps {
  theme?: "light" | "dark";
}

export default function TradingViewLoadingOverlay({}: TradingViewLoadingOverlayProps) {
  return (
    <div
      className="absolute top-0 left-0 right-0 bottom-0 flex items-center justify-center"
      style={{
        position: "absolute",
        width: "100%",
        height: "100%",
        backgroundColor: "rgba(0, 0, 0, 0.85)",
        backdropFilter: "blur(4px)",
        zIndex: 99,
      }}
    >
      <div className="flex flex-col items-center gap-4">
        <Loader2 className="h-12 w-12 animate-spin text-blue-500" />
        <div className="text-center">
          <p className="text-lg font-semibold text-white">
            Live Trading Unavailable
          </p>
          <p className="text-sm mt-1 text-gray-300">
            Please wait while we prepare the trading environment
          </p>
        </div>
      </div>
    </div>
  );
}
