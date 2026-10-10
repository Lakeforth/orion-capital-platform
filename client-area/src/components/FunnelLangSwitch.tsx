import { useSearchParams } from "react-router-dom";
import ReactCountryFlag from "react-country-flag";
import { Check } from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { LANGUAGES } from "@/constants/languages";
import {
  applyLanguage,
  currentTranslateLang,
  normalizeLang,
  DEFAULT_LANG,
} from "@/lib/funnelLanguage";

/**
 * FunnelLangSwitch
 * -----------------------------------------------------------------------------
 * Language switcher for the marketing funnel headers. A flag button opens a
 * dropdown of the 12 supported languages; picking one drives the global Google
 * Translate widget (via `applyLanguage`) and records the choice in the URL so it
 * forwards across funnel pages.
 *
 * The active language is derived without side effects — the `?lang=` param, else
 * the live `googtrans` cookie, else English — so this component is safe to drop
 * into any funnel page regardless of whether that page runs its own language
 * hook. (It deliberately does NOT call `useFunnelLanguage`, which carries the
 * Prediction funnel's reset-to-English policy.)
 */
export default function FunnelLangSwitch({
  className,
  labelClassName = "text-slate-700",
}: {
  className?: string;
  labelClassName?: string;
}) {
  const [searchParams, setSearchParams] = useSearchParams();

  const activeCode =
    normalizeLang(searchParams.get("lang")) ??
    currentTranslateLang() ??
    DEFAULT_LANG;
  const activeLang =
    LANGUAGES.find((l) => l.code === activeCode) ?? LANGUAGES[0];

  const changeLanguage = (code: string) => {
    // Keep the URL authoritative so the choice survives navigation (via
    // `withLang`) and the Prediction funnel's URL-driven reset.
    setSearchParams(
      (prev) => {
        const next = new URLSearchParams(prev);
        if (code === DEFAULT_LANG) next.delete("lang");
        else next.set("lang", code);
        return next;
      },
      { replace: true },
    );

    // Drive Google Translate; the `googtrans` cookie persists it across pages.
    applyLanguage(code);
    localStorage.setItem("selectedLanguage", code);
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <button
          type="button"
          className={`flex h-9 items-center gap-1.5 rounded-full px-1.5 transition-opacity hover:opacity-80 ${className ?? ""}`}
          aria-label="Change language"
        >
          <span className={`text-[12px] font-semibold tracking-wide ${labelClassName}`}>
            {activeLang.countryCode}
          </span>
          <ReactCountryFlag
            countryCode={activeLang.countryCode}
            svg
            style={{ width: "22px", height: "22px", borderRadius: "50%" }}
            title={activeLang.name}
          />
        </button>
      </DropdownMenuTrigger>
      <DropdownMenuContent
        align="end"
        className="w-44 max-h-72 overflow-y-auto notranslate"
      >
        {LANGUAGES.map((lang) => (
          <DropdownMenuItem
            key={lang.code}
            onClick={() => changeLanguage(lang.code)}
            className="flex items-center gap-2 cursor-pointer"
          >
            <span className="w-6 text-[11px] font-semibold tracking-wide text-slate-500">
              {lang.countryCode}
            </span>
            <ReactCountryFlag
              countryCode={lang.countryCode}
              svg
              style={{ fontSize: "1.2em" }}
            />
            <span className="flex-1 text-sm">{lang.name}</span>
            {activeCode === lang.code && (
              <Check className="h-3 w-3 text-[#3B82F6]" />
            )}
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
