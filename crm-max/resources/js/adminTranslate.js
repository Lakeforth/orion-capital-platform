const adminTranslateState = window.__adminTranslateState || {
    widgetId: "google_translate_element",
    selectId: "admin-language-switcher",
    includedLanguages: "ar,de,en,es,fr,hi,it,nl,pt,ru,tr,zh-CN",
    translateScriptId: "google-translate-script",
    languageStorageKey: "admin_translate_language",
    translateRetryLimit: 40,
    translateRetryDelay: 250,
    isApplyingLanguage: false,
    bannerSelectors: [
        ".goog-te-banner-frame.skiptranslate",
        "iframe.goog-te-banner-frame.skiptranslate",
        "iframe.VIpgJd-ZVi9od-ORHb-OEVmcd",
        "iframe.VIpgJd-ZVi9od-xl07Ob-OEVmcd",
        ".VIpgJd-ZVi9od-ORHb-OEVmcd",
        ".VIpgJd-ZVi9od-xl07Ob-OEVmcd",
        ".goog-te-balloon-frame",
        "#goog-gt-tt",
    ].join(","),
};

window.__adminTranslateState = adminTranslateState;

function getTranslateWidget() {
    return document.getElementById(adminTranslateState.widgetId);
}

function getLanguageSelect() {
    return document.getElementById(adminTranslateState.selectId);
}

function getGoogleCombo() {
    return document.querySelector(".goog-te-combo");
}

function getCookie(name) {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : "";
}

function getCookieDomains() {
    const hostname = window.location.hostname;
    const parts = hostname.split(".").filter(Boolean);
    const domains = [null, hostname, `.${hostname}`];

    for (let index = 1; index < parts.length - 1; index += 1) {
        const domain = parts.slice(index).join(".");
        domains.push(domain, `.${domain}`);
    }

    return [...new Set(domains)];
}

function getCookiePaths() {
    const pathname = window.location.pathname || "/";
    const segments = pathname.split("/").filter(Boolean);
    const paths = ["/"];

    if (!segments.length) {
        return paths;
    }

    let currentPath = "";

    segments.forEach((segment) => {
        currentPath += `/${segment}`;
        paths.push(currentPath);
    });

    return [...new Set(paths)];
}

function writeCookie(name, value, expires, domain = null, path = "/") {
    let cookie = `${name}=${encodeURIComponent(value)}; path=${path}`;

    if (expires) {
        cookie += `; expires=${expires}`;
    }

    if (domain) {
        cookie += `; domain=${domain}`;
    }

    document.cookie = cookie;
}

function setCookie(name, value, expires) {
    getCookieDomains().forEach((domain) => {
        writeCookie(name, value, expires, domain);
    });
}

function clearCookie(name) {
    const expires = "Thu, 01 Jan 1970 00:00:00 GMT";

    getCookieDomains().forEach((domain) => {
        getCookiePaths().forEach((path) => {
            writeCookie(name, "", expires, domain, path);
        });
    });
}

function getPersistedLanguage() {
    try {
        return (
            window.localStorage.getItem(adminTranslateState.languageStorageKey) ||
            ""
        );
    } catch (error) {
        return "";
    }
}

function persistLanguage(language) {
    try {
        window.localStorage.setItem(
            adminTranslateState.languageStorageKey,
            language
        );
    } catch (error) {
        // Ignore storage access issues and continue with cookies.
    }
}

function resolveLanguage() {
    const persistedLanguage = getPersistedLanguage();

    if (persistedLanguage) {
        return persistedLanguage;
    }

    // Saved on the signed-in account (Account Settings > Preferences): used on a browser that
    // has no language of its own yet, so the choice follows the user.
    const accountLanguage = document.getElementById("qsLangDropdown")?.dataset.accountLocale;

    if (accountLanguage) {
        return accountLanguage;
    }

    const combo = getGoogleCombo();

    if (combo && combo.value) {
        return combo.value;
    }

    const googTrans = getCookie("googtrans");
    const parts = googTrans.split("/");

    return parts.length >= 3 && parts[2] ? parts[2] : "en";
}

function syncLanguageDirection(language = resolveLanguage()) {
    // Direction must work even while Google's translation script is unavailable.
    if (!getLanguageSelect()) return;
    const direction = language.split("-")[0] === "ar" ? "rtl" : "ltr";
    for (const element of [document.documentElement, document.body]) {
        if (element.getAttribute("dir") !== direction) element.setAttribute("dir", direction);
    }
    if (document.documentElement.getAttribute("lang") !== language) {
        document.documentElement.setAttribute("lang", language);
    }
}

function setSelectBusy(isBusy) {
    const select = getLanguageSelect();

    if (!select) {
        return;
    }

    select.disabled = isBusy;
    select.style.setProperty("opacity", isBusy ? "0.7" : "1");
    select.style.setProperty("cursor", isBusy ? "wait" : "pointer");
}

function syncSelectWithState() {
    const select = getLanguageSelect();

    if (!select) {
        return;
    }

    select.value = resolveLanguage();
    syncLanguageDirection(select.value);
}

function resetTranslateLayout() {
    document.body.style.setProperty("top", "0px", "important");
    document.body.style.setProperty("position", "relative", "important");
    document.documentElement.style.setProperty("top", "0px", "important");

    document
        .querySelectorAll(adminTranslateState.bannerSelectors)
        .forEach((element) => {
            element.style.setProperty("display", "none", "important");
            element.style.setProperty("visibility", "hidden", "important");
            element.style.setProperty("opacity", "0", "important");
            element.style.setProperty("pointer-events", "none", "important");
            element.style.setProperty("height", "0", "important");
            element.style.setProperty("width", "0", "important");
            element.style.setProperty("max-height", "0", "important");
            element.style.setProperty("max-width", "0", "important");
            element.style.setProperty("border", "0", "important");
            element.style.setProperty("box-shadow", "none", "important");
        });
}

function ensureTranslateScript() {
    if (document.getElementById(adminTranslateState.translateScriptId)) {
        return;
    }

    const script = document.createElement("script");
    script.id = adminTranslateState.translateScriptId;
    script.src =
        "https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit";
    script.async = true;
    document.head.appendChild(script);
}

function mountGoogleTranslateWidget() {
    const widget = getTranslateWidget();

    // google.translate can exist as a namespace stub before Google's script
    // has actually attached TranslateElement as a constructor on it -- this
    // function runs on every splade:rendered (Splade fires that on every
    // admin panel page transition), so it can land in that exact window and
    // throw "TranslateElement is not a constructor" uncaught. The retry loop
    // in waitForGoogleCombo() already re-invokes this on a timer, so it's
    // safe to just bail here and let a later call catch it once ready.
    if (
        !widget ||
        !window.google ||
        !window.google.translate ||
        typeof window.google.translate.TranslateElement !== "function"
    ) {
        return false;
    }

    if (getGoogleCombo()) {
        return true;
    }

    widget.innerHTML = "";

    new window.google.translate.TranslateElement(
        {
            pageLanguage: "en",
            autoDisplay: false,
            includedLanguages: adminTranslateState.includedLanguages,
        },
        adminTranslateState.widgetId
    );

    return true;
}

function waitForGoogleCombo(attempt = 0) {
    return new Promise((resolve) => {
        const combo = getGoogleCombo();

        if (combo) {
            resolve(combo);
            return;
        }

        if (attempt >= adminTranslateState.translateRetryLimit) {
            resolve(null);
            return;
        }

        window.setTimeout(() => {
            if (window.google && window.google.translate) {
                mountGoogleTranslateWidget();
            }

            resolve(waitForGoogleCombo(attempt + 1));
        }, adminTranslateState.translateRetryDelay);
    });
}

async function reapplyCurrentLanguage() {
    const language = resolveLanguage();
    syncLanguageDirection(language);

    if (language === "en") {
        syncSelectWithState();
        resetTranslateLayout();
        return;
    }

    let combo = getGoogleCombo();

    if (!combo) {
        combo = await waitForGoogleCombo();
    }

    if (!combo) {
        syncSelectWithState();
        return;
    }

    if (combo.value !== language) {
        combo.value = language;
    }

    combo.dispatchEvent(new Event("change", { bubbles: true }));

    window.setTimeout(resetTranslateLayout, 150);
    window.setTimeout(resetTranslateLayout, 600);
    window.setTimeout(resetTranslateLayout, 1500);
    window.setTimeout(syncSelectWithState, 300);
}

async function applyLanguage(language) {
    if (adminTranslateState.isApplyingLanguage) {
        return;
    }

    if (language === resolveLanguage()) {
        syncSelectWithState();
        return;
    }

    adminTranslateState.isApplyingLanguage = true;
    setSelectBusy(true);

    const select = getLanguageSelect();

    if (select) {
        select.value = language;
    }

    try {
        persistLanguage(language);
        syncLanguageDirection(language);

        if (language === "en") {
            clearCookie("googtrans");
        } else {
            setCookie("googtrans", `/en/${language}`);
        }

        window.setTimeout(() => {
            window.location.assign(
                `${window.location.pathname}${window.location.search}${window.location.hash}`
            );
        }, 50);
    } catch (error) {
        adminTranslateState.isApplyingLanguage = false;
        setSelectBusy(false);
        syncSelectWithState();
    }
}

function bindTranslateSelect() {
    const select = getLanguageSelect();

    if (!select) {
        return;
    }

    if (select.dataset.translateBound === "true") {
        syncSelectWithState();
        setSelectBusy(adminTranslateState.isApplyingLanguage);
        return;
    }

    select.addEventListener("change", (event) => {
        applyLanguage(event.target.value);
    });

    select.dataset.translateBound = "true";
    syncSelectWithState();
    setSelectBusy(adminTranslateState.isApplyingLanguage);
}

function bootAdminTranslate({ reapply = false } = {}) {
    const select = getLanguageSelect();
    const widget = getTranslateWidget();

    if (!select || !widget) {
        return;
    }

    bindTranslateSelect();
    resetTranslateLayout();

    // First visit from this browser -- nothing saved, no account language, no translator cookie:
    // apply the language of the visitor's country (data-ip-locale, DB-IP Lite) exactly as a pick
    // does, so Google reads it from its cookie after one reload. Any later pick replaces it.
    const dropdown = document.getElementById("qsLangDropdown");
    const suggested = dropdown?.dataset.ipLocale;

    if (
        suggested &&
        suggested !== "en" &&
        !getPersistedLanguage() &&
        !dropdown.dataset.accountLocale &&
        !getCookie("googtrans")
    ) {
        applyLanguage(suggested);
        return;
    }

    ensureTranslateScript();
    persistLanguage(resolveLanguage());

    if (window.google && window.google.translate) {
        mountGoogleTranslateWidget();
    }

    if (reapply) {
        window.setTimeout(() => {
            reapplyCurrentLanguage();
        }, 50);
    }
}

window.googleTranslateElementInit = function () {
    bootAdminTranslate({ reapply: true });
};

if (!window.__adminGoogleTranslateObserver) {
    const observer = new MutationObserver(() => {
        resetTranslateLayout();
        syncLanguageDirection();
    });

    observer.observe(document.documentElement, {
        attributes: true,
        childList: true,
        subtree: true,
        attributeFilter: ["class", "style", "dir", "lang"],
    });

    window.__adminGoogleTranslateObserver = observer;
}

if (!window.__adminGoogleTranslateEventsBound) {
    document.addEventListener("DOMContentLoaded", () => {
        bootAdminTranslate({ reapply: true });
    });

    document.addEventListener("splade:rendered", () => {
        window.setTimeout(() => {
            bootAdminTranslate({ reapply: true });
        }, 0);
    });

    window.__adminGoogleTranslateEventsBound = true;
}

bootAdminTranslate({ reapply: true });
