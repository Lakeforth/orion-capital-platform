import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore.ts";
import RegisterBasic from "@/pages/auth/themes/RegisterBasic.tsx";
import RegisterTDPage from "@/pages/auth/themes/RegisterTDPage.tsx";
import RegisterEMPage from "@/pages/auth/themes/RegisterEMPage.tsx";
import RegisterPropFirm from "@/pages/auth/themes/RegisterPropFirm.tsx";
import RegisterPropFirmLight from "@/pages/auth/themes/RegisterPropFirmLight.tsx";
import { BITRACE_CASES_PATH } from "@/pages/bitrace/bitrace-routes";
import { isBitraceEnabledFromSettings } from "@/pages/bitrace/useBitraceEnabled";

export default function RegisterPage() {
    const navigate = useNavigate();
    const user = useUserStore((state) => state.user);
    const token = useUserStore((state) => state.token);
    const settings = useSiteSettingsStore((state) => state.settings);
    const register_page = settings?.r_page ?? "basic";
    const bitraceEnabled = isBitraceEnabledFromSettings(settings?.bitrace_enabled);

    // Post-registration redirect: bitrace users go straight to their case
    const postRegisterPath = bitraceEnabled ? BITRACE_CASES_PATH : "/main";

    useEffect(() => {
        if (user && token) {
            navigate(postRegisterPath);
        }
    }, [user, token, navigate, postRegisterPath]);

    // Prop Firm's second (light) theme -- only when explicitly selected.
    if (register_page === "propfirm_light") {
        return <RegisterPropFirmLight postRegisterPath={postRegisterPath} />;
    }

    // Prop Firm tenants always get their own themed register page, regardless
    // of whatever register_page_design was previously saved -- same rule as
    // login-page.tsx: the module being on always wins.
    if (register_page === "propfirm" || settings?.propfirm_enabled) {
        return <RegisterPropFirm postRegisterPath={postRegisterPath} />;
    }

    if (register_page === "td") {
        return <RegisterTDPage postRegisterPath={postRegisterPath} />;
    }

    if (register_page === "em") {
        return <RegisterEMPage postRegisterPath={postRegisterPath} />;
    }

    return <RegisterBasic postRegisterPath={postRegisterPath} />;
}
