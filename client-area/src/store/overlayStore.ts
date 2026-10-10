import { create } from "zustand";
import useDataStore from "./dataStore";

export type PanelType =
  | "trade-room"
  | "market-watch"
  | "active-orders"
  | "trading-history"
  | "calendar"
  | "market-news"
  | "watch-list"
  | "financial-history"
  | "video-guides"
  | "technical-analysis"
  | null;

interface OverlayState {
  automatedTrading: boolean;
  selectedAdvisorId: string | undefined;
  activePanel: PanelType;
  showDepositModal: boolean;
  setAutomatedTrading: (trading: boolean, advisorId?: string) => void;
  setActivePanel: (panel: PanelType) => void;
  setShowDepositModal: (show: boolean) => void;
}

const useOverlayStore = create<OverlayState>((set) => ({
  automatedTrading: false,
  selectedAdvisorId: undefined,
  activePanel: null,
  showDepositModal: false,
  setAutomatedTrading: (trading, advisorId) => {
    const { activeEA } = useDataStore.getState();
    if (trading && !activeEA) {
      return;
    }
    set({ automatedTrading: trading, selectedAdvisorId: advisorId });
  },
  setActivePanel: (panel) => set({ activePanel: panel }),
  setShowDepositModal: (show) => set({ showDepositModal: show }),
}));

export default useOverlayStore;
