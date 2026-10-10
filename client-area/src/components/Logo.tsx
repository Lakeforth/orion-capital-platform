import useSiteSettingsStore from "@/store/siteSettingStore";
import React from "react";
import { Link } from "react-router-dom";

/**
 * `variant="login"` picks the client area sign-in page's own logo. A tenant
 * whose frontend logo is a pale one cannot use it on a light login
 * background, so Site Settings has a separate upload for that page. The API
 * falls back to the frontend logo when it is unset, so every other tenant is
 * unaffected.
 */
const Logo: React.FC<{ variant?: "default" | "login" }> = ({ variant = "default" }) => {
  const settings = useSiteSettingsStore((state) => state.settings);
  const websiteUrl = settings?.website_url || "/main/dashboard";
  const isExternal = websiteUrl.startsWith("http");

  const source = variant === "login" ? settings?.login_logo || settings?.logo : settings?.logo;

  const logoContent = (
    <>
      <img
        src={source}
        alt="Logo"
        className="hidden md:block w-auto h-8 rounded"
      />
      {/* Small logo - visible on small screens, hidden on md screens and up.
          The login variant keeps its own logo at every width: the point of it
          is the background it sits on, which does not change with the
          viewport. */}
      <img
        src={variant === "login" ? source : settings?.logo_sm || settings?.logo}
        alt="Logo"
        className="block md:hidden w-auto h-6 rounded"
      />
    </>
  );

  if (isExternal) {
    return (
      <a
        href={websiteUrl}
        className="flex items-center py-1 gap-1"
        target="_blank"
        rel="noopener noreferrer"
      >
        {logoContent}
      </a>
    );
  }

  return (
    <Link to={websiteUrl} className="flex items-center py-1 gap-1">
      {logoContent}
    </Link>
  );
};

export default Logo;
