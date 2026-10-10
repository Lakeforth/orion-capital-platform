import { Container } from "../primitives";
import { motion } from "framer-motion";

export function Guarantee() {
  return (
    <section id="guarantee" className="relative py-12">
      <Container>
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.8 }}
          className="mx-auto max-w-[1000px] flex justify-center"
        >
          <img
            src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1781701659/cc98aea1-0147-40c0-ac4b-40f5603a5df2_ihdjh4.png"
            alt="180-Day Money Back Guarantee"
            className="h-auto w-full max-w-[900px] select-none pointer-events-none"
            loading="lazy"
          />
        </motion.div>
      </Container>
    </section>
  );
}
