import useAssetStore from "@/store/assetStore";
import useDataStore from "@/store/dataStore";
import useMarketplaceStore from "@/store/marketplaceStore";
import useSavingsStore from "@/store/savingsStore";
import useTradeStore from "@/store/tradeStore";
import useUserStore, { type ActiveTradingAccount, type User } from "@/store/userStore";
import useWatchlistStore from "@/store/watchlistStore";

const DEFAULT_ASSET_PAIR = "BTC/USD";

const DEFAULT_LEVERAGE = {
  cryptos: 100,
  stocks: 100,
  indices: 100,
  commodities: 100,
  metals: 100,
  forex: 100,
};

const USER_SCOPED_STORAGE_KEYS = [
  "watchlist-storage",
  "marketplace-storage",
  "asset-storage",
];

function clearPersistedUserScopedStorage() {
  if (typeof window === "undefined") {
    return;
  }

  USER_SCOPED_STORAGE_KEYS.forEach((key) => {
    localStorage.removeItem(key);
  });
}

export function resetUserScopedStores() {
  useTradeStore.getState().resetTrades();

  useDataStore.setState({
    data: null,
    leverage: { ...DEFAULT_LEVERAGE },
    isLoading: false,
    error: null,
    activeEA: null,
  });

  useSavingsStore.setState({
    plans: [],
    userSavings: [],
    isLoading: false,
    error: null,
  });

  useWatchlistStore.setState({
    watchlist: [],
  });

  useMarketplaceStore.setState({
    items: [],
    isLoading: false,
    error: null,
    total: 0,
    currentPage: 1,
    totalPages: 1,
    purchasedItems: [],
  });

  useAssetStore.setState({
    activePairs: [DEFAULT_ASSET_PAIR],
    activePair: DEFAULT_ASSET_PAIR,
    activeAsset: null,
    lastWebsocketUpdateByAsset: {},
  });

  clearPersistedUserScopedStorage();
}

export function clearAuthenticatedSession() {
  resetUserScopedStores();
  useUserStore.getState().clearUser();
}

export function replaceAuthenticatedUser(
  user: User,
  token: string,
  tradingAccount?: ActiveTradingAccount | null,
  viaClientAreaLaunch = false,
) {
  resetUserScopedStores();
  useUserStore.getState().setUser(user, token, tradingAccount ?? null);
  useUserStore.getState().setViaClientAreaLaunch(viaClientAreaLaunch);
}
