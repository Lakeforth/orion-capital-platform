import { Container, Logo } from "../primitives";
import { motion } from "framer-motion";

export function SiteFooter() {
  return (
    <footer className="border-t border-line bg-ink-2 py-14">
      <Container>
        <motion.div
          initial={{ opacity: 0 }}
          whileInView={{ opacity: 1 }}
          viewport={{ once: true }}
          transition={{ duration: 1 }}
          className="flex flex-col items-center gap-6 text-center"
        >
          <Logo />

          <p className="max-w-[820px] text-pretty text-[12.5px] leading-relaxed text-muted/80">
            <span className="font-semibold text-muted">Risk disclaimer:</span>{" "}
            Trading and investing carry a high level of risk and may not be
            suitable for every investor. Past performance, member results, and
            figures shown on this page are not a guarantee of future returns and
            are provided for illustration only. The 105.88% average yearly
            return, $8.9M+ generated, and individual member outcomes do not
            represent typical results and your capital is at risk. Quantum AI
            Profits does not provide financial, investment, tax, or legal
            advice. Only invest capital you can afford to lose, and seek
            independent professional advice before making any financial
            decision.
          </p>

          <div className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-[13px] text-muted">
            <a href="#" className="hover:text-text">
              Terms
            </a>
            <a href="#" className="hover:text-text">
              Privacy
            </a>
            <a href="#" className="hover:text-text">
              Contact
            </a>
          </div>

          <p className="font-mono text-[11px] uppercase tracking-widest text-muted/60">
            © {new Date().getFullYear()} Quantum AI Profits. All rights
            reserved.
          </p>
        </motion.div>
      </Container>
    </footer>
  );
}
