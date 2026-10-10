import { useState, type ReactNode } from "react";
import { toast } from "sonner";
import type { AxiosError } from "axios";
import axiosInstance from "@/lib/axios";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import InvestmentAgreementModal from "./InvestmentAgreementModal";
import AiMarketSelectionModal from "./AiMarketSelectionModal";
import type { InvestmentDuration, MarketKey, CommodityInstrumentKey } from "./constants";

/**
 * Mandatory gate for tenants with require_ai_trading_agreement enabled
 * (currently Plus50AI only). Blocks the trade room entirely -- manual
 * trading and AI trading alike -- until the user accepts the Investment
 * Agreement & Risk Disclaimer and selects the markets the AI should trade.
 * Both are also enforced server-side (POST /trades/store, POST
 * /user/ai-trade/toggle), this is purely the UX gate.
 */
export default function Plus50AiAgreementGate({ children }: { children: ReactNode }) {
  const requireAgreement = useSiteSettingsStore(
    (state) => state.settings?.require_ai_trading_agreement === true,
  );
  const agreementCompleted = useUserStore((state) => state.user?.ai_agreement_completed === true);
  const user = useUserStore((state) => state.user);
  const token = useUserStore((state) => state.token);
  const setUser = useUserStore((state) => state.setUser);

  const [step, setStep] = useState<"agreement" | "markets">("agreement");
  const [duration, setDuration] = useState<InvestmentDuration | null>(null);
  const [submitting, setSubmitting] = useState(false);

  if (!requireAgreement || agreementCompleted) {
    return <>{children}</>;
  }

  const handleAgreementContinue = (selectedDuration: InvestmentDuration) => {
    setDuration(selectedDuration);
    setStep("markets");
  };

  const handleMarketSubmit = async (payload: {
    markets: MarketKey[];
    commodityInstruments: CommodityInstrumentKey[];
  }) => {
    if (!duration || !user || !token) return;

    setSubmitting(true);
    try {
      const response = await axiosInstance.post("/user/ai-trading-agreement", {
        duration,
        markets: payload.markets,
        commodity_instruments: payload.commodityInstruments,
      });

      if (response.data?.status === "success" && response.data?.data) {
        setUser({ ...user, ...response.data.data }, token);
        toast.success("Investment Agreement accepted");
      }
    } catch (err: unknown) {
      const axiosErr = err as AxiosError<{ message: string }>;
      toast.error(axiosErr?.response?.data?.message || "Failed to save your agreement, please try again");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <>
      {step === "agreement" && <InvestmentAgreementModal onContinue={handleAgreementContinue} />}
      {step === "markets" && (
        <AiMarketSelectionModal
          onBack={() => setStep("agreement")}
          onSubmit={handleMarketSubmit}
          submitting={submitting}
        />
      )}
    </>
  );
}
