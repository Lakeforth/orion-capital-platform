import { create } from "zustand"

/**
 * A per-tab override for which trading account the app's requests should
 * resolve to (sent as the X-TRADING-ACCOUNT-ID header, matching the backend's
 * ResolveTradingAccount middleware). Only ever set while the /trading route
 * is mounted for a non-default account (e.g. a Prop Firm challenge account) --
 * cleared on unmount so every other part of the app keeps resolving to the
 * user's normal default account.
 */
interface ActiveTradingAccountOverrideState {
  overrideAccountId: string | null
  setOverrideAccountId: (accountId: string | null) => void
  clearOverrideAccountId: () => void
}

const useActiveTradingAccountOverrideStore = create<ActiveTradingAccountOverrideState>((set) => ({
  overrideAccountId: null,
  setOverrideAccountId: (accountId) => set({ overrideAccountId: accountId }),
  clearOverrideAccountId: () => set({ overrideAccountId: null }),
}))

export default useActiveTradingAccountOverrideStore
