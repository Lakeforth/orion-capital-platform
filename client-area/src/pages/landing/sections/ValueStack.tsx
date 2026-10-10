import { Container, CTA, Eyebrow } from "../primitives";
import { motion } from "framer-motion";
import {
  VALUE_ITEMS,
  TOTAL_REAL_VALUE,
  EARLY_ACCESS_PRICE,
} from "../data/landing";

export function ValueStack() {
  return (
    <section
      id="get-started"
      className="relative border-y border-line bg-ink-2 py-16"
    >
      <div className="pointer-events-none absolute inset-0">
        <div className="absolute left-1/2 top-1/2 h-[460px] w-[760px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand/10 blur-[150px]" />
      </div>

      <Container className="relative !max-w-[1300px]">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="mx-auto max-w-[900px] text-center"
        >
          <Eyebrow>The Real Value</Eyebrow>
          <h2 className="mt-5 text-balance font-display text-[30px] font-extrabold leading-tight tracking-tight sm:text-[42px]">
            Everything Inside Quantum AI Profits, and What It's Actually Worth
          </h2>
        </motion.div>

        <div className="mx-auto mt-14 flex max-w-[1280px] flex-col items-center gap-10 lg:flex-row lg:items-start lg:gap-16">
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6, delay: 0.1 }}
            className="flex-1"
          >
            <img
              src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1781870039/Software_Pack_Mockup3_Transparent_BG_xqvh8m.png"
              alt="Quantum AI Profits Software Pack"
              className="h-auto w-full object-contain"
            />
          </motion.div>

          <motion.div
            initial={{ opacity: 0, x: 20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.8, delay: 0.2 }}
            className="w-full overflow-hidden rounded-2xl border border-line-2 bg-surface lg:flex-1"
          >
            <ul className="divide-y divide-line">
              {VALUE_ITEMS.map((it, i) => (
                <motion.li
                  key={it.name}
                  initial={{ opacity: 0, x: -10 }}
                  whileInView={{ opacity: 1, x: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.4, delay: 0.3 + i * 0.05 }}
                  className="flex items-center justify-between gap-4 px-6 py-5"
                >
                  <span className="flex items-center gap-3 text-pretty text-[15px] font-medium text-text/90">
                    <svg
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      className="shrink-0"
                    >
                      <path
                        d="M20 6L9 17l-5-5"
                        stroke="#2b7fff"
                        strokeWidth="2.6"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                    </svg>
                    {it.name}
                  </span>
                  <span className="shrink-0 font-mono text-sm text-muted">
                    Value:{" "}
                    <span className="text-text/70 line-through decoration-money/60">
                      {it.value}
                    </span>
                  </span>
                </motion.li>
              ))}
            </ul>

            <div className="border-t border-line-2 bg-surface-2 px-6 py-6">
              <div className="flex items-center justify-between">
                <span className="font-display text-sm font-semibold uppercase tracking-wider text-muted">
                  Total Real Value
                </span>
                <span className="font-display text-xl font-bold text-text/70 line-through decoration-money/50">
                  {TOTAL_REAL_VALUE}
                </span>
              </div>
              <div className="mt-4 flex items-center justify-between">
                <span className="font-display text-base font-bold text-text">
                  Get Started For
                </span>
                <span className="font-display text-4xl font-extrabold text-money">
                  {EARLY_ACCESS_PRICE}
                </span>
              </div>
            </div>
          </motion.div>
        </div>

        <motion.div
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6, delay: 0.4 }}
          className="mx-auto mt-12 flex max-w-[680px] flex-col items-center gap-9"
        >
          {/* <Countdown /> */}

          <div className="w-full max-w-md rounded-2xl border border-brand/20 bg-white p-6 shadow-[0_20px_50px_-12px_rgba(43,127,255,0.15)]">
            <div className="flex items-center justify-between mb-4">
              <span className="font-display text-sm font-bold uppercase tracking-wider text-brand">
                Limited Availability
              </span>
              <span className="flex items-center gap-1.5 font-mono text-sm font-bold text-money">
                <span className="h-2 w-2 animate-pulse rounded-full bg-money" />
                Live
              </span>
            </div>

            <div className="mb-2 flex items-end justify-between">
              <div className="font-display text-2xl font-black tracking-tight text-text">
                572{" "}
                <span className="text-base font-normal text-muted">/ 600</span>
              </div>
              <div className="mb-1 text-[13px] font-semibold text-muted">
                Seats filled
              </div>
            </div>

            <div className="h-3 w-full overflow-hidden rounded-full bg-ink-2 ring-1 ring-line">
              <motion.div
                initial={{ width: 0 }}
                whileInView={{ width: "95.33%" }}
                viewport={{ once: true }}
                transition={{ duration: 1.5, ease: "easeOut", delay: 0.6 }}
                className="h-full bg-gradient-to-r from-brand to-cyan"
              />
            </div>

            <p className="mt-4 text-center text-sm font-medium text-muted">
              <span className="text-text">572 out of 600 seats</span> have
              already been filled.
            </p>
          </div>

          <CTA
            className="!px-12 !py-5 !text-lg"
            sub="One-time payment · 180-day money back guarantee"
          >
            Get Started for $250
          </CTA>
        </motion.div>
      </Container>
    </section>
  );
}
