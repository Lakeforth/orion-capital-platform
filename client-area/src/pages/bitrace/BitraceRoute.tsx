import { ReactNode } from "react"
import { Navigate } from "react-router-dom"
import { useBitraceEnabled } from "./useBitraceEnabled"

type BitraceRouteProps = {
  children: ReactNode
}

const BitraceRoute = ({ children }: BitraceRouteProps) => {
  const bitraceEnabled = useBitraceEnabled()

  if (!bitraceEnabled) {
    return <Navigate to="/main/dashboard" replace />
  }

  return <>{children}</>
}

export default BitraceRoute
