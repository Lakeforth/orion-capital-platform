/** @type {import('tailwindcss').Config} */
// Palette is taken from the supplied NCapital Pro logo, sampled from the artwork:
//   deep navy  #00204C  (the N mark and "NCapital")
//   bright blue #0088FC (the arrow and "Pro")
// Layout/typography tokens are measured from the reference site's computed styles
// (container 1664px, heading weight 500, body #343434).
//
// Semantic tokens only. The earlier `orange`, `lime` and `brandblue` aliases have been
// REMOVED, not re-pointed — a stray `text-orange` now renders with no colour rather than
// quietly looking fine. Use `brand` (CTA/accent), `navy` (dark surfaces), `sky` (accent on dark).
export default {
  content: ['./src/**/*.{astro,html,js,jsx,md,mdx,ts,tsx}'],
  theme: {
    extend: {
      colors: {
        // Re-pointed to the base44 reference build (Downloads\figma-untitled-4aa9851b):
        //   brand  = figma-subtle    hsl(221 83% 53%)  CTA fill
        //   accent = figma-highlight #4C61FF           headings, links, "Trade"
        //   navy   = figma-text-1-2  #101820           dark sections, footer
        //   lime   = #E5FF91                           accent on dark
        ink:   '#333333',
        navy:  { DEFAULT: '#101820', deep: '#0B1118', soft: '#1C2A38' },
        brand: { DEFAULT: '#2563EB', hover: '#1D4ED8', dark: '#1E40AF', soft: '#60A5FA' },
        accent: '#4C61FF',
        lime:  '#E5FF91',
        risk:  '#E7E9FF',
        safe:  '#E3E8EB',
        rule:  '#C2D5FF',
        sky:   { DEFAULT: '#7CC6FF', pale: '#CFE6FF' },
        band:  '#EBF2FF',
        lilac: '#E3EEFB',
        muted: '#7b7b7b',
        hush:  '#9aa3ad',
        line:  '#E3E9F0',
        paper: '#FEFEFE',

        slateplum: '#3d5878',
      },
      fontFamily: {
        // Suisse Intl is licensed; Inter is the closest free neo-grotesque match.
        sans: ['Inter Variable', 'Inter', 'Arial', 'Helvetica Neue', 'sans-serif'],
      },
      fontSize: {
        display: ['44px', { lineHeight: '54px', fontWeight: '500' }],
        h1:      ['38px', { lineHeight: '44px', fontWeight: '500' }],
        h2:      ['30px', { lineHeight: '33px', fontWeight: '500' }],
        h3:      ['26px', { lineHeight: '36px', fontWeight: '500' }],
        h4:      ['20px', { lineHeight: '24px', fontWeight: '500' }],
        lead:    ['22px', { lineHeight: '32px' }],
        body:    ['16px', { lineHeight: '24px' }],
        small:   ['14px', { lineHeight: '22px' }],
        micro:   ['12px', { lineHeight: '18px' }],
      },
      maxWidth: { shell: '1521px' },
      screens: { '3xl': '1600px' },
      boxShadow: {
        header: '0 2px 10px rgba(0,32,76,.08)',
        card:   '0 2px 18px rgba(0,32,76,.07)',
        lift:   '0 12px 34px rgba(0,32,76,.16)',
      },
    },
  },
  plugins: [],
};
