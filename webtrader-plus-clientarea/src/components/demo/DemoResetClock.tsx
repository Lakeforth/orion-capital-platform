import { useEffect, useRef, useState } from "react";
import { Timer } from "lucide-react";
import useSiteSettingsStore from "@/store/siteSettingStore";

function formatCountdown(seconds: number): string {
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  return [h, m, s].map((v) => String(v).padStart(2, "0")).join(":");
}

export default function DemoResetClock() {
  const settings = useSiteSettingsStore((state) => state.settings);
  const fetchSettings = useSiteSettingsStore((state) => state.fetchSettings);
  const [remaining, setRemaining] = useState(0);
  const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    if (!settings?.is_demo || !settings.demo) return;

    setRemaining(Math.max(0, settings.demo.remaining_seconds));

    intervalRef.current = setInterval(() => {
      setRemaining((prev) => {
        if (prev <= 1) {
          fetchSettings();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => {
      if (intervalRef.current) clearInterval(intervalRef.current);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [settings?.is_demo, settings?.demo?.next_reset_at]);

  if (!settings?.is_demo) return null;

  return (
    <div className="fixed bottom-6 left-6 z-50 flex items-center gap-2 bg-[#121A33] border border-white/10 rounded-full px-4 py-2.5 shadow-lg shadow-black/30">
      <Timer className="w-3.5 h-3.5 text-amber-400 flex-shrink-0" />
      <span className="text-white/70 text-xs">Resets in</span>
      <span className="text-white text-xs font-mono font-bold tracking-wider">{formatCountdown(remaining)}</span>
    </div>
  );
}
