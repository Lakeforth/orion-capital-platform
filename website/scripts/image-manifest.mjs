// Generates IMAGE-MANIFEST.md from the BUILT site, not from the source.
//
//   npm run build && node scripts/image-manifest.mjs
//   (or just: npm run manifest, which builds first)
//
// Reading dist/ rather than parsing .astro matters: several slots declare their id through
// a loop variable (`<Placeholder id={p.id} …/>`). Source parsing reported those literally as
// "p.id"; the built HTML carries the resolved value, so the list is now exact — which is what
// anyone supplying artwork actually needs.
//
// Placeholder.astro emits data-image-slot / -label / -w / -h on BOTH branches, so a slot
// stays listed after its picture is supplied and is marked as filled.

import { readdir, readFile, writeFile } from 'node:fs/promises';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const DIST = fileURLToPath(new URL('../dist/', import.meta.url));
const OUT = fileURLToPath(new URL('../IMAGE-MANIFEST.md', import.meta.url));

async function walk(dir) {
  const out = [];
  for (const e of await readdir(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    if (e.isDirectory()) out.push(...(await walk(p)));
    else if (e.name.endsWith('.html')) out.push(p);
  }
  return out;
}

const attr = (tag, name) => {
  const m = tag.match(new RegExp(`\\b${name}="([^"]*)"`));
  return m ? m[1] : null;
};
const unescape = (s) =>
  (s || '')
    .replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&mdash;/g, '—')
    .replace(/&rsquo;/g, '’').replace(/&times;/g, '×')
    .replace(/\s+/g, ' ').trim();

let files;
try {
  files = await walk(DIST);
} catch {
  console.error('dist/ not found — run `npm run build` first.');
  process.exit(1);
}

const slots = new Map();

for (const f of files) {
  const html = await readFile(f, 'utf8');
  const page = ('/' + relative(DIST, f).replace(/\\/g, '/').replace(/index\.html$/, '')).replace(/\/$/, '') || '/';
  if (page === '/coming-soon') continue;

  for (const m of html.matchAll(/<(?:img|div)\b[^>]*\bdata-image-slot="[^"]*"[^>]*>/g)) {
    const tag = m[0];
    const id = attr(tag, 'data-image-slot');
    if (!id) continue;
    const supplied = /<img\b/.test(tag);
    const prev = slots.get(id);
    const entry = {
      id,
      label: unescape(attr(tag, 'data-image-label')) || (prev?.label ?? ''),
      w: Number(attr(tag, 'data-image-w')) || prev?.w || 800,
      h: Number(attr(tag, 'data-image-h')) || prev?.h || 500,
      src: supplied ? attr(tag, 'src') : (prev?.src ?? null),
      pages: new Set([...(prev?.pages ?? []), page]),
      dark: /ph-dark/.test(tag) || (prev?.dark ?? false),
    };
    slots.set(id, entry);
  }
}

const all = [...slots.values()].sort((a, b) => a.id.localeCompare(b.id));

// Group by the page a slot first appears on; shared shell slots get their own group.
const groups = new Map();
for (const s of all) {
  const pages = [...s.pages];
  const g = pages.length > 2 ? 'Site-wide (header, footer, shared)' : pages.sort()[0];
  if (!groups.has(g)) groups.set(g, []);
  groups.get(g).push(s);
}

const ratio = (w, h) => {
  const gcd = (a, b) => (b ? gcd(b, a % b) : a);
  const d = gcd(w, h);
  return `${w / d}:${h / d}`;
};

const filled = all.filter((s) => s.src).length;

let md = `# Image manifest

Every image slot on the site, generated from the **built** pages by
\`npm run manifest\`. Re-run it after adding or removing a \`<Placeholder>\`.

Until a file is supplied, each slot renders as a labelled grey panel showing its id and size,
so a missing image is visible in review rather than a silent blank box.

## How to supply an image

1. Drop the file in \`public/images/\` (sub-folders are fine, e.g. \`public/images/platform/\`).
2. Pass it to the slot: \`<Placeholder id="..." src="/images/your-file.jpg" alt="..." ... />\`

**Formats** — photography and screenshots: WebP (or JPG). Logos, icons, badges, diagrams: SVG,
or PNG with transparency. **Supply at 2× the listed pixel size** for retina; the listed size is
the CSS box, not the file size. Keep each file under ~300KB after compression.

**Match the listed ratio.** Slots crop with \`object-fit: cover\`, so a different ratio loses
the edges of your picture. Where a slot sits on a dark section, a flat backdrop blends far
better than a vignette.

**Total slots: ${all.length}** — ${filled} supplied, ${all.length - filled} still needed

`;

for (const [g, list] of [...groups.entries()].sort()) {
  md += `\n## ${g}\n\n| Slot id | What it should show | Size (px) | Ratio | On dark | Supplied |\n`;
  md += `|---|---|---|---|---|---|\n`;
  for (const s of list) {
    md += `| \`${s.id}\` | ${s.label || '—'} | ${s.w}×${s.h} | ${ratio(s.w, s.h)} | ${s.dark ? 'yes' : ''} | ${s.src ? '**yes**' : '—'} |\n`;
  }
}

md += `
## Brand assets (not Placeholder slots)

| Asset | Where | Needed |
|---|---|---|
| \`public/images/brand/ncapitalpro-logo.png\` | header, footer, mobile drawer | **supplied** (767×183 PNG) |
| SVG master of the logo | same | **wanted** — scales perfectly, the highest-value asset to send |
| Reversed / white logo | footer, dark sections | currently faked with a CSS filter that flattens it to white and loses the blue |
| \`public/favicon.svg\` | browser tab | drawn from the mark in brand colours — replace if you have an official icon |
| \`public/images/og-default.png\` | link previews on social, WhatsApp, Slack | **1200×630** — not yet supplied |
`;

await writeFile(OUT, md, 'utf8');
console.log(`IMAGE-MANIFEST.md written — ${all.length} slots (${filled} supplied) across ${groups.size} groups`);
