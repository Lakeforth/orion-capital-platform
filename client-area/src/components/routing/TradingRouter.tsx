import { useEffect } from "react";
import { Routes, Route, useSearchParams } from "react-router-dom";
import useSiteSettingsStore from "@/store/siteSettingStore";
import useActiveTradingAccountOverrideStore from "@/store/activeTradingAccountOverrideStore";
import TradingPlatform from "@/components/trading/trading-interface-components/trading-platform";
// import MT4Layout from "@/layouts/MT4Layout";
// import MainContent from "@/components/mt4/main-content";
import { TradingPlatformLight } from "@/components/mt4-light/trading-platform-light.tsx";
import Plus50AiAgreementGate from "@/components/trading/plus50-agreement/Plus50AiAgreementGate";

const TradingRouter = () => {
  const settings = useSiteSettingsStore((state) => state.settings);
  const is_mt4 = settings?.is_mt4 ?? false;
  const [searchParams] = useSearchParams();

  // Whatever account override was set to reach this route (e.g. from a Prop
  // Firm "Launch Trade Room" click, which opens this route in a new tab via
  // a ?account= param since a fresh tab has no access to the opener's
  // in-memory store) only applies while this route is mounted -- clearing it
  // on unmount restores normal default-account resolution everywhere else.
  useEffect(() => {
    const accountId = searchParams.get("account");
    if (accountId) {
      useActiveTradingAccountOverrideStore.getState().setOverrideAccountId(accountId);
    }
    return () => useActiveTradingAccountOverrideStore.getState().clearOverrideAccountId()
  }, [searchParams])

  if (is_mt4) {
    return (
      <Routes>
        <Route
          path="/"
          element={
            <Plus50AiAgreementGate>
              <TradingPlatformLight />
            </Plus50AiAgreementGate>
          }
        />
      </Routes>
    );
  }

  return (
    <Routes>
      <Route
        path="/"
        element={
          <Plus50AiAgreementGate>
            <TradingPlatform />
          </Plus50AiAgreementGate>
        }
      />
    </Routes>
  );
};

export default TradingRouter;
