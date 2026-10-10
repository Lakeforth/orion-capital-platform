import { create } from "zustand";
import { persist } from "zustand/middleware";
import axiosInstance from "@/lib/axios";

export interface SiteSettings {
  status: "active" | "disabled";
  name: string;
  logo?: string;
  r_page?: string;
  l_page?: string;
  fp_page?: string;
  logo_sm?: string;
  /**
   * Logo for the client area's sign-in page only. The API already falls back
   * to the frontend logo when a tenant has not set one, so this is always
   * populated -- it is never "the login logo or nothing".
   */
  login_logo?: string;
  expert_advisor?: boolean;
  favicon?: string;
  theme?: string;
  contactEmail?: string;
  contact_phone?: string | null;
  website_url?: string;
  privacy_url?: string;
  terms_url?: string;
  help_url?: string;
  risk_url?: string;
  login_page_header?: string;
  login_page_footer?: string;
  register_page_header?: string;
  register_page_footer?: string;
  wt_l_page?: string;
  maintenanceMessage?: string;
  // Add other settings as needed
  lastFetched: number;
  is_mt4: boolean;
  must_verify_account: boolean;
  has_marketplace: boolean;
  livetrader_status?: boolean;
  credit_card_deposit?: boolean;
  enable_plan?: boolean;
  enable_autotrader?: boolean;
  enabled_ai_trade?: boolean;
  require_ai_trading_agreement?: boolean;
  use_deposit_modal?: boolean;
  close_all_trades_enabled?: boolean;
  /** "Fund Your Account" popup. On unless the tenant turns the module off. */
  deposit_prompt_enabled?: boolean;
  whatsapp_number?: string | null;
  enable_whatsapp_chat?: boolean;
  is_demo?: boolean;
  bitrace_enabled?: boolean;
  propfirm_enabled?: boolean;
  propfirm_sidebar_color?: string | null;
  propfirm_accent_color?: string | null;
  webtrader_enabled?: boolean;
  webtrader_url?: string | null;
  // Tenant's client-area dashboard URL -- lets a standalone webtrader
  // session (different origin, no client-area routes mounted) navigate
  // back to the real dashboard instead of guessing one from its own host.
  client_area_url?: string | null;
  wallet_components_enabled?: boolean;
  recovery_welcome_title?: string;
  recovery_welcome_message?: string;
  recovery_welcome_email_enabled?: boolean;
  demo?: {
    enabled: boolean;
    frontend_login: { email: string; password: string };
    backend_login: { email: string; password: string };
    manager_login: { email: string; password: string };
    telegram_link: string;
    whatsapp_link: string;
    reset_interval_hours: number;
    last_reset_at: string;
    next_reset_at: string;
    remaining_seconds: number;
  };
}

interface SiteSettingsState {
  settings: SiteSettings | null;
  isLoading: boolean;
  error: string | null;
  baseUrl: string;
  initialized: boolean;
  setBaseUrl: (url: string) => void;
  fetchSettings: () => Promise<void>;
  clearSettings: () => void;
  setInitialized: (value: boolean) => void;
}

const useSiteSettingsStore = create<SiteSettingsState>()(
  persist(
    (set, get) => ({
      settings: null,
      isLoading: false,
      error: null,
      baseUrl: "",
      initialized: false,

      setBaseUrl: (url: string) => {
        set({ baseUrl: url });
      },

      setInitialized: (value: boolean) => {
        set({ initialized: value });
      },

      clearSettings: () => {
        set({ settings: null });
      },

      fetchSettings: async () => {
        const { baseUrl } = get();

        if (!baseUrl) {
          set({ error: "No base URL configured", settings: null });
          return;
        }

        // Only set loading to true if we don't have settings yet
        set({ isLoading: true, error: null });

        try {
          // Make the request to get settings using the existing axios instance
          const response = await axiosInstance.get("/settings");

          // Update the store with the fetched settings
          set({
            settings: {
              ...response?.data?.data,
              lastFetched: Date.now(),
            },
            isLoading: false,
            error: null,
            initialized: true,
          });

          return response.data;
        } catch (error) {
          console.error("Failed to fetch site settings:", error);

          // IMPORTANT: Clear settings when API call fails
          set({
            settings: null,
            error:
              "Failed to fetch site settings. The site might be unreachable.",
            isLoading: false,
            initialized: true,
          });

          throw error;
        }
      },
    }),
    {
      name: "site-settings-storage",
      // Only persist settings, not baseUrl. `initialized` is intentionally excluded:
      // persisting it let SiteProvider's loading gate pass instantly on repeat visits
      // using stale cached settings (e.g. propfirm_enabled), flashing the wrong
      // client-area layout before the real fetch resolved.
      partialize: (state) => ({
        settings: state.settings,
        error: state.error,
      }),
      // Browsers that already cached `initialized: true` from before this fix would
      // otherwise still rehydrate it as true here and reintroduce the same flash --
      // force it false on every load so a real fetch always runs first, regardless
      // of what's sitting in that browser's localStorage already.
      merge: (persistedState, currentState) => ({
        ...currentState,
        ...(persistedState as Partial<SiteSettingsState>),
        initialized: false,
      }),
    }
  )
);

export default useSiteSettingsStore;
