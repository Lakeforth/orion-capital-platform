import { Container, Eyebrow } from "../primitives";
import { STEPS } from "../data/landing";
import { motion } from "framer-motion";

function StepIcon({ index }: { index: number }) {
  // Icons matching the "How to Get Started" flow
  switch (index) {
    case 0: // Account
      return (
        <svg
          width="32"
          height="32"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.5"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
          <circle cx="12" cy="7" r="4" />
        </svg>
      );
    case 1: // Fund
      return (
        <svg
          width="32"
          height="32"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.5"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <rect x="2" y="5" width="20" height="14" rx="2" />
          <line x1="2" y1="10" x2="22" y2="10" />
          <path d="M16 14h.01M12 14h.01M8 14h.01" />
        </svg>
      );
    case 2: // Activate (Robot)
      return (
        <svg
          width="32"
          height="32"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.5"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <rect x="3" y="11" width="18" height="10" rx="2" />
          <circle cx="12" cy="5" r="2" />
          <path d="M12 7v4" />
          <line x1="8" y1="16" x2="8" y2="16" />
          <line x1="16" y1="16" x2="16" y2="16" />
        </svg>
      );
    case 3: // Withdraw
      return (
        <svg
          width="32"
          height="32"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.5"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
          <polyline points="22 4 12 14.01 9 11.01" />
        </svg>
      );
    default:
      return null;
  }
}

export function HowItWorks() {
  return (
    <section id="how-it-works" className="relative py-16 overflow-hidden">
      {/* Subtle background glow */}
      <div className="pointer-events-none absolute inset-0">
        <div className="absolute left-1/2 top-1/2 h-[500px] w-[800px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand/10 blur-[120px]" />
      </div>

      <Container className="relative">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="mx-auto max-w-[800px] text-center"
        >
          <Eyebrow>How to Get Started</Eyebrow>
          <h2 className="mt-5 text-balance font-display text-[32px] font-extrabold leading-tight tracking-tight text-slate-900 sm:text-[44px]">
            You Only Need to Take 4 Simple Steps
          </h2>
        </motion.div>

        <div className="mt-20 grid gap-12 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
          {STEPS.map((step, i) => (
            <div
              key={step.title}
              className="relative flex flex-col items-center text-center group"
            >
              {/* Arrow for desktop */}
              {i < STEPS.length - 1 && (
                <div className="absolute left-[calc(50%+68px)] top-12 hidden w-[calc(100%-120px)] -translate-y-1/2 lg:block">
                  <svg
                    width="100%"
                    height="24"
                    viewBox="0 0 100 24"
                    fill="none"
                    preserveAspectRatio="none"
                  >
                    <path
                      d="M0 12H98"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeDasharray="4 4"
                      className="text-brand/60"
                    />
                    <path
                      d="M92 6L98 12L92 18"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      className="text-brand/60"
                    />
                  </svg>
                </div>
              )}

              <motion.div
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: i * 0.1 }}
                className="flex flex-col items-center"
              >
                {/* Circle Container */}
                <div className="relative mb-8 grid h-24 w-24 place-items-center rounded-full bg-white text-brand shadow-[0_0_30px_rgba(255,255,255,0.1)] transition-all duration-300 group-hover:scale-110 group-hover:shadow-[0_0_40px_rgba(43,127,255,0.3)]">
                  <StepIcon index={i} />

                  {/* Step Number Badge */}
                  <span className="absolute -right-1 -top-1 flex h-7 w-7 items-center justify-center rounded-full bg-brand font-mono text-xs font-bold text-white ring-4 ring-white">
                    {i + 1}
                  </span>
                </div>

                <h3 className="font-display text-xl font-bold leading-snug text-slate-900">
                  {step.title}
                </h3>
                <p className="mt-4 text-pretty text-[15px] leading-relaxed text-slate-600">
                  {step.body}
                </p>
              </motion.div>
            </div>
          ))}
        </div>
      </Container>
    </section>
  );
}
