import { useEffect, useState } from "react";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { setApiBaseUrl } from "@/lib/axios";
import { getApiBaseUrlFromWindowLocation } from "@/lib/api-base-url";
import { isWebtraderHost } from "@/lib/webtrader-host";

import {SiteUnreachable} from "@/components/site/SiteUnreachable.tsx";
import {SiteDisabled} from "@/components/site/SiteDisabled.tsx";
import LoadingScreen from "@/components/loading-screen.tsx";

// Funnel pages that render entirely from static content and never read site
// settings, so they must not be gated behind the settings fetch. '/trivon'
// covers the whole Trivon funnel the same way '/apex' covers Apex's.
const PUBLIC_PATHS = ['/thank-you', '/landing', '/c/', '/apex', '/trivon'];

interface SiteProviderProps {
    children: React.ReactNode;
}

export function SiteProvider({ children }: SiteProviderProps) {
    const {
        settings,
        isLoading,
        error,
        baseUrl,
        initialized,
        fetchSettings,
        setBaseUrl,
    } = useSiteSettingsStore();

    const [initialLoadComplete, setInitialLoadComplete] = useState(false);

    const isPublicPath = typeof window !== 'undefined' &&
        PUBLIC_PATHS.some(p => window.location.pathname.startsWith(p));

    useEffect(() => {
        if (typeof window !== "undefined") {
            const apiUrl = getApiBaseUrlFromWindowLocation();
            setBaseUrl(apiUrl);
            setApiBaseUrl(apiUrl);
        }
    }, [setBaseUrl]);

    useEffect(() => {
        const loadSettings = async () => {
            try {
                await fetchSettings();
            } catch (error) {
                console.error("SiteProvider - Error fetching settings:", error);
            } finally {
                setInitialLoadComplete(true);
            }
        };

        // If baseUrl is set, load settings
        if (baseUrl && !initialLoadComplete) {
            loadSettings();
        }
    }, [baseUrl, fetchSettings, initialLoadComplete]);

    // Shared by both React apps (App.tsx and WebtraderApp.tsx both render
    // this same SiteProvider) -- isWebtraderHost() is what distinguishes
    // which one is actually running, same check main.tsx uses to decide
    // which app to mount in the first place.
    useEffect(() => {
        if (settings?.name) {
            const label = isWebtraderHost() ? "Web Trader" : "Client Area";
            document.title = `${label} - ${settings.name}`;
        }
    }, [settings?.name]);

    // Centralized here (not in App.tsx) so both apps get a tenant favicon --
    // webtrader previously had none at all.
    useEffect(() => {
        if (settings?.favicon) {
            const link =
                (document.querySelector("link[rel*='icon']") as HTMLLinkElement) ||
                document.createElement("link");
            link.type = "image/x-icon";
            link.rel = "shortcut icon";
            link.href = settings.favicon;
            document.getElementsByTagName("head")[0].appendChild(link);
        }
    }, [settings?.favicon]);

    if (isPublicPath) {
        return <>{children}</>;
    }

    if (!initialLoadComplete && isLoading && !initialized) {
        return <LoadingScreen />;
    }

    // If there's an error, show unreachable screen
    if (error) {
        return <SiteUnreachable />;
    }

    if (settings?.status === "disabled") {
        return <SiteDisabled />;
    }

    if (!settings && initialLoadComplete) {
        return <SiteUnreachable />;
    }

    if (initialized && settings?.status) {
        return <>{children}</>;
    }

    return null;
}
