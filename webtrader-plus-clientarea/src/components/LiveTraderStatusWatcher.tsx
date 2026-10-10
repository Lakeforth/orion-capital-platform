import { useEffect } from "react";
import useSiteSettingsStore from "@/store/siteSettingStore";
import useTradeStore from "@/store/tradeStore";

/**
 * Component that watches livetrader_status and disconnects trade WebSocket when disabled
 */
export default function LiveTraderStatusWatcher() {
  const livetraderStatus = useSiteSettingsStore(
    (state) => state.settings?.livetrader_status ?? true
  );

  useEffect(() => {
    if (!livetraderStatus) {
      console.log("[LiveTraderStatusWatcher] Disconnecting trade WebSocket - livetrader_status is false");
      useTradeStore.getState().disconnectWebSocket();
    }
  }, [livetraderStatus]);

  return null;
}
