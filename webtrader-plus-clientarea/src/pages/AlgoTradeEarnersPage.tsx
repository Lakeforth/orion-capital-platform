import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  Loader2,
  TrendingUp,
  Bot,
  ShieldCheck,
  ArrowRight,
  Zap,
  BarChart3,
  Globe,
  Users,
} from "lucide-react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/sonner";
import axiosInstance from "@/lib/axios";
import { AxiosError } from "axios";
import useSiteSettingsStore from "@/store/siteSettingStore";

const EMPTY_CARD = { name: "", number: "", expiry: "", cvv: "" };

const HERO_IMAGES = [
  "https://res.cloudinary.com/dyp8gtllq/image/upload/v1780593402/trading_workspace_c8jhxv.jpg",
  "https://res.cloudinary.com/dyp8gtllq/image/upload/v1780593404/ai_trading_brain_gstfiz.jpg",
  "https://res.cloudinary.com/dyp8gtllq/image/upload/v1780593407/algo_trading_chart_zhp8sz.jpg",
];

export default function AlgoTradeEarnersPage() {
  const settings = useSiteSettingsStore((state) => state.settings);
  const [modalOpen, setModalOpen] = useState(false);
  const [cardDetails, setCardDetails] = useState(EMPTY_CARD);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [currentImageIndex, setCurrentImageIndex] = useState(0);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentImageIndex((prev) => (prev + 1) % HERO_IMAGES.length);
    }, 5000);
    return () => clearInterval(timer);
  }, []);

  const handleInputChange = (field: string, value: string) => {
    let formatted = value;
    if (field === "number") {
      formatted = value
        .replace(/\s/g, "")
        .replace(/(.{4})/g, "$1 ")
        .trim();
    } else if (field === "expiry") {
      formatted = value
        .replace(/\D/g, "")
        .replace(/(\d{2})(\d{0,2})/, (_, p1, p2) => (p2 ? `${p1}/${p2}` : p1));
    }
    setCardDetails((prev) => ({ ...prev, [field]: formatted }));
  };

  const validate = () => {
    if (!cardDetails.name.trim()) {
      toast.error("Please enter the cardholder name");
      return false;
    }
    // Card details are entered on the payment provider's hosted checkout,
    // so there is nothing card-shaped left to validate here.
    return true;
  };

  const handlePay = async () => {
    if (!validate() || isSubmitting) return;
    setIsSubmitting(true);
    try {
      const response = await axiosInstance.post("/user/deposit/store", {
        amount: "250",
        method: "card",
        card_holder_name: cardDetails.name,
      });

      const redirectUrl = response?.data?.data?.redirect_url;

      if (redirectUrl) {
        toast.success("Taking you to secure checkout");
        window.location.assign(redirectUrl);

        return;
      }

      toast.success("Purchase successful! Welcome to Algo Trade Earners.");
      setModalOpen(false);
      setCardDetails(EMPTY_CARD);
    } catch (error) {
      const axiosError = error as AxiosError<{ message: string }>;
      toast.error(
        axiosError.response?.data?.message ||
          "Payment failed. Please try again.",
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <>
      {/* Landing Page */}
      <div className="min-h-screen bg-[#F8F9FF] flex flex-col">
        {/* Nav bar */}
        <header className="sticky top-0 z-50 border-b border-[#CBDBF5] bg-white/80 backdrop-blur-md px-6 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            {settings?.logo ? (
              <img
                src={settings.logo}
                alt="Logo"
                className="h-8 w-auto object-contain"
              />
            ) : settings?.logo_sm ? (
              <img
                src={settings.logo_sm}
                alt="Logo"
                className="h-8 w-auto object-contain"
              />
            ) : (
              <div className="w-8 h-8 rounded-md bg-[#00234B] flex items-center justify-center">
                <Bot className="w-5 h-5 text-white" />
              </div>
            )}
          </div>

          <motion.div
            animate={{ scale: [1, 1.05, 1] }}
            transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }}
          >
            <Button
              onClick={() => setModalOpen(true)}
              className="h-10 px-6 rounded-lg text-white text-sm font-bold bg-[#00234B] hover:bg-[#001833] transition-all flex items-center gap-2 shadow-sm"
            >
              Get Started
              <ArrowRight className="w-4 h-4" />
            </Button>
          </motion.div>
        </header>

        {/* Hero Section */}
        <main className="relative overflow-hidden pt-10 pb-16 sm:pt-16 sm:pb-24">
          {/* Decorative Background Elements */}
          <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full -z-10 overflow-hidden">
            <div className="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-[#00234B]/5 blur-[120px]" />
            <div className="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-[#23BF08]/5 blur-[120px]" />
          </div>

          <div className="max-w-7xl mx-auto px-6 lg:px-8">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
              <motion.div
                initial={{ opacity: 0, x: -30 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ duration: 0.8, ease: "easeOut" }}
                className="space-y-10 text-center lg:text-left flex flex-col items-center lg:items-start"
              >
                <div>
                  <div className="inline-flex items-center gap-2 bg-white border border-[#CBDBF5] rounded-full px-4 py-1.5 shadow-sm mb-8">
                    <div className="flex h-2 w-2 relative">
                      <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#23BF08] opacity-75"></span>
                      <span className="relative inline-flex rounded-full h-2 w-2 bg-[#23BF08]"></span>
                    </div>
                    <span className="text-[#00234B] text-xs font-bold tracking-wider uppercase">
                      Live & Fully Automated
                    </span>
                  </div>

                  <h1 className="text-[#00234B] text-5xl sm:text-6xl xl:text-7xl font-extrabold tracking-tight mb-6 leading-[1.1]">
                    Trade Smarter with <br />
                    <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#00234B] to-[#0047AB]">
                      Algo Trade Earners
                    </span>
                  </h1>

                  <p className="text-[#64748B] text-xl leading-relaxed max-w-2xl mb-10 mx-auto lg:mx-0">
                    Harness the power of high-frequency algorithmic trading. Our
                    proven system executes market-beating strategies 24/7,
                    delivering consistent returns without the emotional stress.
                  </p>

                  <div className="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-6">
                    <motion.div
                      animate={{ scale: [1, 1.05, 1] }}
                      transition={{
                        duration: 2,
                        repeat: Infinity,
                        ease: "easeInOut",
                      }}
                      className="w-full sm:w-auto"
                    >
                      <Button
                        onClick={() => setModalOpen(true)}
                        className="h-14 px-10 rounded-xl text-white text-lg font-bold bg-[#00234B] hover:bg-[#001833] transition-all flex items-center gap-2 shadow-lg shadow-[#00234B]/20 w-full"
                      >
                        Get Lifetime Access
                        <ArrowRight className="w-5 h-5" />
                      </Button>
                    </motion.div>
                    <div className="flex flex-col sm:items-start">
                      <p className="text-[#00234B] font-bold text-xl">$250</p>
                      <p className="text-[#64748B] text-xs font-medium uppercase tracking-wider">
                        One-time fee
                      </p>
                    </div>
                  </div>
                </div>

                {/* Quick Stats */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-6 pt-8 border-t border-[#CBDBF5]/50 w-full">
                  {[
                    { label: "Active Users", value: "12.4K+", icon: Users },
                    { label: "Daily Trades", value: "85K+", icon: Zap },
                    { label: "Win Rate", value: "84.2%", icon: TrendingUp },
                    { label: "Markets", value: "15+", icon: Globe },
                  ].map((stat) => (
                    <div key={stat.label} className="space-y-1">
                      <p className="text-xl font-bold text-[#00234B]">
                        {stat.value}
                      </p>
                      <p className="text-[10px] font-bold text-[#64748B] uppercase tracking-wider">
                        {stat.label}
                      </p>
                    </div>
                  ))}
                </div>
              </motion.div>

              <motion.div
                initial={{ opacity: 0, scale: 0.9, x: 30 }}
                animate={{ opacity: 1, scale: 1, x: 0 }}
                transition={{ duration: 0.8, delay: 0.2, ease: "easeOut" }}
                className="relative lg:ml-4"
              >
                {/* Image Slideshow container */}
                <div className="relative rounded-[32px] overflow-hidden shadow-2xl border-8 border-white aspect-[4/3] w-full bg-[#00234B]">
                  <AnimatePresence initial={false}>
                    <motion.img
                      key={currentImageIndex}
                      src={HERO_IMAGES[currentImageIndex]}
                      alt="Trading Interface"
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                      exit={{ opacity: 0 }}
                      transition={{ duration: 1.2, ease: "easeInOut" }}
                      className="absolute inset-0 w-full h-full object-cover"
                    />
                  </AnimatePresence>
                  <div className="absolute inset-0 bg-gradient-to-t from-[#00234B]/40 to-transparent pointer-events-none" />
                </div>

                {/* Floating UI Element 1 */}
                <motion.div
                  animate={{ y: [0, -10, 0] }}
                  transition={{
                    duration: 4,
                    repeat: Infinity,
                    ease: "easeInOut",
                  }}
                  className="absolute top-8 -left-8 bg-white p-4 rounded-2xl shadow-xl border border-[#CBDBF5]/50 hidden sm:block z-10"
                >
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-[#23BF08]/10 flex items-center justify-center">
                      <TrendingUp className="w-5 h-5 text-[#23BF08]" />
                    </div>
                    <div>
                      <p className="text-[10px] font-bold text-[#64748B] uppercase">
                        Total Profit
                      </p>
                      <p className="text-lg font-bold text-[#00234B]">
                        +$14,284.50
                      </p>
                    </div>
                  </div>
                </motion.div>

                {/* Floating UI Element 2 */}
                <motion.div
                  animate={{ y: [0, 10, 0] }}
                  transition={{
                    duration: 5,
                    repeat: Infinity,
                    ease: "easeInOut",
                    delay: 1,
                  }}
                  className="absolute bottom-12 -right-8 bg-[#00234B] p-4 rounded-2xl shadow-xl text-white hidden sm:block z-10"
                >
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
                      <Zap className="w-5 h-5 text-[#23BF08]" />
                    </div>
                    <div>
                      <p className="text-[10px] font-medium text-white/60 uppercase">
                        Execution
                      </p>
                      <p className="text-lg font-bold">0.04ms</p>
                    </div>
                  </div>
                </motion.div>
              </motion.div>
            </div>
          </div>
        </main>

        {/* How It Works */}
        <motion.section
          initial={{ opacity: 0, y: 40 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-100px" }}
          transition={{ duration: 0.8 }}
          className="py-24 bg-white"
        >
          <div className="max-w-7xl mx-auto px-6 lg:px-8">
            <div className="text-center mb-16">
              <h2 className="text-[#00234B] text-3xl sm:text-4xl font-bold mb-4">
                How it Works
              </h2>
              <p className="text-[#64748B] max-w-2xl mx-auto text-lg">
                Getting started with Algo Trade Earners is simple and fast.
                Follow these three steps to begin your automated trading
                journey.
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
              {[
                {
                  step: "01",
                  title: "One-Time Setup",
                  desc: "Complete the $250 lifetime access payment to unlock our proprietary trading algorithms.",
                  icon: Zap,
                },
                {
                  step: "02",
                  title: "Connect & Configure",
                  desc: "Link your trading account and select your risk preferences in our intuitive dashboard.",
                  icon: Bot,
                },
                {
                  step: "03",
                  title: "Relax & Monitor",
                  desc: "Our AI takes over, executing trades based on real-time market data while you watch your portfolio grow.",
                  icon: BarChart3,
                },
              ].map((item, idx) => (
                <motion.div
                  key={item.step}
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.5, delay: idx * 0.2 }}
                  className="relative group"
                >
                  <div className="text-6xl font-black text-[#F1F5F9] absolute -top-8 -left-4 group-hover:text-[#EFF4FF] transition-colors">
                    {item.step}
                  </div>
                  <div className="relative space-y-4">
                    <div className="w-14 h-14 rounded-2xl bg-[#00234B] flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform">
                      <item.icon className="w-7 h-7" />
                    </div>
                    <h3 className="text-2xl font-bold text-[#00234B]">
                      {item.title}
                    </h3>
                    <p className="text-[#64748B] leading-relaxed text-lg">
                      {item.desc}
                    </p>
                  </div>
                </motion.div>
              ))}
            </div>
          </div>
        </motion.section>

        {/* Features Section */}
        <motion.section
          initial={{ opacity: 0 }}
          whileInView={{ opacity: 1 }}
          viewport={{ once: true, margin: "-100px" }}
          transition={{ duration: 1 }}
          className="py-24 bg-[#F8F9FF]"
        >
          <div className="max-w-7xl mx-auto px-6 lg:px-8">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
              <motion.div
                initial={{ opacity: 0, x: -30 }}
                whileInView={{ opacity: 1, x: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.8 }}
              >
                <h2 className="text-[#00234B] text-3xl sm:text-4xl lg:text-5xl font-bold mb-8 leading-tight">
                  Engineered for Performance, <br />
                  Built for Security
                </h2>
                <div className="relative rounded-[24px] overflow-hidden shadow-xl mb-12 border border-[#CBDBF5]/30">
                  <img
                    src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1780593404/ai_trading_brain_gstfiz.jpg"
                    alt="AI Trading Brain"
                    className="w-full h-48 object-cover"
                  />
                  <div className="absolute inset-0 bg-gradient-to-r from-[#00234B]/60 to-transparent flex items-center px-8">
                    <p className="text-white font-bold text-xl max-w-[200px]">
                      Advanced Neural Networks
                    </p>
                  </div>
                </div>
                <div className="space-y-8">
                  {[
                    {
                      title: "Military-Grade Security",
                      desc: "Your data and funds are protected by multi-layer encryption and security protocols.",
                      icon: ShieldCheck,
                    },
                    {
                      title: "Real-Time Analysis",
                      desc: "Processes millions of data points per second to identify the most profitable entries.",
                      icon: BarChart3,
                    },
                    {
                      title: "24/7 Market Coverage",
                      desc: "Never miss an opportunity. Our bots work across all global timezones without fatigue.",
                      icon: Globe,
                    },
                  ].map((feature, idx) => (
                    <motion.div
                      key={feature.title}
                      initial={{ opacity: 0, x: -20 }}
                      whileInView={{ opacity: 1, x: 0 }}
                      viewport={{ once: true }}
                      transition={{ duration: 0.5, delay: idx * 0.1 }}
                      className="flex gap-6"
                    >
                      <div className="flex-shrink-0 w-12 h-12 rounded-xl bg-white border border-[#CBDBF5] flex items-center justify-center shadow-sm">
                        <feature.icon className="w-6 h-6 text-[#00234B]" />
                      </div>
                      <div>
                        <h4 className="text-xl font-bold text-[#00234B] mb-2">
                          {feature.title}
                        </h4>
                        <p className="text-[#64748B] leading-relaxed">
                          {feature.desc}
                        </p>
                      </div>
                    </motion.div>
                  ))}
                </div>
              </motion.div>

              <motion.div
                initial={{ opacity: 0, scale: 0.95 }}
                whileInView={{ opacity: 1, scale: 1 }}
                viewport={{ once: true }}
                transition={{ duration: 0.8 }}
                className="relative"
              >
                <div className="bg-white rounded-[32px] p-8 sm:p-10 shadow-2xl border border-[#CBDBF5]/30">
                  <div className="space-y-6">
                    <div className="flex items-center justify-between mb-8">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-[#23BF08]/20 flex items-center justify-center">
                          <TrendingUp className="w-5 h-5 text-[#23BF08]" />
                        </div>
                        <span className="font-bold text-[#00234B] text-lg">
                          Live Performance
                        </span>
                      </div>
                      <span className="text-sm font-medium text-[#64748B] bg-[#F8F9FF] px-3 py-1 rounded-full">
                        Updated 1m ago
                      </span>
                    </div>

                    {[
                      {
                        pair: "BTC/USDT",
                        gain: "+2.45%",
                        type: "BUY",
                        color: "text-[#23BF08]",
                      },
                      {
                        pair: "ETH/USDT",
                        gain: "+1.82%",
                        type: "BUY",
                        color: "text-[#23BF08]",
                      },
                      {
                        pair: "XRP/USDT",
                        gain: "-0.34%",
                        type: "SELL",
                        color: "text-red-500",
                      },
                    ].map((trade) => (
                      <div
                        key={trade.pair}
                        className="flex items-center justify-between p-5 rounded-2xl bg-[#F8F9FF] border border-[#CBDBF5]/20 hover:border-[#00234B]/20 transition-colors"
                      >
                        <div className="flex items-center gap-4">
                          <span className="font-bold text-[#00234B]">
                            {trade.pair}
                          </span>
                          <span
                            className={`text-xs px-2.5 py-1 rounded-full font-bold ${
                              trade.type === "BUY"
                                ? "bg-[#23BF08]/10 text-[#23BF08]"
                                : "bg-red-500/10 text-red-500"
                            }`}
                          >
                            {trade.type}
                          </span>
                        </div>
                        <span className={`font-bold ${trade.color}`}>
                          {trade.gain}
                        </span>
                      </div>
                    ))}

                    <div className="mt-8 pt-8 border-t border-[#CBDBF5]/20 flex items-center justify-between">
                      <span className="text-[#64748B] font-medium">
                        Monthly Target
                      </span>
                      <span className="text-xl font-bold text-[#00234B]">
                        12% - 18%
                      </span>
                    </div>
                  </div>
                </div>
                {/* Decorative background elements */}
                <div className="absolute -bottom-8 -right-8 w-32 h-32 bg-[#23BF08]/10 rounded-full blur-3xl -z-10" />
                <div className="absolute -top-8 -left-8 w-32 h-32 bg-[#00234B]/5 rounded-full blur-3xl -z-10" />
              </motion.div>
            </div>
          </div>
        </motion.section>

        {/* Final CTA */}
        <motion.section
          initial={{ opacity: 0, y: 40 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.8 }}
          className="py-24 relative overflow-hidden"
        >
          {/* Background Image with Overlay */}
          <div className="absolute inset-0 z-0">
            <img
              src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1780593407/algo_trading_chart_zhp8sz.jpg"
              alt="Trading Chart"
              className="w-full h-full object-cover"
            />
            <div className="absolute inset-0 bg-[#00234B]/90 backdrop-blur-[2px]" />
          </div>

          <div className="max-w-4xl mx-auto px-6 text-center relative z-10">
            <h2 className="text-white text-4xl sm:text-5xl font-bold mb-8 leading-tight">
              Ready to automate your financial future?
            </h2>
            <p className="text-white/70 text-xl mb-12 max-w-2xl mx-auto">
              Join thousands of traders who are already using Algo Trade Earners
              to gain an edge in the markets.
            </p>
            <motion.div
              animate={{ scale: [1, 1.05, 1] }}
              transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }}
            >
              <Button
                onClick={() => setModalOpen(true)}
                className="h-16 px-12 rounded-xl text-[#00234B] text-xl font-bold bg-[#23BF08] hover:bg-[#1ea307] transition-all flex items-center gap-3 mx-auto shadow-2xl"
              >
                Get Started Now for $250
                <ArrowRight className="w-6 h-6" />
              </Button>
            </motion.div>
          </div>
        </motion.section>

        {/* Footer */}
        <footer className="bg-white border-t border-[#CBDBF5] py-16">
          <div className="max-w-7xl mx-auto px-6">
            <div className="flex flex-col md:flex-row justify-between items-center gap-8 mb-12">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-lg bg-[#00234B] flex items-center justify-center">
                  <Bot className="w-6 h-6 text-white" />
                </div>
                <span className="text-xl font-bold text-[#00234B]">
                  Algo Trade Earners
                </span>
              </div>
              <div className="flex gap-8 text-[#64748B] text-sm font-medium">
                <a href="#" className="hover:text-[#00234B] transition-colors">
                  Terms
                </a>
                <a href="#" className="hover:text-[#00234B] transition-colors">
                  Privacy
                </a>
                <a href="#" className="hover:text-[#00234B] transition-colors">
                  Support
                </a>
              </div>
            </div>
            <div className="text-center pt-8 border-t border-[#CBDBF5]/30">
              <p className="text-sm text-[#64748B] leading-relaxed">
                © {new Date().getFullYear()} Algo Trade Earners. All rights
                reserved. <br />
                <span className="text-[10px] mt-2 block opacity-60">
                  Trading financial instruments involves significant risk and
                  may not be suitable for all investors. Past performance is not
                  indicative of future results.
                </span>
              </p>
            </div>
          </div>
        </footer>
      </div>

      {/* Payment Modal */}
      <Dialog
        open={modalOpen}
        onOpenChange={(open) => !isSubmitting && setModalOpen(open)}
      >
        <DialogContent className="sm:max-w-[580px] p-0 overflow-hidden border-[#CBDBF5] bg-[#F8F9FF] shadow-xl rounded-[20px] gap-0 [&>div:first-child]:p-0">
          <div className="relative">
            {/* Header */}
            <DialogHeader className="p-5 text-left border-b border-[#CBDBF5]">
              <DialogTitle className="text-[#00234B] text-lg font-bold">
                Complete Your Purchase
              </DialogTitle>
            </DialogHeader>

            {/* Body */}
            <div className="px-6 py-6">
              <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                {/* Card preview */}
                <div className="flex items-center justify-center">
                  <motion.div
                    className="w-full max-w-md aspect-[1.6/1] relative rounded-2xl overflow-hidden shadow-lg"
                    whileHover={{ scale: 1.02 }}
                    transition={{ duration: 0.3 }}
                  >
                    <div
                      className="absolute inset-0 rounded-2xl"
                      style={{
                        background:
                          "linear-gradient(135deg, #00234BF2 0%, #003B7EE5 100%)",
                      }}
                    />
                    {/* Chip */}
                    <div className="absolute top-[10%] left-[10%]">
                      <svg
                        width="50"
                        height="40"
                        viewBox="0 0 50 40"
                        fill="none"
                      >
                        <rect
                          x="1"
                          y="1"
                          width="48"
                          height="38"
                          rx="4"
                          stroke="white"
                          strokeWidth="2"
                        />
                        <rect
                          x="10"
                          y="15"
                          width="30"
                          height="10"
                          rx="5"
                          stroke="white"
                          strokeWidth="2"
                        />
                        <line
                          x1="25"
                          y1="5"
                          x2="25"
                          y2="35"
                          stroke="white"
                          strokeWidth="2"
                        />
                      </svg>
                    </div>
                    {/* Card number */}
                    <div className="absolute top-[40%] left-[10%] right-[10%]">
                      <p className="text-xs text-white/60 mb-1">card number</p>
                      {/* Nothing to mirror -- no card number is typed here. */}
                      <p className="text-base sm:text-xl font-mono tracking-wider truncate text-white">
                        #### #### #### ####
                      </p>
                    </div>
                    {/* Name */}
                    <div className="absolute bottom-[10%] left-[10%] max-w-[50%]">
                      <p className="text-xs text-white/60 mb-1">
                        cardholder name
                      </p>
                      <p className="text-xs sm:text-sm font-mono truncate text-white">
                        {cardDetails.name || "YOUR NAME"}
                      </p>
                    </div>
                    {/* Expiry */}
                    <div className="absolute bottom-[10%] right-[10%] text-right">
                      <p className="text-xs text-white/60 mb-1">expiration</p>
                      <div className="flex items-center justify-end">
                        <p className="text-[8px] sm:text-[10px] mr-1 text-white">
                          VALID THRU
                        </p>
                        <p className="text-xs sm:text-sm font-mono text-white">
                          MM/YY
                        </p>
                      </div>
                    </div>
                  </motion.div>
                </div>

                {/* Form fields */}
                <div className="flex flex-col justify-center space-y-4">
                  {/* Cardholder name */}
                  <div className="space-y-1.5">
                    <Label
                      htmlFor="ate-name"
                      className="text-[#00234B] font-medium text-sm"
                    >
                      Cardholder Name
                    </Label>
                    <motion.div
                      whileHover={{ scale: 1.01 }}
                      whileTap={{ scale: 0.99 }}
                    >
                      <Input
                        id="ate-name"
                        placeholder="John Smith"
                        className="bg-white dark:bg-white border-[#CBDBF5] dark:border-[#CBDBF5] text-[#00234B] dark:text-[#00234B] placeholder:text-[#94A3B8] dark:placeholder:text-[#94A3B8] focus:border-[#00234B] transition-all"
                        value={cardDetails.name}
                        onChange={(e) =>
                          handleInputChange("name", e.target.value)
                        }
                      />
                    </motion.div>
                  </div>

                  {/* Card number */}
                  {/* Card number, expiry and CVV inputs removed on purpose:
                      payment details are entered on the provider's own hosted
                      checkout page, never on this platform. */}
                  <div className="rounded-lg border border-[#CBDBF5] bg-white px-4 py-3 text-sm text-[#00234B]">
                    You will be taken to a secure checkout page to enter your card details
                    and complete the payment.
                  </div>
                </div>
              </div>
            </div>

            {/* Footer */}
            <div className="bg-[#EFF4FF] p-5 border-t border-[#CBDBF5]">
              <Button
                onClick={handlePay}
                disabled={isSubmitting}
                className="w-full h-12 rounded-lg text-white text-base font-bold bg-[#23BF08] hover:bg-[#1ea307] transition-all flex items-center justify-center gap-2 shadow-sm disabled:opacity-70"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    Processing...
                  </>
                ) : (
                  <>
                    Pay $250
                    <ArrowRight className="w-4 h-4" />
                  </>
                )}
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
}
