"use client";

import { useEffect, useState } from "react";
import { TIMER_KEY, TIMER_DURATION_MS } from "../data/landing";

export interface TimeLeft {
  days: number;
  hours: number;
  minutes: number;
  seconds: number;
  expired: boolean;
}

/**
 * Per-visitor 48h countdown.
 *
 * - On first visit, stamps `qap_timer_start` in localStorage.
 * - On return visits, resumes from the stored timestamp.
 * - A different device/browser starts its own fresh 48h window.
 * - At zero it stays at 00:00:00:00 (expired) and never auto-resets.
 *
 * Returns `null` until mounted on the client (avoids hydration mismatch).
 */
export function useCountdown(): TimeLeft | null {
  const [remaining, setRemaining] = useState<number | null>(null);

  useEffect(() => {
    let raw = localStorage.getItem(TIMER_KEY);
    if (!raw) {
      raw = String(Date.now());
      localStorage.setItem(TIMER_KEY, raw);
    }
    const start = parseInt(raw, 10);

    const tick = () =>
      setRemaining(Math.max(0, start + TIMER_DURATION_MS - Date.now()));

    tick();
    const id = window.setInterval(tick, 1000);
    return () => window.clearInterval(id);
  }, []);

  if (remaining === null) return null;

  const totalSeconds = Math.floor(remaining / 1000);
  return {
    days: Math.floor(totalSeconds / 86400),
    hours: Math.floor((totalSeconds % 86400) / 3600),
    minutes: Math.floor((totalSeconds % 3600) / 60),
    seconds: totalSeconds % 60,
    expired: remaining <= 0,
  };
}
