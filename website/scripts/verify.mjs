// Post-build checks over dist/. Catches the failure modes that compile fine but are wrong:
// dead internal links, removed-product references, colourless Tailwind tokens, stray casts.
//
//   node scripts/verify.mjs
import { readdir, readFile } from 'node:fs/promises';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const DIST = fileURLToPath(new URL('../dist/', import.meta.url));

async function walk(dir) {
  const out = [];
  for (const e of await readdir(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    if (e.isDirectory()) out.push(...(await walk(p)));
    else out.push(p);
  }
  return out;
}

const files = await walk(DIST);
const html = files.filter((f) => f.endsWith('.html'));
const routes = new Set(
  html.map((f) => {
    const r = '/' + relative(DIST, f).replace(/\\/g, '/').replace(/index\.html$/, '').replace(/\.html$/, '');
    return r.replace(/\/$/, '') || '/';
  })
);
const assets = new Set(files.map((f) => '/' + relative(DIST, f).replace(/\\/g, '/')));

const fail = [];
const warn = [];

for (const f of html) {
  const src = await readFile(f, 'utf8');
  // Redirect stubs from astro.config `redirects` are bare meta-refresh pages — nothing to check.
  if (/<meta http-equiv="refresh"/i.test(src)) continue;
  const page = '/' + relative(DIST, f).replace(/\\/g, '/').replace(/index\.html$/, '').replace(/\/$/, '') || '/';

  // 1. MetaTrader is listed because the base44 reference copy names it (owner's call,
  //    2026-10). Warn rather than fail so it stays visible until the platform is confirmed.
  const mt = src.match(/MetaTrader|\bMT4\b|\bMT5\b/gi);
  if (mt) warn.push(`${page}: ${mt.length} MetaTrader reference(s) — confirm the platform is offered`);

  // 2. Tokens deleted from tailwind.config render colourless — grep the emitted classes.
  //    (`lime` is a live token again: the reference's #E5FF91 accent on dark.)
  const dead = src.match(/\b(?:text|bg|border|from|via|to|ring|fill|stroke|divide|decoration)-(?:orange|brandblue)\b/g);
  if (dead) fail.push(`${page}: dead colour token(s): ${[...new Set(dead)].join(', ')}`);

  // 3. A TS generic that leaked through as literal text.
  if (/Record&lt;string|as Record</.test(src)) fail.push(`${page}: unrendered TypeScript cast in output`);

  // 4. Internal links must resolve to a built route or a real asset.
  for (const m of src.matchAll(/href="(\/[^"#?]*)(?:[#?][^"]*)?"/g)) {
    const href = m[1].replace(/\/$/, '') || '/';
    if (routes.has(href) || assets.has(m[1]) || assets.has(href)) continue;
    fail.push(`${page}: dead internal link -> ${m[1]}`);
  }

  // 5. Anchors referenced on the page should exist on that page.
  const ids = new Set([...src.matchAll(/\sid="([^"]+)"/g)].map((m) => m[1]));
  for (const m of src.matchAll(/href="#([^"]+)"/g)) {
    if (!ids.has(m[1])) warn.push(`${page}: anchor #${m[1]} has no matching id`);
  }

  // 6. Exactly one h1.
  const h1 = (src.match(/<h1[\s>]/g) || []).length;
  if (h1 !== 1) fail.push(`${page}: ${h1} <h1> elements (expected 1)`);

  // 7. Funnel URLs should come from config, but the rendered href is the real URL —
  //    so just assert both destinations actually appear somewhere on the site.
}

const all = (await Promise.all(html.map((f) => readFile(f, 'utf8')))).join('');
if (!all.includes('client-area.orion-capital.inc')) fail.push('client-area URL missing site-wide');
if (!all.includes('webtrader.orion-capital.inc')) fail.push('webtrader URL missing site-wide');

const dedupe = (a) => [...new Set(a)];
console.log(`pages: ${html.length}   routes: ${routes.size}`);
console.log(`\nFAIL (${dedupe(fail).length}):`);
dedupe(fail).forEach((x) => console.log('  x ' + x));
console.log(`\nWARN (${dedupe(warn).length}):`);
dedupe(warn).slice(0, 25).forEach((x) => console.log('  ! ' + x));
process.exit(dedupe(fail).length ? 1 : 0);
