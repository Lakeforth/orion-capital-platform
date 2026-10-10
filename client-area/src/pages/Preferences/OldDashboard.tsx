import { Check, Wallet, Coins, Building2, PiggyBank } from "lucide-react";
import { SuccessRateCard } from "@/components/dashboard/SuccessRateCard";
import { TradingResultsChart } from "@/components/dashboard/TradingResultsChart";
import { StatCard } from "@/components/dashboard/StatCard";
import { TopStoriesWidget } from "@/components/dashboard/TopStoriesWidget";
import { useCurrency } from "@/hooks/useCurrency";
import useUserStore from "@/store/userStore";
import { Button } from "@/components/ui/button";
import useSiteSettingsStore from "@/store/siteSettingStore.ts";
import { openWebtrader } from "@/lib/trade-room-link";

export default function OldDashboard() {
  const user = useUserStore((state) => state.user);
  const { formatCurrency } = useCurrency();
  const balance = user?.balance || 0;
  const tradesSummary = user?.trades_summary || {
    total_pnl: 0,
    total_wins: 0,
    total_losses: 0,
    trades_count: 0,
    total_deposit: 0,
    win_rate: 0,
  };

  const isVerified = user?.verification_status === "approved";
  const settings = useSiteSettingsStore((state) => state.settings);

  return (
    <div className="p-6 bg-background text-foreground min-h-screen">
      {/* Welcome Section */}
      <div
        className="mb-6 rounded-xl overflow-hidden"
        style={{
          background: "linear-gradient(to right, #1E293B, #0F172A)",
          borderLeft: "1px solid var(--primary)",
          boxShadow: "0 4px 20px rgba(79, 70, 229, 0.15)",
        }}
      >
        <div className="p-8 flex flex-col px-10 md:flex-row justify-between items-center">
          <div className="md:max-w-md gap-5 sm:text-left text-center">
            <h2 className="sm:text-3xl font-bold text-white mb-2">
              Welcome{" "}
              <span className="text-primary">
                {user?.first_name} {user?.last_name}
              </span>
            </h2>
            <h3 className="sm:text-xl text-gray-300 mb-4">to {settings?.name} ®</h3>

            <div className="flex items-center gap-2 mt-2">
              <span className="text-muted-foreground">Account status:</span>
              {isVerified ? (
                <div className="flex items-center gap-1 text-green-400">
                  Verified <Check className="h-4 w-4" />
                </div>
              ) : (
                <div className="flex items-center gap-1 text-red-400">UnVerified</div>
              )}
            </div>
          </div>

          <div className="md:w-80 mt-6 md:mt-0">
            <div
              className="rounded-lg p-6 flex flex-col items-center relative"
              style={{
                background:
                  "linear-gradient(to bottom, rgba(30, 41, 59, 0.5), rgba(15, 23, 42, 0.7))",
                border: "1px solid rgba(255, 255, 255, 0.05)",
                boxShadow: "0 0 20px rgba(16, 185, 129, 0.2)",
              }}
            >
              {/* Plan icon only when plans are enabled */}
              {settings?.enable_plan && (
                <div className="absolute top-4 right-4 w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center overflow-hidden">
                  <img
                    src={
                      user?.account_type?.image ||
                      "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744370355/main_plate_exi8jv.png"
                    }
                    alt="Account Badge"
                    className="w-full h-full object-cover"
                  />
                </div>
              )}

              <h3 className="text-lg font-semibold text-white self-start mb-4">Account</h3>

              <div className="w-full space-y-4">
                <div className="flex justify-between items-center">
                  <span className="text-muted-foreground">Balance</span>
                  <span className="text-white font-medium">{formatCurrency(balance)}</span>
                </div>

                

                <div className="flex justify-between items-center">
                  <span className="text-muted-foreground">{user?.custom_wallet}</span>
                  <span className="text-green-500 font-medium">
                    {formatCurrency(user?.credit_balance || 0)}
                  </span>
                </div>

                <Button
                  className="w-full bg-green-500 hover:bg-green-600 text-white mt-4"
                  onClick={() => openWebtrader()}
                >
                  Trade Now
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Account Summary Section */}
      <div
        className="mb-6 rounded-xl overflow-hidden"
        style={{
          background: "linear-gradient(to right, #1E293B, #0F172A)",
          borderLeft: "1px solid var(--color-trading-green, #10B981)",
          boxShadow: "0 4px 20px rgba(16, 185, 129, 0.1)",
        }}
      >
        <div className="p-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-xl font-bold text-white">Account Summary</h2>
          </div>

          <div className="flex flex-col lg:flex-row gap-8">
            <div className="lg:w-2/3 grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
              <StatCard
                title="Total Balance"
                value={formatCurrency(balance)}
                icon={<Wallet className="h-10 w-10 text-accent" />}
              />

              <StatCard
                title="Profit and Loss"
                value={formatCurrency(tradesSummary.total_pnl)}
                icon={<Coins className="h-10 w-10 text-accent" />}
                footnote="* using current exchange rate"
              />
              <StatCard
                title="Total Deposits"
                value={formatCurrency(tradesSummary.total_deposit || 0)}
                icon={<Building2 className="h-10 w-10 text-accent" />}
                valueClassName="text-success"
              />

              <StatCard
                title="Profitable Orders"
                value={`${tradesSummary.total_wins} / ${tradesSummary.trades_count}`}
                icon={<PiggyBank className="h-12 w-12 text-accent" />}
              />
            </div>

            <div className="lg:w-1/3">
              <SuccessRateCard />
            </div>
          </div>
        </div>
      </div>

      {/* Trading Results Chart */}
      <div
        className="mb-6 rounded-xl overflow-hidden"
        style={{
          background: "linear-gradient(to right, #1E293B, #0F172A)",
          borderLeft: "1px solid var(--green-500, #10B981)",
          boxShadow: "0 4px 20px rgba(16, 185, 129, 0.1)",
        }}
      >
        <div className="p-6">
          <TradingResultsChart />
        </div>
      </div>

      {/* Top Stories Section */}
      <div
        className="mb-6 rounded-xl overflow-hidden"
        style={{
          background: "linear-gradient(to right, #1E293B, #0F172A)",
          borderLeft: "1px solid var(--pink-500, #EC4899)",
          boxShadow: "0 4px 20px rgba(236, 72, 153, 0.1)",
        }}
      >
        <div className="p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-xl font-bold text-white">Top Stories</h2>
          </div>
          <TopStoriesWidget />
        </div>
      </div>
    </div>
  );
}