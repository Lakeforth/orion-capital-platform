import { create } from "zustand";
import { persist } from "zustand/middleware";
import axiosInstance from "@/lib/axios";

export interface UserAccount {
  type: "balance" | "bonus" | "credit";
  transfer_type: "to" | "from";
  title: string;
  balance: number;
  balance_val: string;
  status: "active" | "inactive" | "suspended";
  currency: string;
}

export interface ActiveTradingAccount {
  account_id: number | null;
  account_type: string;
  name: string | null;
  trading_server: string | null;
}

// The resolved account's full wallet-component breakdown -- only meaningful
// once the tenant has wallet_components_enabled on (see siteSettingStore);
// deposit_val/credit_val/profit_val are all 0 until then. Distinct from
// ActiveTradingAccount, which deliberately excludes balance fields.
export interface TradingAccountBalance {
  id: string;
  account_id: number | null;
  balance_val: number;
  bonus_val: number;
  deposit_val: number;
  credit_val: number;
  profit_val: number;
  equity: number;
  available_balance: number;
  withdrawable_balance: number;
}

// The backend's trading_account payload also carries the account's plain
// password (deliberately, for the copy-credentials UI elsewhere) and
// balance -- neither belongs in this persisted, long-lived store, so only
// pick the fields the header badge actually needs before it's saved to
// localStorage.
function toActiveTradingAccount(
  source: Partial<ActiveTradingAccount> | null | undefined
): ActiveTradingAccount | null {
  if (!source) return null;
  return {
    account_id: source.account_id ?? null,
    account_type: source.account_type ?? "",
    name: source.name ?? null,
    trading_server: source.trading_server ?? null,
  };
}

export interface UserNotification {
  id: string;
  title: string;
  message: string;
  image: string | null;
  color: "danger" | "success" | "warning" | null;
  type: string;
  time: string;
  read_at: string | null;
  created_at: string | null;
  read: boolean;
}

export interface User {
  id: string;
  account_id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  country_code: string | null;
  country: string | null;
  address: string | null;
  city?: string | null;
  zip_code?: string | null;
  /**
   * Account Settings fields with no column of their own; the API stores them in
   * users.settings and returns them alongside the rest. See App\Support\ClientProfile.
   */
  apartment?: string | null;
  tax_id?: string | null;
  emergency_contact?: string | null;
  emergency_contact_phone?: string | null;
  preferred_communication?: string | null;
  /** Opt-out from the sign-in alert email; absent means on. */
  signin_alerts?: boolean | null;
  two_factor_enabled?: boolean;
  username: string | null;
  avatar: string;
  balance: number;
  available_balance?: number | string;
  savings_balance: number;
  credit_balance: number;
  birth_date?: string;
  custom_wallet?: string;
  copy_trader: number;
  autotrader: boolean;
  autotrader_status: string;
  ai_trade: boolean;
  ai_agreement_completed?: boolean;
  ai_agreement_duration?: "12_24" | "36_60" | null;
  ai_agreement_markets?: string[];
  ai_agreement_commodity_instruments?: string[];
  verification_status: string;
  notification_msg: string | null;
  status: string;
  can_open_trade: boolean;
  can_close_trade: boolean;
  trades_summary: {
    total_pnl: number;
    total_wins: number;
    total_losses: number;
    total_deposit: number;
    trades_count: number;
    win_rate: number;
  };
  account_type: {
    id: string;
    title: string;
    leverage: number;
    icon: string;
    color: string;
    image: string;
    expert_advisors: string[]; // Array of expert advisor IDs available for this plan
  };
  eas? : string[];
  accounts: UserAccount[];
  notifications: UserNotification[];
  campaign?: string;
  source?: string;
  trading_account?: TradingAccountBalance | null;
}

interface UserStore {
  user: User | null;
  token: string | null;
  selectedAccountIndex: number;
  // The session's resolved trading account, when unambiguous. Set at login
  // (webtrader/account-ID flow) and refreshed on every getCurrentUser() call
  // (normal client-area sessions too) -- never carries the account's
  // password/balance, see toActiveTradingAccount().
  activeTradingAccount: ActiveTradingAccount | null;
  // True only when this webtrader session was reached via the client-area
  // "Trade Room" launch-token flow (WebtraderLaunchConsumePage) -- false for
  // a direct login on the webtrader login form itself. Gates whether the
  // header's "Back to Dashboard" affordance shows: a direct login has no
  // client-area session to go back to, so it must stay hidden there. Not
  // meaningful outside a webtrader origin. Persisted so it survives a
  // reload/reopened tab for the same session, same as the rest of this store.
  viaClientAreaLaunch: boolean;
  setUser: (user: User, token: string, tradingAccount?: ActiveTradingAccount | null) => void;
  setViaClientAreaLaunch: (value: boolean) => void;
  clearUser: () => void;
  isLoading: boolean;
  setSelectedAccountIndex: (index: number) => void;
  getCurrentUser: () => Promise<void>;
}

const useUserStore = create<UserStore>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      selectedAccountIndex: 0,
      activeTradingAccount: null,
      viaClientAreaLaunch: false,
      // tradingAccount is optional because most callers are patching an
      // unrelated user field (profile edit, AI-trade toggle, agreement
      // acceptance) and have no business touching the active trading
      // account -- omitting it must preserve whatever's already resolved,
      // not silently clear it. The one call site that actually needs to
      // set/reset it (login, see session.ts) always passes it explicitly.
      setUser: (user, token, tradingAccount = get().activeTradingAccount) =>
        set({ user, token, activeTradingAccount: toActiveTradingAccount(tradingAccount) }),
      setViaClientAreaLaunch: (value) => set({ viaClientAreaLaunch: value }),
      clearUser: () =>
        set({
          user: null,
          token: null,
          selectedAccountIndex: 0,
          activeTradingAccount: null,
          viaClientAreaLaunch: false,
        }),
      isLoading: true,
      setSelectedAccountIndex: (index) => set({ selectedAccountIndex: index }),
      getCurrentUser: async () => {
        // Don't attempt to fetch if no token is available
        const token = get().token;
        if (!token) {
          set({ isLoading: false });
          return;
        }

        set({ isLoading: true });
        try {
          const response = await axiosInstance.get("/user");

          if (response.data && response.data.data) {
            // trading_account is the server's resolved active account for
            // this session (same resolution used by the trade room's
            // balance) -- refresh it here too so account details (and the
            // header badge) show up on a normal client-area session, not
            // just a webtrader login.
            set({
              user: response.data.data,
              activeTradingAccount: response.data.data.trading_account
                ? toActiveTradingAccount(response.data.data.trading_account)
                : get().activeTradingAccount,
              isLoading: false,
            });
          }
        } catch (error) {
          console.error("Failed to fetch user data:", error);
          // Don't clear user on error, just set loading to false
          set({ isLoading: false });
        }
      },
    }),
    {
      name: "user-storage",
      onRehydrateStorage: () => (state) => {
        if (state) state.isLoading = false;
      },
    }
  )
);

export default useUserStore;
