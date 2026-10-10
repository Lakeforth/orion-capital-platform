import axiosInstance from "@/lib/axios"
import useSiteSettingsStore from "@/store/siteSettingStore"
import { toast } from "@/components/ui/sonner"
import { AxiosError } from "axios"

type AccountId = number | string

function webtraderReady(): boolean {
  const settings = useSiteSettingsStore.getState().settings
  return Boolean(settings?.webtrader_enabled && settings?.webtrader_url)
}

function errorMessage(error: unknown, fallback: string): string {
  return error instanceof AxiosError
    ? error.response?.data?.message ?? fallback
    : fallback
}

/**
 * A tab opened for a URL we do not have yet.
 *
 * window.open() only counts as user-initiated while the click that triggered
 * it is still on the stack, so opening it *after* awaiting the launch-token
 * request gets it silently swallowed by the popup blocker -- no tab, no
 * error, nothing. Claiming the tab first and pointing it at the URL once it
 * arrives keeps the gesture intact.
 *
 * `noopener` cannot be used here (it makes window.open return null, and we
 * need the handle), so the opener reference is dropped by hand instead --
 * possible only while the tab is still about:blank and same-origin.
 */
function claimTab(): Window | null {
  const tab = window.open("about:blank", "_blank")

  if (tab) {
    try {
      tab.opener = null
    } catch {
      // Cross-origin by the time we got here; nothing to drop.
    }
  }

  return tab
}

function sendTab(tab: Window | null, url: string): void {
  if (tab) {
    tab.location.replace(url)
    return
  }

  // The blocker took the tab anyway. An in-place navigation is never
  // blocked, so the user still gets where they asked to go.
  window.location.href = url
}

async function fetchLaunchRedirectUrl(accountId: AccountId): Promise<string | null> {
  try {
    const response = await axiosInstance.post(`/trading-accounts/${accountId}/launch-token`)
    return response.data?.data?.redirect_url ?? null
  } catch (error) {
    toast.error(errorMessage(error, "Could not open the trade room right now."))
    return null
  }
}

/**
 * Navigates the current tab to the trade room, auto-logging the user into
 * that specific account's webtrader session. Callers always know exactly
 * which account this is for (a "Launch"/"Trade Now" action on a specific
 * account's own row or detail page).
 */
export async function openTradeRoomInPlace(
  navigate: (path: string) => void,
  accountId: AccountId,
): Promise<void> {
  const fallbackPath = `/trading?account=${accountId}`

  if (!webtraderReady()) {
    navigate(fallbackPath)
    return
  }

  const redirectUrl = await fetchLaunchRedirectUrl(accountId)

  if (!redirectUrl) {
    navigate(fallbackPath)
    return
  }

  // Real cross-origin navigation, never react-router's navigate() -- the
  // webtrader host is a different origin entirely.
  window.location.href = redirectUrl
}

/**
 * Opens the trade room in a new tab, auto-logging the user into that
 * specific account's webtrader session. Same rules as openTradeRoomInPlace().
 */
export async function openTradeRoomInNewTab(accountId: AccountId): Promise<void> {
  if (!webtraderReady()) {
    window.open(`/trading?account=${accountId}`, "_blank", "noopener,noreferrer")
    return
  }

  const tab = claimTab()
  const redirectUrl = await fetchLaunchRedirectUrl(accountId)

  sendTab(tab, redirectUrl ?? `/trading?account=${accountId}`)
}

/**
 * The generic "WebTrader" entry points -- sidebar nav, header button, account
 * summary card -- are not tied to any one trading account. They used to open
 * the webtrader's own login form and leave the client to sign in again, which
 * in practice meant the platform never opened at all: that form wants a
 * trading account ID and the account's system-generated password, which a
 * client has usually never seen.
 *
 * They now hand the existing session over, landing on whichever account the
 * backend already resolves to (see TradingAccountController's
 * resolvedUserAccount) unless one is named here.
 */
export async function openWebtrader(accountId?: AccountId): Promise<void> {
  const settings = useSiteSettingsStore.getState().settings

  if (!settings?.webtrader_enabled || !settings?.webtrader_url) {
    toast.error("Webtrader is not available for this account.")
    return
  }

  const tab = claimTab()

  try {
    const response = await axiosInstance.post(
      "/webtrader/launch-token",
      accountId ? { account_id: accountId } : {},
    )
    const redirectUrl = response.data?.data?.redirect_url

    if (!redirectUrl) throw new Error("No redirect url")

    sendTab(tab, redirectUrl)
  } catch (error) {
    // Deliberately no fall back to the login form: that form was the
    // complaint. Say what went wrong instead -- most often "you have no
    // trading account yet", which no login screen would have solved.
    tab?.close()
    toast.error(errorMessage(error, "Could not open the trade room right now."))
  }
}

/**
 * The trip back. The client area and the webtrader are separate origins with
 * separate sessions, so "Back to Client Area" used to be a bare cross-origin
 * redirect that landed on the login screen for anyone who had reached the
 * terminal through a launch link. This hands the session over the same way
 * the outbound trip does.
 */
export async function returnToClientArea(path?: string): Promise<void> {
  const settings = useSiteSettingsStore.getState().settings
  const clientAreaUrl = settings?.client_area_url

  if (!clientAreaUrl) {
    toast.error("The client area is not available for this account.")
    return
  }

  // Only ever a path within the client area, never a whole URL -- it is
  // handed straight to the router over there, so "//evil.example" or an
  // absolute URL would turn this into an open redirect.
  const safePath =
    path && path.startsWith("/") && !path.startsWith("//") ? path : null
  const destination = safePath
    ? new URL(safePath.replace(/^\//, ""), `${clientAreaUrl.replace(/\/+$/, "")}/`).toString()
    : clientAreaUrl

  try {
    const response = await axiosInstance.post("/client-area/launch-token")
    const redirectUrl: string | undefined = response.data?.data?.redirect_url

    if (!redirectUrl) throw new Error("No redirect url")

    window.location.href = safePath
      ? `${redirectUrl}&path=${encodeURIComponent(safePath)}`
      : redirectUrl
  } catch {
    // A view-only manager session is refused the handoff (the API blocks
    // every unsafe method for it), and anything else here is just as
    // unexplainable to the user. The destination is still right; it just
    // asks them to sign in, which is where this always used to land.
    window.location.href = destination
  }
}

/**
 * Switches which trading account this webtrader session is for, in place.
 *
 * A webtrader session is bound to one account at login, so there is nothing
 * to toggle client-side: the only honest switch is a fresh session for the
 * other account, which is exactly what a launch token buys. Navigates rather
 * than opening a tab -- the user asked to change what this window shows.
 */
export async function switchTradingAccount(accountId: AccountId): Promise<boolean> {
  try {
    const response = await axiosInstance.post("/webtrader/launch-token", {
      account_id: accountId,
    })
    const redirectUrl = response.data?.data?.redirect_url

    if (!redirectUrl) throw new Error("No redirect url")

    window.location.href = redirectUrl
    return true
  } catch (error) {
    toast.error(errorMessage(error, "Could not switch account right now."))
    return false
  }
}
