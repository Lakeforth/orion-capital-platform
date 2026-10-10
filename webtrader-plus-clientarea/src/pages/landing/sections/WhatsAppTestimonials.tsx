import { Container, Eyebrow } from "../primitives";
import { motion } from "framer-motion";

const SCREENSHOTS = [
  "https://res.cloudinary.com/dyp8gtllq/image/upload/v1781715077/33_bpv8a3.jpg",
  "https://res.cloudinary.com/dyp8gtllq/image/upload/v1781715076/34_lnszlb.jpg",
  "https://res.cloudinary.com/dyp8gtllq/image/upload/v1781561251/12_qqol2i.jpg",
];

export function WhatsAppTestimonials() {
  return (
    <section id="results" className="relative py-12">
      <Container>
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="text-center"
        >
          <Eyebrow>Real Member Results</Eyebrow>
          <h2 className="mt-5 text-balance font-display text-[30px] font-extrabold tracking-tight text-slate-900 sm:text-[40px]">
            Straight From Members' Phones
          </h2>
          <p className="mx-auto mt-4 max-w-[46ch] text-pretty text-slate-600">
            Unfiltered testimonies from people already benefiting from the
            system.
          </p>
        </motion.div>

        <div className="mx-auto mt-12 grid max-w-[920px] grid-cols-1 gap-6 sm:grid-cols-3">
          {SCREENSHOTS.map((url, i) => (
            <motion.div
              key={url}
              initial={{ opacity: 0, y: 30 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.6, delay: i * 0.15 }}
              className="overflow-hidden rounded-2xl border border-line-2 bg-white shadow-sm transition-transform duration-300 hover:-translate-y-1 hover:shadow-xl"
            >
              <img
                src={url}
                alt={`WhatsApp testimonial ${i + 1}`}
                className="w-full h-auto"
                loading="lazy"
              />
            </motion.div>
          ))}
        </div>
      </Container>
    </section>
  );
}
