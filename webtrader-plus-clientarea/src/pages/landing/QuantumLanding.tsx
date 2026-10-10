import { useSearchParams } from "react-router-dom";
import { Nav } from "./sections/Nav";
import { Hero } from "./sections/Hero";
import { Features } from "./sections/Features";
import { HowItWorks } from "./sections/HowItWorks";
import { WhatsAppTestimonials } from "./sections/WhatsAppTestimonials";
import { ValueStack } from "./sections/ValueStack";
import { Reviews } from "./sections/Reviews";
import { Guarantee } from "./sections/Guarantee";
import { SiteFooter } from "./sections/SiteFooter";
import { LandingContext } from "./LandingContext";
import { useApplyLangParam } from "@/lib/funnelLanguage";

/**
 * Quantum AI Profits — public landing page.
 * Standalone, no auth required. Drop into a public route (see README).
 */
export function QuantumLanding() {
  const [searchParams] = useSearchParams();
  const uuid = searchParams.get("uuid");

  // Honor an incoming `?lang=` deep link (e.g. a German ad link).
  useApplyLangParam();

  // The "landing_page" hot-lead ping used to fire from here, but this is the
  // very first touchpoint (before capture/survey), so email is never in the
  // store yet and it silently never fired. Moved to PaymentPromptPage.tsx's
  // mount, where email is guaranteed to be known.

  return (
    <LandingContext.Provider value={{ uuid }}>
      <div className="qap-root relative">
        <Nav />
        <main>
          <Hero />
          <Features />
          <HowItWorks />
          <ValueStack />
          <WhatsAppTestimonials />
          <Reviews />
          <Guarantee />
        </main>
        <SiteFooter />
      </div>
    </LandingContext.Provider>
  );
}

export default QuantumLanding;
