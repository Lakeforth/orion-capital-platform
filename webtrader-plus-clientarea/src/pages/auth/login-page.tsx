import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import useUserStore from "@/store/userStore";
import LoginBasic from "@/pages/auth/themes/LoginBasic.tsx";
import ICMarketLogin from "@/pages/auth/themes/ICMarketLogin.tsx";
import useSiteSettingsStore from "@/store/siteSettingStore.ts";
import LoginTradeNation from "@/pages/auth/themes/LoginTradeNation.tsx";
import ICMarketLoginLight from "@/pages/auth/themes/ICMarketLoginLight.tsx";
import PropFirmLogin from "@/pages/auth/themes/PropFirmLogin.tsx";
import PropFirmLoginLight from "@/pages/auth/themes/PropFirmLoginLight.tsx";


export default function LoginPage() {
  const settings = useSiteSettingsStore((state) => state.settings);

  const navigate = useNavigate();
  const user = useUserStore((state) => state.user);
  const token = useUserStore((state) => state.token);


  const loginType = settings?.l_page ?? "basic";

  useEffect(() => {
    if (user && token) {
      navigate("/main");
    }
  }, [user, token, navigate]);

  // Prop Firm's second (light) theme -- only when explicitly selected, does
  // NOT auto-activate from propfirm_enabled alone (unlike the dark theme
  // below, which stays the default for propfirm tenants).
  if (loginType === "propfirm_light") {
    return <PropFirmLoginLight />;
  }

  // Prop Firm tenants always get their own themed login, regardless of
  // whatever login_page_design was previously saved (Nick's explicit call,
  // 2026-07-12 -- the module being on should always win, not just when unset).
  if (loginType === "propfirm" || settings?.propfirm_enabled) {
    return <PropFirmLogin />;
  }

  if (loginType === "basic") {
    return <LoginBasic />;
  }

  if (loginType === "td") {
    return <LoginTradeNation />;
  }

  if (loginType === "ic") {
    return <ICMarketLoginLight />;
  }

  return <ICMarketLogin />;
}
