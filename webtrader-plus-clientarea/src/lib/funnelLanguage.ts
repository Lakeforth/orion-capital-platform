import { useEffect, useState } from "react";
import { useSearchParams } from "react-router-dom";

/**
 * funnelLanguage.ts
 * -----------------------------------------------------------------------------
 * URL-driven language for the marketing funnels (`?lang=de`, `?lang=es`, …).
 *
 * This app has no i18next. Translation is the Google Translate widget (see
 * public/js/gt.js + index.html), which rewrites text nodes in the live DOM. So
 * driving the language means calling the global `window.doGTranslate('en|<code>')`
 * — the same thing the Header language switcher does. The funnel pages keep
 * their English copy; Google translates it at runtime.
 *
 * `window.doGTranslate` / `window.GTranslateGetCurrentLang` are declared globally
 * in src/components/Header.tsx.
 *
 * Shared by every funnel (Prediction, Trivon, Quantum, Brokerage) — the
 * mechanism
 * drives the single global widget, so it is funnel-agnostic. `predictionLanguage.ts`
 * re-exports this module for backwards compatibility.
 */

/**
 * The 12 languages Google Translate is configured to offer.
 * Source of truth: `includedLanguages` in public/js/gt.js. Keep in sync.
 */
export const SUPPORTED_LANGS = [
  "ar",
  "de",
  "en",
  "es",
  "fr",
  "hi",
  "it",
  "ja",
  "ko",
  "pt",
  "ru",
  "zh-CN",
] as const;

/** Lowercased code -> canonical code (Google is case-sensitive about `zh-CN`). */
const CANONICAL: Record<string, string> = {
  ...Object.fromEntries(SUPPORTED_LANGS.map((c) => [c.toLowerCase(), c])),
  // The legacy TranslateButton uses a bare `zh`; accept it as an alias.
  zh: "zh-CN",
};

/**
 * Normalize a raw `?lang=` value to a supported code.
 * Returns null when absent or unsupported — callers then leave the language alone
 * (the funnel copy is authored in English, so that renders English).
 */
export function normalizeLang(raw: string | null | undefined): string | null {
  if (!raw) return null;
  return CANONICAL[raw.trim().toLowerCase()] ?? null;
}

/** Resolve once the Google Translate widget has injected its globals. */
function whenTranslateReady(timeoutMs = 5000): Promise<boolean> {
  if (typeof window.doGTranslate === "function") return Promise.resolve(true);

  return new Promise((resolve) => {
    const started = Date.now();
    const id = window.setInterval(() => {
      if (typeof window.doGTranslate === "function") {
        window.clearInterval(id);
        resolve(true);
      } else if (Date.now() - started > timeoutMs) {
        window.clearInterval(id);
        resolve(false);
      }
    }, 150);
  });
}

/**
 * Apply `code` via Google Translate, guarding against a reload loop.
 *
 * `doGTranslate` reloads the page whenever it falls back to the cookie path, and
 * `doGTranslate('en|en')` reloads *unconditionally*. Since `?lang=` survives the
 * reload, calling it blindly on every mount would loop forever. So:
 *   - target `en`: only reset when a translation is actually active.
 *   - other:       only apply when we aren't already in that language.
 */
export async function applyLanguage(code: string) {
  const ready = await whenTranslateReady();
  if (!ready) return;

  // No cookie => page is untranslated (i.e. English).
  const current = window.GTranslateGetCurrentLang?.() ?? null;

  if (code === "en") {
    if (current && current !== "en") window.doGTranslate?.("en|en");
    return;
  }

  if (current !== code) window.doGTranslate?.(`en|${code}`);
}

/** The default language. Copy is authored in it, so it needs no translation. */
export const DEFAULT_LANG = "en";

/**
 * The language Google Translate is *currently* rendering, read from its
 * `googtrans` cookie. Null means untranslated, i.e. English.
 */
export function currentTranslateLang(): string | null {
  if (typeof window === "undefined") return null;
  return normalizeLang(window.GTranslateGetCurrentLang?.() ?? null);
}

/**
 * Google Translate persists its choice in a year-long `googtrans` cookie (see
 * public/js/gt.js) and restores it on every load, so a visitor who once opened
 * `?lang=de` keeps seeing German on a bare URL. The funnel treats the URL as
 * authoritative, so an absent `?lang=` must actively reset to English.
 *
 * Clearing that cookie goes through `doGTranslate('en|en')`, which *reloads the
 * page*. If the delete ever failed to take, we would reload forever — so the
 * reset is allowed to fire at most once per tab.
 */
const RESET_SENTINEL = "prediction-lang-reset";

async function resetToEnglish() {
  const current = currentTranslateLang();

  // No cookie, or one already pinned to English (TranslateButton writes
  // `googtrans=/en/en` rather than deleting it): nothing to undo.
  if (!current || current === DEFAULT_LANG) {
    sessionStorage.removeItem(RESET_SENTINEL);
    return;
  }
  if (sessionStorage.getItem(RESET_SENTINEL)) return; // already tried this tab

  sessionStorage.setItem(RESET_SENTINEL, "1");
  await applyLanguage(DEFAULT_LANG);
}

/**
 * Reads `?lang=`, applies it, and returns the language actually on screen so the
 * caller can pick matching prices/assets and forward it on via `withLang`.
 *
 * The initial value falls back to the live translation state: until the reset
 * below completes, the DOM really is still showing the cookie's language, and
 * reporting that keeps currency and imagery matched to the visible text.
 *
 * Note the URL-authoritative policy: an absent `?lang=` actively resets to
 * English. Funnels that want a manually-picked language to persist on bare URLs
 * (via the cookie) should use `useApplyLangParam` instead.
 */
export function useFunnelLanguage(): string {
  const [searchParams] = useSearchParams();
  const param = normalizeLang(searchParams.get("lang"));

  const [effective, setEffective] = useState(
    () => param ?? currentTranslateLang() ?? DEFAULT_LANG,
  );

  useEffect(() => {
    if (param && param !== DEFAULT_LANG) {
      applyLanguage(param);
      setEffective(param);
      return;
    }
    // Absent param, or an explicit `?lang=en`: the URL says English, so undo
    // any sticky translation. Both paths share the reload guard.
    resetToEnglish();
    setEffective(DEFAULT_LANG);
  }, [param]);

  return effective;
}

/**
 * Lightweight, apply-only variant of `useFunnelLanguage` for funnels that don't
 * localize their own assets/prices. Honors an incoming `?lang=` deep link (so a
 * German link translates the page), but does NOT reset to English when the param
 * is absent — that lets a language the visitor picked via the switcher persist on
 * bare URLs through Google Translate's `googtrans` cookie.
 */
export function useApplyLangParam(): void {
  const [searchParams] = useSearchParams();
  const code = normalizeLang(searchParams.get("lang"));

  useEffect(() => {
    if (code && code !== DEFAULT_LANG) applyLanguage(code);
  }, [code]);
}

/** Append `lang` to a path that may or may not already carry a query string. */
export function withLang(path: string, lang: string | null): string {
  // `en` is the default; leaving it off keeps funnel URLs clean.
  if (!lang || lang === DEFAULT_LANG) return path;
  return `${path}${path.includes("?") ? "&" : "?"}lang=${lang}`;
}
