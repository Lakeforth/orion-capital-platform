import { defineConfig } from 'astro/config';
import tailwind from '@astrojs/tailwind';

// Static output only: `npm run build` emits plain HTML/CSS/JS into dist/,
// which can be dropped on any nginx/Apache box and moved between servers freely.
export default defineConfig({
  site: 'https://orion-capital.inc',
  output: 'static',
  trailingSlash: 'ignore',
  integrations: [tailwind({ applyBaseStyles: false })],
  // Market URLs moved to the base44 reference's slugs. Static output writes a
  // meta-refresh page at each old path so existing links and bookmarks still land.
  redirects: {
    '/markets/cryptocurrencies': '/markets/crypto',
    '/markets/stocks-indices': '/markets/stocks',
  },
  build: { inlineStylesheets: 'auto' },
});
