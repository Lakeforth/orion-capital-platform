export const BITRACE_DASHBOARD_PATH = "/main/dashboard"
export const BITRACE_CASES_PATH = "/main/bitrace/cases"
export const BITRACE_PROFILE_PATH = "/main/bitrace/profile"
export const BITRACE_SUPPORT_PATH = "/main/bitrace/support"
export const PLATFORM_CHAT_PATH = "/main/chat"

export const getBitraceCaseDetailPath = (caseId: string) =>
  `${BITRACE_CASES_PATH}/${caseId}`

export const isBitraceCaseRoute = (pathname: string) =>
  pathname === BITRACE_CASES_PATH || pathname.startsWith(`${BITRACE_CASES_PATH}/`)

export const isBitraceRoute = (pathname: string) => {
  if (pathname === BITRACE_DASHBOARD_PATH) return true
  if (pathname.startsWith("/main/bitrace/")) return true
  return false
}
