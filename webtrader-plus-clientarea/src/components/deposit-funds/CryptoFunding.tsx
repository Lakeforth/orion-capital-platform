import React, { useEffect, useState, useMemo } from "react";
import axios from "axios";
import {
  Copy,
  Lock,
  Wallet,
  ChevronLeft,
  ChevronRight,
  Check,
} from "lucide-react";

import useDataStore from "@/store/dataStore";
import { useDepositMutation } from "@/services/deposit/deposit-queries";
import { QRCodeSVG } from "qrcode.react";
import { toast } from "@/components/ui/sonner";
import useAssetStore from "@/store/assetStore";
import useUserStore from "@/store/userStore";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import type { TradingAccount } from "@/services/trading-accounts/trading-account-types";

interface CryptoFundingProps {
  onChangeMethod: () => void;
  onClose?: () => void;
  stepsCount?: 3 | 4;
  onStepChange?: (step: number) => void;
  onSubmittedChange?: (submitted: boolean) => void;
  // "spot_wallet" (default) or "trading_account:{id}" -- only rendered as a
  // choice when tradingAccounts is passed (wallet_components_enabled).
  destination?: string;
  onDestinationChange?: (destination: string) => void;
  tradingAccounts?: TradingAccount[];
}

const CryptoFunding: React.FC<CryptoFundingProps> = ({
  onChangeMethod,
  onClose,
  stepsCount = 4,
  onStepChange,
  onSubmittedChange,
  destination = "spot_wallet",
  onDestinationChange,
  tradingAccounts,
}) => {
  const [currentStep, setCurrentStep] = useState(1);
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [orderId, setOrderId] = useState<number | null>(null);
  const [selectedAssetId, setSelectedAssetId] = useState<string>("");
  const [selectedNetwork, setSelectedNetwork] = useState<string>("");
  const [depositAmount, setDepositAmount] = useState<string>("0.00");
  const [isUsdMode, setIsUsdMode] = useState(false);
  const [copied, setCopied] = useState(false);

  const { data, fetchData, deposit_config } = useDataStore();
  const fetchAssets = useAssetStore((state) => state.fetchAssets);
  const assets = useAssetStore((state) => state.assets);
  const token = useUserStore((state) => state.token);
  const depositMutation = useDepositMutation();

  useEffect(() => {
    onSubmittedChange?.(isSubmitted);
  }, [isSubmitted, onSubmittedChange]);

  useEffect(() => {
    if (isSubmitted) {
      onStepChange?.(stepsCount);
    } else {
      onStepChange?.(currentStep);
    }
  }, [currentStep, isSubmitted, onStepChange, stepsCount]);

  useEffect(() => {
    fetchAssets();
    const interval = setInterval(() => {
      fetchAssets();
    }, 60000); // Refresh every 1 minute
    return () => clearInterval(interval);
  }, [fetchAssets]);

  useEffect(() => {
    if (!data) {
      fetchData();
    }
  }, [data, fetchData]);

  useEffect(() => {
    if (data) {
      console.log("Site Data Loaded:", data);
      console.log("Deposit Config:", deposit_config);
      console.log("Available Wallets:", data.wallets);
    }
  }, [data, deposit_config]);

  const wallets = useMemo(() => data?.wallets || [], [data?.wallets]);

  // Derived unique assets (cryptos) from deposit_config
  const availableAssets = useMemo(() => {
    return deposit_config?.crypto?.wallets || [];
  }, [deposit_config]);

  // Find current selected asset config
  const selectedAssetConfig = useMemo(() => {
    return availableAssets.find((a) => a.id === selectedAssetId);
  }, [availableAssets, selectedAssetId]);

  const selectedCrypto = useMemo(
    () => selectedAssetConfig?.code || "",
    [selectedAssetConfig],
  );

  const availableNetworks = useMemo<string[]>(() => {
    return selectedAssetConfig?.networks?.map((n) => n.name) || [];
  }, [selectedAssetConfig]);

  // Find matching wallet
  const selectedWalletData = useMemo(() => {
    if (!selectedAssetConfig || !selectedNetwork) return null;

    const networkConfig = selectedAssetConfig.networks?.find(
      (n) => n.name === selectedNetwork,
    );

    let baseWallet = null;
    if (networkConfig?.wallets && networkConfig.wallets.length > 0) {
      baseWallet = networkConfig.wallets[0];
    }

    if (!baseWallet) {
      // Fallback search
      return (
        wallets.find(
          (w) =>
            w.crypto === selectedCrypto && w.crypto_network === selectedNetwork,
        ) || wallets[0]
      );
    }

    // Try to find the full wallet data in the root wallets list by ID
    const fullWallet = wallets.find((w) => w.id === baseWallet.id);

    if (fullWallet) return fullWallet;

    // Construct a minimal version if full metadata is not found
    return {
      id: baseWallet.id,
      type: "wallet" as const,
      crypto: selectedCrypto,
      crypto_network: selectedNetwork,
      address: baseWallet.address,
      balance: "0",
      barcode: baseWallet.qrcode,
      status: "active",
    };
  }, [selectedAssetConfig, selectedNetwork, selectedCrypto, wallets]);

  // Dynamic price calculation from assetStore
  const currentAssetPrice = useMemo(() => {
    if (!selectedCrypto || assets.length === 0) return 0;

    // Attempt to find matching asset in assetStore
    const asset = assets.find(
      (a) =>
        a.type === "crypto" &&
        (a.symbol.toLowerCase().includes(selectedCrypto.toLowerCase()) ||
          a.sy.toLowerCase().includes(selectedCrypto.toLowerCase()) ||
          a.name.toLowerCase().includes(selectedCrypto.toLowerCase())),
    );

    if (asset) return parseFloat(asset.rate);

    // Fallback for common stablecoins if not in store
    if (
      ["USDT", "USDC", "BUSD", "DAI"].includes(selectedCrypto.toUpperCase())
    ) {
      return 1.0;
    }

    return 0;
  }, [assets, selectedCrypto]);

  const usdEquivalent = useMemo(() => {
    const amount = parseFloat(depositAmount || "0");
    return isUsdMode ? amount : amount * currentAssetPrice;
  }, [depositAmount, isUsdMode, currentAssetPrice]);

  const cryptoAmount = useMemo(() => {
    const amount = parseFloat(depositAmount || "0");
    if (isUsdMode) {
      return currentAssetPrice > 0
        ? (amount / currentAssetPrice).toFixed(10).replace(/\.?0+$/, "")
        : "0.00";
    }
    return depositAmount;
  }, [depositAmount, isUsdMode, currentAssetPrice]);

  const handleToggleMode = () => {
    const val = parseFloat(depositAmount || "0");
    if (isUsdMode) {
      // Swapping from USD to Crypto
      if (currentAssetPrice > 0) {
        setDepositAmount((val / currentAssetPrice).toFixed(8));
      }
    } else {
      // Swapping from Crypto to USD
      setDepositAmount((val * currentAssetPrice).toFixed(2));
    }
    setIsUsdMode(!isUsdMode);
  };

  // Update selectedNetwork when crypto changes if current network is not available
  useEffect(() => {
    if (
      availableNetworks.length > 0 &&
      !availableNetworks.includes(selectedNetwork)
    ) {
      setSelectedNetwork(availableNetworks[0]);
    }
  }, [availableNetworks, selectedNetwork]);

  // Set initial selected crypto if none is selected
  useEffect(() => {
    if (availableAssets.length > 0 && !selectedAssetId) {
      const defaultAsset =
        availableAssets.find((a) => a.default) || availableAssets[0];
      setSelectedAssetId(defaultAsset.id);
    }
  }, [availableAssets, selectedAssetId]);

  const handleCopyAddress = () => {
    if (selectedWalletData) {
      navigator.clipboard.writeText(selectedWalletData.address);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  const qrCodeValue = useMemo(() => {
    if (!selectedWalletData?.address) return "";

    const address = selectedWalletData.address;
    const amount = cryptoAmount;
    const crypto = selectedWalletData.crypto.toLowerCase();

    if (!amount || parseFloat(amount) <= 0) return address;

    // Standard URI schemes for common cryptocurrencies
    switch (crypto) {
      case "btc":
      case "bitcoin":
        return `bitcoin:${address}?amount=${amount}`;
      case "eth":
      case "ethereum":
        return `ethereum:${address}?value=${amount}`;
      case "usdt":
        return address;
      default:
        return address;
    }
  }, [selectedWalletData, cryptoAmount]);

  const handleSubmitDeposit = async () => {
    if (!depositAmount || !selectedWalletData?.address) {
      toast.error("Validation Error", {
        description: "Please ensure a valid deposit address is generated.",
      });
      return;
    }

    try {
      const response = await depositMutation.mutateAsync({
        amount: usdEquivalent, // Send the dollar equivalent
        method: "crypto",
        wallet_id: selectedWalletData.id,
        crypto: selectedCrypto,
        crypto_network: selectedNetwork,
        card_holder_name: "",
        destination,
      });
      setOrderId(response.data.order_id);
      setIsSubmitted(true);
      setCurrentStep(stepsCount);
      toast.success("Deposit request submitted successfully!", {
        description: "Your deposit is awaiting admin approval.",
      });
    } catch (error: unknown) {
      console.error("Deposit submission failed:", error);

      let errorMessage = "Failed to submit deposit. Please try again.";
      if (axios.isAxiosError(error)) {
        errorMessage = error.response?.data?.message || error.message;
      } else if (error instanceof Error) {
        errorMessage = error.message;
      }

      toast.error("Submission Failed", {
        description: errorMessage,
      });
    }
  };

  const handleNextStep = () => {
    if (currentStep === stepsCount - 1) {
      handleSubmitDeposit();
    } else if (currentStep < stepsCount) {
      setCurrentStep(currentStep + 1);
    }
  };

  const handlePreviousStep = () => {
    if (currentStep > 1) {
      if (currentStep === stepsCount && isSubmitted) {
        setIsSubmitted(false);
      }
      setCurrentStep(currentStep - 1);
    }
  };

  return (
    <div className="space-y-4 md:space-y-4 text-[#1C2030] min-h-[400px] w-full">
      {/* Step 1: Deposit Configuration */}
      {currentStep === 1 && !isSubmitted && (
        <div className="space-y-4">
          {/* Info Box */}
          <div className="flex items-start gap-4 bg-[#EBF5FF] p-3 rounded-xl border border-[#D1E9FF]">
            <div className="bg-white p-1.5 rounded-lg shadow-sm border border-[#D1E9FF] mt-0.5">
              <Lock className="w-3.5 h-3.5 text-[#1C2030]" />
            </div>
            <p className="text-[10px] font-bold text-[#4B5563] leading-relaxed">
              Select your preferred cryptocurrency and network. Only send
              supported assets to the generated address to avoid loss of funds.
            </p>
          </div>

          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-3">
              {/* Asset Select */}
              <div className="space-y-1.5">
                <label className="text-[10px] font-black text-[#374151] uppercase tracking-widest">
                  Select Asset
                </label>
                <Select
                  value={selectedAssetId}
                  onValueChange={(val) => setSelectedAssetId(val)}
                >
                  <SelectTrigger className="w-full bg-[#2D334A] border-none rounded-xl h-11 text-xs font-black text-white focus:ring-0 transition-all cursor-pointer px-3">
                    <SelectValue placeholder="Select Asset" />
                  </SelectTrigger>
                  <SelectContent className="bg-[#2D334A] border-none text-white">
                    {availableAssets.map((asset) => (
                      <SelectItem
                        key={asset.id}
                        value={asset.id}
                        className="text-[11px] font-black uppercase focus:bg-[#1C2030] focus:text-white"
                      >
                        <div className="flex items-center gap-2">
                          <img
                            src={asset.logo}
                            alt={asset.name}
                            className="w-3.5 h-3.5 rounded-full"
                          />
                          <span>
                            {asset.name} ({asset.code})
                          </span>
                        </div>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {/* Network Select */}
              <div className="space-y-1.5">
                <label className="text-[10px] font-black text-[#374151] uppercase tracking-widest">
                  Network
                </label>
                <Select
                  value={selectedNetwork}
                  onValueChange={(val) => setSelectedNetwork(val)}
                >
                  <SelectTrigger className="w-full bg-[#2D334A] border-none rounded-xl h-11 text-xs font-black text-white focus:ring-0 transition-all cursor-pointer px-3">
                    <SelectValue placeholder="Select Network" />
                  </SelectTrigger>
                  <SelectContent className="bg-[#2D334A] border-none text-white">
                    {availableNetworks.map((network) => (
                      <SelectItem
                        key={network}
                        value={network}
                        className="text-[11px] font-black uppercase focus:bg-[#1C2030] focus:text-white"
                      >
                        <span>{network}</span>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            {/* Amount Input */}
            <div className="space-y-1.5">
              <div className="flex items-center justify-between">
                <label className="text-[10px] font-black text-[#374151] uppercase tracking-widest">
                  Amount ({isUsdMode ? "USD" : selectedCrypto})
                </label>
                <button
                  onClick={handleToggleMode}
                  className="text-[9px] font-black text-[#FF7700] uppercase tracking-widest hover:underline"
                >
                  Switch to {isUsdMode ? selectedCrypto : "USD"}
                </button>
              </div>
              <div className="relative group">
                <input
                  type="number"
                  value={depositAmount}
                  onChange={(e) => setDepositAmount(e.target.value)}
                  className="w-full bg-white border border-gray-100 rounded-2xl pl-10 pr-24 py-4 shadow-sm text-[32px] font-black text-[#1C2030] focus:ring-0 transition-all placeholder:text-gray-300"
                  placeholder="0.00"
                />
                <div className="absolute left-5 top-1/2 -translate-y-1/2 pointer-events-none">
                  <span className="text-[24px] text-gray-300 font-bold">
                    {isUsdMode ? "$" : ""}
                  </span>
                </div>
                <div className="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none">
                  <span className="text-xs font-black text-gray-300 uppercase tracking-widest">
                    {isUsdMode ? "USD" : selectedCrypto}
                  </span>
                </div>
              </div>
              <div className="flex items-center justify-between text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">
                <span>
                  ≈{" "}
                  {isUsdMode
                    ? `${cryptoAmount} ${selectedCrypto}`
                    : `$${usdEquivalent.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
                </span>
                <span>
                  1 {selectedCrypto} ≈ $
                  {currentAssetPrice.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  })}
                </span>
              </div>
            </div>

            {/* Deposit Destination -- only shown once the tenant has real
                trading accounts to choose from; otherwise everyone still
                just deposits to the Spot Wallet. */}
            {tradingAccounts && tradingAccounts.length > 0 && (
              <div className="space-y-1.5">
                <label className="text-[10px] font-black text-[#374151] uppercase tracking-widest">
                  Deposit To
                </label>
                <Select value={destination} onValueChange={onDestinationChange}>
                  <SelectTrigger className="w-full bg-[#2D334A] border-none rounded-xl h-11 text-xs font-black text-white focus:ring-0 transition-all cursor-pointer px-3">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent className="bg-[#2D334A] border-none text-white">
                    <SelectItem
                      value="spot_wallet"
                      className="text-[11px] font-black uppercase focus:bg-[#1C2030] focus:text-white"
                    >
                      Spot Wallet
                    </SelectItem>
                    {tradingAccounts.map((account) => (
                      <SelectItem
                        key={account.id}
                        value={`trading_account:${account.id}`}
                        className="text-[11px] font-black uppercase focus:bg-[#1C2030] focus:text-white"
                      >
                        {account.account_type
                          ? account.account_type.charAt(0).toUpperCase() +
                            account.account_type.slice(1)
                          : "Live"}{" "}
                        Account #{account.account_id}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}

            {/* Quick Amounts */}
            <div className="grid grid-cols-4 gap-2">
              {["250", "500", "1000", "5000"].map((val) => (
                <button
                  key={val}
                  onClick={() => {
                    setDepositAmount(val);
                    if (!isUsdMode) setIsUsdMode(true);
                  }}
                  className="py-2 rounded-xl border border-gray-100 bg-white hover:border-gray-200 font-black text-[11px] transition-all shadow-sm active:scale-95 cursor-pointer text-[#1C2030]"
                >
                  +${val}
                </button>
              ))}
            </div>
          </div>

          {/* Footer inside CryptoFunding for step 1 */}
          <div className="absolute bottom-0 left-0 right-0 bg-[#F9FAFB] border-t border-[#E5E7EB] px-8 py-3 flex items-center justify-between z-20 rounded-b-2xl">
            <button
              type="button"
              onClick={onClose}
              className="text-xs font-bold text-[#374151] hover:underline"
            >
              Cancel process
            </button>
            <div className="flex gap-2">
              <button
                type="button"
                onClick={onChangeMethod}
                className="flex items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-xs font-bold text-[#1C2030] hover:bg-gray-50 transition-colors"
              >
                <ChevronLeft className="w-3.5 h-3.5" />
                Back
              </button>
              <button
                type="button"
                onClick={handleNextStep}
                disabled={!depositAmount || parseFloat(depositAmount) <= 0}
                className={`flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white transition-all ${
                  depositAmount && parseFloat(depositAmount) > 0
                    ? "bg-[#1C2030] hover:bg-[#2D334A]"
                    : "bg-[#C4C6D0] cursor-not-allowed"
                }`}
              >
                Continue
                <ChevronRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Step 2: Payment Details / QR Code */}
      {currentStep === 2 && !isSubmitted && (
        <div className="space-y-4 w-full">
          {/* Main Info Area (QR + Details) */}
          <div className="flex gap-6 items-start">
            {/* QR Code Container */}
            <div className="bg-white p-2.5 rounded-xl border border-gray-100 flex flex-col items-center gap-1.5">
              <div className="w-[110px] h-[110px] bg-[#1C2030] rounded-lg flex items-center justify-center overflow-hidden">
                {qrCodeValue ? (
                  <QRCodeSVG
                    value={qrCodeValue}
                    size={80}
                    level="H"
                    includeMargin={false}
                    fgColor="#FFFFFF"
                    bgColor="transparent"
                  />
                ) : (
                  <div className="text-[8px] font-black text-white/40 text-center px-4 uppercase tracking-widest">
                    Generating...
                  </div>
                )}
              </div>
              <span className="text-[9px] font-black text-[#1C2030] uppercase tracking-widest">
                SCAN TO PAY
              </span>
            </div>

            {/* Amount & Network Info */}
            <div className="flex-1 space-y-4">
              <div className="space-y-0.5">
                <p className="text-[9px] font-black text-[#6B7280] uppercase tracking-widest">
                  AMOUNT TO DEPOSIT
                </p>
                <h3 className="text-xl font-black text-[#1C2030] tracking-tight">
                  {cryptoAmount} {selectedCrypto}
                </h3>
                <p className="text-xs font-bold text-[#9CA3AF]">
                  ≈ $
                  {usdEquivalent.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                  })}
                </p>
              </div>

              <div className="h-px bg-gray-100 w-full" />

              <div className="flex gap-10">
                <div className="space-y-0.5">
                  <p className="text-[9px] font-black text-[#6B7280] uppercase tracking-widest">
                    NETWORK
                  </p>
                  <div className="flex items-center gap-1.5">
                    <div className="w-1.5 h-1.5 rounded-full bg-[#FF7700]" />
                    <span className="text-xs font-black text-[#1C2030] uppercase">
                      {selectedNetwork}
                    </span>
                  </div>
                </div>
                <div className="space-y-0.5">
                  <p className="text-[9px] font-black text-[#6B7280] uppercase tracking-widest">
                    TIME
                  </p>
                  <p className="text-xs font-black text-[#1C2030] uppercase">
                    10-30 MIN
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div className="h-px bg-gray-100 w-full" />

          {/* Address Bar Area */}
          <div className="space-y-1.5">
            <label className="text-[9px] font-black text-[#6B7280] uppercase tracking-widest">
              DEPOSIT ADDRESS
            </label>
            <div className="flex gap-2">
              <div className="flex-1 relative group">
                <div className="absolute left-3.5 top-1/2 -translate-y-1/2">
                  <Wallet className="w-3.5 h-3.5 text-[#00A041]" />
                </div>
                <input
                  type="text"
                  readOnly
                  value={selectedWalletData?.address || ""}
                  className="w-full bg-[#1C2030] border-none rounded-xl pl-10 pr-4 py-3 text-[10px] font-black text-white focus:ring-0 transition-all select-all cursor-pointer"
                />
              </div>
              <button
                onClick={handleCopyAddress}
                className={`w-11 h-11 flex items-center justify-center rounded-xl transition-all shadow-lg ${
                  copied
                    ? "bg-[#00A041] text-white"
                    : "bg-[#2EB82E] text-white hover:bg-[#26a026] shadow-green-500/10"
                }`}
              >
                {copied ? (
                  <Check className="w-4 h-4 stroke-[4]" />
                ) : (
                  <Copy className="w-4 h-4" />
                )}
              </button>
            </div>
          </div>

          {/* Footer inside CryptoFunding for step 2 */}
          <div className="absolute bottom-0 left-0 right-0 bg-[#F9FAFB] border-t border-[#E5E7EB] px-8 py-3 flex items-center justify-between z-20 rounded-b-2xl">
            <button
              type="button"
              onClick={onClose}
              className="text-xs font-bold text-[#374151] hover:underline"
            >
              Cancel process
            </button>
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => setCurrentStep(1)}
                className="flex items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-xs font-bold text-[#1C2030] hover:bg-gray-50 transition-colors"
              >
                <ChevronLeft className="w-3.5 h-3.5" />
                Back
              </button>
              <button
                type="button"
                onClick={handleNextStep}
                disabled={!selectedWalletData?.address}
                className={`flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white transition-all ${
                  selectedWalletData?.address
                    ? "bg-[#1C2030] hover:bg-[#2D334A]"
                    : "bg-[#C4C6D0] cursor-not-allowed"
                }`}
              >
                Continue
                <ChevronRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      )}
      {/* Step 3: Payment Review */}
      {currentStep === 3 && stepsCount === 4 && !isSubmitted && (
        <div className="flex gap-8">
          {/* Left Side: Crypto Info */}
          <div className="w-[340px] space-y-3">
            <div
              className="rounded-[20px] p-6 text-white flex flex-col justify-between relative shadow-xl overflow-hidden min-h-[210px]"
              style={{
                background:
                  "linear-gradient(135deg, #00234BF2 0%, #003B7EE5 100%)",
              }}
            >
              <div className="flex justify-between items-start">
                <div className="space-y-0.5">
                  <p className="text-[9px] font-bold text-white/60 uppercase tracking-widest">
                    Selected Asset
                  </p>
                  <div className="flex items-center gap-1.5 bg-white/10 px-2 py-1 rounded-lg border border-white/20">
                    <img
                      src={selectedAssetConfig?.logo}
                      alt={selectedCrypto}
                      className="w-4 h-4 rounded-full"
                    />
                    <span className="font-black tracking-tight text-xs">
                      {selectedCrypto}
                    </span>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-[9px] font-bold text-white/60 uppercase tracking-widest">
                    Network
                  </p>
                  <p className="text-base font-black tracking-tight">
                    {selectedNetwork}
                  </p>
                </div>
              </div>

              <div className="space-y-3">
                <div className="bg-white/5 p-3 rounded-xl border border-white/10">
                  <p className="text-[7px] font-bold text-white/40 uppercase tracking-widest mb-0.5">
                    Deposit Address
                  </p>
                  <p className="text-[9px] font-mono break-all opacity-80 leading-relaxed">
                    {selectedWalletData?.address ||
                      "Connect wallet to view address"}
                  </p>
                </div>
              </div>
            </div>

            {/* Encryption Box */}
            <div className="flex items-center gap-3 bg-[#F3F4F6] p-3 rounded-xl border border-gray-100">
              <div className="bg-[#1C2030] p-1.5 rounded-lg shadow-sm">
                <Lock className="w-3.5 h-3.5 text-white" />
              </div>
              <div>
                <p className="text-[9px] font-black text-[#1C2030] uppercase tracking-wider">
                  Secure Crypto Transfer
                </p>
                <p className="text-[9px] text-[#6B7280] font-bold">
                  Funds are protected by blockchain encryption
                </p>
              </div>
            </div>
          </div>

          {/* Right Side: Order Summary */}
          <div className="flex-1 space-y-6 pt-1">
            <div className="space-y-4">
              <h3 className="text-[10px] font-black text-[#6B7280] uppercase tracking-widest">
                Order Summary
              </h3>

              <div className="space-y-6">
                <div className="flex justify-between items-center">
                  <span className="text-sm font-bold text-[#6B7280]">
                    Deposit Amount
                  </span>
                  <span className="text-xl font-black text-[#1C2030]">
                    $
                    {usdEquivalent.toLocaleString(undefined, {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    })}
                  </span>
                </div>

                <div className="flex justify-between items-center">
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-bold text-[#6B7280]">
                      Exchange Rate
                    </span>
                    <span className="bg-gray-100 text-[8px] font-bold px-1 py-0.5 rounded text-[#9CA3AF]">
                      Live
                    </span>
                  </div>
                  <span className="text-xs font-black text-[#00A041]">
                    1 {selectedCrypto} ≈ $
                    {currentAssetPrice.toLocaleString(undefined, {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    })}
                  </span>
                </div>

                <div className="border-t border-gray-100 pt-4 space-y-1">
                  <p className="text-[10px] font-black text-[#374151] uppercase tracking-widest">
                    Total in {selectedCrypto}
                  </p>
                  <p className="text-[44px] font-black text-[#1C2030] leading-none tracking-tight">
                    {cryptoAmount}
                  </p>
                </div>
              </div>
            </div>

            <p className="text-[8px] font-bold text-[#9CA3AF] leading-relaxed max-w-[340px]">
              * Confirming this transaction means you acknowledge the provided
              address is correct. Incorrect network transfers may result in
              permanent loss.
            </p>
          </div>

          {/* Footer for Step 4 Review */}
          <div className="absolute bottom-0 left-0 right-0 bg-[#F9FAFB] border-t border-[#E5E7EB] px-8 py-3 flex items-center justify-between z-20 rounded-b-2xl">
            <button
              type="button"
              onClick={onClose}
              className="text-xs font-bold text-[#374151] hover:underline"
            >
              Cancel process
            </button>
            <div className="flex gap-2">
              <button
                type="button"
                onClick={handlePreviousStep}
                className="flex items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-xs font-bold text-[#1C2030] hover:bg-gray-50 transition-colors"
              >
                <ChevronLeft className="w-3.5 h-3.5" />
                Back
              </button>
              <button
                type="button"
                onClick={handleNextStep}
                disabled={depositMutation.isPending}
                className={`flex items-center gap-2 px-10 py-2.5 rounded-xl text-xs font-bold text-white transition-all ${
                  !depositMutation.isPending
                    ? "bg-[#2EB82E] hover:bg-[#26a026] shadow-lg shadow-green-500/20"
                    : "bg-[#C4C6D0] cursor-not-allowed"
                }`}
              >
                {depositMutation.isPending
                  ? "Confirming..."
                  : "Confirm & Transfer"}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Success State */}
      {isSubmitted && (
        <div className="space-y-4 py-2 w-full flex flex-col items-center">
          {/* Success Icon */}
          <div className="relative">
            <div className="w-16 h-16 bg-[#EBF5FF] rounded-2xl flex items-center justify-center">
              <div className="w-8 h-8 bg-[#00A041] rounded-xl flex items-center justify-center shadow-lg shadow-green-500/20">
                <Check className="w-5 h-5 text-white stroke-[4]" />
              </div>
            </div>
          </div>

          {/* Success Text */}
          <div className="space-y-1 text-center">
            <h3 className="text-lg font-bold text-[#1C2030] tracking-tight">
              Transaction Completed
            </h3>
            <p className="text-[#6B7280] text-xs leading-relaxed max-w-[380px]">
              Your deposit request has been successfully submitted. Your balance
              will be updated once the transaction is confirmed on the network.
            </p>
          </div>

          {/* Transaction Info Box */}
          <div className="w-full bg-[#F9FAFB] rounded-xl p-4 space-y-3">
            <div className="flex justify-between items-center">
              <span className="text-[11px] font-bold text-[#6B7280]">
                Amount to Deposit
              </span>
              <span className="text-xs font-bold text-[#00A041]">
                {cryptoAmount} {selectedCrypto}
              </span>
            </div>

            <div className="flex justify-between items-center">
              <span className="text-[11px] font-bold text-[#6B7280]">
                Reference ID
              </span>
              <div className="flex items-center gap-2">
                <span className="text-xs font-bold text-[#1C2030]">
                  {orderId ?? "—"}
                </span>
                <Copy className="w-3 h-3 text-[#C4C6D0] cursor-pointer hover:text-[#1C2030] transition-colors" />
              </div>
            </div>

            <div className="flex justify-between items-center">
              <span className="text-[9px] font-black text-[#6B7280] uppercase tracking-widest">
                Network Fee
              </span>
              <span className="text-xs font-bold text-[#6B7280]">
                0.00 {selectedCrypto} (Sponsored)
              </span>
            </div>
          </div>

          {/* Footer Buttons */}
          <div className="w-full flex gap-3 mt-1">
            <button
              onClick={onClose}
              className="flex-1 py-2.5 px-4 rounded-xl border border-gray-300 bg-white text-xs font-bold text-[#1C2030] hover:bg-gray-50 transition-all"
            >
              {token ? "Back to Dashboard" : "Login to account"}
            </button>
            <button
              onClick={() => {
                setIsSubmitted(false);
                setCurrentStep(1);
                setOrderId(null);
                setDepositAmount("0.00");
                depositMutation.reset();
              }}
              className="flex-1 py-2.5 px-4 rounded-xl bg-[#2EB82E] text-xs font-bold text-white hover:bg-[#26a026] transition-all shadow-lg shadow-green-500/10"
            >
              Deposit Another
            </button>
          </div>

          {/* Contact Support Link */}
          <div className="text-center">
            <p className="text-[11px] font-bold text-[#6B7280]">
              Having issues?{" "}
              <a
                href="/main/chat"
                className="text-[#1C2030] hover:underline inline-flex items-center gap-1"
              >
                Contact Support <span className="text-[8px]">↗</span>
              </a>
            </p>
          </div>
        </div>
      )}
    </div>
  );
};

export default CryptoFunding;
