import { create } from "zustand";
import { persist } from "zustand/middleware";

/**
 * Trivon Funded funnel store.
 *
 * Same shape as the Prediction `predictionFunnelStore`, but persisted under its
 * own key so the two funnels never overwrite each other's lead data in
 * localStorage. Populated by the survey from the campaign lookup and the
 * capture response (see trivonApi.ts).
 */

interface TrivonFunnelData {
  whatsapp_number: string | null;
  whatsapp_message: string | null;
  campaign_name: string | null;
  email: string | null;
  /** Captured on the survey; the checkout shows it back for reassurance. */
  full_name: string | null;
  /** The campaign's own logo, when it supplies one. */
  logo: string | null;
  /** Backend-supplied post-capture redirect. For `end_zone === 'platform'`
   *  campaigns the checkout forwards to it instead of the thank-you page. */
  redirect_url: string | null;
}

const EMPTY: TrivonFunnelData = {
  whatsapp_number: null,
  whatsapp_message: null,
  campaign_name: null,
  email: null,
  full_name: null,
  logo: null,
  redirect_url: null,
};

interface TrivonFunnelStore {
  data: TrivonFunnelData | null;
  setFunnelData: (data: TrivonFunnelData) => void;
  /** Merge a partial update, preserving fields set at other funnel stages. */
  patchFunnelData: (patch: Partial<TrivonFunnelData>) => void;
  clearFunnelData: () => void;
}

const useTrivonFunnelStore = create<TrivonFunnelStore>()(
  persist(
    (set) => ({
      data: null,
      setFunnelData: (data) => set({ data }),
      patchFunnelData: (patch) =>
        set((s) => ({ data: { ...EMPTY, ...s.data, ...patch } })),
      clearFunnelData: () => set({ data: null }),
    }),
    {
      name: "trivon-funnel-storage",
    },
  ),
);

export default useTrivonFunnelStore;
