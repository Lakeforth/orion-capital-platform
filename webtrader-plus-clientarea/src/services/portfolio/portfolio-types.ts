import type { TradingAccount } from "@/services/trading-accounts/trading-account-types";

export interface PortfolioSummary {
  main_portfolio_balance: number;
  spot_wallet_balance: number;
  total_trading_accounts_balance: number;
  bonus_wallet_balance: number;
  credit_wallet_balance: number;
  credit_wallet_drawn: number;
  credit_wallet_limit: number;
  credit_wallet_available: number;
  trading_accounts: TradingAccount[];
}

export interface PortfolioSummaryResponse {
  status: string;
  message: string;
  data: PortfolioSummary;
}
