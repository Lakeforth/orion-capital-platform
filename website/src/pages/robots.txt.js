// Generated rather than a static public/robots.txt, for the same reason the sitemap is:
// the Sitemap line carries the domain, and a hardcoded copy silently kept pointing at the old
// one through the move to orion-capital.inc. Deriving it from site.domain means a future domain
// change is one edit in one place.
import { site } from '../config/site.js';

export function GET() {
  const body = [
    'User-agent: *',
    'Allow: /',
    // Not a published page — it is the placeholder every unbuilt nav link points at.
    'Disallow: /coming-soon',
    '',
    `Sitemap: https://${site.domain}/sitemap.xml`,
    '',
  ].join('\n');

  return new Response(body, { headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
}
