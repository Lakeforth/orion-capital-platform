import { Container, Eyebrow, Stars } from "../primitives";
import { REVIEWS } from "../data/landing";
import { motion } from "framer-motion";

export function Reviews() {
  return (
    <section
      id="reviews"
      className="relative border-y border-line bg-ink-2 py-10"
    >
      <Container>
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="text-center"
        >
          <Eyebrow>Trustpilot Testimonials</Eyebrow>
          <h2 className="mt-4 text-balance font-display text-[28px] font-extrabold leading-tight tracking-tight text-slate-900 sm:text-[36px]">
            Real People. Real Losses. Real Recoveries.
          </h2>
          <div className="mt-4 flex flex-col items-center justify-center gap-2">
            <Stars size={16} rating={4.9} />
            <span className="font-display text-[13px] font-bold text-slate-900">
              Excellent 4.9/5 on Trustpilot
            </span>
          </div>
        </motion.div>

        <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {REVIEWS.map((r, i) => (
            <motion.div
              key={r.name}
              initial={{ opacity: 0, scale: 0.9 }}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: i * 0.1 }}
              className="flex flex-col rounded-xl border border-line bg-surface p-5 shadow-sm transition-shadow duration-300 hover:shadow-lg"
            >
              <div className="mb-4 flex items-center gap-2.5">
                <div className="grid h-9 w-9 place-items-center rounded-full bg-ink-2 font-display text-sm font-bold text-brand">
                  {r.name.charAt(0)}
                </div>
                <div>
                  <div className="font-display text-[14px] font-bold text-text">
                    {r.name}
                  </div>
                  <div className="text-[11px] font-medium leading-tight text-muted">
                    <div>{r.role}</div>
                    <div className="mt-0.5">{r.country}</div>
                  </div>
                </div>
              </div>

              <div className="mb-3 flex items-center gap-2">
                <Stars size={12} rating={r.rating} />
                <span className="font-mono text-[11px] font-bold text-slate-500">
                  {r.rating.toFixed(1)}/5
                </span>
              </div>

              <blockquote className="text-pretty text-[13px] leading-relaxed text-text/80">
                "{r.text}"
              </blockquote>
            </motion.div>
          ))}
        </div>
      </Container>
    </section>
  );
}
