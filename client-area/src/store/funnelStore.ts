import { create } from "zustand";
import { persist } from "zustand/middleware";

interface FunnelData {
  whatsapp_number: string | null;
  whatsapp_message: string | null;
  campaign_name: string | null;
  email: string | null;
}

interface FunnelStore {
  data: FunnelData | null;
  setFunnelData: (data: FunnelData) => void;
  clearFunnelData: () => void;
}

const useFunnelStore = create<FunnelStore>()(
  persist(
    (set) => ({
      data: null,
      setFunnelData: (data) => set({ data }),
      clearFunnelData: () => set({ data: null }),
    }),
    {
      name: "funnel-storage",
    }
  )
);

export default useFunnelStore;
