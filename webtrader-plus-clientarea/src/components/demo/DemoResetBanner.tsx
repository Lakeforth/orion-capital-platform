import { useEffect, useRef, useState } from "react";
import { Timer } from "lucide-react";
import useSiteSettingsStore from "@/store/siteSettingStore";

function formatCountdown(seconds: number): string {
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  return [h, m, s].map((v) => String(v).padStart(2, "0")).join(":");
}

export default function DemoResetBanner() {
  const settings = useSiteSettingsStore((state) => state.settings);
  const fetchSettings = useSiteSettingsStore((state) => state.fetchSettings);
  const [remaining, setRemaining] = useState<number>(0);
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
  }, [settings?.is_demo, settings?.demo?.next_reset_at, fetchSettings]);

  if (!settings?.is_demo) return null;

  return (
    <div className="w-full bg-amber-500/15 border-b border-amber-500/25 px-4 py-1.5 flex items-center justify-center gap-2 text-amber-400 text-xs font-medium">
      <Timer className="w-3.5 h-3.5 flex-shrink-0" />
      <span>Demo account resets in <span className="font-mono font-bold">{formatCountdown(remaining)}</span></span>
    </div>
  );
}
