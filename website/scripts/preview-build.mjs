// Prepares dist/ for a shareable preview link.
//
//   node scripts/preview-build.mjs
//
// Two changes, both preview-only — never run this for the real deploy:
//   1. injects <meta name="robots" content="noindex,nofollow"> into every page, so a
//      work-in-progress broker site carrying placeholder licence data cannot be indexed;
//   2. rewrites root-absolute hrefs/srcs to relative, because the preview host serves the
//      site under a path rather than at a domain root.
import { readdirSync, readFileSync, writeFileSync, renameSync, existsSync } from 'node:fs';
import { join, relative, dirname, sep } from 'node:path';

const DIST = 'dist';
const walk = (d) =>
  readdirSync(d, { withFileTypes: true }).flatMap((e) =>
    e.isDirectory() ? walk(join(d, e.name)) : [join(d, e.name)]
  );

// The preview host reserves paths beginning with "_", so Astro's default asset directory
// has to be renamed here. The real deploy keeps "_astro" — this is preview-only.
const ASSET_FROM = '_astro';
const ASSET_TO = 'astro-assets';
if (existsSync(join(DIST, ASSET_FROM))) renameSync(join(DIST, ASSET_FROM), join(DIST, ASSET_TO));

const files = walk(DIST);
const pages = files.filter((f) => f.endsWith('.html'));

// Rewrite the reference in every text asset, not just pages: the CSS bundle points at fonts.
for (const f of files.filter((x) => /\.(html|css|js|xml|txt)$/.test(x))) {
  const t = readFileSync(f, 'utf8');
  if (t.includes(ASSET_FROM)) writeFileSync(f, t.split(ASSET_FROM).join(ASSET_TO), 'utf8');
}

let injected = 0;
let rewritten = 0;

for (const f of pages) {
  let html = readFileSync(f, 'utf8');

  if (!/name="robots"/.test(html)) {
    html = html.replace(/<head>/i, '<head><meta name="robots" content="noindex,nofollow">');
    injected++;
  }

  // Depth of this page below dist/, so "/about" becomes "../about" from a nested page.
  const depth = relative(DIST, dirname(f)).split(sep).filter(Boolean).length;
  const prefix = depth === 0 ? './' : '../'.repeat(depth);

  const before = html;
  // Only root-absolute, same-site references. Protocol-relative and absolute URLs are left alone.
  html = html.replace(/(\s(?:href|src))="\/(?!\/)([^"]*)"/g, (_m, attr, path) => `${attr}="${prefix}${path}"`);
  html = html.replace(/(\ssrcset)="([^"]*)"/g, (m, attr, val) =>
    val.includes('/') ? `${attr}="${val.replace(/(^|,\s*)\/(?!\/)/g, `$1${prefix}`)}"` : m
  );
  // Page links point at directories ("./about"). Whether that resolves depends on the host
  // doing directory-index lookup, so name index.html explicitly and remove the guesswork.
  html = html.replace(/((?:href)=")((?:\.{1,2}\/)+)([^"?#]*?)(\/)?((?:[#?][^"]*)?)"/g,
    (m, head, up, path, slash, tail) => {
      if (!path) return `${head}${up}index.html${tail}"`; // "./" or "../" — the home page
      if (/\.[a-z0-9]{2,5}$/i.test(path)) return m;       // already a file (.css, .webp, .xml)
      return `${head}${up}${path}/index.html${tail}"`;
    });

  if (html !== before) rewritten++;

  writeFileSync(f, html, 'utf8');
}

console.log(`preview build: ${pages.length} pages — noindex on ${injected}, links rewritten in ${rewritten}`);
console.log(`total files: ${files.length}`);
