
import { Button } from "@/components/ui/button";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Globe } from "lucide-react";

// Languages you want to support
const languages = [
    { code: 'en', name: 'English', flag: '🇬🇧' },
    { code: 'es', name: 'Español', flag: '🇪🇸' },
    { code: 'fr', name: 'Français', flag: '🇫🇷' },
    { code: 'de', name: 'Deutsch', flag: '🇩🇪' },
    { code: 'zh', name: '中文', flag: '🇨🇳' },
    { code: 'ru', name: 'Русский', flag: '🇷🇺' }
];

const TranslateButton = () => {
    const triggerGoogleTranslate = (lang: string) => {
        // Skip translation if selecting English
        if (lang === 'en') {
            // Create a cookie to remember the language preference
            document.cookie = `googtrans=/en/en; path=/; domain=${window.location.hostname}`;
            window.location.reload();
            return;
        }

        // Set Google Translate cookie for the selected language
        document.cookie = `googtrans=/en/${lang}; path=/; domain=${window.location.hostname}`;

        // Reload the page to apply the translation
        window.location.reload();
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="flex items-center gap-1">
                    <Globe className="h-4 w-4" />
                    <span>Translate</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {languages.map(lang => (
                    <DropdownMenuItem
                        key={lang.code}
                        onClick={() => triggerGoogleTranslate(lang.code)}
                        className="cursor-pointer"
                    >
                        <span className="mr-2">{lang.flag}</span>
                        <span>{lang.name}</span>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
};

export default TranslateButton;
