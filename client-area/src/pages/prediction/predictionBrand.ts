/**
 * Brand-pack logos from the Prediction Profits AI design kit (Drive folder).
 * Light = dark wordmark for white surfaces (nav, survey card, thank-you).
 * Dark = white wordmark for navy/black surfaces (footer).
 */
export const BRAND_LOGO_URL = "/assets/prediction/logo-light.png";
export const BRAND_LOGO_DARK_URL = "/assets/prediction/logo-dark.png";

/** German product box (Prognose Gewinne KI) for landing + checkout. */
export const PRODUCT_BOX_DE_URL = "/assets/prediction/product-box-de.png";

/** German 180-Tage guarantee seal + pill banner. */
export const GUARANTEE_SEAL_DE_URL = "/assets/prediction/guarantee-seal-de.jpg";
export const GUARANTEE_PILL_DE_URL = "/assets/prediction/guarantee-pill-de.png";

/**
 * The logo to show across the funnel. The brand-pack lockup is authoritative so
 * the Drive design always displays (campaign CRM uploads no longer override it).
 */
export function useCampaignLogo(variant: "light" | "dark" = "light"): string {
  return variant === "dark" ? BRAND_LOGO_DARK_URL : BRAND_LOGO_URL;
}

const CLOUDINARY = "https://res.cloudinary.com/dyp8gtllq/image/upload";

/**
 * The dark artwork behind the funnel's navy surfaces: the survey's promo panel
 * (and its mobile backdrop), plus the checkout and thank-you pages. Keep the
 * three in step — a lead walks all of them in one sitting.
 */
export const PANEL_IMAGE_URL = `${CLOUDINARY}/v1783955716/www_hg8rql_1_wuiiu7.png`;

/**
 * WhatsApp chat screenshots for the landing page's "Straight From Their Phones"
 * section. These are images of chat text, so the Google Translate widget that
 * localizes the rest of the page cannot touch them — each language needs its
 * own set.
 */
const SCREENSHOTS_EN = [
  `${CLOUDINARY}/v1783607969/q_j0kwaa.jpg`,
  `${CLOUDINARY}/v1783607969/q1_w3gtfs.jpg`,
  `${CLOUDINARY}/v1783690165/english_bbsvtz.jpg`,
];

const SCREENSHOTS_DE = [
  `${CLOUDINARY}/v1783690116/yt_ftophw.jpg`,
  `${CLOUDINARY}/v1783690115/rr_mwvudj.jpg`,
  `${CLOUDINARY}/v1783690113/qw_om1qha.jpg`,
];

/** Mirrors `pricingFor` — only German diverges; everything else gets English. */
export const screenshotsFor = (lang: string | null): string[] =>
  lang === "de" ? SCREENSHOTS_DE : SCREENSHOTS_EN;
