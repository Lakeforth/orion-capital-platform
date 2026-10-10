import React from "react";
import { Link, useLocation } from "react-router-dom";
import {
  LayoutDashboard,
  ArrowUpDown,
  FileCheck,
  Folders,
  History,
  Handshake,
  ChartCandlestick,
  MessageSquare,
  BarChart2,
  Users,
} from "lucide-react";
import { openWebtrader } from "@/lib/trade-room-link";
import { KYC_SIDEBAR_OPENS_SETTINGS } from "@/lib/webtrader-profile-mode";

interface SidebarProps {
  onLinkClick?: () => void;
}

const Sidebar: React.FC<SidebarProps> = ({ onLinkClick }) => {
  const location = useLocation();
  const [showScrollIndicator, setShowScrollIndicator] = React.useState(true);
  const [showScrollUpIndicator, setShowScrollUpIndicator] =
    React.useState(false);
  const scrollContainerRef = React.useRef<HTMLDivElement>(null);

  // Function to check if a nav item is active
  const isActive = (path: string) => {
    // Some items point at a tab, not just a page. Those are active only when
    // their query matches too, or KYC would light up on every Settings tab.
    const [pathname, search] = path.split("?");
    if (location.pathname !== pathname) return false;
    if (!search) return true;

    const current = new URLSearchParams(location.search);
    return [...new URLSearchParams(search).entries()].every(
      ([key, value]) => current.get(key) === value
    );
  };

  const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
    const element = e.currentTarget;
    const isAtBottom =
      element.scrollHeight - element.scrollTop - element.clientHeight < 10;
    const isAtTop = element.scrollTop < 10;
    setShowScrollIndicator(!isAtBottom);
    setShowScrollUpIndicator(!isAtTop);
  };

  const navItems = [
    { title: "Dashboard", icon: LayoutDashboard, path: "/main/dashboard" },
    { title: "Trade Analytics", icon: BarChart2, path: "/main/analytics" },
    // AI Trading is deliberately not listed: no tenant uses it. The page and its
    // route still exist, so a saved link keeps working, but nothing links to it.
    {
      title: "WebTrader",
      icon: ChartCandlestick,
      path: "/trading",
      isExternal: true,
    },
    { title: "Trading Accounts", icon: Folders, path: "/main/trading-accounts" },
    { title: "Withdrawal", icon: ArrowUpDown, path: "/main/withdrawal" },
    {
      title: "Transaction History",
      icon: History,
      path: "/main/transaction-history",
    },
    {
      title: "KYC",
      icon: FileCheck,
      // Goes to the Settings page's KYC tab. The standalone verification page
      // is still there and still works -- see lib/webtrader-profile-mode.
      path: KYC_SIDEBAR_OPENS_SETTINGS ? "/main/settings?tab=kyc" : "/main/verification",
    },
    { title: "KYC Partners", icon: Handshake, path: "/main/kyc-partners" },
    { title: "Partners", icon: Users, path: "/main/partners" },
  ];

  return (
    <aside className="bg-[#121A33] h-full w-full flex flex-col shadow-2xl border-r border-white/5 overflow-hidden">
      {/* Branding Header */}

      {/* Navigation */}
      <div className="flex-1 relative overflow-hidden flex flex-col">
        {/* Scroll Up Indicator - Mobile Only */}
        {showScrollUpIndicator && (
          <div className="md:hidden absolute top-0 left-0 right-0 h-8 bg-gradient-to-b from-[#121A33] to-transparent z-10 flex justify-center items-start pointer-events-none">
            <div className="text-[#FF7700] animate-bounce mt-1">↑</div>
          </div>
        )}

        <div
          ref={scrollContainerRef}
          onScroll={handleScroll}
          className="flex-1 w-full overflow-y-auto px-0 py-2 scrollbar-hide"
        >
          <nav className="w-full">
            <ul className="space-y-1">
              {navItems.map((item, index) => {
                const active = isActive(item.path);
                const Content = (
                  <div
                    className={`flex items-center gap-4 py-3 px-8 relative transition-all duration-200 group ${
                      active
                        ? "bg-[#FF7700]/10 text-[#FF7700]"
                        : "text-gray-400 hover:bg-white/5 hover:text-white"
                    }`}
                  >
                    <item.icon
                      className={`h-5 w-5 ${active ? "text-[#FF7700]" : "text-gray-400 group-hover:text-white"}`}
                    />
                    <span className="font-medium text-[14px]">
                      {item.title}
                    </span>

                    {/* Active Indicator Bar */}
                    {active && (
                      <div className="absolute right-0 top-0 bottom-0 w-[4px] bg-[#FF7700] rounded-l-sm shadow-[0_0_15px_rgba(255,119,0,0.4)]" />
                    )}
                  </div>
                );

                return (
                  <li key={index}>
                    {item.isExternal ? (
                      <button
                        onClick={() => {
                          openWebtrader();
                          if (onLinkClick) onLinkClick();
                        }}
                        className="w-full text-left focus:outline-none"
                      >
                        {Content}
                      </button>
                    ) : (
                      <Link
                        to={item.path}
                        onClick={onLinkClick}
                        className="block"
                      >
                        {Content}
                      </Link>
                    )}
                  </li>
                );
              })}
            </ul>
          </nav>
        </div>

        {/* Scroll Down Indicator - Mobile Only */}
        {showScrollIndicator && (
          <div className="md:hidden absolute bottom-0 left-0 right-0 h-8 bg-gradient-to-t from-[#121A33] to-transparent z-10 flex justify-center items-end pointer-events-none">
            <div className="text-[#FF7700] animate-bounce mb-1">↓</div>
          </div>
        )}
      </div>

      {/* Footer / Logout */}
      <div className="p-4 border-t border-white/5 flex-shrink-0 space-y-2">
        <Link
          to="/main/chat"
          onClick={onLinkClick}
          className={`flex items-center gap-4 py-3 px-4 rounded-lg transition-all duration-200 ${
            isActive("/main/chat")
              ? "bg-[#FF7700]/10 text-[#FF7700]"
              : "text-gray-400 hover:bg-white/5 hover:text-white"
          }`}
        >
          <MessageSquare
            className={`h-5 w-5 ${isActive("/main/chat") ? "text-[#FF7700]" : ""}`}
          />
          <span className="font-medium">Live Chat</span>
        </Link>
      </div>
    </aside>
  );
};

export default Sidebar;
