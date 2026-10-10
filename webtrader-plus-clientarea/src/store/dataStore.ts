import { create } from "zustand";
import axiosInstance from "@/lib/axios";

export interface Currency {
  id: number;
  name: string;
  symbol: string;
  icon: string;
  code: string;
  rate: string;
}

export interface Wallet {
  id: string;
  crypto: string;
  crypto_network: string;
  address: string;
  balance: string;
  barcode: string | null;
  status: string;
  type?: "wallet" | "link";
}

export interface WithdrawalMethod {
  id: string;
  title: string;
  min_amount: number;
  max_amount: number;
}

export interface Plan {
  id: string;
  title: string;
  color: string;
  icon: string;
  leverage: number;
  price?: number | string | null;
  features: string[];
}

export interface AssetCat {
  cryptos: number;
  stocks: number;
  indices: number;
  commodities: number;
  metals: number;
  forex: number;
}

export interface ExpertAdvisor {
  id: string;
  name: string;
  slug: string;
  image: string;
}

export interface DepositCryptoNetworkWallet {
  id: string;
  address: string;
  qrcode: string | null;
}

export interface DepositCryptoNetwork {
  name: string;
  wallets: DepositCryptoNetworkWallet[];
}

export interface DepositCryptoWallet {
  id: string;
  logo: string;
  name: string;
  code: string;
  networks: DepositCryptoNetwork[];
  default?: boolean;
}

export interface DepositCryptoConfig {
  enabled: boolean;
  estimated_time: string;
  wallets: DepositCryptoWallet[];
}

export interface DepositCreditCardConfig {
  enabled: boolean;
  estimated_time: string;
}

export interface DepositInteracConfig {
  enabled: boolean;
  estimated_time: string;
  recipient_name: string | null;
  email: string | null;
  security_question: string | null;
  security_answer: string | null;
}

export interface DepositConfig {
  crypto: DepositCryptoConfig;
  credit_card: DepositCreditCardConfig;
  interac: DepositInteracConfig;
}

export interface PlanFeatureDefinition {
  id: number;
  index: number;
  name: string;
  icon_key: string;
  use_image: boolean;
  image_url: string | null;
}

export interface SiteData {
  currencies: Currency[];
  wallets: Wallet[];
  withdrawal_methods: WithdrawalMethod[];
  crypto_networks: string[];
  plan_features: string[];
  plan_feature_definitions: PlanFeatureDefinition[];
  plans: Plan[];
  leverage?: AssetCat;
  expert_advisors: ExpertAdvisor[];
  deposit_config: DepositConfig;
  webtrader_default_pairs: string[];
}

interface DataStore {
  data: SiteData | null;
  leverage: AssetCat;
  deposit_config: DepositConfig | null;
  isLoading: boolean;
  error: string | null;
  activeEA: ExpertAdvisor | null;
  fetchData: () => Promise<void>;
  activateEA: (ea: ExpertAdvisor) => void;
  deactivateEA: () => void;
}

// Default leverage values
const defaultLeverage: AssetCat = {
  cryptos: 100,
  stocks: 100,
  indices: 100,
  commodities: 100,
  metals: 100,
  forex: 100,
};

const useDataStore = create<DataStore>((set) => ({
  data: null,
  leverage: defaultLeverage,
  deposit_config: null,
  isLoading: false,
  error: null,
  activeEA: null,
  fetchData: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await axiosInstance.get("/data");
      const responseData = response.data.data;

      // Extract leverage from the response and use defaults if missing
      const leverage = responseData.leverage || defaultLeverage;
      const deposit_config = responseData.deposit_config || null;

      set({
        data: responseData,
        leverage,
        deposit_config,
        isLoading: false,
      });
    } catch (error) {
      console.error("Failed to fetch site data:", error);
      set({
        error: "Failed to fetch site data. Please try again later.",
        isLoading: false,
      });
    }
  },
  activateEA: (ea) => set({ activeEA: ea }),
  deactivateEA: () => set({ activeEA: null }),
}));

export default useDataStore;
