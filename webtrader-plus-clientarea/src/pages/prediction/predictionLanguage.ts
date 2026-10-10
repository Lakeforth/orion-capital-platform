/**
 * predictionLanguage.ts
 * -----------------------------------------------------------------------------
 * The funnel language mechanism now lives in the shared `@/lib/funnelLanguage`
 * module (it drives the single global Google Translate widget, so it is shared
 * by every funnel). This file is kept as a thin re-export so existing Prediction
 * imports keep working unchanged.
 */
export * from "@/lib/funnelLanguage";
