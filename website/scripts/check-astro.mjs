// Parses every .astro page and reports the precise syntax error with its real source
// location — Astro's own build error only gives a transformed position.
import { transform } from '@astrojs/compiler';
import * as esbuild from 'esbuild';
import { readdirSync, readFileSync } from 'node:fs';
import { join, sep } from 'node:path';

const walk = (d) =>
  readdirSync(d, { withFileTypes: true }).flatMap((e) =>
    e.isDirectory() ? walk(join(d, e.name)) : e.name.endsWith('.astro') ? [join(d, e.name)] : []
  );

let bad = 0;
for (const f of walk('src')) {
  const src = readFileSync(f, 'utf8');
  let out;
  try {
    out = await transform(src, { filename: f.split(sep).join('/'), sourcemap: false });
  } catch (e) {
    bad++; console.log(`\n[compiler] ${f}\n  ${e.message}`); continue;
  }
  try {
    await esbuild.transform(out.code, { loader: 'ts' });
  } catch (e) {
    bad++;
    console.log(`\n[syntax] ${f}`);
    for (const err of e.errors ?? []) {
      console.log('  ' + err.text);
      if (err.location) {
        const gl = err.location.line;
        const lines = out.code.split('\n');
        for (let i = Math.max(0, gl - 5); i < Math.min(lines.length, gl + 3); i++) {
          console.log(`  ${i + 1 === gl ? '>>' : '  '} ${String(i + 1).padStart(4)} ${lines[i]}`);
        }
      }
    }
  }
}
console.log(bad ? `\n${bad} file(s) with errors` : '\nall .astro files parse cleanly');
