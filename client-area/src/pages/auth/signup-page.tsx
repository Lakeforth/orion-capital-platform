import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import useUserStore from "@/store/userStore";
import RegisterTradeNation from "@/pages/auth/themes/RegisterTradeNation.tsx";
import useSiteSettingsStore from "@/store/siteSettingStore.ts";
import RegisterBasic from "@/pages/auth/themes/RegisterBasic.tsx";
import RegisterICMarket from "@/pages/auth/themes/RegisterICMarket.tsx";
import RegisterEquityMarket from "@/pages/auth/themes/RegisterEquityMarket.tsx";
import SignUpPropFirm from "@/pages/auth/themes/SignUpPropFirm.tsx";
import SignUpPropFirmLight from "@/pages/auth/themes/SignUpPropFirmLight.tsx";

import { BITRACE_CASES_PATH } from "@/pages/bitrace/bitrace-routes";
import { isBitraceEnabledFromSettings } from "@/pages/bitrace/useBitraceEnabled";

export default function SignUpPage() {
  const navigate = useNavigate();
  const user = useUserStore((state) => state.user);
  const token = useUserStore((state) => state.token);


  const settings = useSiteSettingsStore((state) => state.settings);

  const register_page = settings?.r_page ?? "basic";
  const bitraceEnabled = isBitraceEnabledFromSettings(settings?.bitrace_enabled);
  const postRegisterPath = bitraceEnabled ? BITRACE_CASES_PATH : "/main";


  useEffect(() => {
    if (user && token) {
      navigate(postRegisterPath);
    }
  }, [user, token, navigate, postRegisterPath]);


  // Prop Firm's second (light) theme -- only when explicitly selected.
  if (register_page === "propfirm_light") {
    return <SignUpPropFirmLight />;
  }

  // Prop Firm tenants always get their own themed sign-up page, regardless
  // of the saved design -- same precedence rule as login/register/forgot-password.
  if (register_page === "propfirm" || settings?.propfirm_enabled) {
    return <SignUpPropFirm />;
  }

  if (register_page === "basic") {
    return <RegisterBasic postRegisterPath={postRegisterPath} />;
  }

  if (register_page === "ic") {
    return <RegisterICMarket />;
  }

  if (register_page === "td") {
    return <RegisterTradeNation />;
  }

  if (register_page === "em") {
    return <RegisterEquityMarket />;
  }

  return <RegisterBasic />;
}
