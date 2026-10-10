import { Container, CTA, Eyebrow } from "../primitives";
import { FEATURES, type FeatureIconName } from "../data/landing";
import { motion } from "framer-motion";

function FeatureIcon({ name }: { name: FeatureIconName }) {
  switch (name) {
    case "bolt":
      return (
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
          <path
            d="M13 2L4 14h6l-1 8 9-12h-6l1-8z"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinejoin="round"
          />
        </svg>
      );
    case "bank":
      return (
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
          <g
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <path d="M3 10l9-6 9 6" />
            <path d="M5 10v8M19 10v8M9 10v8M15 10v8M3 21h18" />
          </g>
        </svg>
      );
    case "clock":
      return (
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
          <g
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3.5 2" />
          </g>
        </svg>
      );
  }
}

export function Features() {
  return (
    <section
      id="features"
      className="relative border-y border-line bg-ink-2 py-16 pt-20"
    >
      <Container>
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="mx-auto max-w-[800px] text-center"
        >
          <Eyebrow>The Difference</Eyebrow>
          <h2 className="mt-5 text-balance font-display text-[32px] font-extrabold leading-tight tracking-tight text-text sm:text-[44px]">
            See Why Quantum AI Profits Is Different
          </h2>
        </motion.div>

        <div className="mt-14 grid gap-5 md:grid-cols-3">
          {FEATURES.map((f, i) => (
            <motion.div
              key={f.title}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: i * 0.1 }}
              className="group relative overflow-hidden rounded-2xl border border-line bg-surface p-7 transition-colors duration-300 hover:border-line-2"
            >
              <div className="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-brand/10 opacity-0 blur-2xl transition-opacity duration-300 group-hover:opacity-100" />
              <div className="mb-5 grid h-12 w-12 place-items-center rounded-xl bg-gradient-to-br from-brand/20 to-cyan/10 text-cyan ring-1 ring-line-2">
                <FeatureIcon name={f.icon} />
              </div>
              <h3 className="font-display text-xl font-bold leading-snug text-text">
                {f.title}
              </h3>
              <p className="mt-3 text-pretty text-[15px] leading-relaxed text-muted">
                {f.body}
              </p>
              <span className="mt-6 inline-block font-mono text-[40px] font-bold leading-none text-line-2">
                0{i + 1}
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
