// Hand-rolled so the sitemap lists exactly the pages that are actually published,
// rather than every route including the `coming-soon` placeholder.
import { builtPages } from '../data/nav.js';
import { site } from '../config/site.js';

export function GET() {
  const base = `https://${site.domain}`;
  const urls = builtPages
    .map((p) => `  <url><loc>${base}${p === '/' ? '/' : p}</loc><changefreq>weekly</changefreq>` +
                `<priority>${p === '/' ? '1.0' : '0.8'}</priority></url>`)
    .join('\n');
  return new Response(
    `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls}\n</urlset>\n`,
    { headers: { 'Content-Type': 'application/xml' } }
  );
}
