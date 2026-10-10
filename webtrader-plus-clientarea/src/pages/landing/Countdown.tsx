"use client";

import { Fragment } from "react";
import { useCountdown } from "./hooks/useCountdown";

const pad = (n: number) => String(n).padStart(2, "0");

export function Countdown() {
  const t = useCountdown();
  if (t === null) return null; // not mounted yet

  const units = [
    { v: t.days, l: "Days" },
    { v: t.hours, l: "Hours" },
    { v: t.minutes, l: "Mins" },
    { v: t.seconds, l: "Secs" },
  ];

  return (
    <div className="flex flex-col items-center gap-3">
      <span
        className={`font-mono text-[12px] uppercase tracking-[0.22em] ${
          t.expired ? "text-muted" : "text-gold"
        }`}
      >
        {t.expired ? "Early access window closed" : "Early access price ends in"}
      </span>
      <div className="flex items-center gap-2 sm:gap-3">
        {units.map((u, i) => (
          <Fragment key={u.l}>
            <div className="flex w-[64px] flex-col items-center rounded-xl border border-white/10 bg-ink px-2 py-3 sm:w-[76px]">
              <span
                className={`font-mono text-3xl font-bold tabular-nums sm:text-4xl ${
                  t.expired ? "text-white/40" : "text-white"
                }`}
              >
                {pad(u.v)}
              </span>
              <span className="mt-1 font-mono text-[10px] uppercase tracking-widest text-white/40">
                {u.l}
              </span>
            </div>
            {i < units.length - 1 && (
              <span className="font-mono text-2xl text-text/20">:</span>
            )}
          </Fragment>
        ))}
      </div>
    </div>
  );
}
