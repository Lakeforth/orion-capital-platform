import { useState, useMemo, useEffect } from "react";
// import StepProgressBar from "./StepProgressBar";

import {
  AlertTriangle,
  ShieldCheck,
  X,
  ChevronLeft,
  ChevronRight,
  Zap,
  Clock,
  Check,
  Landmark,
} from "lucide-react";
import CardFunding from "./CardFunding";
import CryptoFunding from "./CryptoFunding";
import InteracFunding from "./InteracFunding";
import useDataStore from "@/store/dataStore";
import useUserStore from "@/store/userStore";
import { useTradingAccountsQuery } from "@/services/trading-accounts/trading-account-queries";
import TranslationErrorBoundary from "../TranslationErrorBoundary";
import { createPortal } from "react-dom";

interface DepositFundsProps {
  onClose?: () => void;
  initialMethod?: string | null;
  isOpen?: boolean;
}

const DepositFunds: React.FC<DepositFundsProps> = ({
  onClose,
  initialMethod,
  isOpen = false,
}) => {
  // selectedMethod keeps track of whether the user wants to use card or crypto for funding
  const [selectedMethod, setSelectedMethod] = useState<string | null>(
    initialMethod || null,
  );
  // showCardFunding determines if we should show the detailed funding form (CardFunding or CryptoFunding)
  const [showCardFunding, setShowCardFunding] = useState(!!initialMethod);

  const [overrideStepsCount, setOverrideStepsCount] = useState<3 | 4 | null>(
    null,
  );

  const [isSubmitted, setIsSubmitted] = useState(false);

  // Lock scroll when modal is open
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "unset";
    }
    return () => {
      document.body.style.overflow = "unset";
    };
  }, [isOpen]);

  const url = window.location.href.toLowerCase();
  // stepsCount varies based on the domain to cater to different checkout flows
  const urlStepsCount: 3 | 4 =
    url.includes("fincapitalmarkets.org") ||
    url.includes("equitymarketspro.com")
      ? 4
      : 3;

  const internalStepsCount: 3 | 4 = overrideStepsCount ?? urlStepsCount;
  const [currentStep, setCurrentStep] = useState(initialMethod ? 2 : 1);

  const steps = useMemo(() => {
    if (internalStepsCount === 3) {
      return [
        { number: 1, label: "METHOD" },
        { number: 2, label: "AMOUNT" },
        { number: 3, label: "DETAILS" },
        { number: 4, label: "COMPLETED" },
      ];
    }
    return [
      { number: 1, label: "METHOD" },
      { number: 2, label: "AMOUNT" },
      { number: 3, label: "DETAILS" },
      { number: 4, label: "REVIEW" },
      { number: 5, label: "COMPLETED" },
    ];
  }, [internalStepsCount]);

  const stepInfo = useMemo(() => {
    // Determine if we are on the final 'COMPLETED' step
    const isLastStep = currentStep === steps.length;

    if (isLastStep && isSubmitted) {
      return {
        title: "COMPLETED",
        subtitle: "",
      };
    }

    switch (currentStep) {
      case 1:
        return {
          title: "SELECT METHOD",
          subtitle:
            "Select your deposit method and add funds to your account to start trading instantly.",
        };
      case 2:
        return {
          title: "ENTER AMOUNT",
          subtitle:
            "Specify how much you would like to securely add to your account.",
        };
      case 3:
        return {
          title: "PAYMENT DETAILS",
          subtitle: "Carefully enter your deposit information to proceed.",
        };
      case 4:
        return internalStepsCount === 4
          ? {
              title: "REVIEW PAYMENT DETAILS",
              subtitle:
                "Please double check your transaction information before confirming.",
            }
          : isSubmitted
            ? {
                title: "COMPLETED",
                subtitle: "Your deposit request has been submitted.",
              }
            : {
                title: "PAYMENT DETAILS",
                subtitle:
                  "Carefully enter your deposit information to proceed.",
              };
      case 5:
        return isSubmitted
          ? {
              title: "COMPLETED",
              subtitle: "Your deposit request has been submitted.",
            }
          : {
              title: "REVIEW PAYMENT DETAILS",
              subtitle:
                "Please double check your transaction information before confirming.",
            };
      default:
        return { title: "FUNDS DEPOSIT", subtitle: "" };
    }
  }, [currentStep, internalStepsCount, steps, isSubmitted]);

  const { user } = useUserStore();
  const isVerified =
    user?.verification_status === "approved" ||
    user?.verification_status === "verified";
  const { deposit_config } = useDataStore();
  const { data: tradingAccounts } = useTradingAccountsQuery(isOpen);
  // Where this deposit should land: "spot_wallet" (default -- client
  // decides which account to fund later) or "trading_account:{id}" (fund
  // one specific account directly, bypassing the Spot Wallet). Always
  // available -- unrelated to the (retired) per-account component system.
  const [destination, setDestination] = useState("spot_wallet");

  const cryptoEnabled = deposit_config?.crypto?.enabled !== false; // Default to true if not specified
  const cardEnabled = deposit_config?.credit_card?.enabled !== false;
  const interacEnabled = deposit_config?.interac?.enabled === true;

  if (!isOpen) return null;

  // If showCardFunding is true, render the specific funding component based on selectedMethod
  const renderContent = () => {
    if (showCardFunding) {
      return selectedMethod === "card" ? (
        <CardFunding
          onChangeMethod={() => {
            setShowCardFunding(false);
            setCurrentStep(1);
          }}
          onClose={onClose}
          stepsCount={internalStepsCount}
          onStepChange={(step) => setCurrentStep(step + 1)}
          onSubmittedChange={setIsSubmitted}
          destination={destination}
          onDestinationChange={setDestination}
          tradingAccounts={tradingAccounts}
        />
      ) : selectedMethod === "interac" && deposit_config?.interac ? (
        <InteracFunding
          config={deposit_config.interac}
          onChangeMethod={() => {
            setShowCardFunding(false);
            setCurrentStep(1);
          }}
          onClose={onClose}
          onStepChange={(step) => setCurrentStep(step + 1)}
          onSubmittedChange={setIsSubmitted}
          destination={destination}
          onDestinationChange={setDestination}
          tradingAccounts={tradingAccounts}
        />
      ) : (
        <CryptoFunding
          onChangeMethod={() => {
            setShowCardFunding(false);
            setCurrentStep(1);
          }}
          onClose={onClose}
          stepsCount={internalStepsCount}
          destination={destination}
          onDestinationChange={setDestination}
          tradingAccounts={tradingAccounts}
          onStepChange={(step) => setCurrentStep(step + 1)}
          onSubmittedChange={setIsSubmitted}
        />
      );
    }

    return (
      <div className="space-y-6">
        {/* Info Text */}
        <p className="text-[#6B7280] text-xs md:text-sm mb-4 md:mb-6 max-w-2xl leading-relaxed">
          Select your deposit method and add funds to your account to start
          trading instantly. You can deposit using Fiat or crypto payment
          methods below.
        </p>

        {/* Anti-Fraud Box */}
        <div className="flex items-start gap-3 md:gap-4 bg-[#EBF5FF] p-3 md:p-4 rounded-xl border border-[#D1E9FF] mb-4">
          <div className="bg-white p-1.5 rounded-lg shadow-sm border border-[#D1E9FF] flex-shrink-0">
            <ShieldCheck className="w-4 h-4 text-[#1C2030]" />
          </div>
          <div className="space-y-0.5">
            <h4 className="text-[9px] md:text-[10px] font-black text-[#1C2030] uppercase tracking-wider">
              Anti-Fraud Verification
            </h4>
            <p className="text-[10px] md:text-[11px] text-[#4B5563] leading-tight">
              For your security, some deposits require verification. Our team
              may request identity confirmation before funds are credited.
            </p>
          </div>
        </div>

        {/* Payment Method Section */}
        <div className="space-y-3">
          <h3 className="text-[10px] font-black text-[#6B7280] uppercase tracking-widest">
            Payment Method
          </h3>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {/* Card Payment Option */}
            {cardEnabled && (
              <div
                onClick={() => setSelectedMethod("card")}
                className={`relative rounded-xl border p-4 transition-all duration-300 cursor-pointer ${
                  selectedMethod === "card"
                    ? "border-[#1C2030] ring-1 ring-[#1C2030] bg-white shadow-md"
                    : "border-[#C4C6D0] bg-white hover:border-[#9CA3AF]"
                }`}
              >
                <div className="flex items-start justify-between mb-3">
                  <div className="p-1.5 bg-[#F3F4F6] rounded-lg border border-[#E5E7EB]">
                    <img
                      src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1779371845/image_4_zkvwmc.png"
                      alt="Credit Card"
                      className="w-6 h-6 object-contain"
                    />
                  </div>
                  {!isVerified && (
                    <span className="bg-[#EBF5FF] text-[#1C2030] text-[8px] px-2 py-1 rounded-md font-bold uppercase tracking-wider border border-[#D1E9FF]">
                      KYC Required
                    </span>
                  )}
                </div>

                <h4 className="text-base font-bold text-[#1C2030] mb-0.5">
                  Credit/Debit Card
                </h4>
                <div className="flex items-center gap-1 text-[#FF7700] text-xs font-bold mb-4">
                  <Zap className="w-3 h-3 fill-current" />
                  <span>
                    {deposit_config?.credit_card?.estimated_time || "Instant"}
                  </span>
                </div>

                <div className="mt-auto">
                  <img
                    src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1779452129/image_6_n1zqkw.png"
                    alt="Payment Methods"
                    className="h-4 object-contain"
                  />
                </div>
              </div>
            )}

            {/* Crypto Payment Option */}
            {cryptoEnabled && (
              <div
                onClick={() => setSelectedMethod("crypto")}
                className={`relative rounded-xl border p-4 transition-all duration-300 cursor-pointer ${
                  selectedMethod === "crypto"
                    ? "border-[#1C2030] ring-1 ring-[#1C2030] bg-white shadow-md"
                    : "border-[#C4C6D0] bg-white hover:border-[#9CA3AF]"
                }`}
              >
                <div className="flex items-start mb-3">
                  <div className="flex -space-x-2">
                    <div className="w-8 h-8 rounded-full border-2 border-white bg-[#F7931A] flex items-center justify-center shadow-sm">
                      <span className="text-white text-[8px] font-bold">
                        BTC
                      </span>
                    </div>
                    <div className="w-8 h-8 rounded-full border-2 border-white bg-[#627EEA] flex items-center justify-center shadow-sm">
                      <span className="text-white text-[8px] font-bold">
                        ETH
                      </span>
                    </div>
                    <div className="w-8 h-8 rounded-full border-2 border-white bg-[#345D9D] flex items-center justify-center shadow-sm">
                      <span className="text-white text-[8px] font-bold">
                        LTC
                      </span>
                    </div>
                  </div>
                </div>

                <h4 className="text-base font-bold text-[#1C2030] mb-0.5">
                  Cryptocurrency
                </h4>
                <div className="flex items-center gap-1 text-[#FF7700] text-xs font-bold mb-4">
                  <Clock className="w-3 h-3" />
                  <span>
                    {deposit_config?.crypto?.estimated_time ||
                      "20 - 30 minutes"}
                  </span>
                </div>
                <p className="text-[9px] font-bold text-[#9CA3AF] uppercase tracking-widest">
                  BTC, ETH, USDT, AND MORE
                </p>
              </div>
            )}

            {interacEnabled && (
              <div
                onClick={() => setSelectedMethod("interac")}
                className={`relative rounded-xl border p-4 transition-all duration-300 cursor-pointer ${
                  selectedMethod === "interac"
                    ? "border-[#1C2030] ring-1 ring-[#1C2030] bg-white shadow-md"
                    : "border-[#C4C6D0] bg-white hover:border-[#9CA3AF]"
                }`}
              >
                <div className="flex items-start mb-3">
                  <div className="w-8 h-8 rounded-lg bg-[#FFCA05] flex items-center justify-center shadow-sm">
                    <Landmark className="w-4 h-4 text-[#1C2030]" />
                  </div>
                </div>
                <h4 className="text-base font-bold text-[#1C2030] mb-0.5">
                  Interac e-Transfer 🇨🇦
                </h4>
                <div className="flex items-center gap-1 text-[#FF7700] text-xs font-bold mb-4">
                  <Clock className="w-3 h-3" />
                  <span>{deposit_config?.interac?.estimated_time}</span>
                </div>
                <p className="text-[9px] font-bold text-[#9CA3AF] uppercase tracking-widest">
                  CANADIAN BANK TRANSFER
                </p>
              </div>
            )}
          </div>
        </div>
      </div>
    );
  };

  const modalContent = (
    <TranslationErrorBoundary>
      <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        {/* Backdrop */}
        <div className="absolute inset-0 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300" />

        {/* Modal Container */}
        <div
          className={`bg-[#FFFFFF] w-full flex flex-col max-h-[90vh] md:max-h-[85vh] text-[#1C2030] relative overflow-hidden rounded-2xl shadow-2xl z-10 transition-all duration-300 ${
            currentStep === 4 && internalStepsCount === 4
              ? "max-w-[896px]"
              : "max-w-[670px]"
          }`}
        >
          {/* Header */}
          <div className="px-4 md:px-6 pt-4 md:pt-6 pb-2">
            <div className="flex items-center justify-between mb-1">
              <h2 className="text-xl md:text-[28px] font-bold tracking-tight text-[#1C2030]">
                {stepInfo.title}
              </h2>
              <button
                onClick={onClose}
                className="p-1 hover:bg-gray-100 rounded-full transition-colors flex-shrink-0"
              >
                <X className="w-5 h-5 text-[#1C2030]" />
              </button>
            </div>
            <p className="text-[#6B7280] text-xs md:text-[13px] leading-relaxed max-w-[500px]">
              {stepInfo.subtitle}
            </p>
          </div>

          {/* Content Area */}
          <div
            className={`flex-1 px-4 md:px-6 pb-24 md:pb-20 overflow-y-auto ${
              isSubmitted ? "pt-0" : "pt-4"
            }`}
          >
            {/* Progress Section */}
            <div className="mb-4 md:mb-6">
              <div className="flex justify-end mb-1.5">
                <span className="text-[8px] md:text-[10px] font-bold text-[#1C2030] uppercase tracking-wider">
                  {isSubmitted
                    ? 100
                    : Math.round(
                        ((currentStep - 1) / (steps.length - 1)) * 100,
                      )}
                  % COMPLETE
                </span>
              </div>
              <div className="h-1 w-full bg-[#E5E7EB] rounded-full overflow-hidden mb-3">
                <div
                  className="h-full bg-[#00A041] transition-all duration-500"
                  style={{
                    width: `${
                      isSubmitted
                        ? 100
                        : ((currentStep - 1) / (steps.length - 1)) * 100
                    }%`,
                  }}
                />
              </div>

              {/* Step Labels */}
              <div className="flex justify-between px-0.5 border-b border-gray-100 pb-3 gap-1">
                {steps.map((step) => {
                  const isCompleted = step.number < currentStep || isSubmitted;
                  const isActive = step.number === currentStep && !isSubmitted;

                  return (
                    <div
                      key={step.number}
                      className="flex items-center gap-1 flex-1 md:flex-initial"
                    >
                      {isCompleted && (
                        <div className="bg-[#00A041] rounded-full p-0.5 flex-shrink-0">
                          <Check className="w-2 h-2 text-white stroke-[4]" />
                        </div>
                      )}
                      <span
                        className={`text-[7px] md:text-[9px] font-bold uppercase tracking-wider truncate ${
                          isActive
                            ? "text-[#1C2030]"
                            : isCompleted
                              ? "text-[#00A041]"
                              : "text-[#C4C6D0]"
                        }`}
                      >
                        {step.label}
                      </span>
                    </div>
                  );
                })}
              </div>
            </div>

            {renderContent()}
          </div>

          {/* Footer (Only for Step 1 selection) */}
          {!showCardFunding && (
            <div className="absolute bottom-0 left-0 right-0 bg-[#F9FAFB] border-t border-[#E5E7EB] px-4 md:px-6 py-3 flex flex-col md:flex-row items-center justify-between gap-3 md:gap-0 z-20">
              <button
                onClick={onClose}
                className="text-xs font-bold text-[#1C2030] hover:underline order-2 md:order-1"
              >
                Cancel process
              </button>
              <div className="flex gap-2 order-1 md:order-2 w-full md:w-auto">
                <button
                  onClick={onClose}
                  className="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2 rounded-lg border border-[#C4C6D0] bg-white text-xs font-bold text-[#1C2030] hover:bg-gray-50 transition-colors"
                >
                  <ChevronLeft className="w-3.5 h-3.5" />
                  Back
                </button>
                <button
                  onClick={() => {
                    if (selectedMethod) {
                      setShowCardFunding(true);
                      setCurrentStep(2);
                    }
                  }}
                  disabled={!selectedMethod}
                  className={`flex-1 md:flex-none flex items-center justify-center gap-2 px-6 py-2 rounded-lg text-xs font-bold text-white transition-all ${
                    selectedMethod
                      ? "bg-[#1C2030] hover:bg-[#2D334A]"
                      : "bg-gray-300 cursor-not-allowed"
                  }`}
                >
                  Continue
                  <ChevronRight className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          )}

          {/* Override Toggle (Warning Icon) */}
          <button
            onClick={() =>
              setOverrideStepsCount(internalStepsCount === 3 ? 4 : 3)
            }
            className="absolute top-6 right-16 p-2 text-amber-500 hover:text-amber-600 transition-colors z-50 opacity-20 hover:opacity-100"
            title={`Toggle Flow (Currently ${internalStepsCount} steps)`}
          >
            <AlertTriangle className="w-5 h-5" />
          </button>
        </div>
      </div>
    </TranslationErrorBoundary>
  );

  return createPortal(modalContent, document.body);
};

export default DepositFunds;
