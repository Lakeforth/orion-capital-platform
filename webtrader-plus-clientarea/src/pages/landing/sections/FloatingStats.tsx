import { Container } from "../primitives";
import { STATS } from "../data/landing";

export function FloatingStats() {
  return (
    <Container className="relative z-20 -mb-10 translate-y-12">
      <div className="grid grid-cols-1 divide-y divide-white/10 rounded-2xl border border-white/10 bg-ink p-2 shadow-[0_30px_80px_-28px_rgba(13,38,76,0.45)] sm:grid-cols-2 sm:divide-x sm:divide-y-0">
        {STATS.map((s) => (
          <div
            key={s.label}
            className="flex flex-col items-center gap-1 px-8 py-7 text-center"
          >
            <span className="font-display text-4xl font-extrabold tracking-tight text-white sm:text-5xl">
              {s.value}
            </span>
            <span className="font-mono text-[12px] uppercase tracking-[0.18em] text-white/55">
              {s.label}
            </span>
          </div>
        ))}
      </div>
    </Container>
  );
}
