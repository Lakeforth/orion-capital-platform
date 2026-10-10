import { useState } from "react";
import {
  AlertTriangle,
  ScrollText,
  Lock,
  CalendarRange,
  ShieldAlert,
  TrendingUp,
  ArrowRight,
  Check,
} from "lucide-react";
import { cn } from "@/lib/utils";
import type { InvestmentDuration } from "./constants";

interface Props {
  onContinue: (duration: InvestmentDuration) => void;
}

const DURATION_OPTIONS: {
  key: InvestmentDuration;
  eyebrow: string;
  eyebrowClassName: string;
  range: string;
  bullets: string[];
}[] = [
  {
    key: "12_24",
    eyebrow: "STANDARD TERM",
    eyebrowClassName: "text-[#89ceff]",
    range: "12–24",
    bullets: ["Expected lower volatility exposure", "Monthly yield withdrawal available"],
  },
  {
    key: "36_60",
    eyebrow: "EXTENDED TERM",
    eyebrowClassName: "text-[#4edea3]",
    range: "36–60",
    bullets: ["Optimal for AI algorithmic cycles", "Maximum compounding potential"],
  },
];

const RISK_DISCLOSURES: { title: string; body: string }[] = [
  {
    title: "Capital at Risk:",
    body: "Trading in financial markets, including equities, derivatives, and digital assets, involves significant risk of loss and is not suitable for all investors. You may lose all or a portion of your initial investment.",
  },
  {
    title: "Market Volatility:",
    body: "Past performance of any trading system, manual or AI-driven, is not indicative of future results. Market conditions can change rapidly and unpredictably.",
  },
  {
    title: "Execution Risks:",
    body: "Algorithmic trading systems are subject to technical failures, latency issues, and unforeseen market anomalies that may adversely affect execution prices.",
  },
];

export default function InvestmentAgreementModal({ onContinue }: Props) {
  const [duration, setDuration] = useState<InvestmentDuration | null>(null);
  const [agreed, setAgreed] = useState(false);

  const canContinue = duration !== null && agreed;

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="bg-[#051424] border border-[#3e4850] rounded-lg shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
        <div className="bg-[rgba(13,28,45,0.5)] border-b border-[#3e4850] px-6 py-6 flex items-center gap-3 shrink-0">
          <AlertTriangle className="text-amber-400 w-[22px] h-[19px] shrink-0" />
          <h2 className="text-[#d4e4fa] text-2xl font-semibold leading-tight">
            Investment Agreement &amp; Risk Disclaimer
          </h2>
        </div>

        <div className="flex-1 overflow-y-auto px-6 py-6 space-y-8">
          <section className="space-y-4">
            <h3 className="flex items-center gap-2 text-[#bec8d2] text-xl font-semibold">
              <ScrollText className="w-[14px] h-[14px]" />
              Terms of Commitment
            </h3>
            <div className="bg-[#0d1c2d] border border-[#3e4850] rounded p-4 space-y-3">
              <p className="text-sm text-[rgba(212,228,250,0.9)] leading-relaxed">
                By proceeding, you acknowledge that institutional-grade manual and algorithmic (AI) trading
                strategies require significant temporal commitment to effectively manage market cycles and
                execute long-term statistical edges.
              </p>
              <div className="bg-[#122131] border-l-2 border-[#0ea5e9] rounded-sm px-3.5 py-3 flex gap-3">
                <Lock className="w-[19px] h-[19px] text-[#0ea5e9] shrink-0 mt-0.5" />
                <p className="text-xs text-[#bec8d2] leading-relaxed">
                  <span className="font-semibold text-[#d4e4fa]">Fund Lock-Up:</span> You agree that full
                  account liquidation or total withdrawal of principal funds is prohibited prior to the
                  completion of your selected investment duration. Monthly or quarterly periodic withdrawals
                  of generated yields are permitted and recommended, subject to account performance and
                  minimum margin requirements.
                </p>
              </div>
            </div>
          </section>

          <section className="space-y-4">
            <h3 className="flex items-center gap-2 text-[#bec8d2] text-xl font-semibold">
              <CalendarRange className="w-[14px] h-[15px]" />
              Select Investment Duration
            </h3>
            <div className="flex gap-4">
              {DURATION_OPTIONS.map((option) => {
                const selected = duration === option.key;
                return (
                  <button
                    key={option.key}
                    type="button"
                    onClick={() => setDuration(option.key)}
                    className={cn(
                      "flex-1 text-left bg-[#122131] border rounded p-[17px] relative transition-colors",
                      selected ? "border-[#89ceff]" : "border-[#3e4850] hover:border-[#5a6773]",
                    )}
                  >
                    {selected && (
                      <span className="absolute right-4 top-4 flex items-center justify-center w-5 h-5 rounded-full bg-[#89ceff] text-[#051424]">
                        <Check className="w-3 h-3" strokeWidth={3} />
                      </span>
                    )}
                    <p className={cn("text-[13px] font-semibold tracking-wider uppercase", option.eyebrowClassName)}>
                      {option.eyebrow}
                    </p>
                    <p className="text-[#d4e4fa] text-4xl font-bold tracking-tight mt-1">{option.range}</p>
                    <p className="text-[#bec8d2] text-sm mt-1">Months Commitment</p>
                    <div className="border-t border-[rgba(62,72,80,0.5)] mt-4 pt-3 space-y-2">
                      {option.bullets.map((bullet) => (
                        <div key={bullet} className="flex items-center gap-2 text-xs text-[rgba(212,228,250,0.8)]">
                          <TrendingUp className="w-3 h-3 shrink-0" />
                          <span>{bullet}</span>
                        </div>
                      ))}
                    </div>
                  </button>
                );
              })}
            </div>
          </section>

          <section className="space-y-3">
            <h3 className="flex items-center gap-2 text-[#ffb4ab] text-xl font-semibold">
              <ShieldAlert className="w-3 h-[15px]" />
              Risk Disclosures
            </h3>
            <div className="bg-[#1a0f14] border border-[rgba(147,0,10,0.5)] rounded p-[17px]">
              <ul className="space-y-2 pl-1">
                {RISK_DISCLOSURES.map((item) => (
                  <li key={item.title} className="text-xs text-[rgba(190,200,210,0.9)] leading-relaxed">
                    <span className="font-bold">{item.title}</span> {item.body}
                  </li>
                ))}
              </ul>
            </div>
          </section>
        </div>

        <div className="bg-[#010f1f] border-t border-[#3e4850] px-6 py-6 space-y-4 shrink-0">
          <label className="flex items-start gap-3 cursor-pointer select-none">
            <span className="flex items-center h-5 pt-0.5">
              <input
                type="checkbox"
                checked={agreed}
                onChange={(e) => setAgreed(e.target.checked)}
                className="w-4 h-4 rounded-sm bg-[#122131] border border-[#88929b] accent-[#0ea5e9]"
              />
            </span>
            <span className="text-sm text-[rgba(212,228,250,0.8)] leading-relaxed">
              I have read, understood, and agree to the Investment Agreement and Risk Disclaimer, including
              the fund lock-up conditions for the selected duration.
            </span>
          </label>
          <div className="flex justify-end">
            <button
              type="button"
              disabled={!canContinue}
              onClick={() => duration && onContinue(duration)}
              className={cn(
                "flex items-center gap-2 px-6 py-3 rounded-sm text-[13px] font-semibold uppercase tracking-wider transition-opacity",
                canContinue
                  ? "bg-[#0ea5e9] text-[#003751] opacity-100 cursor-pointer"
                  : "bg-[#0ea5e9] text-[#003751] opacity-50 cursor-not-allowed",
              )}
            >
              Continue to Market Selection
              <ArrowRight className="w-[14px] h-[14px]" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

