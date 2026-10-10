/**
 * languages.ts
 * -----------------------------------------------------------------------------
 * The 12 languages the global Google Translate widget is configured to offer,
 * with display names and ISO country codes for `react-country-flag`. Kept in
 * sync with `SUPPORTED_LANGS` in `@/lib/funnelLanguage` (and `includedLanguages`
 * in public/js/gt.js).
 *
 * Shared by the funnel language switcher and the dashboard/prop-firm headers.
 */
export type Language = {
  /** Google Translate language code (e.g. "de", "zh-CN"). */
  code: string;
  /** Native display name. */
  name: string;
  /** ISO 3166-1 alpha-2 code for the flag. */
  countryCode: string;
};

export const LANGUAGES: Language[] = [
  { code: "en", name: "English", countryCode: "GB" },
  { code: "es", name: "Español", countryCode: "ES" },
  { code: "fr", name: "Français", countryCode: "FR" },
  { code: "de", name: "Deutsch", countryCode: "DE" },
  { code: "it", name: "Italiano", countryCode: "IT" },
  { code: "pt", name: "Português", countryCode: "PT" },
  { code: "ru", name: "Русский", countryCode: "RU" },
  { code: "zh-CN", name: "中文", countryCode: "CN" },
  { code: "ja", name: "日本語", countryCode: "JP" },
  { code: "ko", name: "한국어", countryCode: "KR" },
  { code: "ar", name: "العربية", countryCode: "SA" },
  { code: "hi", name: "हिन्दी", countryCode: "IN" },
];
