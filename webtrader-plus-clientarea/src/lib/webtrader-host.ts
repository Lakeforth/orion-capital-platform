/**
 * Detects whether the current hostname is a "webtrader" host -- the
 * standalone trading subdomain (e.g. webtrader.trivonfunded.com for a
 * branded custom domain, or webtrader-{slug}.{platform domain} for the
 * shared wildcard site). Mirrors the exact prefix rules the backend's
 * ResolveTenant middleware already strips (app/Http/Middleware/ResolveTenant.php,
 * TENANT_HOST_PREFIXES + the separate webtrader_ check) -- keep these two in
 * sync if either changes.
 *
 * Deliberately not hostname-allowlist-based (no hardcoded tenant domains
 * here) so this works automatically for any tenant that gets its own
 * webtrader domain later, not just the pilot one.
 */
export function isWebtraderHost(hostname: string = window.location.hostname): boolean {
  const firstLabel = hostname.split(".")[0];

  return (
    firstLabel === "webtrader" ||
    firstLabel.startsWith("webtrader-") ||
    firstLabel.startsWith("webtrader_")
  );
}
