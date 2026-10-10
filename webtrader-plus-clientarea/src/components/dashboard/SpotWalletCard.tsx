import React from "react";
import { Wallet } from "lucide-react";

interface SpotWalletCardProps {
  balance: number;
  formatCurrency: (value: number) => string;
  onDeposit?: () => void;
}

// Spot Wallet is the pooled balance a deposit lands in by default before a
// client chooses which trading account to fund with it (or funds an
// account directly, bypassing this). Distinct from any single trading
// account's own balance -- see PortfolioBalanceCard for that breakdown.
const SpotWalletCard: React.FC<SpotWalletCardProps> = ({
  balance,
  formatCurrency,
  onDeposit,
}) => {
  return (
    <div className="bg-white rounded-xl p-5 shadow-xl border border-gray-100 flex flex-col justify-between">
      <div>
        <div className="flex justify-between items-start mb-1">
          <h3 className="text-black font-bold text-sm uppercase tracking-wider">
            Spot Wallet
          </h3>
          <div className="p-1 bg-gray-50 rounded-md text-gray-400">
            <Wallet className="h-3 w-3" />
          </div>
        </div>
        <p className="text-[11px] text-gray-400 mb-3">
          Funds not yet assigned to a trading account.
        </p>
        <h2 className="text-2xl font-bold text-[#00234B]">
          {formatCurrency(balance)}
        </h2>
      </div>

      {onDeposit && (
        <button
          onClick={onDeposit}
          className="w-full bg-black hover:bg-gray-900 text-white py-3 rounded-xl mt-4 font-bold text-sm transition-all"
        >
          Deposit
        </button>
      )}
    </div>
  );
};

export default SpotWalletCard;
