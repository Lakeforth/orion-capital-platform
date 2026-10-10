// Removes internal editorial notes from a built dist/, for a client-facing or
// production build.  Run via:  npm run build:clean
//
// What it removes:
//   * elements carrying the `site-todo` chip class
//   * bracketed [TODO ...] / [TODO — compliance: ...] runs left inline in prose
//
// What it deliberately KEEPS:
//   * every [SAMPLE] / [SAMPLE FIGURE] marker. Those sit next to licence numbers,
//     entity names and prices that are illustrative. Hiding them would turn clearly
//     labelled sample data into something that reads as a verified regulatory claim,
//     which is the one outcome worth avoiding more than looking unfinished.
//
// `npm run build` keeps everything, so the working build stays honest about what is
// still outstanding; only this pass produces the tidied copy.

import { readdir, readFile, writeFile } from 'node:fs/promises';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const DIST = fileURLToPath(new URL('../dist/', import.meta.url));

async function walk(dir) {
  const out = [];
  for (const e of await readdir(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    if (e.isDirectory()) out.push(...(await walk(p)));
    else if (e.name.endsWith('.html')) out.push(p);
  }
  return out;
}

// Remove a whole element (open tag .. matching close tag) for a self-contained chip.
// Chips are leaf-ish spans/divs, so a non-greedy match to the next matching close tag
// is safe here; anything nested deeper is left alone and caught by the text pass.
function stripChips(html) {
  return html.replace(
    /<(span|div|p|li)\b[^>]*\bclass="[^"]*\bsite-todo\b[^"]*"[^>]*>[\s\S]*?<\/\1>/g,
    ''
  );
}

// Remove bracketed TODO runs embedded in sentences, then tidy the whitespace and any
// punctuation left stranded (" ." / " ,") by the removal.
function stripInline(html) {
  return html
    .replace(/\s*\[TODO[^\]]*\]/g, '')
    .replace(/[ \t]{2,}/g, ' ')
    .replace(/\s+([.,;:])/g, '$1');
}

const files = await walk(DIST);
let chips = 0, inline = 0, touched = 0;

for (const f of files) {
  const before = await readFile(f, 'utf8');
  const noChips = stripChips(before);
  const after = stripInline(noChips);
  if (after === before) continue;

  chips += (before.match(/\bsite-todo\b/g) || []).length;
  inline += (noChips.match(/\[TODO[^\]]*\]/g) || []).length;
  await writeFile(f, after, 'utf8');
  touched++;
  console.log('  cleaned ' + relative(DIST, f).replace(/\\/g, '/'));
}

const remaining = (
  await Promise.all(files.map((f) => readFile(f, 'utf8')))
).join('').match(/\[SAMPLE[^\]]*\]/g) || [];

console.log(`\n${touched} file(s) cleaned — ${chips} chip(s), ${inline} inline note(s) removed.`);
console.log(`${remaining.length} [SAMPLE] marker(s) kept, by design.`);
