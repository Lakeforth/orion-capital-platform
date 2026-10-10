import TradingDashboard from "@/pages/Preferences/TradingDashboard"
import BitraceDashboardPage from "./BitraceDashboardPage"
import { useBitraceEnabled } from "./useBitraceEnabled"
import PropFirmDashboardPage from "../propfirm/PropFirmDashboardPage"
import { usePropfirmEnabled } from "../propfirm/usePropfirmEnabled"

const ClientDashboardSwitch = () => {
  const bitraceEnabled = useBitraceEnabled()
  const propfirmEnabled = usePropfirmEnabled()

  if (propfirmEnabled) {
    return <PropFirmDashboardPage />
  }

  if (bitraceEnabled) {
    return <BitraceDashboardPage />
  }

  return <TradingDashboard />
}

export default ClientDashboardSwitch
