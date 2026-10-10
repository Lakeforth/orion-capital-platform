import { Container, CTA } from "../primitives";
import { TRUST_TAGS } from "../data/landing";
import { motion } from "framer-motion";
import { DollarSign, TrendingUp } from "lucide-react";
import { useEffect } from "react";

export function Hero() {
  useEffect(() => {
    // Vidalytics script loading logic
    (function (
      v: any,
      i: Document,
      d: string,
      a: string,
      l: string,
      y?: string,
      t?: any,
      c?: string,
      s?: any,
    ) {
      y = "_" + d.toLowerCase();
      c = d + "L";
      if (!v[d]) {
        v[d] = {};
      }
      if (!v[c as any]) {
        (v as any)[c as any] = {};
      }
      if (!v[y]) {
        v[y] = {};
      }
      var vl = "Loader",
        vli = v[y][vl],
        vsl = (v as any)[c as any][vl + "Script"],
        ve = "Embed";
      if (!vsl) {
        (v as any)[c as any][vl + "Script"] = function (
          u: string,
          cb: () => void,
        ) {
          if (t) {
            cb();
            return;
          }
          s = i.createElement("script");
          s.type = "text/javascript";
          s.async = 1;
          s.src = u;
          if ((s as any).readyState) {
            (s as any).onreadystatechange = function () {
              if (
                (s as any).readyState === "loaded" ||
                (s as any).readyState == "complete"
              ) {
                (s as any).onreadystatechange = null;
                cb();
              }
            };
          } else {
            s.onload = function () {
              cb();
            };
          }
          i.getElementsByTagName("head")[0].appendChild(s);
        };
        vsl = (v as any)[c as any][vl + "Script"];
      }
      vsl(l + "loader.min.js", function () {
        if (!vli) {
          var vlc = (v as any)[c as any][vl];
          vli = new vlc();
        }
        vli.loadScript(l + "player.min.js", function () {
          var vec = v[d][ve];
          t = new vec();
          t.run(a);
        });
      });
    })(
      window,
      document,
      "Vidalytics",
      "vidalytics_embed_0WbNtYVBil0CWuvE",
      "https://fast.vidalytics.com/embeds/m5U_Vg3s/0WbNtYVBil0CWuvE/",
    );
  }, []);

  return (
    <section id="top" className="relative overflow-hidden pt-[68px]">
      {/* ambient glow */}
      <div className="pointer-events-none absolute inset-0">
        <div className="absolute left-1/2 top-[-10%] h-[520px] w-[820px] -translate-x-1/2 rounded-full bg-brand/15 blur-[150px]" />
        <div className="absolute right-[-10%] top-[18%] h-[360px] w-[360px] rounded-full bg-cyan/15 blur-[120px]" />
      </div>

      <Container className="relative pt-8 pb-14 text-center sm:pt-12">
        <motion.h1
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.1 }}
          className="mx-auto max-w-[18ch] text-balance font-display text-[38px] font-extrabold leading-[1.05] tracking-tight text-slate-900 sm:text-[58px]"
        >
          Let AI Quants{" "}
          <span className="bg-gradient-to-r from-brand via-cyan to-brand bg-clip-text text-transparent">
            Exploit the Financial Markets
          </span>{" "}
          for You.
        </motion.h1>

        <motion.p
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.2 }}
          className="mx-auto mt-5 max-w-[42ch] text-pretty text-lg text-slate-600 sm:text-xl"
        >
          Passive income is now possible for anyone.
        </motion.p>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.3 }}
          className="mt-8 flex flex-wrap items-center justify-center gap-3"
        >
          {TRUST_TAGS.map((t) => (
            <span
              key={t}
              className="inline-flex items-center gap-2 rounded-full border border-line bg-surface/70 px-4 py-2 text-sm font-medium text-text/90"
            >
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                <path
                  d="M20 6L9 17l-5-5"
                  stroke="#2b7fff"
                  strokeWidth="2.6"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                />
              </svg>
              {t}
            </span>
          ))}
        </motion.div>

        {/* VSL video placeholder */}
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.8, delay: 0.4 }}
          className="relative mx-auto mt-12 max-w-[860px]"
        >
          {/* Left Floating Card */}
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 0.5, duration: 0.8 }}
            className="absolute -left-12 top-[15%] z-30 hidden w-56 -translate-y-1/2 lg:block"
          >
            <motion.div
              animate={{ y: [0, -10, 0] }}
              transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
              className="rounded-2xl border border-white/10 bg-ink p-5 text-left shadow-[0_30px_60px_-15px_rgba(0,0,0,0.5)]"
            >
              <div className="flex items-center justify-between">
                <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-white/10 text-white">
                  <DollarSign size={16} />
                </div>
                <div className="flex items-center gap-1 rounded-md bg-money/20 px-2 py-0.5 text-[10px] font-bold text-money">
                  <TrendingUp size={10} /> Verified
                </div>
              </div>
              <div className="mt-4 font-display text-3xl font-black tracking-tight text-white">
                $8.9M+
              </div>
              <div className="mt-1 font-mono text-[10px] font-bold uppercase tracking-widest text-white/40">
                Generated for members
              </div>
              <div className="mt-4 h-1 w-full overflow-hidden rounded-full bg-white/5">
                <motion.div
                  initial={{ width: 0 }}
                  animate={{ width: "100%" }}
                  transition={{ delay: 1, duration: 1.5 }}
                  className="h-full bg-money"
                />
              </div>
            </motion.div>
          </motion.div>

          {/* Right Floating Card */}
          <motion.div
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 0.7, duration: 0.8 }}
            className="absolute -right-16 bottom-[20%] z-30 hidden w-60 lg:block"
          >
            <motion.div
              animate={{ y: [0, 10, 0] }}
              transition={{
                duration: 5,
                repeat: Infinity,
                ease: "easeInOut",
                delay: 0.5,
              }}
              className="relative overflow-hidden rounded-2xl border border-white/20 bg-brand p-5 text-left shadow-[0_30px_60px_-15px_rgba(43,127,255,0.4)]"
            >
              {/* background graph svg */}
              <svg
                className="absolute bottom-0 right-0 h-16 w-full opacity-20"
                viewBox="0 0 240 60"
                fill="none"
              >
                <path
                  d="M0 50C40 45 80 55 120 40C160 25 200 35 240 10"
                  stroke="white"
                  strokeWidth="3"
                  strokeLinecap="round"
                />
              </svg>

              <div className="relative z-10">
                <div className="inline-flex items-center gap-1 rounded-md bg-white/20 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-white">
                  Average Yearly Return
                </div>
                <div className="mt-3 font-display text-4xl font-black tracking-tight text-white">
                  105.88%
                </div>
                <div className="mt-1 font-mono text-[10px] font-bold uppercase tracking-widest text-white/80">
                  Tracked Performance
                </div>
              </div>
            </motion.div>
          </motion.div>

          <div className="glow-brand overflow-hidden rounded-2xl border border-line-2 bg-white">
            <div className="flex items-center justify-center gap-2.5 bg-gradient-to-r from-brand to-cyan px-4 py-3 font-display text-sm font-bold text-white">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M8 5v14l11-7-11-7z" fill="#fff" />
              </svg>
              Watch this short video before getting started
            </div>
            <div className="relative bg-surface-2">
              <div
                id="vidalytics_embed_0WbNtYVBil0CWuvE"
                style={{
                  width: "100%",
                  position: "relative",
                  paddingTop: "56.25%",
                }}
              ></div>
            </div>
          </div>
        </motion.div>

        {/* trust line */}
        <div className="mt-8 flex flex-col items-center gap-2">
          <div className="flex flex-wrap items-center justify-center gap-x-3 gap-y-2 font-mono text-[13px] uppercase tracking-wider text-slate-500">
            <span className="text-slate-700">
              Trusted by 5,300+ members worldwide
            </span>
            <span className="hidden text-slate-300 sm:inline">|</span>
            <span className="text-slate-700">5-year track record</span>
          </div>
          <div className="flex items-center gap-1.5 font-sans text-[14px] font-black uppercase tracking-wide text-black">
            <span>🔒 100% Secure 256-Bit Security Encryption</span>
          </div>
        </div>

        <div className="mt-9 flex justify-center">
          <CTA>Get Started for $250</CTA>
        </div>
      </Container>
    </section>
  );
}
