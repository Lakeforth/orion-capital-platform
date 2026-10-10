/**
 * predictionApi.ts
 * -----------------------------------------------------------------------------
 * All backend calls for the Prediction funnel, against a single hardcoded host.
 *
 * Mirrors the Quantum funnel's integration (src/pages/CampaignSurveyPage.tsx,
 * QuantumLanding.tsx, PaymentPromptPage.tsx) endpoint-for-endpoint — only the
 * host differs. Every call swallows its error and returns null so the funnel
 * always advances, exactly like Quantum.
 *
 * NOTE: we deliberately do NOT reuse `axiosInstance` / `useCampaignQuery` for
 * the campaign lookup. Their baseURL is derived from `window.location.origin`
 * (see src/lib/api-base-url.ts), which would hit the wrong host whenever the
 * app isn't served from PREDICTION_BASE_URL.
 */

export const PREDICTION_BASE_URL = "https://secure.2ix1.com";

export type FunnelStatus = "landing_page" | "checkout";

export interface CapturePayload {
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  country: string;
  ip_address: string;
  device_type: string;
  browser_type: string;
  language: string;
}

/** Shape of the campaign/capture response we care about. The API is
 *  inconsistent about casing, so callers should read both variants. */
export interface CampaignLike {
  name?: string;
  campaign_name?: string;
  campaign?: string;
  whatsapp_number?: string;
  whatsappNumber?: string;
  phone?: string;
  whatsapp_message?: string;
  whatsappMessage?: string;
  message?: string;
  logo?: string;
  favicon?: string;
  /** Raw tracking script (e.g. a Meta Pixel snippet) set on the campaign,
   *  meant to run only on that campaign's own landing page. */
  trackingScript?: string | null;
  /** Backend-supplied post-capture redirect. For `end_zone === 'platform'`
   *  campaigns this is `{front_end_url}/auto-login?token=...`, which the
   *  checkout step forwards to so the lead lands in the trade room. */
  redirect_url?: string | null;
}

/** Runs the campaign's own tracking script (e.g. a Meta Pixel snippet) once,
 *  when one is set. innerHTML alone won't execute <script> tags, so each one
 *  is recreated as a real script element; anything else (e.g. a pixel's
 *  <noscript><img> fallback) is inserted as-is. */
export function applyCampaignTrackingScript(c: CampaignLike | null | undefined) {
  if (!c?.trackingScript) return;
  const container = document.createElement("div");
  container.innerHTML = c.trackingScript;
  Array.from(container.childNodes).forEach((node) => {
    if (node.nodeName === "SCRIPT") {
      const oldScript = node as HTMLScriptElement;
      const newScript = document.createElement("script");
      Array.from(oldScript.attributes).forEach((attr) =>
        newScript.setAttribute(attr.name, attr.value),
      );
      newScript.text = oldScript.text;
      document.head.appendChild(newScript);
    } else {
      document.head.appendChild(node);
    }
  });
}

/** Sets the browser tab icon to the campaign's own favicon, when one is set. */
export function applyCampaignFavicon(c: CampaignLike | null | undefined) {
  if (!c?.favicon) return;
  let link = document.querySelector<HTMLLinkElement>("link[rel='icon']");
  if (!link) {
    link = document.createElement("link");
    link.rel = "icon";
    document.head.appendChild(link);
  }
  link.href = c.favicon;
}

const JSON_HEADERS = {
  "Content-Type": "application/json",
  Accept: "application/json",
} as const;

/* ------------------------------ env detection ----------------------------- */

export function getDeviceType() {
  return /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent)
    ? "mobile"
    : window.innerWidth < 1024
      ? "tablet"
      : "desktop";
}

export function getBrowserType() {
  const ua = navigator.userAgent;
  if (/Edg\//.test(ua)) return "Edge";
  if (/OPR\/|Opera/.test(ua)) return "Opera";
  if (/Chrome\//.test(ua) && !/Chromium/.test(ua)) return "Chrome";
  if (/Firefox\//.test(ua)) return "Firefox";
  if (/Safari\//.test(ua) && !/Chrome/.test(ua)) return "Safari";
  if (/MSIE|Trident/.test(ua)) return "IE";
  return "Other";
}

export interface GeoInfo {
  ip: string;
  countryName: string;
  countryCode: string;
}

/** ipify for the IP, ipapi for the country. Either may fail; we return blanks. */
export async function detectIpAndGeo(): Promise<GeoInfo> {
  const geo: GeoInfo = { ip: "", countryName: "", countryCode: "" };

  console.log(
    "[Prediction] GET geo →",
    "https://api4.ipify.org?format=json",
    "https://ipapi.co/json/",
  );

  const [ipify, ipapi] = await Promise.allSettled([
    fetch("https://api4.ipify.org?format=json").then((r) => r.json()),
    fetch("https://ipapi.co/json/").then((r) => r.json()),
  ]);

  if (ipify.status === "fulfilled") geo.ip = ipify.value?.ip ?? "";
  if (ipapi.status === "fulfilled") {
    // Only fall back to ipapi's IP when ipify didn't give us one.
    if (!geo.ip) geo.ip = ipapi.value?.ip ?? "";
    geo.countryName = ipapi.value?.country_name ?? "";
    geo.countryCode = ipapi.value?.country_code ?? "";
  }

  console.log("[Prediction] geo resolved ←", geo);
  return geo;
}

/* ------------------------------- endpoints -------------------------------- */

/** GET {BASE}/api/v1/leads/campaigns/{uuid} */
export async function fetchCampaign(
  uuid: string,
): Promise<CampaignLike | null> {
  const url = `${PREDICTION_BASE_URL}/api/v1/leads/campaigns/${uuid}`;
  console.log("[Prediction] GET campaign →", url);
  try {
    const resp = await fetch(url);
    const data = await resp.json();
    console.log("[Prediction] campaign response ←", data);
    return data?.data ?? null;
  } catch (e) {
    console.error("prediction campaign lookup failed", e);
    return null;
  }
}

/** POST {BASE}/c/{uuid}/capture */
export async function postCapture(
  uuid: string,
  payload: CapturePayload,
): Promise<CampaignLike | null> {
  const url = `${PREDICTION_BASE_URL}/c/${uuid}/capture`;
  console.log("[Prediction] POST capture →", url, payload);
  try {
    const resp = await fetch(url, {
      method: "POST",
      headers: JSON_HEADERS,
      body: JSON.stringify(payload),
    });
    const data = await resp.json();
    console.log("[Prediction] capture response ←", data);
    // The capture body is flat (`{ redirect_url }`), while campaign fields (if
    // any) would arrive nested under `data`. Merge both so `redirect_url`
    // survives without disturbing the existing logo/WhatsApp reads.
    const nested = data?.data ?? {};
    return {
      ...nested,
      redirect_url: data?.redirect_url ?? nested.redirect_url ?? null,
    };
  } catch (e) {
    console.error("prediction capture failed", e);
    return null;
  }
}

/** POST {BASE}/c/{uuid}/survey */
export async function postSurvey(
  uuid: string,
  payload: { email: string; answers: Record<string, string> },
): Promise<void> {
  const url = `${PREDICTION_BASE_URL}/c/${uuid}/survey`;
  console.log("[Prediction] POST survey →", url, payload);
  try {
    const resp = await fetch(url, {
      method: "POST",
      headers: JSON_HEADERS,
      body: JSON.stringify(payload),
    });
    const data = await resp.json();
    console.log("[Prediction] survey response ←", data);
  } catch (e) {
    console.error("prediction survey submission failed", e);
  }
}

/** POST {BASE}/c/{uuid}/status — fire and forget */
export function postStatus(uuid: string, email: string, status: FunnelStatus) {
  const url = `${PREDICTION_BASE_URL}/c/${uuid}/status`;
  const payload = { email, status };
  console.log("[Prediction] POST status →", url, payload);
  fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  })
    .then(async (resp) => {
      const data = await resp.json().catch(() => null);
      console.log(`[Prediction] status ${status} response ←`, data);
    })
    .catch((e) => console.error(`prediction status ${status} failed`, e));
}

/* --------------------------------- helpers -------------------------------- */

/** The API returns WhatsApp fields in both snake_case and camelCase. */
export function readWhatsApp(c: CampaignLike | null | undefined) {
  return {
    number: c?.whatsapp_number ?? c?.whatsappNumber ?? c?.phone ?? null,
    message: c?.whatsapp_message ?? c?.whatsappMessage ?? c?.message ?? null,
    campaignName: c?.campaign_name ?? c?.campaign ?? c?.name ?? null,
  };
}
