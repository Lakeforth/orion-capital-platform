import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import axiosInstance from "@/lib/axios";
import type {
  BonusWalletTransferResponse,
  CreateTradingAccountPayload,
  CreditWalletTransferResponse,
  SpotWalletTransferResponse,
  TradingAccount,
  TradingAccountResponse,
  TradingAccountsResponse,
} from "./trading-account-types";

export const TRADING_ACCOUNTS_QUERY_KEY = ["trading-accounts"] as const;
const PORTFOLIO_SUMMARY_QUERY_KEY = ["portfolio-summary"] as const;

const fetchTradingAccounts = async (): Promise<TradingAccount[]> => {
  const response = await axiosInstance.get<TradingAccountsResponse>("/trading-accounts");
  return response.data.data ?? [];
};

const createTradingAccount = async (
  payload: CreateTradingAccountPayload,
): Promise<TradingAccount> => {
  const response = await axiosInstance.post<TradingAccountResponse>("/trading-accounts", payload);
  return response.data.data;
};

export const useTradingAccountsQuery = (enabled = true) =>
  useQuery({
    queryKey: TRADING_ACCOUNTS_QUERY_KEY,
    queryFn: fetchTradingAccounts,
    enabled,
  });

export const useCreateTradingAccountMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: createTradingAccount,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TRADING_ACCOUNTS_QUERY_KEY });
      // A practice account opens pre-funded, so portfolio totals move too.
      queryClient.invalidateQueries({ queryKey: PORTFOLIO_SUMMARY_QUERY_KEY });
    },
  });
};

// accountId here is the account's numeric account_id (what the backend
// route expects), NOT its UUID id -- see
// TradingAccountController::findUserAccount().

const fundAccountFromSpotWallet = async ({
  accountId,
  amount,
}: {
  accountId: number;
  amount: number;
}): Promise<SpotWalletTransferResponse["data"]> => {
  const response = await axiosInstance.post<SpotWalletTransferResponse>(
    `/trading-accounts/${accountId}/deposit`,
    { amount },
  );
  return response.data.data;
};

const sendAccountToSpotWallet = async ({
  accountId,
  amount,
}: {
  accountId: number;
  amount: number;
}): Promise<SpotWalletTransferResponse["data"]> => {
  const response = await axiosInstance.post<SpotWalletTransferResponse>(
    `/trading-accounts/${accountId}/transfer`,
    { amount },
  );
  return response.data.data;
};

export const useFundAccountFromSpotWalletMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: fundAccountFromSpotWallet,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TRADING_ACCOUNTS_QUERY_KEY });
      queryClient.invalidateQueries({ queryKey: PORTFOLIO_SUMMARY_QUERY_KEY });
    },
  });
};

export const useSendAccountToSpotWalletMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: sendAccountToSpotWallet,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TRADING_ACCOUNTS_QUERY_KEY });
      queryClient.invalidateQueries({ queryKey: PORTFOLIO_SUMMARY_QUERY_KEY });
    },
  });
};

const fundAccountFromCreditWallet = async ({
  accountId,
  amount,
}: {
  accountId: number;
  amount: number;
}): Promise<CreditWalletTransferResponse["data"]> => {
  const response = await axiosInstance.post<CreditWalletTransferResponse>(
    `/trading-accounts/${accountId}/fund-from-credit`,
    { amount },
  );
  return response.data.data;
};

const fundAccountFromBonusWallet = async ({
  accountId,
  amount,
}: {
  accountId: number;
  amount: number;
}): Promise<BonusWalletTransferResponse["data"]> => {
  const response = await axiosInstance.post<BonusWalletTransferResponse>(
    `/trading-accounts/${accountId}/fund-from-bonus`,
    { amount },
  );
  return response.data.data;
};

export const useFundAccountFromCreditWalletMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: fundAccountFromCreditWallet,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TRADING_ACCOUNTS_QUERY_KEY });
      queryClient.invalidateQueries({ queryKey: PORTFOLIO_SUMMARY_QUERY_KEY });
    },
  });
};

export const useFundAccountFromBonusWalletMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: fundAccountFromBonusWallet,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TRADING_ACCOUNTS_QUERY_KEY });
      queryClient.invalidateQueries({ queryKey: PORTFOLIO_SUMMARY_QUERY_KEY });
    },
  });
};

const changeTradingAccountPassword = async ({
  accountId,
  password,
}: {
  accountId: number;
  password: string;
}): Promise<void> => {
  await axiosInstance.post(`/trading-accounts/${accountId}/change-password`, { password });
};

export const useChangeTradingAccountPasswordMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: changeTradingAccountPassword,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TRADING_ACCOUNTS_QUERY_KEY });
    },
  });
};
