import { Wallet, Coins, CreditCard, PiggyBank } from "lucide-react";
import { SuccessRateCard } from "@/components/dashboard/SuccessRateCard";
import { TradingResultsChart } from "@/components/dashboard/TradingResultsChart";
import { StatCard } from "@/components/dashboard/StatCard";
// import { TopStoriesWidget } from "@/components/dashboard/TopStoriesWidget";
import { useCurrency } from "@/hooks/useCurrency";
import useUserStore from "@/store/userStore";
import { usePortfolioSummaryQuery } from "@/services/portfolio/portfolio-queries";

export default function TradeAnalytics() {
  // const navigate = useNavigate();
  const user = useUserStore((state) => state.user);
  const { formatCurrency } = useCurrency();
  // Same source as the main dashboard's Total Portfolio Balance -- nets out
  // what's owed on Credit instead of treating it as owned money. user.balance
  // (from UserResource) doesn't do that, so it isn't used here anymore.
  const { data: portfolioSummary } = usePortfolioSummaryQuery();
  const balance = portfolioSummary?.main_portfolio_balance ?? 0;
  const creditDrawn = portfolioSummary?.credit_wallet_drawn ?? 0;
  const creditLimit = portfolioSummary?.credit_wallet_limit ?? 0;
  const creditAvailable = portfolioSummary?.credit_wallet_available ?? 0;
  const tradesSummary = user?.trades_summary || {
    total_pnl: 0,
    total_wins: 0,
    total_losses: 0,
    trades_count: 0,
    total_deposit: 0,
    win_rate: 0,
  };

 
  return (
    <div className="p-6 bg-background text-foreground min-h-screen">
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-[#fff]">Trade Analytics</h1>
        <p className="text-gray-500 text-sm mt-1">Comprehensive overview of your trading performance.</p>
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
                title="Credit Wallet"
                value={formatCurrency(creditDrawn)}
                icon={<CreditCard className="h-10 w-10 text-accent" />}
                valueClassName="text-destructive"
                footnote={`${formatCurrency(creditDrawn)} used of ${formatCurrency(creditLimit)} limit -- ${formatCurrency(creditAvailable)} left`}
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
      {/* <div
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
      </div> */}
    </div>
  );
}