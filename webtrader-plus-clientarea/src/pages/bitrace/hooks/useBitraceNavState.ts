import { useLocation } from "react-router-dom"
import {
  BITRACE_PROFILE_PATH,
  BITRACE_SUPPORT_PATH,
  isBitraceCaseRoute,
} from "../bitrace-routes"
import { useBitracePrimaryCase } from "./useBitracePrimaryCase"

export const useBitraceNavState = () => {
  const location = useLocation()
  const { primaryCase, cases, isLoading } = useBitracePrimaryCase()

  const isCaseRoute = isBitraceCaseRoute(location.pathname)
  const isProfileRoute = location.pathname === BITRACE_PROFILE_PATH
  const isSupportRoute = location.pathname === BITRACE_SUPPORT_PATH

  let activeSidebarLabel = "Dashboard"

  if (isCaseRoute) {
    activeSidebarLabel = "My Cases"
  } else if (isProfileRoute) {
    activeSidebarLabel = "Profile"
  } else if (isSupportRoute) {
    activeSidebarLabel = "Support"
  }

  return {
    activeSidebarLabel,
    primaryCaseId: primaryCase?.id,
    cases,
    isLoading,
    isCaseRoute,
    isProfileRoute,
    isSupportRoute,
  }
}
