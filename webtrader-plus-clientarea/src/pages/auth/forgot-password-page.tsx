import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore.ts";
import ForgotPasswordBasic from "@/pages/auth/themes/ForgotPasswordBasic.tsx";
import ForgotPasswordPropFirm from "@/pages/auth/themes/ForgotPasswordPropFirm.tsx";
import ForgotPasswordPropFirmLight from "@/pages/auth/themes/ForgotPasswordPropFirmLight.tsx";

export default function ForgotPasswordPage() {
  const navigate = useNavigate();
  const user = useUserStore((state) => state.user);
  const token = useUserStore((state) => state.token);
  const settings = useSiteSettingsStore((state) => state.settings);
  const forgotPasswordPage = settings?.fp_page ?? "basic";

  useEffect(() => {
    if (user && token) {
      navigate("/main");
    }
  }, [user, token, navigate]);

  // Prop Firm's second (light) theme -- only when explicitly selected.
  if (forgotPasswordPage === "propfirm_light") {
    return <ForgotPasswordPropFirmLight />;
  }

  // Prop Firm tenants always get their own themed forgot-password page,
  // regardless of the saved design, same rule as login-page.tsx.
  if (forgotPasswordPage === "propfirm" || settings?.propfirm_enabled) {
    return <ForgotPasswordPropFirm />;
  }

  return <ForgotPasswordBasic />;
}
