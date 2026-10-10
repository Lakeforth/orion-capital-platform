import React from "react";
import { Wallet, ArrowDownToLine, TrendingUp } from "lucide-react";

interface PortfolioBalanceCardProps {
  tradingAccounts: number;
  bonus: number;
  credit: number;
  spotWallet: number;
  formatCurrency: (value: number) => string;
  onDeposit: () => void;
}

// Credit is now the credit wallet's real signed balance, same as
// Bonus/Spot Wallet: an admin crediting it (positive balance) adds to net
// worth, a negative balance (drawn against the credit line elsewhere)
// naturally subtracts via signed addition below. Trading Accounts already
// includes any historically-drawn credit/bonus folded into account balances
// by the earlier wallet-component backfill, so it needs no further
// adjustment here.
const PortfolioBalanceCard: React.FC<PortfolioBalanceCardProps> = ({
  tradingAccounts,
  bonus,
  credit,
  spotWallet,
  formatCurrency,
  onDeposit,
}) => {
  const displayTotal = tradingAccounts + bonus + spotWallet + credit;

  // For the visual chart, we use positive values to ensure segments are rendered correctly
  const chartTradingAccounts = Math.max(0, tradingAccounts);
  const chartBonus = Math.max(0, bonus);
  const chartSpotWallet = Math.max(0, spotWallet);
  const chartCredit = Math.max(0, credit);
  const chartTotal = chartTradingAccounts + chartBonus + chartSpotWallet + chartCredit || 1;

  const tradingAccountsPercent = (chartTradingAccounts / chartTotal) * 100;
  const bonusPercent = (chartBonus / chartTotal) * 100;
  const spotWalletPercent = (chartSpotWallet / chartTotal) * 100;
  const creditPercent = (chartCredit / chartTotal) * 100;

  const circumference = 2 * Math.PI * 40;

  return (
    <div className="bg-white rounded-xl p-5 shadow-xl border border-gray-100 flex flex-col justify-between ">
      <div>
        <div className="flex justify-between items-start mb-1">
          <h3 className="text-black font-bold text-sm uppercase tracking-wider">
            Total Portfolio Balance
          </h3>
          <div className="p-1 bg-gray-50 rounded-md text-gray-400">
            <Wallet className="h-3 w-3" />
          </div>
        </div>

        <div className="mb-4">
          <h2 className="text-3xl font-bold text-[#00234B]">
            {formatCurrency(displayTotal)}
          </h2>
          {/* Hardcoded, and has always been: there is no day-change figure on
              this payload. Grey rather than green while that is true -- a
              permanent green "+0% today" is the card claiming a gain it has
              not been told about. */}
          <p className="text-gray-400 text-sm font-bold mt-0.5 flex items-center gap-1">
            <TrendingUp className="h-3 w-3" />
            +0% ({formatCurrency(0)}) today
          </p>
        </div>

        {/* Donut Chart */}
        <div className="h-40 w-full relative flex items-center justify-center mb-4">
          <div className="absolute inset-0 flex flex-col items-center justify-center z-10">
            <span className="text-lg font-bold text-[#00234B] leading-none">
              {formatCurrency(displayTotal).replace(/\.00$/, "")}
            </span>
            <span className="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-wider">
              Total
            </span>
          </div>
          <svg className="w-full h-full -rotate-90" viewBox="0 0 100 100">
            <circle
              cx="50"
              cy="50"
              r="40"
              fill="transparent"
              stroke="#F3F4F6"
              strokeWidth="12"
            />
            {/* Trading Accounts Segment */}
            {tradingAccountsPercent > 0 && (
              <circle
                cx="50"
                cy="50"
                r="40"
                fill="transparent"
                stroke="#00234B"
                strokeWidth="12"
                strokeDasharray={`${(tradingAccountsPercent / 100) * circumference} ${circumference}`}
                strokeLinecap="butt"
                transform="rotate(0 50 50)"
              />
            )}
            {/* Bonus Segment */}
            {bonusPercent > 0 && (
              <circle
                cx="50"
                cy="50"
                r="40"
                fill="transparent"
                stroke="#7C3AED"
                strokeWidth="12"
                strokeDasharray={`${(bonusPercent / 100) * circumference} ${circumference}`}
                strokeLinecap="butt"
                transform={`rotate(${(tradingAccountsPercent / 100) * 360} 50 50)`}
              />
            )}
            {/* Spot Wallet Segment */}
            {spotWalletPercent > 0 && (
              <circle
                cx="50"
                cy="50"
                r="40"
                fill="transparent"
                stroke="#00B3FE"
                strokeWidth="12"
                strokeDasharray={`${(spotWalletPercent / 100) * circumference} ${circumference}`}
                strokeLinecap="butt"
                transform={`rotate(${((tradingAccountsPercent + bonusPercent) / 100) * 360} 50 50)`}
              />
            )}
            {/* Credit Segment */}
            {creditPercent > 0 && (
              <circle
                cx="50"
                cy="50"
                r="40"
                fill="transparent"
                stroke="#F43F5E"
                strokeWidth="12"
                strokeDasharray={`${(creditPercent / 100) * circumference} ${circumference}`}
                strokeLinecap="butt"
                transform={`rotate(${((tradingAccountsPercent + bonusPercent + spotWalletPercent) / 100) * 360} 50 50)`}
              />
            )}
            {/* Fallback if all 0 */}
            {chartTotal <= 1 &&
              chartTradingAccounts === 0 &&
              chartBonus === 0 &&
              chartSpotWallet === 0 &&
              chartCredit === 0 && (
                <circle
                  cx="50"
                  cy="50"
                  r="40"
                  fill="transparent"
                  stroke="#00234B"
                  strokeWidth="12"
                  strokeDasharray={`${circumference} ${circumference}`}
                  strokeLinecap="butt"
                />
              )}
          </svg>
        </div>

        <div className="space-y-2">
          <div className="flex justify-between items-center">
            <div className="flex items-center gap-3">
              <div className="h-3 w-3 rounded-full bg-[#00234B]" />
              <span className="text-[#00234B] font-semibold text-sm">
                Trading Accounts
              </span>
            </div>
            <div className="text-right">
              <p className="text-[#00234B] font-bold text-sm">
                {formatCurrency(tradingAccounts)}
              </p>
              <p className="text-[10px] text-gray-400 font-bold">
                {tradingAccountsPercent.toFixed(1)}%
              </p>
            </div>
          </div>
          <div className="flex justify-between items-center">
            <div className="flex items-center gap-3">
              <div className="h-3 w-3 rounded-full bg-[#7C3AED]" />
              <span className="text-[#00234B] font-semibold text-sm">
                Bonus
              </span>
            </div>
            <div className="text-right">
              <p className="text-[#00234B] font-bold text-sm">
                {formatCurrency(bonus)}
              </p>
              <p className="text-[10px] text-gray-400 font-bold">
                {bonusPercent.toFixed(1)}%
              </p>
            </div>
          </div>
          <div className="flex justify-between items-center">
            <div className="flex items-center gap-3">
              <div className="h-3 w-3 rounded-full bg-[#00B3FE]" />
              <span className="text-[#00234B] font-semibold text-sm">
                Spot Wallet
              </span>
            </div>
            <div className="text-right">
              <p className="text-[#00234B] font-bold text-sm">
                {formatCurrency(spotWallet)}
              </p>
              <p className="text-[10px] text-gray-400 font-bold">
                {spotWalletPercent.toFixed(1)}%
              </p>
            </div>
          </div>
          <div className="flex justify-between items-center">
            <div className="flex items-center gap-3">
              <div className="h-3 w-3 rounded-full bg-[#F43F5E]" />
              <span className="text-[#00234B] font-semibold text-sm">
                Credit
              </span>
            </div>
            <div className="text-right">
              <p className="text-[#F43F5E] font-bold text-sm">
                {formatCurrency(credit)}
              </p>
              <p className="text-[10px] text-gray-400 font-bold">
                {creditPercent.toFixed(1)}%
              </p>
            </div>
          </div>
        </div>
      </div>

      <button
        onClick={onDeposit}
        className="w-full bg-black hover:bg-gray-900 text-white py-3.5 rounded-xl mt-4 font-bold flex items-center justify-center gap-2 transition-all shadow-lg text-sm"
      >
        <ArrowDownToLine className="h-5 w-5" />
        Deposit
      </button>
    </div>
  );
};

export default PortfolioBalanceCard;
