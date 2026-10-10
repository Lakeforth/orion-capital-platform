import type { ReactNode } from "react";
import { GET_STARTED_ANCHOR } from "./data/landing";
import { motion } from "framer-motion";
import GlobalLogo from "@/components/Logo";
import { useLandingContext } from "./LandingContext";

/* ---------------------------------- Layout --------------------------------- */
export function Container({
  className = "",
  children,
}: {
  className?: string;
  children: ReactNode;
}) {
  return (
    <div className={`mx-auto w-full max-w-[1160px] px-6 ${className}`}>
      {children}
    </div>
  );
}

/* ----------------------------------- CTA ----------------------------------- */
export function CTA({
  children,
  className = "",
  sub,
  onClick,
}: {
  children: ReactNode;
  className?: string;
  sub?: string;
  onClick?: () => void;
}) {
  const { uuid } = useLandingContext();
  const href = uuid ? `${GET_STARTED_ANCHOR}?uuid=${uuid}` : GET_STARTED_ANCHOR;

  return (
    <div className="relative inline-flex items-center justify-center">
      {/* Pulsing Outside Div */}
      <motion.div
        animate={{
          top: ["-4px", "-12px", "-4px"],
          left: ["-4px", "-12px", "-4px"],
          right: ["-4px", "-12px", "-4px"],
          bottom: ["-4px", "-12px", "-4px"],
          opacity: [0.5, 0.2, 0.5],
        }}
        transition={{
          duration: 3,
          repeat: Infinity,
          ease: "easeInOut",
        }}
        className="absolute rounded-2xl bg-gradient-to-r from-brand to-cyan opacity-50"
      />

      <a
        href={href}
        onClick={onClick}
        className={`group relative z-10 inline-flex flex-col items-center justify-center rounded-xl bg-gradient-to-r from-brand to-cyan px-9 py-4 text-center font-display text-base font-bold text-white transition-all duration-200 hover:shadow-lg hover:shadow-brand/20 active:scale-[0.98] ${className}`}
      >
        <span className="relative z-10 flex items-center gap-2">
          {children}
          <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            className="transition-transform duration-200 group-hover:translate-x-0.5"
          >
            <path
              d="M5 12h14M13 6l6 6-6 6"
              stroke="currentColor"
              strokeWidth="2.2"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
        </span>
        {sub && (
          <span className="relative z-10 mt-0.5 font-body text-[11px] font-semibold tracking-wide text-white/80">
            {sub}
          </span>
        )}
      </a>
    </div>
  );
}

/* --------------------------------- Eyebrow --------------------------------- */
export function Eyebrow({ children }: { children: ReactNode }) {
  return (
    <span className="inline-flex items-center gap-2 rounded-full border border-brand/25 bg-white px-3.5 py-1.5 font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-brand shadow-[0_8px_24px_-10px_rgba(43,127,255,0.55)]">
      <span className="h-1.5 w-1.5 rounded-full bg-brand" />
      {children}
    </span>
  );
}

/* ---------------------------------- Stars ---------------------------------- */
export function Stars({
  size = 18,
  className = "",
  rating = 5,
}: {
  size?: number;
  className?: string;
  rating?: number;
}) {
  return (
    <div className={`flex gap-1 ${className}`}>
      {[0, 1, 2, 3, 4].map((i) => {
        const fillAmount = Math.max(0, Math.min(100, (rating - i) * 100));

        return (
          <span
            key={i}
            className="relative grid place-items-center rounded-[3px] bg-[#dcdce6]"
            style={{ width: size + 6, height: size + 6 }}
          >
            {/* Green background for the rating */}
            <div
              className="absolute left-0 top-0 h-full bg-[#00b67a] rounded-[3px]"
              style={{ width: `${fillAmount}%` }}
            />
            <svg
              width={size}
              height={size}
              viewBox="0 0 24 24"
              fill="#fff"
              className="relative z-10"
            >
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
          </span>
        );
      })}
    </div>
  );
}

/* ------------------------------- Placeholder ------------------------------- */
export function Placeholder({
  label,
  className = "",
  ratio,
}: {
  label: string;
  className?: string;
  ratio?: string;
}) {
  return (
    <div
      className={`ph-stripes relative grid place-items-center rounded-2xl border border-dashed border-line-2 bg-surface ${className}`}
      style={ratio ? { aspectRatio: ratio } : undefined}
    >
      <span className="rounded-md bg-ink/75 px-3 py-1.5 font-mono text-[11px] uppercase tracking-widest text-white/70">
        {label}
      </span>
    </div>
  );
}

/* ---------------------------------- Logo ----------------------------------- */
export function Logo() {
  return <GlobalLogo />;
}
