import AccountIdentity from "./account-identity";
import AccountSwitcher from "./account-switcher";
import FunnelLangSwitch from "@/components/FunnelLangSwitch";
import type React from "react";

import { useRef, useState, useEffect } from "react";
import {
  Plus,
  Wallet,
  ChevronDown,
  Menu,
  X,
  LineChart,
  Clock,
  BarChart3,
  Calendar,
  Newspaper,
  LogOut,
  Bot,
  ArrowUpFromLine,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { useMobile } from "@/hooks/use-mobile";
import useUserStore from "@/store/userStore";
import useAssetStore from "@/store/assetStore";
import { Link, useNavigate } from "react-router-dom";
import AutoTraderModal from "./auto-trader-modal";
import Logo from "@/components/Logo";
import { useCurrency } from "@/hooks/useCurrency";
import AccountPlansModal from "@/components/AccountPlanModal";
import { Dialog, DialogContent, DialogTrigger } from "@/components/ui/dialog";
import DepositFunds from "@/components/deposit-funds/DepositFunds";
import WithdrawFunds from "@/components/withdrawal/WithdrawFunds";
import PersonalInformationDialog from "@/components/profile/PersonalInformationDialog";
import {
  CLIENT_AREA_PATHS,
  WITHDRAW_OPENS_CLIENT_AREA,
  canOpenClientArea,
} from "@/lib/webtrader-profile-mode";
import MarketWatchPanel from "./panels/market-watch-panel";
import { clearAuthenticatedSession } from "@/lib/session";
import { isWebtraderHost } from "@/lib/webtrader-host";
import { returnToClientArea } from "@/lib/trade-room-link";
import useSiteSettingsStore from "@/store/siteSettingStore";
import axiosInstance from "@/lib/axios";
import { toast } from "sonner";
import type { AxiosError } from "axios";

// Define the ActiveView type
type ActiveView =
  | "market-watch"
  | "active-orders"
  | "trading-history"
  | "calendar"
  | "market-news"
  | null;

interface HeaderProps {
  activePairs: string[];
  activePair: string;
  setActivePair: (pair: string) => void;
  removeCurrencyPair: (pair: string) => void;
  toggleView: (view: ActiveView) => void;
}

interface AssetListingTabsProps {
  activePairs: string[];
  activePair: string;
  handlePairClick: (pair: string) => void;
  getAssetCategory: (pair: string) => string;
  removeCurrencyPair: (pair: string) => void;
  isMobile: boolean;
  showScrollButtons?: boolean;
  scrollTabs?: (direction: "left" | "right") => void;
  tabsListRef: React.RefObject<HTMLDivElement | null>;
  activeTabRef: React.RefObject<HTMLDivElement | null>;
  setIsMarketWatchOpen: (value: boolean) => void;
}

function AssetListingTabs({
  activePairs,
  activePair,
  handlePairClick,
  removeCurrencyPair,
  isMobile,
  showScrollButtons,
  scrollTabs,
  tabsListRef,
  activeTabRef,
  setIsMarketWatchOpen,
  getAssetCategory,
}: AssetListingTabsProps) {
  return (
    <div className={`flex items-center ${isMobile ? "w-full" : "h-full"}`}>
      {!isMobile && showScrollButtons && (
        <Button
          variant="ghost"
          size="icon"
          className="h-8 w-8"
          onClick={() => scrollTabs?.("left")}
        >
          <ChevronLeft className="h-4 w-4" />
        </Button>
      )}
      <div
        className={`overflow-hidden ${
          isMobile
            ? "w-full"
            : "max-w-[300px] sm:max-w-[400px] md:max-w-[500px]"
        } h-full flex items-center`}
      >
        <div className="w-full h-full flex items-center">
          <div
            ref={tabsListRef}
            className="flex gap-2 overflow-x-auto scrollbar-hide whitespace-nowrap h-[70%]"
            style={{ scrollbarWidth: "none" }}
          >
            {activePairs.map((pair, index) => (
              <div
                key={index}
                ref={activePair === pair ? activeTabRef : null}
                className={`relative flex items-center gap-3 px-4 py-2 cursor-pointer border-[1px] border-muted ${
                  activePair === pair
                    ? "border-b-2 border-b-accent "
                    : "hover:bg-muted"
                }`}
                onClick={() => handlePairClick(pair)}
              >
                <CurrencyFlag pair={pair} />
                <div className="flex flex-col items-start">
                  <span className="text-xs">{pair}</span>
                  <span className="text-xs text-muted-foreground hidden sm:inline">
                    {getAssetCategory(pair)}
                  </span>
                </div>
                <button
                  className="absolute top-0 left-[-7px] ml-1 sm:ml-2 rounded-full hover:bg-muted p-0.5"
                  onClick={(e) => {
                    e.stopPropagation();
                    removeCurrencyPair(pair);
                  }}
                >
                  <X className="h-4 w-4" />
                </button>
              </div>
            ))}
          </div>
        </div>
      </div>
      {!isMobile && showScrollButtons && (
        <Button
          variant="ghost"
          size="icon"
          className="h-8 w-8"
          onClick={() => scrollTabs?.("right")}
        >
          <ChevronRight className="h-4 w-4" />
        </Button>
      )}
      {
        <Button
          variant="outline"
          size="icon"
          className="rounded-md ml-2"
          onClick={() => setIsMarketWatchOpen(true)}
        >
          <Plus className="h-4 w-4" />
        </Button>
      }
    </div>
  );
}

export default function Header({
  activePairs,
  activePair,
  setActivePair,
  removeCurrencyPair,
  toggleView,
}: HeaderProps) {
  const navigate = useNavigate();
  const [isDepositModalOpen, setIsDepositModalOpen] = useState(false);
  const [isWithdrawModalOpen, setIsWithdrawModalOpen] = useState(false);
  const useDepositModal = useSiteSettingsStore(
    (state) => state.settings?.use_deposit_modal === true,
  );
  const { formatCurrency } = useCurrency();
  const tabsListRef = useRef<HTMLDivElement>(null);
  const activeTabRef = useRef<HTMLDivElement>(null);
  const [showScrollButtons, setShowScrollButtons] = useState(false);
  const isMobile = useMobile();
  const [isSearchOpen, setIsSearchOpen] = useState(false);
  const [isMarketWatchOpen, setIsMarketWatchOpen] = useState(false);
  // assets is intentionally not subscribed here -- it's only read
  // imperatively inside findAssetByPair below. Subscribing to the full
  // array would re-render this header (and its tab bar) on every
  // websocket price tick for every symbol on the platform.
  const activeAsset = useAssetStore((state) => state.activeAsset);
  const setActiveAsset = useAssetStore((state) => state.setActiveAsset);
  const user = useUserStore((state) => state.user);
  const activeTradingAccount = useUserStore((state) => state.activeTradingAccount);
  const enablePlan = useSiteSettingsStore(
    (state) => state.settings?.enable_plan === true,
  );
  // This header is shared between the trade room panel embedded in the
  // main client-area app (where /main/dashboard etc. are real routes) and
  // the standalone WebtraderApp shell (which deliberately mounts none of
  // the client-area routes -- see WebtraderApp.tsx). Inside the latter,
  // <Link to="/main/..."> just bounces off WebtraderApp's catch-all back
  // to "/", making Profile/Account Details/Settings look unresponsive and
  // leaving no way back to the dashboard at all.
  const onWebtrader = isWebtraderHost();
  const [isProfileOpen, setIsProfileOpen] = useState(false);
  const clientAreaUrl = useSiteSettingsStore(
    (state) => state.settings?.client_area_url,
  );
  // Only true when this session was launched from the client area's Trade
  // Room -- a direct login on the webtrader form itself has no dashboard to
  // go back to, so the button must stay hidden there (see viaClientAreaLaunch
  // on useUserStore).
  const viaClientAreaLaunch = useUserStore((state) => state.viaClientAreaLaunch);
  const showDashboardButton = onWebtrader && viaClientAreaLaunch && !!clientAreaUrl;
  const canUseDeposit = useDepositModal || !onWebtrader || showDashboardButton;

  // Withdraw mirrors Deposit. On the webtrader it always opens in place: that
  // origin mounts no client-area routes, so there is nowhere else to send
  // them, which is the whole reason the terminal had no withdrawal at all.
  const handleWithdraw = () => {
    if (onWebtrader) {
      // Leaves the terminal for the client area's withdrawal page. Falls back
      // to the dialog when this session has no client area to go to -- see
      // lib/webtrader-profile-mode.
      if (WITHDRAW_OPENS_CLIENT_AREA && canOpenClientArea(showDashboardButton)) {
        returnToClientArea(CLIENT_AREA_PATHS.withdrawal);
        return;
      }

      setIsWithdrawModalOpen(true);
      return;
    }

    navigate("/main/withdrawal");
  };

  const handleDeposit = () => {
    if (useDepositModal) {
      setIsDepositModalOpen(true);
      return;
    }

    if (onWebtrader) {
      if (!clientAreaUrl || !viaClientAreaLaunch) return;
      // Hands the session over on the way, or the deposit page greets them
      // with a login form -- the client area is a separate origin.
      returnToClientArea("/main/deposit");
      return;
    }

    navigate("/main/deposit");
  };
  const selectedAccountIndex = useUserStore(
    (state) => state.selectedAccountIndex,
  );
  const [isPlansModalOpen, setIsPlansModalOpen] = useState(false);
  const enabledAiTrade = useSiteSettingsStore(
    (state) => state.settings?.enabled_ai_trade === true,
  );
  const aiTrade = useUserStore((state) => state.user?.ai_trade);
  const setUser = useUserStore((state) => state.setUser);
  const token = useUserStore((state) => state.token);

  const handleAiTradeToggle = async () => {
    try {
      const response = await axiosInstance.post("/user/ai-trade/toggle");

      if (
        response.data?.status === "success" &&
        response.data?.data &&
        user &&
        token
      ) {
        const updatedUser = {
          ...user,
          ai_trade: response.data.data.ai_trade,
        };
        setUser(updatedUser, token);
        toast.success(
          `AI Trading ${response.data.data.ai_trade ? "enabled" : "disabled"} successfully`,
        );
      }
    } catch (err: unknown) {
      const axiosErr = err as AxiosError<{ message: string }>;
      const errorMessage =
        axiosErr?.response?.data?.message || "Failed to toggle AI Trading";
      toast.error(errorMessage);
    }
  };

  // const setSelectedAccountIndex = useUserStore(
  //   (state) => state.setSelectedAccountIndex
  // );
  // Check if scroll buttons should be shown
  useEffect(() => {
    const checkScrollable = () => {
      if (tabsListRef.current) {
        const { scrollWidth, clientWidth } = tabsListRef.current;
        setShowScrollButtons(scrollWidth > clientWidth);
      }
    };

    checkScrollable();
    window.addEventListener("resize", checkScrollable);

    return () => {
      window.removeEventListener("resize", checkScrollable);
    };
  }, [activePairs]);

  // Scroll active tab into view when it changes
  useEffect(() => {
    if (activeTabRef.current && tabsListRef.current) {
      const tabElement = activeTabRef.current;
      const container = tabsListRef.current;

      // Calculate position to scroll to
      const tabLeft = tabElement.offsetLeft;
      const tabRight = tabLeft + tabElement.offsetWidth;
      const containerLeft = container.scrollLeft;
      const containerRight = containerLeft + container.offsetWidth;

      // If tab is not fully visible, scroll to make it visible
      if (tabLeft < containerLeft) {
        // Tab is to the left of the visible area
        container.scrollTo({ left: tabLeft - 10, behavior: "smooth" });
      } else if (tabRight > containerRight) {
        // Tab is to the right of the visible area
        container.scrollTo({
          left: tabRight - container.offsetWidth + 10,
          behavior: "smooth",
        });
      }
    }
  }, [activePair]);

  const scrollTabs = (direction: "left" | "right") => {
    if (tabsListRef.current) {
      const scrollAmount = direction === "left" ? -200 : 200;
      tabsListRef.current.scrollBy({ left: scrollAmount, behavior: "smooth" });
    }
  };

  const findAssetByPair = (pair: string) =>
    useAssetStore
      .getState()
      .assets.find((a) => a.sy === pair || a.symbol_display === pair);

  const handlePairClick = (pair: string) => {
    setActivePair(pair);

    // Find and set the corresponding asset
    const asset = findAssetByPair(pair);
    if (asset) {
      setActiveAsset(asset);
    }
  };

  const getAssetCategory = (pair: string) => {
    // Find and set the corresponding asset
    const asset = findAssetByPair(pair);
    if (asset) {
      return asset.type;
    }
    return "forex";
  };

  const headerStyle = {
    backgroundColor: user?.account_type?.color || "gray",
  };

  return (
    <>
      <header className=" flex items-center justify-between h-12 md:h-20 px-6 border-b border-secondary border-2 bg-background">
        {/* left side */}
        <div className="flex items-center gap-6 h-full">
          <Logo />


          {/* Mobile menu */}
          {isMobile && (
            <div className="flex items-center">
              <Sheet>
                <SheetTrigger asChild>
                  <Button size="icon" className="mr-2 w-6 h-6">
                    <Menu className="h-6 w-6" />
                  </Button>
                </SheetTrigger>
                <SheetContent
                  side="left"
                  className="w-[280px] sm:w-[350px] p-0"
                >
                  <div className="flex flex-col h-full">
                    <div className="p-4 border-b border-border">
                      <h2 className="text-lg font-bold">Menu</h2>
                    </div>

                    {/* Account info in mobile menu */}
                    <div className="bg-muted/30 rounded-lg p-3 mx-3 mb-4">
                      <div className="flex items-center gap-2 mb-2">
                        <Dialog>
                          <DialogTrigger asChild>
                            <Avatar className="h-8 w-8 border border-border cursor-pointer">
                              <AvatarImage src={user?.avatar} alt="avatar" />
                              <AvatarFallback>
                                {user
                                  ? `${user.first_name[0]}${user.last_name[0]}`.toUpperCase()
                                  : ""}
                              </AvatarFallback>
                            </Avatar>
                          </DialogTrigger>
                          <DialogContent className="w-full h-full flex items-center justify-center">
                            <img
                              src={user?.avatar}
                              className="w-full h-full object-cover p-4"
                              alt="Profile"
                            />
                          </DialogContent>
                        </Dialog>
                        <div>
                          <AccountIdentity className="!items-start mb-1" />
                          <div className="text-xs text-muted-foreground flex items-center gap-1.5">
                            {user?.account_type?.title || "STANDARD"}
                            {activeTradingAccount?.account_id && (
                              <span className="text-[10px] font-semibold border border-border rounded px-1 py-0.5">
                                #{activeTradingAccount.account_id}
                              </span>
                            )}
                          </div>
                          <div className="text-green-500 font-bold">
                            {formatCurrency(user?.balance || 0)}
                          </div>
                        </div>
                      </div>
                      <div className="space-y-2">
                        {user?.accounts.map((account, index) => (
                          <div
                            key={index}
                            className={`flex justify-between items-center p-2 rounded text-xs gap-1 ${
                              selectedAccountIndex === index ? "" : ""
                            }`}
                            // onClick={() => setSelectedAccountIndex(index)}
                          >
                            <span>{account.title}</span>
                            <span>{formatCurrency(account.balance)}</span>
                          </div>
                        ))}
                      </div>
                    </div>

                    {/* Side panels in mobile menu */}
                    <div className="overflow-y-auto flex-1">
                      <div className="px-3 py-2">
                        <h3 className="text-sm font-medium mb-2">Panels</h3>
                        <div className="space-y-1">
                          <SheetTrigger className="w-full">
                            <Button
                              variant="outline"
                              className="w-full justify-start gap-2 text-sm hover:bg-slapte-700"
                              onClick={() => toggleView("market-watch")}
                            >
                              <LineChart className="h-4 w-4" />
                              <span>Market Watch</span>
                            </Button>
                          </SheetTrigger>
                          <SheetTrigger className="w-full">
                            <Button
                              variant="outline"
                              className="w-full justify-start gap-2 text-sm hover:bg-slapte-700"
                              onClick={() => toggleView("active-orders")}
                            >
                              <BarChart3 className="h-4 w-4" />
                              <span>Active Orders</span>
                            </Button>
                          </SheetTrigger>
                          <SheetTrigger className="w-full">
                            <Button
                              variant="outline"
                              className="w-full justify-start gap-2 text-sm hover:bg-slapte-700"
                              onClick={() => toggleView("trading-history")}
                            >
                              <Clock className="h-4 w-4" />
                              <span>Trading History</span>
                            </Button>
                          </SheetTrigger>
                          <SheetTrigger className="w-full">
                            <Button
                              variant="outline"
                              className="w-full justify-start gap-2 text-sm hover:bg-slapte-700"
                              onClick={() => toggleView("calendar")}
                            >
                              <Calendar className="h-4 w-4" />
                              <span>Calendar</span>
                            </Button>
                          </SheetTrigger>
                          <SheetTrigger className="w-full">
                            <Button
                              variant="outline"
                              className="w-full justify-start gap-2 text-sm hover:bg-slapte-700"
                              onClick={() => toggleView("market-news")}
                            >
                              <Newspaper className="h-4 w-4" />
                              <span>Market News</span>
                            </Button>
                          </SheetTrigger>
                        </div>
                      </div>

                      <div className="border-t border-border mt-2 px-3 py-2">
                        <div className="space-y-3">
                          {enabledAiTrade && (
                            <div
                              className={`flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 border ${
                                aiTrade
                                  ? "animate-pulse-green border-trading-green/40 shadow-sm"
                                  : "bg-muted/30 border-muted-foreground/20"
                              }`}
                            >
                              <div className="flex items-center gap-3">
                                <div
                                  className={`p-2 rounded-lg ${aiTrade ? "bg-trading-green/20 text-trading-green" : "bg-muted text-muted-foreground"}`}
                                >
                                  <Bot
                                    className={`h-6 w-6 ${aiTrade ? "animate-pulse" : ""}`}
                                  />
                                </div>
                                <div className="flex flex-col">
                                  <Label
                                    htmlFor="mobile-ai-trading-toggle"
                                    className={`text-sm font-bold cursor-pointer ${aiTrade ? "text-trading-green" : "text-foreground"}`}
                                  >
                                    AI TRADING
                                  </Label>
                                  <span
                                    className={`text-xs ${aiTrade ? "text-trading-green/80 font-medium" : "text-muted-foreground"}`}
                                  >
                                    {aiTrade
                                      ? "Bot is currently active"
                                      : "Automated trading is off"}
                                  </span>
                                </div>
                              </div>
                              <Switch
                                id="mobile-ai-trading-toggle"
                                checked={Boolean(aiTrade)}
                                onCheckedChange={handleAiTradeToggle}
                                className="scale-110 data-[state=unchecked]:bg-black data-[state=checked]:bg-trading-green border border-muted-foreground/30 shadow-inner"
                              />
                            </div>
                          )}
                          <AutoTraderModal />
                          {canUseDeposit && <Button
                            className="w-full justify-start gap-2"
                            onClick={handleDeposit}
                          >
                            <Wallet className="h-4 w-4" />
                            <span>Deposit</span>
                          </Button>}
                          <Button
                            variant="outline"
                            className="w-full justify-start gap-2"
                            onClick={handleWithdraw}
                          >
                            <ArrowUpFromLine className="h-4 w-4" />
                            <span>Withdraw</span>
                          </Button>
                        </div>
                      </div>

                      <DepositFunds
                        isOpen={isDepositModalOpen}
                        onClose={() => setIsDepositModalOpen(false)}
                      />

                      {/* Plan Type */}
                      {enablePlan && (
                        <div className="flex mt-2 px-3 py-2 w-full">
                          {user?.account_type ? (
                            <Button
                              style={headerStyle}
                              onClick={() => setIsPlansModalOpen(true)}
                              className="text-white cursor-pointer font-medium rounded-md shadow-md transition-all duration-300 group"
                            >
                              <div className="flex items-center gap-1">
                                <div className="flex items-center justify-center w-5 h-5 rounded-full bg-white/20 backdrop-blur-sm">
                                  <img
                                    src={user?.account_type?.icon}
                                    alt={`${user?.account_type?.title} icon`}
                                    className="w-4 h-4 object-contain"
                                  />
                                </div>
                                <div className="flex flex-col items-start">
                                  <span className="text-xs text-white font-bold">
                                    {user?.account_type?.title}
                                  </span>
                                </div>
                              </div>
                            </Button>
                          ) : null}

                          <AccountPlansModal
                            open={isPlansModalOpen}
                            onOpenChange={setIsPlansModalOpen}
                          />
                        </div>
                      )}

                      <div className="border-t border-border mt-2 px-3 py-2">
                        <h3 className="text-sm font-medium mb-2">
                          Currency Pairs
                        </h3>
                        <div className="space-y-2">
                          {activePairs.map((pair, index) => (
                            <>
                              <div
                                key={index}
                                className={`flex items-center justify-between p-2 rounded-md ${
                                  activePair === pair
                                    ? "bg-primary/10"
                                    : "hover:bg-muted/50"
                                }`}
                                onClick={() => handlePairClick(pair)}
                              >
                                <div className="flex items-center gap-2">
                                  <CurrencyFlag pair={pair} />
                                  <span>{pair}</span>
                                </div>
                                {activePairs.length > 1 && (
                                  <button
                                    className="rounded-full hover:bg-muted p-0.5"
                                    onClick={(e) => {
                                      e.stopPropagation();
                                      removeCurrencyPair(pair);
                                    }}
                                  >
                                    <X className="h-4 w-4" />
                                  </button>
                                )}
                              </div>
                            </>
                          ))}
                          <Button
                            variant="outline"
                            size="sm"
                            className="w-full mt-2"
                            onClick={() => setIsMarketWatchOpen(true)}
                          >
                            <Plus className="h-4 w-4 mr-2" /> Add Pair
                          </Button>
                        </div>
                      </div>
                    </div>

                    {/* Logout button */}
                    <div className="border-t border-border p-3">
                      <Button
                        variant="ghost"
                        className="w-full justify-start text-red-500"
                        onClick={() => {
                          clearAuthenticatedSession();
                          window.location.href = "/";
                        }}
                      >
                        <LogOut className="h-4 w-4 mr-2" />
                        <span>Logout</span>
                      </Button>
                    </div>
                  </div>
                </SheetContent>
              </Sheet>
              {/* Mobile search button */}
              {/* <Button
                variant="ghost"
                size="icon"
                className="mr-2"
                onClick={() => {
                  setIsSearchOpen(!isSearchOpen);
                  console.log("isSearch open:", isSearchOpen);
                }}
              >
                <Search className="h-5 w-5" />
              </Button> */}{" "}
              {/* Mobile active pair display */}
              <div
                className="flex items-center gap-1 bg-muted/50 px-2 py-1 rounded-md"
                onClick={() => setIsSearchOpen(!isSearchOpen)}
              >
                <span className="text-sm font-medium">
                  {activeAsset?.symbol_display || activePair}
                </span>
                <ChevronDown className="h-4 w-4 ml-1" />
              </div>
            </div>
          )}

          {/* {isMobile && (
            <div className="w-full border-t border-secondary mt-2">
              <AssetListingTabs
                activePairs={activePairs}
                activePair={activePair}
                handlePairClick={handlePairClick}
                removeCurrencyPair={removeCurrencyPair}
                isMobile={true}
                tabsListRef={tabsListRef}
                activeTabRef={activeTabRef}
                setIsMarketWatchOpen={setIsMarketWatchOpen}
              />
            </div>
          )} */}

          {/* Desktop asset listing tabs */}
          {!isMobile && (
            <AssetListingTabs
              activePairs={activePairs}
              activePair={activePair}
              handlePairClick={handlePairClick}
              removeCurrencyPair={removeCurrencyPair}
              isMobile={false}
              showScrollButtons={showScrollButtons}
              scrollTabs={scrollTabs}
              tabsListRef={tabsListRef}
              activeTabRef={activeTabRef}
              getAssetCategory={getAssetCategory}
              setIsMarketWatchOpen={setIsMarketWatchOpen}
            />
          )}
        </div>{" "}
        {/* Market Watch Modal */}
        <Dialog open={isMarketWatchOpen} onOpenChange={setIsMarketWatchOpen}>
          <DialogContent className="sm:max-w-[425px] h-[90vh] p-0 gap-0">
            <div className="overflow-y-auto h-full">
              <MarketWatchPanel
                addCurrencyPair={(pair) => {
                  handlePairClick(pair);
                  setIsMarketWatchOpen(false);
                }}
              />
            </div>
          </DialogContent>
        </Dialog>
        {/* Mobile search dropdown */}
        {isMobile && isSearchOpen && (
          <div className="absolute top-16 left-0 right-0 bg-background border-b border-border p-3 z-50">
            {/* <div className="relative">
              <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
              <input
                className="w-full bg-muted/50 border border-border rounded-md py-2 pl-10 pr-3"
                placeholder="Search currency pairs..."
              />
            </div> */}
            <div className="mt-2 max-h-[300px] overflow-y-auto">
              {activePairs.map((pair) => (
                <div
                  key={pair}
                  className={`flex items-center justify-between p-2 rounded-md ${
                    activePair === pair ? "bg-primary/10" : "hover:bg-muted/50"
                  }`}
                  onClick={() => {
                    handlePairClick(pair);
                    setIsSearchOpen(false);
                  }}
                >
                  <div className="flex items-center gap-2">
                    <CurrencyFlag pair={pair} />
                    <span>{pair}</span>
                  </div>
                  {activePairs.length > 1 && (
                    <button
                      className="rounded-full hover:bg-muted p-0.5"
                      onClick={(e) => {
                        e.stopPropagation();
                        removeCurrencyPair(pair);
                      }}
                    >
                      <X className="h-4 w-4" />
                    </button>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}
        {/* right side */}
        <div className="flex items-center gap-6">
          {!isMobile && (
            <>
              {enabledAiTrade && (
                <div
                  className={`flex items-center gap-2 px-3 py-1.5 rounded-full transition-all duration-300 border ${
                    aiTrade
                      ? "animate-pulse-green border-trading-green/40 shadow-sm"
                      : "bg-muted/50 border-muted-foreground/20"
                  }`}
                >
                  <div className="flex items-center gap-2">
                    <div
                      className={`relative ${aiTrade ? "text-trading-green" : "text-muted-foreground"}`}
                    >
                      <Bot
                        className={`h-5 w-5 ${aiTrade ? "animate-pulse" : ""}`}
                      />
                      {aiTrade && (
                        <span className="absolute -top-0.5 -right-0.5 flex h-2 w-2">
                          <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-trading-green opacity-75"></span>
                          <span className="relative inline-flex rounded-full h-2 w-2 bg-trading-green"></span>
                        </span>
                      )}
                    </div>
                    <div className="flex flex-col">
                      <Label
                        htmlFor="ai-trading-toggle"
                        className={`text-[10px] font-bold leading-none cursor-pointer uppercase tracking-wider ${
                          aiTrade
                            ? "text-trading-green"
                            : "text-muted-foreground"
                        }`}
                      >
                        AI Trader
                      </Label>
                      <span
                        className={`text-[9px] font-medium leading-none mt-0.5 ${aiTrade ? "text-trading-green/80" : "text-muted-foreground/60"}`}
                      >
                        {aiTrade ? "ACTIVE" : "INACTIVE"}
                      </span>
                    </div>
                  </div>
                  <Switch
                    id="ai-trading-toggle"
                    checked={Boolean(aiTrade)}
                    onCheckedChange={handleAiTradeToggle}
                    className="scale-110 origin-right data-[state=unchecked]:bg-black data-[state=checked]:bg-trading-green border border-muted-foreground/30 shadow-inner"
                  />
                </div>
              )}
              <AutoTraderModal />

              {canUseDeposit && <Button
                variant="outline"
                className="flex-1 h-full gap-3 text-primary border-trading-accent hover:bg-trading-green/10"
                onClick={handleDeposit}
              >
                <Wallet className="h-5 w-5" />
                <span className="text-base hidden lg:flex">Deposit</span>
              </Button>}

            </>
          )}

          <div className="flex items-center gap-2">
            {isMobile && <AccountIdentity compact />}
            <FunnelLangSwitch labelClassName="text-foreground" />
            {enablePlan && (
              <img
                src={
                  user?.account_type?.image ||
                  "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744370355/main_plate_exi8jv.png"
                }
                alt={`${user?.account_type?.title || "Basic"} Badge`}
                className="w-10 h-10 md:w-12 md:h-12"
              />
            )}
            {/* Which account is being traded, and the way to change it. */}
            {!isMobile && <AccountSwitcher />}
            {/* modal={false} because this menu opens the Profile
                dialog. Both a modal menu and a modal dialog park
                `pointer-events: none` on <body> and restore what they found on
                close -- the dialog opens while the menu is still up, saves the
                menu's "none" as the value to put back, and the whole terminal
                is left unclickable after the dialog closes. Verified: with the
                menu modal, body kept `pointer-events: none` with nothing open. */}
            <DropdownMenu modal={false}>
              <DropdownMenuTrigger asChild>
                {/* The avatar alone. The name briefly lived in here too, but it
                    now reads under the live badge and the account number as
                    part of the switcher, and one subject with two separate
                    menus is worse than either arrangement. */}
                <Button
                  variant="ghost"
                  size="icon"
                  aria-label="Account menu"
                  className="rounded-full p-0 h-10 w-10"
                >
                  <Avatar className="w-8 h-8 md:w-10 md:h-10">
                    <AvatarImage src={user?.avatar} alt="avatar" />
                  </Avatar>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                {onWebtrader ? (
                  // Account Details/Settings live in the client-area app, which
                  // this standalone origin never loads -- offer a real
                  // cross-origin way back instead of dead routes. The profile
                  // itself is handled here rather than sent away: a client who
                  // logged straight into the terminal, or who was launched into
                  // it by an agent, has no client area to be sent to.
                  <>
                    <DropdownMenuItem
                      // Leaves the terminal for the client area's Settings page.
                      // Falls back to the dialog when this session has no client
                      // area to go to -- see lib/webtrader-profile-mode.
                      onSelect={() => {
                        if (canOpenClientArea(showDashboardButton)) {
                          returnToClientArea(CLIENT_AREA_PATHS.profile);
                          return;
                        }
                        setIsProfileOpen(true);
                      }}
                    >
                      Profile
                    </DropdownMenuItem>
                    {/* Hidden for a direct webtrader login (no client-area
                        session to return to) -- same gate as the header button. */}
                    {showDashboardButton && (
                      <DropdownMenuItem
                        onClick={() => {
                          returnToClientArea();
                        }}
                      >
                        Client Area
                      </DropdownMenuItem>
                    )}
                  </>
                ) : (
                  <>
                    <Link to="/main/dashboard">
                      <DropdownMenuItem>Profile</DropdownMenuItem>
                    </Link>
                    <Link to="/main/accounts">
                      <DropdownMenuItem>Account Details</DropdownMenuItem>
                    </Link>
                    <Link to="/main/settings">
                      <DropdownMenuItem>Settings</DropdownMenuItem>
                    </Link>
                  </>
                )}
                <DropdownMenuItem
                  className="text-red-500"
                  onClick={() => {
                    clearAuthenticatedSession();
                    window.location.href = "/";
                  }}
                >
                  Logout
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </header>
      {/* Mobile asset listing tabs */}
      {isMobile && (
        <div className="w-full">
          <AssetListingTabs
            activePairs={activePairs}
            activePair={activePair}
            handlePairClick={handlePairClick}
            removeCurrencyPair={removeCurrencyPair}
            isMobile={true}
            tabsListRef={tabsListRef}
            activeTabRef={activeTabRef}
            getAssetCategory={getAssetCategory}
            setIsMarketWatchOpen={setIsMarketWatchOpen}
          />
        </div>
      )}

      {/* Deposit Funds Modal */}
      <DepositFunds
        isOpen={isDepositModalOpen}
        onClose={() => setIsDepositModalOpen(false)}
      />

      <WithdrawFunds
        open={isWithdrawModalOpen}
        onOpenChange={setIsWithdrawModalOpen}
      />

      <PersonalInformationDialog
        open={isProfileOpen}
        onOpenChange={setIsProfileOpen}
      />

    </>
  );
}

export function CurrencyFlag({ pair }: { pair?: string }) {
  // Handle undefined or invalid pair format
  if (!pair || !pair.includes("/")) {
    return (
      <div className="h-5 w-5 rounded-full bg-muted flex items-center justify-center text-xs text-muted-foreground">
        {pair ? pair.charAt(0) : "?"}
      </div>
    );
  }

  // This would be replaced with actual flag images in a real implementation
  const firstCurrency = pair.split("/")[0];
  const color =
    firstCurrency === "AUD"
      ? "bg-blue-500"
      : firstCurrency === "EUR"
        ? "bg-yellow-500"
        : firstCurrency === "USD"
          ? "bg-green-500"
          : firstCurrency === "GBP"
            ? "bg-purple-500"
            : firstCurrency === "CAD"
              ? "bg-red-500"
              : "bg-purple-500";

  return (
    <div
      className={`h-5 w-5 rounded-full ${color} flex items-center justify-center text-xs text-white`}
    >
      {firstCurrency.charAt(0)}
    </div>
  );
}

function ChevronLeft(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      {...props}
    >
      <polyline points="15 18 9 12 15 6" />
    </svg>
  );
}

function ChevronRight(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      {...props}
    >
      <polyline points="9 18 15 12 9 6" />
    </svg>
  );
}
