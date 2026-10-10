import { useEffect, useRef } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import axiosInstance from "@/lib/axios";
import { replaceAuthenticatedUser } from "@/lib/session";
import { AxiosError } from "axios";
import { toast } from "@/components/ui/sonner";
import LoadingScreen from "@/components/loading-screen";

/**
 * Consumes a single-use launch token minted by the client area
 * (TradingAccountController::launchToken()) and exchanges it for a real
 * session via POST auth/trading-account/launch. Unlike the main app's
 * auto-login.tsx, the query-string token is NEVER treated as the session
 * credential itself -- it's stripped from the visible URL immediately and
 * only ever sent once, in a POST body, to be exchanged for a fresh token.
 */
export default function WebtraderLaunchConsumePage() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const hasRun = useRef(false);

    useEffect(() => {
        if (hasRun.current) return;
        hasRun.current = true;

        const token = searchParams.get("token");

        // Scrub the token out of the address bar before doing anything else,
        // so it doesn't linger visible in a shared/screen-shared tab.
        window.history.replaceState({}, "", "/launch");

        if (!token) {
            toast.error("Missing or invalid launch link.");
            navigate("/login");
            return;
        }

        (async () => {
            try {
                const response = await axiosInstance.post("/auth/trading-account/launch", { token });
                const { user, token: sessionToken, trading_account } = response.data.data;
                replaceAuthenticatedUser(user, sessionToken, trading_account, true);
                navigate("/");
            } catch (error) {
                if (error instanceof AxiosError) {
                    toast.error(error.response?.data?.message || "This launch link has expired or was already used.");
                } else {
                    toast.error("Failed to launch WebTrader. Please sign in directly.");
                }
                navigate("/login");
            }
        })();
    }, [searchParams, navigate]);

    return <LoadingScreen />;
}
