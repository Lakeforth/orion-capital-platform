import React, { useState, useEffect } from "react";
import {
  Lock,
  ChevronLeft,
  ChevronRight,
  ArrowLeft,
  Copy,
  Check,
} from "lucide-react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { toast } from "sonner";

import { useDepositMutation } from "@/services/deposit/deposit-queries";
import useSiteSettingsStore from "@/store/siteSettingStore";
import useUserStore from "@/store/userStore";
import type { TradingAccount } from "@/services/trading-accounts/trading-account-types";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

// Validation Schema
const cardFundingSchema = z.object({
  amount: z
    .string()
    .min(1, "Amount is required")
    .refine((val) => !isNaN(Number(val)), "Amount must be a number")
    .refine((val) => Number(val) >= 5, "Minimum amount is $5")
    .refine((val) => Number(val) <= 100000, "Maximum amount is $100,000"),
  // Card number, expiry and CVV are deliberately absent. Payment details are
  // entered on the provider's own hosted checkout page, never here -- this
  // form collects an amount and a name and then hands off.
  nameOnCard: z
    .string()
    .min(2, "Name must be at least 2 characters")
    .max(50, "Name must not exceed 50 characters"),
});

type CardFundingFormData = z.infer<typeof cardFundingSchema>;

interface CardFundingProps {
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

const CardFunding: React.FC<CardFundingProps> = ({
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

  const { settings } = useSiteSettingsStore();
  const token = useUserStore((state) => state.token);
  const { mutate, isPending } = useDepositMutation();

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

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors, isValid },
    reset,
  } = useForm<CardFundingFormData>({
    resolver: zodResolver(cardFundingSchema),
    mode: "onChange",
  });

  const amount = watch("amount");

  const handleFinalSubmit = (data: CardFundingFormData) => {
    mutate(
      {
        amount: Number(data.amount),
        method: "card",
        card_holder_name: data.nameOnCard,
        destination,
      },
      {
        onSuccess: (response) => {
          // A tenant on hosted checkout gets a redirect_url back: the deposit
          // row exists but nothing has been paid yet, so send the browser to
          // the provider rather than showing a success state for a payment
          // that has not happened.
          const redirectUrl = response.data.redirect_url;

          if (redirectUrl) {
            toast.success("Taking you to secure checkout", {
              description: "You will complete your payment on our provider's page.",
            });
            window.location.assign(redirectUrl);

            return;
          }

          toast.success("Payment submitted successfully!", {
            description: "Your deposit is awaiting admin approval.",
          });
          setOrderId(response.data.order_id);
          setIsSubmitted(true);
          reset();
        },
        onError: (error) => {
          const errorMessage =
            error instanceof Error
              ? error.message
              : "Payment submission failed";
          toast.error("Submission Failed", {
            description: errorMessage,
          });
        },
      },
    );
  };

  return (
    <div className="space-y-4 md:space-y-4 text-[#1C2030] min-h-[400px] w-full">
      {/* Step 1: Amount Selection */}
      {currentStep === 1 && !isSubmitted && (
        <div className="space-y-4">
          {/* Security Box */}
          <div className="flex items-start gap-4 bg-[#EBF5FF] p-3 rounded-xl border border-[#D1E9FF]">
            <div className="bg-white p-1.5 rounded-lg shadow-sm border border-[#D1E9FF] mt-0.5">
              <Lock className="w-3.5 h-3.5 text-[#1C2030]" />
            </div>
            <p className="text-[10px] font-bold text-[#1F2937] leading-relaxed">
              We use secure, encrypted technology to protect your information.
              All data is handled in compliance with international security
              standards.
            </p>
          </div>

          <div className="space-y-4">
            {/* Quick Select */}
            <div className="space-y-2">
              <p className="text-[10px] font-black text-[#374151] uppercase tracking-widest">
                Quick Select Amount
              </p>
              <div className="grid grid-cols-3 gap-3">
                {["250", "1000", "5000"].map((value) => (
                  <button
                    key={value}
                    type="button"
                    onClick={() => {
                      setValue("amount", value, { shouldValidate: true });
                    }}
                    className={`py-3 rounded-xl border-2 text-sm font-black transition-all ${
                      amount === value
                        ? "border-[#1C2030] bg-white shadow-md"
                        : "border-gray-100 bg-white hover:border-gray-200"
                    }`}
                  >
                    ${Number(value).toLocaleString()}
                  </button>
                ))}
              </div>
            </div>

            {/* Amount Input */}
            <div className="space-y-1.5">
              <label className="text-[10px] font-black text-[#374151] uppercase tracking-widest">
                Amount (USD)
              </label>
              <div className="relative group">
                <input
                  type="text"
                  {...register("amount")}
                  placeholder="0.00"
                  className={`w-full bg-white border border-gray-100 rounded-2xl pl-10 pr-6 py-4 shadow-sm text-[32px] font-black text-[#1C2030] focus:ring-0 transition-all placeholder:text-gray-300 ${
                    errors.amount ? "text-red-500" : ""
                  }`}
                />
                <div className="absolute left-5 top-1/2 -translate-y-1/2 pointer-events-none">
                  <span className="text-[24px] text-gray-300 font-bold">$</span>
                </div>
              </div>
              {errors.amount && (
                <p className="text-[10px] font-bold text-red-500 mt-1">
                  {errors.amount.message}
                </p>
              )}
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

            {/* Payment Method Preview */}
            <div className="space-y-1.5">
              <label className="text-[10px] font-black text-[#374151] uppercase tracking-widest">
                Payment Method
              </label>
              <div className="flex items-center justify-between p-3 rounded-2xl border border-gray-100 bg-white">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-[#1C2030] rounded-xl flex items-center justify-center">
                    <svg
                      className="w-5 h-5 text-white"
                      viewBox="0 0 24 24"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <rect
                        x="2"
                        y="5"
                        width="20"
                        height="14"
                        rx="2"
                        stroke="currentColor"
                        strokeWidth="2"
                      />
                      <path
                        d="M2 10H22"
                        stroke="currentColor"
                        strokeWidth="2"
                      />
                    </svg>
                  </div>
                  <div>
                    <p className="text-xs font-black text-[#1C2030]">
                      Card Payment
                    </p>
                    <p className="text-[10px] text-gray-400 font-bold">
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={onChangeMethod}
                  className="text-[10px] font-black text-[#FF7700] uppercase tracking-widest hover:underline"
                >
                  Change
                </button>
              </div>
            </div>
          </div>

          {/* Footer inside CardFunding for step 1 */}
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
                onClick={() => setCurrentStep(currentStep + 1)}
                disabled={!amount || !!errors.amount}
                className={`flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white transition-all ${
                  amount && !errors.amount
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

      {/* Step 2: Payment Details */}
      {currentStep === 2 && !isSubmitted && (
        <form
          onSubmit={handleSubmit((data) => {
            if (stepsCount === 3) {
              handleFinalSubmit(data);
            } else {
              setCurrentStep(3);
            }
          })}
          className="space-y-4 w-full"
        >
          {/* Security Box */}
          <div className="flex items-start gap-4 bg-[#EBF5FF] p-3 rounded-xl border border-[#D1E9FF]">
            <div className="bg-white p-2 rounded-lg shadow-sm border border-[#D1E9FF] mt-0.5">
              <Lock className="w-4 h-4 text-[#3B82F6]" />
            </div>
            <p className="text-[10px] font-bold text-[#1F2937] leading-relaxed">
              <span className="text-[#1C2030] font-black">
                {settings?.name || "The platform"}
              </span>{" "}
              uses secure, encrypted technology. Data entered here is safe and
              handled with banking-grade security protocols.
            </p>
          </div>

          <div className="space-y-4">
            {/* Cardholder Name */}
            <div className="space-y-1.5">
              <label className="text-[10px] font-black text-[#374151] uppercase tracking-widest">
                Cardholder Name
              </label>
              <input
                type="text"
                placeholder="EX: JOHN DOE"
                {...register("nameOnCard")}
                className={`w-full px-4 py-3 rounded-xl border-2 ${
                  errors.nameOnCard
                    ? "border-red-500 focus:border-red-500"
                    : "border-gray-100 focus:border-[#1C2030]"
                } bg-[#F9FAFB] text-[#1C2030] font-black uppercase focus:outline-none transition-all placeholder:text-gray-300 text-sm`}
              />
              {errors.nameOnCard && (
                <p className="text-[10px] font-bold text-red-500 mt-1">
                  {errors.nameOnCard.message}
                </p>
              )}
            </div>

            {/* Where the card details are actually entered. Nothing on this
                page asks for a card number -- that happens on the payment
                provider's own PCI-compliant checkout, which is the point. */}
            <div className="flex items-start gap-3 rounded-xl border-2 border-gray-100 bg-[#F9FAFB] px-4 py-3">
              <Lock className="mt-0.5 h-4 w-4 shrink-0 text-[#1C2030]" />
              <div className="space-y-1">
                <p className="text-xs font-black text-[#1C2030]">
                  Card details are entered on our payment provider's page
                </p>
                <p className="text-[10px] font-bold leading-relaxed text-[#6B7280]">
                  When you continue, you will be taken to a secure checkout to pay by card.
                  Your balance is credited automatically once the payment is confirmed.
                </p>
              </div>
            </div>

            {/* Save Card Checkbox */}
            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="saveCard"
                className="w-4 h-4 border-2 border-gray-200 rounded cursor-pointer accent-[#1C2030] hover:border-gray-300 transition-colors"
              />
              <label
                htmlFor="saveCard"
                className="text-xs font-bold text-[#4B5563] cursor-pointer"
              >
                Save card details for future payments
              </label>
            </div>
          </div>

          {/* Footer inside CardFunding for step 2 */}
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
                disabled={isPending}
              >
                <ChevronLeft className="w-3.5 h-3.5" />
                Back
              </button>
              <button
                type="submit"
                disabled={!isValid || isPending}
                className={`flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white transition-all ${
                  isValid && !isPending
                    ? "bg-[#1C2030] hover:bg-[#2D334A]"
                    : "bg-[#C4C6D0] cursor-not-allowed"
                }`}
              >
                Continue
                <ChevronRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </form>
      )}

      {/* Step 3: Payment Review */}
      {currentStep === 3 && stepsCount === 4 && !isSubmitted && (
        <div className="flex gap-8">
          {/* Left Side: Card & Info */}
          <div className="w-[400px] space-y-4">
            {/* Gradient Card */}
            <div
              className="h-[210px] rounded-[20px] p-6 text-white flex flex-col justify-between relative shadow-xl overflow-hidden"
              style={{
                background:
                  "linear-gradient(135deg, #00234BF2 0%, #003B7EE5 100%)",
              }}
            >
              <div className="flex justify-between items-start">
                <div className="space-y-0.5">
                  <p className="text-[9px] font-bold text-white/60 uppercase tracking-widest">
                    Selected Card
                  </p>
                  <div className="w-8 h-5.5 bg-white/10 rounded flex items-center justify-center border border-white/20">
                    <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none">
                      <rect
                        x="2"
                        y="5"
                        width="20"
                        height="14"
                        rx="2"
                        stroke="currentColor"
                        strokeWidth="2"
                      />
                      <path
                        d="M2 10H22"
                        stroke="currentColor"
                        strokeWidth="2"
                      />
                    </svg>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-[9px] font-bold text-white/60 uppercase tracking-widest">
                    Paying By
                  </p>
                  <p className="text-lg font-black tracking-tight">CARD</p>
                </div>
              </div>

              <div className="space-y-3">
                {/* No card digits to show -- they are never entered here. */}
                <div className="flex items-center gap-2 px-1">
                  <Lock className="h-3.5 w-3.5 text-white/60" />
                  <p className="text-xs font-bold text-white/80">
                    Secure checkout on our payment provider's page
                  </p>
                </div>

                <div className="flex justify-between items-end border-t border-white/10 pt-3">
                  <div>
                    <p className="text-[8px] font-bold text-white/40 uppercase tracking-widest mb-0.5">
                      Card Holder
                    </p>
                    <p className="text-xs font-bold uppercase tracking-wider">
                      {watch("nameOnCard") || "JOHN DOE"}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-[8px] font-bold text-white/40 uppercase tracking-widest mb-0.5">
                      Amount
                    </p>
                    <p className="text-xs font-bold tracking-wider">
                      ${Number(amount || 0).toLocaleString()}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {/* Change Card Button */}
            <button
              type="button"
              onClick={() => setCurrentStep(2)}
              className="w-full py-3 rounded-xl border border-gray-100 bg-white text-xs font-bold text-[#6B7280] hover:bg-gray-50 transition-all flex items-center justify-center gap-2 shadow-sm"
            >
              <ArrowLeft className="w-3.5 h-3.5" />
              Use another card instead
            </button>

            {/* Encryption Box */}
            <div className="flex items-center gap-3 bg-[#F3F4F6] p-3 rounded-xl border border-gray-100">
              <div className="bg-[#1C2030] p-1.5 rounded-lg shadow-sm">
                <Lock className="w-3.5 h-3.5 text-white" />
              </div>
              <div>
                <p className="text-[9px] font-black text-[#1C2030] uppercase tracking-wider">
                  Bank-Level Encryption Active
                </p>
                <p className="text-[9px] text-[#6B7280] font-bold">
                  Your connection is secured with 256-bit encryption
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
                    {Number(watch("amount") || 0).toLocaleString(undefined, {
                      minimumFractionDigits: 2,
                    })}
                  </span>
                </div>

                <div className="flex justify-between items-center">
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-bold text-[#6B7280]">
                      Service Fee
                    </span>
                    <span className="bg-gray-100 text-[8px] font-bold px-1 py-0.5 rounded text-[#6B7280]">
                      0%
                    </span>
                  </div>
                  <span className="text-xl font-black text-[#00A041]">
                    $0.00
                  </span>
                </div>

                <div className="border-t border-gray-100 pt-4 space-y-1">
                  <p className="text-[10px] font-black text-[#374151] uppercase tracking-widest">
                    Total to Charge
                  </p>
                  <p className="text-[44px] font-black text-[#1C2030] leading-none tracking-tight">
                    $
                    {Number(watch("amount") || 0).toLocaleString(undefined, {
                      minimumFractionDigits: 2,
                    })}
                  </p>
                </div>
              </div>
            </div>

            <p className="text-[8px] font-bold text-[#6B7280] leading-relaxed max-w-[340px]">
              * By clicking "Confirm & Pay", you agree to our Terms of Service
              and acknowledge that funds will be debited immediately from the
              selected account.
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
                onClick={() => setCurrentStep(2)}
                className="flex items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-xs font-bold text-[#1C2030] hover:bg-gray-50 transition-colors"
                disabled={isPending}
              >
                <ChevronLeft className="w-3.5 h-3.5" />
                Back
              </button>
              <button
                type="button"
                onClick={() => handleFinalSubmit(watch())}
                disabled={isPending}
                className={`flex items-center gap-2 px-10 py-2.5 rounded-xl text-xs font-bold text-white transition-all ${
                  !isPending
                    ? "bg-[#2EB82E] hover:bg-[#26a026] shadow-lg shadow-green-500/20"
                    : "bg-[#C4C6D0] cursor-not-allowed"
                }`}
              >
                {isPending ? "Processing..." : "Confirm & Pay"}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Success State (for 3-step or 4-step flows) */}
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
            <p className="text-[#374151] text-xs leading-relaxed max-w-[380px]">
              Your funds have been successfully added to your account. Your
              updated balance is now available for trading.
            </p>
          </div>

          {/* Transaction Info Box */}
          <div className="w-full bg-[#F9FAFB] rounded-xl p-4 space-y-3">
            <div className="flex justify-between items-center">
              <span className="text-[11px] font-bold text-[#6B7280]">
                Amount Deposited
              </span>
              <span className="text-xs font-bold text-[#00A041]">
                +{" "}
                {Number(watch("amount")).toLocaleString(undefined, {
                  minimumFractionDigits: 2,
                })}{" "}
                USD
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
                0.00 USD (Sponsored)
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
                reset();
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

export default CardFunding;
