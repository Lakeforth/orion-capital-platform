import { useState } from "react";
import { Gem, UsersRound } from "lucide-react";

import { useCurrency } from "@/hooks/useCurrency";
import useUserStore from "@/store/userStore";
import { useNavigate } from "react-router-dom";
import useSiteSettingsStore from "@/store/siteSettingStore";
import MarketOverviewWidget from "@/components/dashboard/MarketOverviewWidget";
import MarketNewsWidget from "@/components/dashboard/MarketNewsWidget";
import AccountPlansModal from "@/components/AccountPlanModal";
import PortfolioBalanceCard from "@/components/dashboard/PortfolioBalanceCard";
import { usePortfolioSummaryQuery } from "@/services/portfolio/portfolio-queries";
import useOverlayStore from "@/store/overlayStore";
import AnnouncementsDiscoveries from "@/components/dashboard/AnnouncementsDiscoveries";
import IntelligenceHub from "@/components/dashboard/IntelligenceHub";
import { AddPartnerDialog } from "@/components/AddPartnerDialog";
import { usePartnersQuery } from "@/services/partners/partner-queries";

export default function Dashboard() {
  const navigate = useNavigate();
  const [isPlansModalOpen, setIsPlansModalOpen] = useState(false);
  const [addPartnerOpen, setAddPartnerOpen] = useState(false);
  const { data: partners } = usePartnersQuery();
  const user = useUserStore((state) => state.user);
  const { formatCurrency } = useCurrency();
  const settings = useSiteSettingsStore((state) => state.settings);

  const status = user?.verification_status || "incomplete";
  // const isApproved = status === "approved";

  const getVerificationUI = () => {
    switch (status) {
      case "approved":
        return {
          text: "Your account is verified. Start exploring the markets.",
          button: null,
        };
      case "completed":
        return {
          text: "Verification in progress. We're reviewing your documents.",
          button: null,
        };
      case "rejected":
        return {
          text: "Verification rejected. Please re-submit your documents or contact support.",
          button: "Re-verify Now",
        };
      case "incomplete":
      default:
        return {
          text: "Please complete the verification process to unlock full account access.",
          button: "Start KYC Verification Now",
        };
    }
  };

  const verifUI = getVerificationUI();

  // Trading Accounts / Bonus / Credit / Spot Wallet all come straight from
  // portfolioSummary() -- Credit and Bonus are client-level wallets, not
  // attached to any specific trading account (see the wallet redesign
  // plan), so there's no per-tenant fork needed here anymore.
  // credit_wallet_balance (not credit_wallet_drawn) -- this dashboard shows
  // the credit wallet's real signed balance like a normal wallet; drawn/
  // limit/available still power the separate credit-line panel elsewhere.
  const { data: portfolioSummary } = usePortfolioSummaryQuery();
  const tradingAccountsBalance = portfolioSummary?.total_trading_accounts_balance ?? 0;
  const bonusWalletBalance = portfolioSummary?.bonus_wallet_balance ?? 0;
  const creditWalletBalance = portfolioSummary?.credit_wallet_balance ?? 0;
  const spotWalletBalance = portfolioSummary?.spot_wallet_balance ?? 0;

  const setShowDepositModal = useOverlayStore(
    (state) => state.setShowDepositModal,
  );

  return (
    <div className="bg-[#121A33] p-5 pt-10 min-h-screen font-sans">
      <div className="max-w-[1600px] mx-auto bg-[#EDF0F4] rounded-xl shadow-2xl p-6 md:p-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          {/* Left Column: Welcome & Market Overview */}
          <div className="lg:col-span-2 space-y-6">
            {/* Welcome Section */}
            <div
              className="rounded-xl overflow-hidden relative flex flex-col justify-center min-h-[300px]"
              style={{
                background: "linear-gradient(135deg, #0B1424 0%, #1A1B3A 100%)",
                boxShadow: "0 10px 30px rgba(0, 0, 0, 0.3)",
              }}
            >
              {/* Background SVG Illustration */}
              <div className="absolute inset-0 opacity-90 pointer-events-none">
                <img
                  src="/assets/SVG.png"
                  alt=""
                  className="w-full h-full object-cover"
                />
              </div>

              {/* Content Overlay */}
              <div className="relative z-10 p-8 md:p-10 flex flex-col items-start justify-center gap-6 h-full">
                <div className="space-y-4">
                  <h2 className="text-3xl md:text-4xl font-normal text-white">
                    Welcome back{" "}
                    <span className="font-bold">{user?.first_name}</span>
                    {partners && partners.length > 0 && (
                      <span className="font-bold capitalize">
                        {", "}
                        {partners
                          .map((p) => p.name.split(" ")[0].toLowerCase())
                          .join(", ")}
                      </span>
                    )}{" "}
                    to{" "}
                    <span className="font-bold">
                      {settings?.name || "LiveTrader"}
                    </span>{" "}
                    Client Portal
                  </h2>
                  <div className="flex flex-col gap-2">
                    <p className="text-gray-400 text-lg max-w-xl">
                      {verifUI.text}
                    </p>
                  </div>
                </div>

                {verifUI.button && (
                  <button
                    onClick={() => navigate("/main/verification")}
                    className="bg-[#FF7700] hover:bg-[#FF8811] text-white px-8 py-4 rounded-xl font-bold text-lg flex items-center gap-4 transition-all duration-300 shadow-lg hover:shadow-[#FF7700]/20 transform hover:-translate-y-1"
                  >
                    {verifUI.button}
                    <div className="bg-white/20 p-1 rounded">
                      <svg
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      >
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                        <line x1="12" y1="9" x2="12" y2="13" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                      </svg>
                    </div>
                  </button>
                )}
              </div>
            </div>

            {/* Announcements & Discoveries */}
            <AnnouncementsDiscoveries />

            {/* Market Overview Widget */}
            <MarketOverviewWidget height={400} />
          </div>

          {/* Right Column: Account Type & Portfolio */}
          <div className="lg:col-span-1 space-y-6">
            {/* Account Type Card */}
            <div className="bg-white rounded-xl p-6 shadow-xl border border-gray-100 h-fit">
              <span className="text-sm font-bold text-[#00234B] uppercase tracking-wider block mb-4">
                Account Type
              </span>

              <div className="flex items-center gap-4 mb-6">
                <div className="p-1 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center overflow-hidden">
                  {user?.account_type?.image ? (
                    <img
                      src={user.account_type.image}
                      alt=""
                      className="h-12 w-12 object-contain"
                    />
                  ) : (
                    <div className="p-2 text-[#00234B]">
                      <Gem className="h-8 w-8" strokeWidth={1.5} />
                    </div>
                  )}
                </div>
                <div>
                  <h3 className="text-3xl font-bold text-[#00234B] capitalize">
                    {user?.account_type?.title?.split(" ")[0]?.toLowerCase() ||
                      "classic"}
                  </h3>
                </div>
              </div>

              <button
                onClick={() => setIsPlansModalOpen(true)}
                className="w-full bg-[#FF7700] hover:bg-[#FF8811] text-white py-3.5 rounded-xl font-bold text-lg transition-all shadow-lg shadow-[#FF7700]/20 transform active:scale-[0.98] animate-subtle-pulse"
              >
                Upgrade
              </button>
              <button
                onClick={() => setAddPartnerOpen(true)}
                className="w-full mt-2 border-2 border-[#00234B]/30 text-[#00234B] py-3 rounded-xl font-extrabold text-base transition-all hover:bg-[#00234B]/5 flex items-center justify-center gap-2"
              >
                <UsersRound className="h-5 w-5" />
                {partners && partners.length > 0
                  ? "Add Partner"
                  : "Switch to Joint Account"}
              </button>
            </div>

            <style>{`
              @keyframes subtle-pulse {
                0% { box-shadow: 0 0 0 0 rgba(204, 85, 0, 0.5); }
                70% { box-shadow: 0 0 0 10px rgba(204, 85, 0, 0); }
                100% { box-shadow: 0 0 0 0 rgba(204, 85, 0, 0); }
              }
              .animate-subtle-pulse {
                animation: subtle-pulse 2s infinite;
              }
            `}</style>

            {/* Portfolio Card -- Spot Wallet is now just one of the four
                breakdown lines here, so it no longer needs its own card
                below; the Deposit button serves the same purpose the old
                SpotWalletCard's Deposit button did. */}
            <PortfolioBalanceCard
              tradingAccounts={tradingAccountsBalance}
              bonus={bonusWalletBalance}
              credit={creditWalletBalance}
              spotWallet={spotWalletBalance}
              formatCurrency={formatCurrency}
              onDeposit={() => {
                // Same fork Header.tsx's own Deposit button already uses --
                // not every tenant has the modal flow enabled.
                if (settings?.use_deposit_modal) {
                  setShowDepositModal(true);
                } else {
                  navigate("/main/deposit");
                }
              }}
            />
          </div>
        </div>

        {/* Market News Widget (Stay at bottom) */}
        <div className="mb-8 max-w-[740px]">
          <MarketNewsWidget />
        </div>

        {/* Intelligence Hub */}
        <div className="max-w-[740px]">
          <IntelligenceHub />
        </div>
      </div>

      <AccountPlansModal
        open={isPlansModalOpen}
        onOpenChange={setIsPlansModalOpen}
      />
      <AddPartnerDialog
        open={addPartnerOpen}
        onOpenChange={setAddPartnerOpen}
      />
    </div>
  );
}
