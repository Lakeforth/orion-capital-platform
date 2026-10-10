# website

The public website at **https://orion-capital.inc**. Astro, built to static HTML —
plain HTML, CSS and JS.
## Layout

```
src/
  config/site.js      brand facts, outbound links, disclosure text
  config/feed.js      live price feed configuration
  data/               nav taxonomy, pricing tables
  layouts/Base.astro  head, canonical, JSON-LD, risk strip
  components/
    shell/            header, footer, language switcher
    sections/         reusable page sections
    ui/               Placeholder, Icon, Logo
  pages/              11 published pages; routes map to filenames
scripts/              verify, image manifest, build checks
```

## Build

```
npm install
npm run dev        # http://localhost:4330
npm run build      # writes dist/
npm run preview    # serve the built output on :4331
npm run verify     # crawl dist/ for dead links, dead Tailwind tokens, duplicate h1s
npm run manifest   # rebuild IMAGE-MANIFEST.md from the built pages
```

Node 20 or newer. Astro 4.16.

`npm run verify` catches dead internal links and colour tokens deleted from the
Tailwind config — both render as silently broken pages, so run it before publishing.

If you edit `tailwind.config.mjs`, restart the dev server: Tailwind reads that file
once at startup and will otherwise insist a new class does not exist.