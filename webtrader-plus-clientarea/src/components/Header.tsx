import React, { useState, useEffect } from "react";
import { Button } from "./ui/button";
import { useNavigate } from "react-router-dom";
import {
  Wallet,
  Menu,
  Settings,
  LogOut,
  Search,
  ChevronDown,
  AlertTriangle,
  CheckCircle,
  Clock,
  XCircle,
} from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "./ui/dropdown-menu";
import { Avatar, AvatarImage, AvatarFallback } from "./ui/avatar";
import DepositFunds from "./deposit-funds/DepositFunds";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import useOverlayStore from "@/store/overlayStore";
import AccountPlansModal from "./AccountPlanModal";
import DepositPromptModal from "./DepositPromptModal";
import NotificationDropdown from "./NotificationDropdown";
import ReactCountryFlag from "react-country-flag";
import { clearAuthenticatedSession } from "@/lib/session";
import Logo from "./Logo";
import { Check } from "lucide-react";

declare global {
  interface Window {
    doGTranslate?: (lang_pair: string) => void;
    GTranslateGetCurrentLang?: () => string | null;
  }
}

const LANGUAGES = [
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

interface HeaderProps {
  toggleSidebar: () => void;
}

const Header: React.FC<HeaderProps> = ({ toggleSidebar }) => {
  const navigate = useNavigate();
  // Global (not local) so any component -- e.g. the Dashboard's Spot
  // Wallet card -- can trigger this same modal via useOverlayStore instead
  // of needing its own copy of this open/close state.
  const isDepositModalOpen = useOverlayStore((state) => state.showDepositModal);
  const setIsDepositModalOpen = useOverlayStore((state) => state.setShowDepositModal);
  const [initialDepositMethod, setInitialDepositMethod] = useState<
    string | null
  >(null);
  const [isPlansModalOpen, setIsPlansModalOpen] = useState(false);
  const [currentLang, setCurrentLang] = useState<string>("en");
  const user = useUserStore((state) => state.user);
  const siteSettings = useSiteSettingsStore((state) => state.settings);
  const useDepositModal = siteSettings?.use_deposit_modal === true;

  useEffect(() => {
    const lang =
      window.GTranslateGetCurrentLang?.() ||
      localStorage.getItem("selectedLanguage") ||
      "en";
    setCurrentLang(lang);
  }, []);

  const changeLanguage = (langCode: string) => {
    localStorage.setItem("selectedLanguage", langCode);
    setCurrentLang(langCode);
    window.doGTranslate?.(`en|${langCode}`);
  };

  const activeLang =
    LANGUAGES.find((l) => l.code === currentLang) ?? LANGUAGES[0];

  const handleLogout = () => {
    clearAuthenticatedSession();
    navigate("/");
  };

  const getVerificationStatusUI = () => {
    const status = (user?.verification_status || "incomplete")
      .toLowerCase()
      .trim();
    switch (status) {
      case "approved":
        return {
          label: "VERIFIED",
          color: "text-green-500",
          icon: <CheckCircle className="h-2.5 w-2.5" />,
        };
      case "completed":
        return {
          label: "IN REVIEW",
          color: "text-blue-500",
          icon: <Clock className="h-2.5 w-2.5" />,
        };
      case "rejected":
        return {
          label: "REJECTED",
          color: "text-red-500",
          icon: <XCircle className="h-2.5 w-2.5" />,
        };
      case "incomplete":
      default:
        return {
          label: "UNVERIFIED",
          color: "text-[#FF7700]",
          icon: <AlertTriangle className="h-2.5 w-2.5" />,
        };
    }
  };

  const statusUI = getVerificationStatusUI();

  return (
    <header
      className="fixed top-0 left-0 right-0 h-16 bg-[#121A33] border-b border-white/5 flex items-center justify-between px-4 md:px-6 z-50"
      style={{ paddingTop: "max(0.5rem, env(safe-area-inset-top))" }}
    >
      {/* Left: Logo & Mobile Menu */}
      <div className="flex items-center gap-4">
        <Button
          className="md:hidden p-2 rounded bg-[#FF7700] text-white hover:bg-[#FF7700]/90"
          onClick={toggleSidebar}
        >
          <Menu className="h-6 w-6" />
        </Button>
        <div className="flex flex-col flex-shrink-0 items-start justify-center leading-none">
          <Logo />
        </div>
      </div>

      {/* Middle: Search Bar */}
      <div className="hidden md:flex flex-1 justify-center mx-8">
        <div className="relative w-full max-w-[300px]">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
          <input
            type="text"
            placeholder="Global Search"
            className="w-full bg-[#222222] border-none rounded-lg py-2 pl-10 pr-4 text-sm text-white focus:ring-1 focus:ring-white/20 placeholder:text-gray-500 transition-all"
          />
        </div>
      </div>

      {/* Right: Actions & Profile */}
      <div className="flex items-center gap-2 md:gap-5">
        {/* Deposit Button */}
        <Button
          onClick={() =>
            useDepositModal
              ? setIsDepositModalOpen(true)
              : navigate("/main/deposit")
          }
          className="bg-[#2EB82E] hover:bg-[#26a026] text-white font-bold h-10 px-4 md:px-6 rounded flex items-center gap-2 shadow-lg shadow-green-500/10"
          title="Deposit Funds"
        >
          <Wallet className="h-4 w-4" />
          <span className="hidden sm:inline text-xs md:text-sm tracking-wide">
            DEPOSIT
          </span>
        </Button>

        {/* Divider */}
        <div className="hidden sm:block w-[1px] h-8 bg-white/10" />

        {/* Icons */}
        <div className="flex items-center gap-3 md:gap-5 px-1 md:px-2">
          <NotificationDropdown />

          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <div className="hidden sm:flex items-center cursor-pointer hover:opacity-80 transition-opacity">
                <ReactCountryFlag
                  countryCode={activeLang.countryCode}
                  svg
                  style={{ fontSize: "1.5em" }}
                  title={activeLang.name}
                />
              </div>
            </DropdownMenuTrigger>
            <DropdownMenuContent
              align="end"
              className="w-44 max-h-72 overflow-y-auto"
            >
              {LANGUAGES.map((lang) => (
                <DropdownMenuItem
                  key={lang.code}
                  onClick={() => changeLanguage(lang.code)}
                  className="flex items-center gap-2 cursor-pointer"
                >
                  <ReactCountryFlag
                    countryCode={lang.countryCode}
                    svg
                    style={{ fontSize: "1.2em" }}
                  />
                  <span className="flex-1 text-sm">{lang.name}</span>
                  {currentLang === lang.code && (
                    <Check className="h-3 w-3 text-primary" />
                  )}
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
          </DropdownMenu>
        </div>

        {/* Profile Dropdown */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <div className="flex items-center gap-3 cursor-pointer group p-1 rounded-lg hover:bg-white/5 transition-all">
              <div className="hidden lg:flex flex-col items-end text-right">
                <div className="flex items-center gap-1">
                  <span className="text-sm font-bold text-white group-hover:text-white/90 transition-colors">
                    {user?.first_name} {user?.last_name}
                  </span>
                  <ChevronDown className="h-3 w-3 text-gray-400 group-hover:text-white transition-colors" />
                </div>
                <div className="flex items-center gap-1">
                  <span
                    className={`text-[9px] font-bold tracking-wider uppercase flex items-center gap-1 ${statusUI.color}`}
                  >
                    {statusUI.label} {statusUI.icon}
                  </span>
                </div>
              </div>
              <Avatar className="h-9 w-9 md:h-10 md:w-10 border border-white/10 group-hover:border-[#FF7700]/30 transition-all">
                <AvatarImage src={user?.avatar} alt={user?.first_name} />
                <AvatarFallback className="bg-[#FF7700] text-white font-bold">
                  {user?.first_name?.charAt(0)}
                </AvatarFallback>
              </Avatar>
            </div>
          </DropdownMenuTrigger>
          <DropdownMenuContent className="w-56 mt-2" align="end" forceMount>
            <DropdownMenuLabel className="font-normal">
              <div className="flex flex-col space-y-1">
                <p className="text-sm font-medium leading-none">
                  {user?.first_name} {user?.last_name}
                </p>
                <p className="text-xs leading-none text-muted-foreground">
                  {user?.email}
                </p>
              </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => navigate("/main/settings")}
              className="cursor-pointer"
            >
              <Settings className="mr-2 h-4 w-4" />
              <span>Settings</span>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={handleLogout}
              className="text-destructive focus:text-destructive cursor-pointer"
            >
              <LogOut className="mr-2 h-4 w-4" />
              <span>Log out</span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      {/* Deposit Funds Modal */}
      <DepositFunds
        isOpen={isDepositModalOpen}
        onClose={() => {
          setIsDepositModalOpen(false);
          setInitialDepositMethod(null);
        }}
        initialMethod={initialDepositMethod}
      />

      {/* Account Plans Modal */}
      <AccountPlansModal
        open={isPlansModalOpen}
        onOpenChange={setIsPlansModalOpen}
      />

      <DepositPromptModal
        onDeposit={(method) => {
          if (useDepositModal) {
            setInitialDepositMethod(method);
            setIsDepositModalOpen(true);
          } else {
            navigate("/main/deposit");
          }
        }}
      />
    </header>
  );
};

export default Header;
