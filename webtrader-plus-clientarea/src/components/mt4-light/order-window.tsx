import { useEffect, useRef, useState, type PointerEvent as ReactPointerEvent } from "react";
import { X } from "lucide-react";
import useDarkModeStore from "@/store/darkModeStore";
import useAssetStore from "@/store/assetStore";
import { TradingInterface } from "../mt4/trading-interface-copy";

const WIDTH = 268;

/**
 * MT4's Order window: floating and non-modal (no backdrop), so the chart,
 * Market Watch and positions stay visible and usable while it is open. It
 * opens over the chart's right edge instead of dropping down over Market
 * Watch, and can be dragged by its title bar. X or Esc closes it.
 */
export function OrderWindow({ open, onClose }: { open: boolean; onClose: () => void }) {
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);
  const symbol = useAssetStore((state) => state.activeAsset?.symbol_display);
  const [position, setPosition] = useState<{ x: number; y: number } | null>(null);
  const grab = useRef<{ dx: number; dy: number } | null>(null);
  const windowRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;
    const onKey = (event: KeyboardEvent) => {
      if (event.key === "Escape") onClose();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [open, onClose]);

  if (!open) return null;

  const width = Math.min(WIDTH, window.innerWidth - 16);
  const startDrag = (event: ReactPointerEvent<HTMLDivElement>) => {
    if ((event.target as HTMLElement).closest("button")) return;
    const rect = windowRef.current?.getBoundingClientRect();
    if (!rect) return;
    grab.current = { dx: event.clientX - rect.left, dy: event.clientY - rect.top };
    event.currentTarget.setPointerCapture(event.pointerId);
  };
  const drag = (event: ReactPointerEvent<HTMLDivElement>) => {
    if (!grab.current) return;
    setPosition({
      x: Math.min(Math.max(0, event.clientX - grab.current.dx), window.innerWidth - width),
      y: Math.min(Math.max(0, event.clientY - grab.current.dy), window.innerHeight - 40),
    });
  };
  const endDrag = () => {
    grab.current = null;
  };

  return (
    <div
      ref={windowRef}
      role="dialog"
      aria-label={`Order${symbol ? ` ${symbol}` : ""}`}
      className={`fixed z-50 border shadow-2xl rounded-sm overflow-hidden ${
        isDarkMode ? "bg-slate-800 border-slate-600" : "bg-white border-slate-400"
      }`}
      style={{ width, ...(position ? { left: position.x, top: position.y } : { right: 16, top: 118 }) }}
    >
      <div
        onPointerDown={startDrag}
        onPointerMove={drag}
        onPointerUp={endDrag}
        onPointerCancel={endDrag}
        className={`h-7 px-2 flex items-center justify-between cursor-move select-none touch-none border-b ${
          isDarkMode
            ? "bg-slate-700 border-slate-600 text-slate-100"
            : "bg-gradient-to-b from-[#E6EEF6] to-[#D2E0EA] border-slate-300 text-slate-800"
        }`}
      >
        <span className="text-[12px] font-bold truncate">Order{symbol ? ` — ${symbol}` : ""}</span>
        <button
          type="button"
          onClick={onClose}
          aria-label="Close order window"
          className={`h-5 w-5 flex items-center justify-center rounded-sm ${
            isDarkMode ? "hover:bg-slate-600" : "hover:bg-red-500 hover:text-white"
          }`}
        >
          <X size={13} />
        </button>
      </div>
      <div className="max-h-[calc(100vh-150px)] overflow-y-auto">
        <TradingInterface />
      </div>
    </div>
  );
}
