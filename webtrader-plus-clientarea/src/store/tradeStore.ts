import { create } from "zustand";
import axiosInstance from "@/lib/axios";
import useUserStore from "@/store/userStore.ts";
import useSiteSettingsStore from "@/store/siteSettingStore";
import useAssetStore, { Asset } from "@/store/assetStore";
import { calculateUsdPnl, quoteToUsd } from "@/lib/trade-pnl";

export interface Trade {
  id: string;
  trade_id: string;
  trade_type: "buy" | "sell";
  leverage: number;
  margin: number;
  amount: number;
  // Raw contract units (lots x the instrument's contract size) -- correct
  // for PnL/margin math (see calculatePnl/calculateMargin below), wrong for
  // display. volume_lots is what a client actually typed and should see.
  volume: number;
  volume_lots?: number;
  opening_price: number;
  closing_price: number;
  paid_commission: number;
  swap: number;
  pnl: number;
  take_profit: number;
  stop_loss: number;
  is_closed: boolean;
  open_time: string;
  close_time: string;
  asset_id: string;
  asset_symbol: string;
  asset_name: string;
  asset_category?: string;
}

export interface AccountSummary {
  balance: number;
  credit: number;
  equity: number;
  margin: number;
  marginLevel: string;
  freeMargin: number;
  pnl: number;
  lifetimePnl: number;
}

export interface PaginationMeta {
  current_page: number;
  from: number;
  path: string;
  per_page: number;
  to: number;
}

export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

interface TradeStore {
  openTrades: Trade[];
  closedTrades: Trade[];
  accountSummary: AccountSummary;
  isLoadingOpen: boolean;
  isLoadingClosed: boolean;
  errorOpen: string | null;
  errorClosed: string | null;
  openTradesMeta: PaginationMeta | null;
  closedTradesMeta: PaginationMeta | null;
  openTradesLinks: PaginationLinks | null;
  closedTradesLinks: PaginationLinks | null;
  wsConnected: boolean;

  fetchOpenTrades: (page?: number) => Promise<void>;
  fetchClosedTrades: (page?: number) => Promise<void>;
  fetchMoreOpenTrades: () => Promise<void>;
  fetchMoreClosedTrades: () => Promise<void>;
  hasMoreOpenTrades: () => boolean;
  hasMoreClosedTrades: () => boolean;
  resetTrades: () => void;
  syncPricesFromAssetStore: () => void;
  calculateAccountSummary: () => void;
  disconnectWebSocket: () => void;
}

// Module-level unsubscribe handle for the assetStore price subscription --
// mirrors the lifecycle role the old module-level `socket` variable played,
// but there's no separate connection to manage anymore (see below).
let unsubscribeFromAssetStore: (() => void) | null = null;

// Closing-price convention matches deriveBidAsk() in assetStore.ts: buy_price
// is what a client pays to BUY (the broker's ask), sell_price is what a
// client receives when they SELL (the broker's bid). An open BUY position's
// live value is what you'd get closing it right now -- i.e. selling -- so it
// must price off sell_price. An open SELL position closes by buying, so it
// must price off buy_price. Falls back to the raw rate if a spread-adjusted
// price isn't available for some reason, rather than silently using 0.
function closingPriceFor(trade: Trade, asset: Asset): number {
  const spreadPrice = trade.trade_type === "buy" ? asset.sell_price : asset.buy_price;
  if (Number.isFinite(spreadPrice) && spreadPrice > 0) {
    return spreadPrice;
  }
  return Number.parseFloat(asset.rate);
}

const defaultAccountSummary: AccountSummary = {
  balance: 0,
  credit: 0.0,
  equity: 0,
  margin: 0.0,
  marginLevel: "∞",
  freeMargin: 0,
  pnl: 0,
  lifetimePnl: 0,
};

// trade.volume is already lots x the instrument's contract size (see the
// Trade interface comment above), so no separate contract-size factor
// belongs here -- multiplying by it again would double-count it.

// marginRate, not the instrument's raw current price -- see the order-entry
// fix in components/trading/trading-interface.tsx for the full rationale.
// Raw price is only USD-denominated when the pair's quote currency is USD;
// for USD-base pairs (USDJPY) or true crosses (GBPJPY, EURGBP) it's in the
// wrong currency, which made an open JPY position's contribution to
// freeMargin/marginLevel ~150x too large.
const calculateMargin = (trade: Trade, marginRate: number): number => {
  return (trade.volume * marginRate) / trade.leverage;
};

// Signature of the last page-1 closed trades response (total + newest id). The
// polling fallbacks (NotificationListener, used whenever the websocket isn't
// connected -- i.e. always, while the build has no real Pusher key) refresh
// trades but not the user, so a close showed its P/L in the history while the
// balance stayed stale until something else happened to refetch the user.
// Refetch the user only when the closed list actually changed, not every poll.
let lastClosedSignature: string | null = null;

const useTradeStore = create<TradeStore>((set, get) => ({
  openTrades: [],
  closedTrades: [],
  accountSummary: { ...defaultAccountSummary },
  isLoadingOpen: false,
  isLoadingClosed: false,
  errorOpen: null,
  errorClosed: null,
  openTradesMeta: null,
  closedTradesMeta: null,
  openTradesLinks: null,
  closedTradesLinks: null,
  wsConnected: false,

  fetchOpenTrades: async (page = 1) => {
    const { openTrades } = get();
    // Only show loading state if we have no data
    if (openTrades.length === 0) {
      set({ isLoadingOpen: true, errorOpen: null });
    } else {
      set({ errorOpen: null });
    }

    if (page === 1) {
      set({
        openTradesMeta: null,
        openTradesLinks: null,
      });
    }

    try {
      const response = await axiosInstance.get(`/open/trades?page=${page}`);

      // Update trades in store
      if (response.data && Array.isArray(response.data.data)) {
        if (page === 1) {
          set({
            openTrades: response.data.data,
            openTradesMeta: response.data.meta || null,
            openTradesLinks: response.data.links || null,
          });
        } else {
          set((state) => ({
            openTrades: [...state.openTrades, ...response.data.data],
            openTradesMeta: response.data.meta || null,
            openTradesLinks: response.data.links || null,
          }));
        }
      }

      // Start (or confirm) the live price subscription if we have trades.
      // subscribeToAssetPriceUpdates() only does its own immediate sync the
      // very first time it's ever called (it's a no-op once already
      // subscribed, which is the normal case here -- the trade room mounts
      // and subscribes long before a customer opens their first trade of
      // the session). Without a direct sync call here too, a newly-opened
      // trade sits at whatever price the server set at creation time until
      // the next live tick happens to arrive, which is exactly the "price
      // doesn't match the chart" window QA reported on a freshly opened
      // GBP/USD position.
      const { openTrades } = get();
      if (openTrades.length > 0) {
        subscribeToAssetPriceUpdates();
        get().syncPricesFromAssetStore();
      }

      // Calculate account summary
      get().calculateAccountSummary();
    } catch (error) {
      console.error("Failed to fetch open trades:", error);
      if (page === 1) {
        set({
          openTradesMeta: null,
          openTradesLinks: null,
          errorOpen: "Failed to fetch open trades. Please try again.",
        });
        get().calculateAccountSummary();
      } else {
        set({ errorOpen: "Failed to fetch open trades. Please try again." });
      }
    } finally {
      set({ isLoadingOpen: false });
    }
  },

  fetchClosedTrades: async (page = 1) => {
    const { closedTrades } = get();
    // Only show loading state if we have no data
    if (closedTrades.length === 0) {
      set({ isLoadingClosed: true, errorClosed: null });
    } else {
      set({ errorClosed: null });
    }

    if (page === 1) {
      set({
        closedTradesMeta: null,
        closedTradesLinks: null,
      });
    }

    try {
      const response = await axiosInstance.get(`/closed/trades?page=${page}`);

      if (response.data && Array.isArray(response.data.data)) {
        if (page === 1) {
          set({
            closedTrades: response.data.data,
            closedTradesMeta: response.data.meta || null,
            closedTradesLinks: response.data.links || null,
          });
          const signature = `${response.data.meta?.total ?? response.data.data.length}:${response.data.data[0]?.id ?? ""}`;
          if (lastClosedSignature !== null && signature !== lastClosedSignature) {
            useUserStore.getState().getCurrentUser();
          }
          lastClosedSignature = signature;
        } else {
          set((state) => ({
            closedTrades: [...state.closedTrades, ...response.data.data],
            closedTradesMeta: response.data.meta || null,
            closedTradesLinks: response.data.links || null,
          }));
        }
      }
    } catch (error) {
      console.error("Failed to fetch closed trades:", error);
      if (page === 1) {
        set({
          closedTradesMeta: null,
          closedTradesLinks: null,
          errorClosed: "Failed to fetch closed trades. Please try again.",
        });
      } else {
        set({ errorClosed: "Failed to fetch closed trades. Please try again." });
      }
    } finally {
      set({ isLoadingClosed: false });
    }
  },

  fetchMoreOpenTrades: async () => {
    const { openTradesMeta, openTradesLinks, isLoadingOpen } = get();

    if (isLoadingOpen || !openTradesLinks?.next) return;

    const nextPage = openTradesMeta ? openTradesMeta.current_page + 1 : 1;
    await get().fetchOpenTrades(nextPage);
  },

  fetchMoreClosedTrades: async () => {
    const { closedTradesMeta, closedTradesLinks, isLoadingClosed } = get();

    if (isLoadingClosed || !closedTradesLinks?.next) return;

    const nextPage = closedTradesMeta ? closedTradesMeta.current_page + 1 : 1;
    await get().fetchClosedTrades(nextPage);
  },

  hasMoreOpenTrades: () => {
    const { openTradesLinks } = get();
    return !!openTradesLinks?.next;
  },

  hasMoreClosedTrades: () => {
    const { closedTradesLinks } = get();
    return !!closedTradesLinks?.next;
  },

  resetTrades: () => {
    if (unsubscribeFromAssetStore) {
      unsubscribeFromAssetStore();
      unsubscribeFromAssetStore = null;
    }

    set({
      openTrades: [],
      closedTrades: [],
      openTradesMeta: null,
      closedTradesMeta: null,
      openTradesLinks: null,
      closedTradesLinks: null,
      accountSummary: { ...defaultAccountSummary },
      errorOpen: null,
      errorClosed: null,
      isLoadingOpen: false,
      isLoadingClosed: false,
      wsConnected: false,
    });
  },

  // Driven by assetStore's own price feed (see subscribeToAssetPriceUpdates
  // below) instead of a second, independent websocket connection -- two
  // separate socket.io connections authenticated with the same API key to
  // the same upstream feed let one silently starve while the other kept
  // working, which is why P/L could sit frozen while the chart kept ticking.
  // Every open position now reads off the exact same tick stream the rest of
  // the UI does, so they can't disagree.
  syncPricesFromAssetStore: () => {
    const { openTrades } = get();
    if (openTrades.length === 0) return;

    const assets = useAssetStore.getState().assets;
    if (assets.length === 0) return;

    const assetsById = new Map(assets.map((asset) => [asset.asset_id, asset]));

    let changed = false;
    const updatedTrades = openTrades.map((trade) => {
      const asset = assetsById.get(trade.asset_id);
      if (!asset) return trade;

      const newPrice = closingPriceFor(trade, asset);
      const quoteRate = quoteToUsd(asset, assets);
      // Preserve server P&L if conversion is unavailable, rather than label
      // a raw foreign-currency amount as dollars.
      if (!Number.isFinite(newPrice) || newPrice <= 0 || quoteRate === null) {
        return trade;
      }
      const pnl = calculateUsdPnl(trade.trade_type, trade.opening_price, newPrice, trade.volume, quoteRate);
      if (newPrice === trade.closing_price && pnl === trade.pnl) {
        return trade;
      }

      changed = true;
      return {
        ...trade,
        closing_price: newPrice,
        pnl,
      };
    });

    if (!changed) return;

    set({ openTrades: updatedTrades });
    get().calculateAccountSummary();
  },

  calculateAccountSummary: () => {
    const { openTrades } = get();

    const user = useUserStore.getState().user;
    const balance = user?.balance || 0;
    const credit = user?.credit_balance || 0;

    const totalPnL = openTrades.reduce((sum, trade) => sum + trade.pnl, 0);

    // Calculate margin. Look up each trade's live margin_rate from the
    // asset store (same pattern as syncPricesFromAssetStore) -- fall back
    // to closing_price only if the asset isn't loaded, matching prior
    // behavior rather than silently zeroing out margin for it.
    const assetsById = new Map(
      useAssetStore.getState().assets.map((asset) => [asset.asset_id, asset])
    );
    const totalMargin = openTrades.reduce((sum, trade) => {
      const marginRate =
        assetsById.get(trade.asset_id)?.margin_rate ?? trade.closing_price;
      return sum + calculateMargin(trade, marginRate);
    }, 0);

    const equity = balance + totalPnL;
    const freeMargin = equity - totalMargin;

    // Calculate margin level - handle division by zero
    const marginLevel =
      totalMargin > 0 ? ((equity / totalMargin) * 100).toFixed(2) + "%" : "—";

    set({
      accountSummary: {
        balance,
        credit,
        equity,
        margin: totalMargin,
        marginLevel,
        freeMargin,
        pnl: totalPnL,
        lifetimePnl: 460.05, // Placeholder value
      },
    });
  },

  disconnectWebSocket: () => {
    if (unsubscribeFromAssetStore) {
      unsubscribeFromAssetStore();
      unsubscribeFromAssetStore = null;
    }
    set({ wsConnected: false });
  },
}));

// Subscribes tradeStore to assetStore's already-live price feed (mounted
// globally per trading layout via AssetPriceStreamInitializer/useAssetWebSocket
// -- see assetStore.ts) instead of opening a second connection of its own.
// assetStore.assets is a new array reference on every tick batch
// (updateAssetFromWebsocket), so comparing references here is enough to
// detect "a tick arrived" without diffing every field.
function subscribeToAssetPriceUpdates() {
  const livetraderStatus =
    useSiteSettingsStore.getState().settings?.livetrader_status ?? true;

  if (!livetraderStatus) {
    if (unsubscribeFromAssetStore) {
      unsubscribeFromAssetStore();
      unsubscribeFromAssetStore = null;
    }
    useTradeStore.setState({ wsConnected: false });
    return;
  }

  if (unsubscribeFromAssetStore) return; // already subscribed

  // Sync immediately in case assetStore already has live prices (the normal
  // case -- its own websocket/fallback poll starts as soon as the trading
  // layout mounts, independent of when trades get fetched).
  useTradeStore.getState().syncPricesFromAssetStore();

  unsubscribeFromAssetStore = useAssetStore.subscribe((state, prevState) => {
    if (state.assets !== prevState.assets) {
      useTradeStore.getState().syncPricesFromAssetStore();
    }
  });

  useTradeStore.setState({ wsConnected: true });
}

// The account summary's balance and credit come from useUserStore's user.
// Callers that refresh the user and the trades in parallel (the .trade.updated
// handler after a close, for one) let calculateAccountSummary() run against the
// pre-close balance, and nothing re-ran it once the fresh user landed: the
// closed P/L showed in the history while Balance stayed stale until some
// unrelated tick. Recompute whenever the user's balance or credit changes.
useUserStore.subscribe((state, prevState) => {
  if (
    state.user?.balance !== prevState.user?.balance ||
    state.user?.credit_balance !== prevState.user?.credit_balance
  ) {
    useTradeStore.getState().calculateAccountSummary();
  }
});

export default useTradeStore;
