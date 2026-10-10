import axios from "axios";
import useUserStore from "@/store/userStore";
import useActiveTradingAccountOverrideStore from "@/store/activeTradingAccountOverrideStore";
import { getApiBaseUrlFromWindowLocation } from "@/lib/api-base-url";

// Get the base URL from the current domain
const getBaseUrl = () => {
    const hostname = window.location.hostname;

    console.log("Determining API base URL for hostname:", hostname);
    return getApiBaseUrlFromWindowLocation();
};

const axiosInstance = axios.create({
    baseURL: getBaseUrl(),
    headers: {
        "Content-Type": "application/json",
    },
    // Local `php artisan serve` handles one request at a time; a post-login burst
    // can queue long enough to exceed a short timeout.
    timeout: import.meta.env.DEV ? 60000 : 15000,
});

export const setApiBaseUrl = (baseUrl: string) => {
    axiosInstance.defaults.baseURL = baseUrl;
};

// Add request interceptor to log requests
axiosInstance.interceptors.request.use(
    (config) => {

        // Get token from Zustand store
        const token = useUserStore.getState().token;

        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }

        // Which trading account this request belongs to. An explicit override
        // (viewing a specific account's detail page, a Prop Firm challenge
        // account, etc.) always wins while it's set. Otherwise fall back to
        // the session's own resolved active account (set at login and
        // refreshed on every /user call, see userStore) -- without this
        // fallback, a session that started with exactly one trading account
        // stops sending any account context at all once a second account is
        // added, and the backend can no longer tell them apart. That silently
        // misfiled a customer's trade profit into a legacy balance field in
        // production (2026-07-30) -- see ResolveTradingAccount.php.
        const overrideAccountId = useActiveTradingAccountOverrideStore.getState().overrideAccountId;
        const sessionAccountId = useUserStore.getState().activeTradingAccount?.account_id;
        const accountIdForHeader = overrideAccountId ?? (sessionAccountId != null ? String(sessionAccountId) : null);
        if (accountIdForHeader) {
            config.headers["X-TRADING-ACCOUNT-ID"] = accountIdForHeader;
        }

        return config;
    },
    (error) => Promise.reject(error)
);

// Add response interceptor to handle token expiration
axiosInstance.interceptors.response.use(
    (response) => response,
    async (error) => {
        // Log the error response for debugging
        console.error("API Error:", {
            status: error.response?.status,
            statusText: error.response?.statusText,
            data: error.response?.data,
            url: error.config?.url,
            method: error.config?.method,
        });

        const originalRequest = error.config;

        // If error is 401 (Unauthorized) and we haven't tried to refresh yet
        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;

            const currentPath = window.location.pathname;

            const authRoutes = ["/", "/login", "/register", "/forgot-password"];
            const isAuthRoute = authRoutes.some((route) => currentPath === route);

            if (!isAuthRoute) {
                try {
                    const { clearAuthenticatedSession } = await import("@/lib/session");
                    clearAuthenticatedSession();
                } catch (sessionError) {
                    console.error("Failed to run full session cleanup:", sessionError);
                    useUserStore.getState().clearUser();
                }
                window.history.pushState({}, "", "/");
                window.dispatchEvent(new Event("popstate"));
            }
        }

        return Promise.reject(error);
    }
);

export default axiosInstance;
