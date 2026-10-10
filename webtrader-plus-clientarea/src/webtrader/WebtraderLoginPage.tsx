import useSiteSettingsStore from "@/store/siteSettingStore";
import WebtraderLoginTerminal from "./themes/WebtraderLoginTerminal";
import WebtraderLoginTerminalDark from "./themes/WebtraderLoginTerminalDark";

/**
 * Dispatches to the tenant's selected webtrader login theme (wt_l_page,
 * set via the admin CRM's Page Setup screen -- mirrors login-page.tsx's
 * l_page dispatch for the main client-area login).
 *
 * Default/unset falls through to the "Trading Terminal (Dark)" theme, not
 * a plain generic form -- the terminal look is generic enough to
 * suit any trade-room brokerage or prop firm out of the box, so there's no
 * value in a separate bare-bones default anymore.
 */
export default function WebtraderLoginPage() {
    const settings = useSiteSettingsStore((state) => state.settings);
    const loginType = settings?.wt_l_page ?? "default";

    if (loginType === "terminal") {
        return <WebtraderLoginTerminal />;
    }

    return <WebtraderLoginTerminalDark />;
}
