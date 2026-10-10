import { Container, CTA, Eyebrow } from "../primitives";
import { AUDIENCE } from "../data/landing";
import { motion } from "framer-motion";

export function WhoFor() {
  return (
    <section className="relative py-16">
      <Container>
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="mx-auto max-w-[800px] text-center"
        >
          <Eyebrow>Is It For You?</Eyebrow>
          <h2 className="mt-5 text-balance font-display text-[32px] font-extrabold leading-tight tracking-tight sm:text-[44px]">
            Is Quantum AI Profits Built for You?
          </h2>
        </motion.div>

        <div className="mx-auto mt-14 grid max-w-[920px] gap-4 sm:grid-cols-2">
          {AUDIENCE.map((a, i) => (
            <motion.div
              key={a}
              initial={{ opacity: 0, x: i % 2 === 0 ? -20 : 20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: i * 0.05 }}
              className="flex items-center gap-4 rounded-xl border border-line bg-surface px-5 py-4 transition-colors duration-200 hover:border-cyan/40"
            >
              <span className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-cyan/15 text-cyan ring-1 ring-cyan/30">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                  <path
                    d="M20 6L9 17l-5-5"
                    stroke="currentColor"
                    strokeWidth="2.6"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  />
                </svg>
              </span>
              <span className="text-pretty text-[15px] font-medium text-text/90">
                {a}
              </span>
            </motion.div>
          ))}
        </div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="mt-12 flex justify-center"
        >
          <CTA>Get Started for $250</CTA>
        </motion.div>
      </Container>
    </section>
  );
}
