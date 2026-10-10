import useSiteSettingsStore from "@/store/siteSettingStore";

const TelegramIcon = () => (
  <svg viewBox="0 0 24 24" fill="currentColor" className="w-7 h-7">
    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
  </svg>
);

export default function TelegramButton() {
  const { settings } = useSiteSettingsStore();

  const telegramLink = settings?.demo?.telegram_link;

  if (!telegramLink) return null;

  return (
    <div className="fixed bottom-24 right-6 z-50">
      <div className="relative cursor-pointer">
        <span className="absolute inset-0 rounded-full bg-[#229ED9] animate-ping opacity-50" />
        <span className="absolute inset-0 rounded-full bg-[#229ED9] animate-pulse opacity-30" />
        <button
          aria-label="Open Telegram"
          onClick={() => window.open(telegramLink, "_blank", "noopener,noreferrer")}
          className="relative w-14 h-14 rounded-full bg-[#229ED9] hover:bg-[#1a8bbf] text-white shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110"
        >
          <TelegramIcon />
        </button>
      </div>
    </div>
  );
}
