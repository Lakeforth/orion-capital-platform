import { useState, useEffect } from "react";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";
import { Loader2 } from "lucide-react";

declare global {
  interface Window {
    /**
     * Changes the current translation to the specified language pair
     */
    doGTranslate?: (lang_pair: string) => void;

    /**
     * Gets the current language from the googtrans cookie
     */
    GTranslateGetCurrentLang?: () => string | null;
  }
}

export function LanguageSelector() {
  // State for the selected language and loading state
  const [language, setLanguage] = useState<string>('en');
  const [isLoading, setIsLoading] = useState<boolean>(false);

  // Languages list with codes, names and flags
  const languages = [
    { code: "en", name: "English", flag: "🇬🇧" },
    { code: "es", name: "Español", flag: "🇪🇸" },
    { code: "fr", name: "Français", flag: "🇫🇷" },
    { code: "de", name: "Deutsch", flag: "🇩🇪" },
    { code: "it", name: "Italiano", flag: "🇮🇹" },
    { code: "pt", name: "Português", flag: "🇵🇹" },
    { code: "ru", name: "Русский", flag: "🇷🇺" },
    { code: "zh-CN", name: "中文", flag: "🇨🇳" },
    { code: "ja", name: "日本語", flag: "🇯🇵" },
    { code: "ko", name: "한국어", flag: "🇰🇷" },
    { code: "ar", name: "العربية", flag: "🇸🇦" },
    { code: "hi", name: "हिन्दी", flag: "🇮🇳" }
  ];

  // Initialize with stored language preference
  useEffect(() => {
    // Check cookies for current language (most reliable)
    const currentLang = window.GTranslateGetCurrentLang?.() || 'en';

    // Check localStorage as fallback
    const storedLang = localStorage.getItem('selectedLanguage') || 'en';

    // Set state to reflect the actual current language
    setLanguage(currentLang !== 'en' ? currentLang : storedLang);
  }, []);

  const changeLanguage = (langCode: string) => {
    setIsLoading(true);

    // Save the selection to localStorage
    localStorage.setItem('selectedLanguage', langCode);
    setLanguage(langCode);

    // Use GTranslate's function-based approach
    if (window.doGTranslate) {
      window.doGTranslate(`en|${langCode}`);

      // Short delay to show loading indicator
      setTimeout(() => {
        setIsLoading(false);
      }, 800);
    } else {
      console.error('GTranslate function not available');
      setIsLoading(false);
    }
  };

  if (isLoading) {
    return (
        <div className="flex justify-center items-center p-4">
          <Loader2 className="h-5 w-5 animate-spin mr-2" />
          <span>Changing language...</span>
        </div>
    );
  }

  return (
      <RadioGroup
          value={language}
          onValueChange={changeLanguage}
          className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2"
      >
        {languages.map((lang) => (
            <div key={lang.code} className="flex items-center space-x-2">
              <RadioGroupItem
                  value={lang.code}
                  id={`language-${lang.code}`}
                  className="border-primary/20"
              />
              <Label
                  htmlFor={`language-${lang.code}`}
                  className="flex items-center gap-1 cursor-pointer"
              >
                <span className="w-5">{lang.flag}</span>
                <span>{lang.name}</span>
              </Label>
            </div>
        ))}
      </RadioGroup>
  );
}
