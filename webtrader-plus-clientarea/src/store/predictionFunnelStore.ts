import { create } from "zustand";
import { persist } from "zustand/middleware";

/**
 * Prediction funnel store.
 *
 * Same shape as the Quantum `funnelStore`, but persisted under its own key so
 * the two funnels never overwrite each other's lead data in localStorage.
 */

interface PredictionFunnelData {
  whatsapp_number: string | null;
  whatsapp_message: string | null;
  campaign_name: string | null;
  email: string | null;
  /** Captured on the survey; the checkout shows it back for reassurance. */
  full_name: string | null;
  /** The campaign's own logo, from the first campaign lookup on the survey. */
  logo: string | null;
  /** Backend-supplied post-capture redirect (from `POST /c/{uuid}/capture`).
   *  For auto-login campaigns this is `/auto-login?token=...`; checkout
   *  forwards to it instead of the thank-you page. */
  redirect_url: string | null;
}

const EMPTY: PredictionFunnelData = {
  whatsapp_number: null,
  whatsapp_message: null,
  campaign_name: null,
  email: null,
  full_name: null,
  logo: null,
  redirect_url: null,
};

interface PredictionFunnelStore {
  data: PredictionFunnelData | null;
  setFunnelData: (data: PredictionFunnelData) => void;
  /** Merge a partial update, preserving fields set at other funnel stages. */
  patchFunnelData: (patch: Partial<PredictionFunnelData>) => void;
  clearFunnelData: () => void;
}

const usePredictionFunnelStore = create<PredictionFunnelStore>()(
  persist(
    (set) => ({
      data: null,
      setFunnelData: (data) => set({ data }),
      patchFunnelData: (patch) =>
        set((s) => ({ data: { ...EMPTY, ...s.data, ...patch } })),
      clearFunnelData: () => set({ data: null }),
    }),
    {
      name: "prediction-funnel-storage",
    },
  ),
);

export default usePredictionFunnelStore;
