import { useEffect, useState, useRef } from "react";
import { useLocation } from "react-router-dom";
import useDataStore from "@/store/dataStore";
import useUserStore from "@/store/userStore";
import { AccountDisabled } from "@/components/site/AccountDisabled.tsx";
import { SitePending } from "@/components/site/SitePending.tsx";
import useSiteSettingsStore from "@/store/siteSettingStore.ts";
import useDarkModeStore from "@/store/darkModeStore";
import WhatsAppButton from "@/components/chat/whatsapp-button";
import TelegramButton from "@/components/chat/telegram-button";
import DemoResetClock from "@/components/demo/DemoResetClock";
import { isBitraceRoute } from "@/pages/bitrace/bitrace-routes";
import { isBitraceEnabledFromSettings } from "@/pages/bitrace/useBitraceEnabled";

export function BaseLayout({ children }: { children: React.ReactNode }) {
  const location = useLocation();
  const fetchSiteData = useDataStore((state) => state.fetchData);
  const token = useUserStore((state) => state.token);
  const user = useUserStore((state) => state.user);
  const getCurrentUser = useUserStore((state) => state.getCurrentUser);
  const [isStoreHydrated, setIsStoreHydrated] = useState(false);
  const dataFetchedRef = useRef(false);

  const settings = useSiteSettingsStore((state) => state.settings);
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);

  const authRoutes = [
    "/register/country-residence",
    "/signup",
    "/",
    "/login",
    "/auto-login",
    "/register",
    "/forgot-password",
  ];
  const isAuthRoute = authRoutes.some((route) => location.pathname === route);

  // Wait for store hydration
  useEffect(() => {
    const hydrateTimer = setTimeout(() => {
      setIsStoreHydrated(true);
    }, 100);

    return () => clearTimeout(hydrateTimer);
  }, []);

  // Fetch session data once after login or on reload (not on auth routes).
  useEffect(() => {
    if (!isStoreHydrated) return;

    const isAuthenticated = !!token;

    if (!isAuthenticated) {
      dataFetchedRef.current = false;
      return;
    }

    if (isAuthRoute || dataFetchedRef.current) {
      return;
    }

    const fetchUserAndSiteData = async () => {
      try {
        await getCurrentUser();
        await fetchSiteData();
        dataFetchedRef.current = true;
      } catch (error) {
        console.error("Error fetching data:", error);
      }
    };

    fetchUserAndSiteData();
  }, [token, isAuthRoute, getCurrentUser, fetchSiteData, isStoreHydrated]);

  if (user?.status === "deactivated" && !isAuthRoute) {
    return <AccountDisabled />;
  }

  if (
    user?.status === "pending" &&
    !isAuthRoute &&
    settings?.must_verify_account
  ) {
    return <SitePending />;
  }

  const isMT4Light = location.pathname.includes("/mt4-light");
  const isLanding = location.pathname === "/landing";
  const isThankYou = location.pathname === "/thank-you";
  const isSurvey =
    location.pathname.startsWith("/c/") || location.pathname === "/survey";
  /** Covers the four Prediction funnel pages: the landing, /prediction-survey
   *  (with or without a uuid), /prediction-checkout and /prediction-thank-you. */
  const isPredictionFunnel = location.pathname.startsWith("/prediction");
  /** Covers the Trivon Funded funnel pages: /trivon, /trivon-survey (with or
   *  without a uuid), /trivon-checkout and /trivon-thank-you. */
  const isTrivonFunnel = location.pathname.startsWith("/trivon");
  /** Covers the Apex Funded funnel pages: /apex, /apex-checkout, /apex-thank-you. */
  const isApexFunnel = location.pathname.startsWith("/apex");
  const showFloatingChatButtons =
    location.pathname !== "/main/chat" &&
    !isLanding &&
    !isThankYou &&
    !isSurvey &&
    !(isBitraceRoute(location.pathname) && isBitraceEnabledFromSettings(settings?.bitrace_enabled));

  return (
    <div
      className={
        isMT4Light ? (isDarkMode ? "dark h-full" : "h-full") : "dark h-full"
      }
    >
      {children}
      {showFloatingChatButtons && (
        <>
          {!isPredictionFunnel && !isTrivonFunnel && !isApexFunnel && (
            <WhatsAppButton />
          )}
          <TelegramButton />
        </>
      )}
      {!isLanding && !isApexFunnel && <DemoResetClock />}
    </div>
  );
}
