// NOTE: the platform's own hostnames are withheld from this bundle and appear
// as placeholders below. The rule they encode is unchanged: a client area
// served from a brand's own domain talks to the API on that same origin, and
// only the development and staging cases are redirected elsewhere.
const API_TARGET_STORAGE_KEY = "api_target_override";

/**
 * Lets a dev point demo-staging.<PLATFORM_HOSTING_DOMAIN> at the <PLATFORM_STAGING_HOST> backend staging
 * instead of production, to test frontend + backend changes together before
 * either goes live. Add ?api=staging once and it persists across reloads via
 * localStorage; ?api=production clears it back to the normal hostname-based
 * resolution below.
 */
function getApiTargetOverride(): string | null {
  const param = new URLSearchParams(window.location.search).get("api");

  if (param === "staging") {
    localStorage.setItem(API_TARGET_STORAGE_KEY, "staging");
    return "staging";
  }

  if (param === "production") {
    localStorage.removeItem(API_TARGET_STORAGE_KEY);
    return null;
  }

  return localStorage.getItem(API_TARGET_STORAGE_KEY);
}

export function getApiBaseUrlFromWindowLocation() {
  const origin = window.location.origin;
  const hostname = window.location.hostname;
  const protocol = window.location.protocol;

  if (getApiTargetOverride() === "staging") {
    return "https://<PLATFORM_STAGING_HOST>/api/v1";
  }

  if (hostname === "localhost" || hostname === "127.0.0.1") {
    return import.meta.env.VITE_API_URL || "https://demo.<PLATFORM_API_HOST>/api/v1";
  }

  if (hostname.includes("<PREVIEW_HOST>")) {
    return "https://demo.<PLATFORM_API_HOST>/api/v1";
  }

  // Example: demo-staging.<PLATFORM_HOSTING_DOMAIN> -> https://demo.<PLATFORM_API_HOST>/api/v1
  if (hostname.endsWith("-staging.<PLATFORM_HOSTING_DOMAIN>")) {
    const subdomain = hostname.replace(/-staging\.<PLATFORM_HOSTING_DOMAIN>$/, "");
    return `${protocol}//${subdomain}.<PLATFORM_API_HOST>/api/v1`;
  }

  if (hostname.includes("staging")) {
    return `${origin.replace("staging", "secure")}/api/v1`;
  }

  return `${origin}/api/v1`;
}
