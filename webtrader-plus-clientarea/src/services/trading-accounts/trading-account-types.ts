export type TradingAccountType = "live" | "practice" | "challenge";

export interface TradingAccount {
  id: string;
  account_id: number | null;
  account_type: TradingAccountType;
  base_currency: string;
  trading_server: string | null;
  // Stored plain on the backend on purpose so the client can copy/share it
  // with the trader -- same convention as everywhere else in this app.
  password: string | null;
  name: string | null;
  balance: number;
  balance_val: number;
  bonus: number;
  bonus_val: number;
  // Only meaningful once the tenant has wallet_components_enabled -- all 0
  // otherwise.
  deposit: number;
  deposit_val: number;
  credit: number;
  credit_val: number;
  profit: number;
  profit_val: number;
  pnl: number;
  margin: number;
  equity: number;
  available_balance: number;
  withdrawable_balance: number;
  is_active: boolean;
}

export interface TradingAccountsResponse {
  status: string;
  message: string;
  data: TradingAccount[];
}

export interface TradingAccountResponse {
  status: string;
  message: string;
  data: TradingAccount;
}

export interface CreateTradingAccountPayload {
  account_type?: "live" | "practice";
  name?: string;
}

export interface SpotWalletTransferResponse {
  status: string;
  message: string;
  data: {
    account: TradingAccount;
    user_balance: number;
  };
}

export interface CreditWalletTransferResponse {
  status: string;
  message: string;
  data: {
    account: TradingAccount;
    credit_wallet_available: number;
  };
}

export interface BonusWalletTransferResponse {
  status: string;
  message: string;
  data: {
    account: TradingAccount;
    bonus_wallet_balance: number;
  };
}
