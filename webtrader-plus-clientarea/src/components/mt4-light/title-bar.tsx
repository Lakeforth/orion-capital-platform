import { Button } from "@/components/ui/button";
import { Moon, Sun, LogOut, ArrowLeft, UserRound, Repeat } from "lucide-react";
import { Switch } from "@/components/ui/switch";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
  DropdownMenuItem,
} from "@/components/ui/dropdown-menu";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { useNavigate } from "react-router-dom";
import { useState } from "react";
import PersonalInformationDialog from "@/components/profile/PersonalInformationDialog";
import SwitchAccountDialog from "@/components/profile/SwitchAccountDialog";
import useDarkModeStore from "@/store/darkModeStore";
import useUserStore from "@/store/userStore";
import { clearAuthenticatedSession } from "@/lib/session";
import { isWebtraderHost } from "@/lib/webtrader-host";
import { returnToClientArea } from "@/lib/trade-room-link";
import { CLIENT_AREA_PATHS, canOpenClientArea } from "@/lib/webtrader-profile-mode";

export function TitleBar() {
  const settings = useSiteSettingsStore((state) => state.settings);
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);
  const user = useUserStore((state) => state.user);
  const unreadNotifications =
    user?.notifications.filter((n) => !n.read_at).length || 0;
  const navigate = useNavigate();
  const [isProfileOpen, setIsProfileOpen] = useState(false);
  const [isSwitchAccountOpen, setIsSwitchAccountOpen] = useState(false);
  // This title bar had no dashboard/profile/settings link at all -- on a
  // standalone webtrader origin (no client-area routes mounted, see
  // WebtraderApp.tsx) that left MT4-light sessions with no way back.
  const onWebtrader = isWebtraderHost();
  // Only true when launched from the client area's Trade Room -- a direct
  // login on the webtrader form itself has no dashboard to go back to (see
  // viaClientAreaLaunch on useUserStore).
  const viaClientAreaLaunch = useUserStore((state) => state.viaClientAreaLaunch);
  const showDashboardButton = onWebtrader && viaClientAreaLaunch && !!settings?.client_area_url;

  const handleLogout = () => {
    clearAuthenticatedSession();
    navigate("/");
  };

  return (
    <div
      className={`${
        isDarkMode
          ? "bg-[#1A202C] border-[#4A5568]"
          : "bg-[#EDF0F4] border-[#C3C3C3]"
      } border-b px-4 h-[38px] flex items-center justify-between shadow-[inset_0_4px_20px_0_rgba(0,0,0,0.05)]`}
    >
      <div className="flex items-center gap-3">
        {settings?.logo_sm ? (
          <img src={settings.logo_sm} alt="Logo" className="h-5 w-auto" />
        ) : (
          <div className="w-5 h-5 flex items-center justify-center">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              className="h-5 w-5"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M3 17L9 11L13 15L21 7"
                stroke="#48BB78"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
              />
              <path
                d="M3 12L9 6L13 10L21 2"
                stroke="#F56565"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
                opacity="0.5"
              />
            </svg>
          </div>
        )}
        <span
          className={`${
            isDarkMode ? "text-slate-200" : "text-[#2D3748]"
          } text-[13px] font-bold tracking-tight`}
        >
          {settings?.name || "Equity Markets Pro"}
        </span>
      </div>

      <div className="flex items-center gap-4">
        {showDashboardButton && (
          <Button
            variant="outline"
            size="sm"
            className="h-6 gap-1.5 px-2 text-[11px]"
            onClick={() => {
              returnToClientArea();
            }}
          >
            <ArrowLeft className="h-3 w-3" />
            <span className="hidden sm:inline">Client Area</span>
          </Button>
        )}

        <div className="flex items-center gap-2">
          <Sun
            className={`h-3.5 w-3.5 ${
              isDarkMode ? "text-slate-500" : "text-slate-600"
            }`}
          />
          <Switch
            checked={isDarkMode}
            onCheckedChange={() => useDarkModeStore.getState().toggleDarkMode()}
            className="data-[state=checked]:bg-slate-700 h-5 w-9"
          />
          <Moon
            className={`h-3.5 w-3.5 ${
              isDarkMode ? "text-slate-300" : "text-slate-500"
            }`}
          />
        </div>

        {/* modal={false}: a modal menu and a modal dialog both park
            `pointer-events: none` on <body> and restore what they found, so
            opening the profile dialog from a modal menu leaves the whole
            terminal unclickable once the dialog closes. */}
        <DropdownMenu modal={false}>
          <DropdownMenuTrigger asChild>
            <div className="relative cursor-pointer hover:opacity-80 transition-opacity">
              <div
                className={`h-7 w-7 rounded-full flex items-center justify-center border ${
                  isDarkMode
                    ? "bg-slate-700 border-slate-600"
                    : "bg-[#CBD5E0] border-slate-400"
                }`}
              >
                {user?.avatar ? (
                  <img
                    src={user.avatar}
                    alt="User"
                    className="h-full w-full rounded-full object-cover"
                  />
                ) : (
                  <div className="text-slate-600">
                    <svg
                      viewBox="0 0 24 24"
                      fill="currentColor"
                      className="h-5 w-5"
                    >
                      <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                    </svg>
                  </div>
                )}
              </div>
              {unreadNotifications > 0 && (
                <div className="absolute -top-0.5 -right-0.5 rounded-full min-w-[14px] h-3.5 px-0.5 flex items-center justify-center text-[9px] font-bold bg-[#E53E3E] text-white border border-white shadow-sm">
                  {unreadNotifications}
                </div>
              )}
            </div>
          </DropdownMenuTrigger>
          <DropdownMenuContent
            align="end"
            className={`w-40 mt-1 ${
              isDarkMode
                ? "bg-slate-800 border-slate-700 text-slate-200"
                : "bg-white border-slate-200 text-slate-800"
            }`}
          >
            {/* The menu unmounts as it closes, so open the dialog from state
                rather than from a trigger nested inside the item. */}
            <DropdownMenuItem
              onSelect={() => {
                if (canOpenClientArea(showDashboardButton)) {
                  returnToClientArea(CLIENT_AREA_PATHS.profile);
                  return;
                }
                setIsProfileOpen(true);
              }}
              className="cursor-pointer flex items-center gap-2"
            >
              <UserRound className="h-4 w-4" />
              <span>Profile</span>
            </DropdownMenuItem>
            <DropdownMenuItem
              onSelect={() => setIsSwitchAccountOpen(true)}
              className="cursor-pointer flex items-center gap-2"
            >
              <Repeat className="h-4 w-4" />
              <span>Switch Account</span>
            </DropdownMenuItem>
            <DropdownMenuItem
              onClick={handleLogout}
              className="cursor-pointer flex items-center gap-2"
            >
              <LogOut className="h-4 w-4" />
              <span>Logout</span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      <PersonalInformationDialog
        open={isProfileOpen}
        onOpenChange={setIsProfileOpen}
      />

      <SwitchAccountDialog
        open={isSwitchAccountOpen}
        onOpenChange={setIsSwitchAccountOpen}
      />
    </div>
  );
}
