import { Outlet, useLocation } from "react-router-dom"
import MainLayout from "@/layouts/MainLayout"
import { isBitraceRoute } from "@/pages/bitrace/bitrace-routes"
import { useBitraceEnabled } from "@/pages/bitrace/useBitraceEnabled"
import { isPropfirmRoute } from "@/pages/propfirm/propfirm-routes"
import { usePropfirmEnabled } from "@/pages/propfirm/usePropfirmEnabled"

const ClientMainLayout = () => {
  const location = useLocation()
  const bitraceEnabled = useBitraceEnabled()
  const propfirmEnabled = usePropfirmEnabled()

  // The index route at "/main" (App.tsx) always immediately redirects to
  // "/main/dashboard" via <Navigate>, but <Navigate> only calls navigate()
  // from a useEffect, so this component's first paint still happens at the
  // literal "/main" pathname -- which isPropfirmRoute/isBitraceRoute don't
  // match -- causing one committed frame of the generic MainLayout before
  // the effect-driven redirect swaps it for the Outlet. Evaluate against
  // the redirect's known destination instead of the transient literal path.
  const effectivePathname =
    location.pathname === "/main" ? "/main/dashboard" : location.pathname

  const isBitraceView = bitraceEnabled && isBitraceRoute(effectivePathname)
  const isPropfirmView = propfirmEnabled && isPropfirmRoute(effectivePathname)

  if (isBitraceView || isPropfirmView) {
    return <Outlet />
  }

  return <MainLayout />
}

export default ClientMainLayout
