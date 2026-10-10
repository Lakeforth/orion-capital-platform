import { Suspense, lazy, useCallback, useEffect, useRef } from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Routes, Route, Navigate, useLocation } from "react-router-dom";
import { Toaster } from "@/components/ui/sonner";
import { SiteProvider } from "@/components/provider/SiteProvider.tsx";
import { usePusher } from "@/hooks/usePusher";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import useTradeStore from "@/store/tradeStore.ts";
import useDataStore from "@/store/dataStore";
import useAssetStore from "@/store/assetStore";
import LoadingScreen from "@/components/loading-screen";

// This standalone bundle never had a QueryClientProvider -- it didn't need
// one as long as nothing in its own import tree used React Query. That
// stopped being true once wallet-component code (usePortfolioSummaryQuery
// etc., only ever intended for the main client-area dashboard) ended up
// sharing a Vite-generated chunk with market-news-panel.tsx, which
// TradingPlatform genuinely does import for the trade room's own Market
// News tab -- Vite's automatic code-splitting merges by shared dependency
// graph, not by architectural intent, so anything reachable from either
// bundle can end up co-located. Real crash confirmed live: "No QueryClient
// set, use QueryClientProvider to set one" on webtrader.dominion-trade.com.
// Adding the provider here (mirroring App.tsx) is the robust fix -- it
// makes this bundle resilient to any future accidental chunk-sharing, not
// just this one instance.
const webtraderQueryClient = new QueryClient();

const WebtraderLoginPage = lazy(() => import("./WebtraderLoginPage"));
const WebtraderForgotPasswordPage = lazy(() => import("./WebtraderForgotPasswordPage"));
const WebtraderResetPasswordPage = lazy(() => import("./WebtraderResetPasswordPage"));
const WebtraderLaunchConsumePage = lazy(() => import("./WebtraderLaunchConsumePage"));
const TradingPlatform = lazy(() => import("@/components/trading/trading-interface-components/trading-platform"));
const TradingPlatformLight = lazy(() =>
    import("@/components/mt4-light/trading-platform-light.tsx").then((m) => ({ default: m.TradingPlatformLight })),
);
const Plus50AiAgreementGate = lazy(() => import("@/components/trading/plus50-agreement/Plus50AiAgreementGate"));

const TRADE_SYNC_POLLING_INTERVAL = 30000;

/**
 * Keeps the trade room in sync with trades closed somewhere other than
 * this window -- the trades:auto-close-expired scheduler, or the same
 * account open in the main client-area app. NotificationListener already
 * does this for the client-area, but it's never mounted here (this bundle
 * deliberately excludes the whole client-area tree), so without this the
 * balance/position display would only ever refresh from a close the user
 * triggers in this same window.
 *
 * Falls back to 30s polling whenever the socket isn't actually connected,
 * same as NotificationListener does for the client-area -- this used to be
 * websocket-only, so a Pusher connection that never came up (auth failure,
 * network/proxy issue, briefly dropped) meant this never synced at all
 * until a manual page refresh, with no fallback of any kind.
 */
function WebtraderTradeSync() {
    const { subscribeToPrivateChannel, unsubscribeFromChannel, connectionStatus } = usePusher();
    const userId = useUserStore((state) => state.user?.id);
    const getCurrentUser = useUserStore((state) => state.getCurrentUser);
    const fetchOpenTrades = useTradeStore((state) => state.fetchOpenTrades);
    const fetchClosedTrades = useTradeStore((state) => state.fetchClosedTrades);
    const subscribed = useRef(false);

    const syncTrades = useCallback(() => {
        getCurrentUser();
        fetchOpenTrades();
        fetchClosedTrades();
    }, [getCurrentUser, fetchOpenTrades, fetchClosedTrades]);

    useEffect(() => {
        if (connectionStatus !== "connected" || !userId) return;

        if (!subscribed.current) {
            subscribeToPrivateChannel(`trade.${userId}`, {
                ".trade.updated": syncTrades,
            });
            subscribed.current = true;
        }

        return () => {
            if (subscribed.current) {
                unsubscribeFromChannel(`trade.${userId}`);
                subscribed.current = false;
            }
        };
    }, [connectionStatus, userId, subscribeToPrivateChannel, unsubscribeFromChannel, syncTrades]);

    useEffect(() => {
        if (connectionStatus === "connected" || !userId) return;

        const interval = setInterval(syncTrades, TRADE_SYNC_POLLING_INTERVAL);
        return () => clearInterval(interval);
    }, [connectionStatus, userId, syncTrades]);

    // The client area loads /data in BaseLayout, which this standalone shell never
    // mounts -- without it the trade room's per-category leverage stayed at the
    // store's placeholder 1:100 instead of the admin's Trade Settings values.
    useEffect(() => {
        if (!userId) return;

        const loadTradingConfiguration = async () => {
            await useDataStore.getState().fetchData();
            const configuredPairs =
                useDataStore.getState().data?.webtrader_default_pairs ?? ["BTCUSD"];
            useAssetStore.getState().applyDefaultPairs(configuredPairs);
        };

        void loadTradingConfiguration();
    }, [userId]);

    return null;
}

/**
 * Standalone shell mounted instead of the normal client-area <App /> when
 * main.tsx detects a webtrader hostname (see src/lib/webtrader-host.ts).
 * Deliberately does not import anything from the client-area route tree --
 * that's what makes client-area pages structurally unreachable here, not
 * just conventionally hidden.
 */
function WebtraderRoute({ children }: { children: React.ReactNode }) {
    const user = useUserStore((state) => state.user);
    const token = useUserStore((state) => state.token);
    const location = useLocation();

    if (!user || !token) {
        return <Navigate to="/login" state={{ from: location }} replace />;
    }

    return (
        <>
            <WebtraderTradeSync />
            {children}
        </>
    );
}

function WebtraderTradingUI() {
    const settings = useSiteSettingsStore((state) => state.settings);
    const isMt4 = settings?.is_mt4 ?? false;

    return (
        <Plus50AiAgreementGate>
            {isMt4 ? <TradingPlatformLight /> : <TradingPlatform />}
        </Plus50AiAgreementGate>
    );
}

export default function WebtraderApp() {
    return (
        <QueryClientProvider client={webtraderQueryClient}>
            <BrowserRouter>
                <Toaster position="top-right" />
                <SiteProvider>
                    <Suspense fallback={<LoadingScreen />}>
                        <Routes>
                            <Route path="/login" element={<WebtraderLoginPage />} />
                            <Route path="/forgot-password" element={<WebtraderForgotPasswordPage />} />
                            <Route path="/reset-password" element={<WebtraderResetPasswordPage />} />
                            <Route path="/launch" element={<WebtraderLaunchConsumePage />} />
                            <Route
                                path="/"
                                element={
                                    <WebtraderRoute>
                                        <WebtraderTradingUI />
                                    </WebtraderRoute>
                                }
                            />
                            <Route path="*" element={<Navigate to="/" replace />} />
                        </Routes>
                    </Suspense>
                </SiteProvider>
            </BrowserRouter>
        </QueryClientProvider>
    );
}
