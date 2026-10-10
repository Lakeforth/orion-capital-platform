import { create } from "zustand";
import { persist } from "zustand/middleware";
import { Asset } from "./assetStore";

interface WatchlistStore {
  watchlist: Asset[];
  addToWatchlist: (asset: Asset) => void;
  removeFromWatchlist: (symbolDisplay: string) => void;
  isInWatchlist: (symbolDisplay: string) => boolean;
}

const useWatchlistStore = create<WatchlistStore>()(
  persist(
    (set, get) => ({
      watchlist: [],
      addToWatchlist: (asset) => {
        const isAlreadyInWatchlist = get().watchlist.some(
          (item) => item.symbol_display === asset.symbol_display
        );
        if (!isAlreadyInWatchlist) {
          set((state) => ({
            watchlist: [...state.watchlist, asset],
          }));
        }
      },
      removeFromWatchlist: (symbolDisplay) => {
        set((state) => ({
          watchlist: state.watchlist.filter(
            (item) => item.symbol_display !== symbolDisplay
          ),
        }));
      },
      isInWatchlist: (symbolDisplay) => {
        return get().watchlist.some(
          (item) => item.symbol_display === symbolDisplay
        );
      },
    }),
    {
      name: "watchlist-storage",
    }
  )
);

export default useWatchlistStore;
