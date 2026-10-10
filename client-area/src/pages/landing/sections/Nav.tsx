"use client";

import { useEffect, useState } from "react";
import { Container, CTA, Logo } from "../primitives";
import { Menu, X } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import FunnelLangSwitch from "@/components/FunnelLangSwitch";

export function Nav() {
  const [scrolled, setScrolled] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 12);
    window.addEventListener("scroll", onScroll);
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  const navLinks = [
    { name: "Features", href: "#features" },
    { name: "How it Works", href: "#how-it-works" },
    { name: "Pricing", href: "#get-started" },
    { name: "Results", href: "#results" },
    { name: "Reviews", href: "#reviews" },
  ];

  return (
    <header
      className={`fixed inset-x-0 top-0 z-50 transition-all duration-300 ${
        scrolled || mobileMenuOpen
          ? "border-b border-line bg-white/95 backdrop-blur-xl"
          : "border-b border-transparent"
      }`}
    >
      <Container className="flex h-[68px] items-center justify-between">
        <Logo />

        {/* Desktop Nav */}
        <nav className="hidden md:flex items-center gap-8">
          {navLinks.map((link) => (
            <a
              key={link.name}
              href={link.href}
              className="text-sm font-semibold text-slate-600 transition-colors hover:text-brand"
            >
              {link.name}
            </a>
          ))}
        </nav>

        <div className="flex items-center gap-4">
          <FunnelLangSwitch />
          <CTA className="hidden sm:inline-flex !px-6 !py-2.5 !text-sm">
            Get Started for $250
          </CTA>

          {/* Mobile Menu Toggle */}
          <button
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-900 md:hidden"
            aria-label="Toggle menu"
          >
            {mobileMenuOpen ? <X size={20} /> : <Menu size={20} />}
          </button>
        </div>
      </Container>

      {/* Mobile Menu Overlay */}
      <AnimatePresence>
        {mobileMenuOpen && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: "auto" }}
            exit={{ opacity: 0, height: 0 }}
            transition={{ duration: 0.3 }}
            className="overflow-hidden bg-white border-t border-line md:hidden"
          >
            <nav className="flex flex-col p-6 gap-4">
              {navLinks.map((link) => (
                <a
                  key={link.name}
                  href={link.href}
                  onClick={() => setMobileMenuOpen(false)}
                  className="text-base font-bold text-slate-900 active:text-brand"
                >
                  {link.name}
                </a>
              ))}
              <div className="pt-4 border-t border-line">
                <CTA
                  className="w-full !py-4"
                  onClick={() => setMobileMenuOpen(false)}
                >
                  Get Started for $250
                </CTA>
              </div>
            </nav>
          </motion.div>
        )}
      </AnimatePresence>
    </header>
  );
}
