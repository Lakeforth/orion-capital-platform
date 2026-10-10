import { useState } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { toast } from "@/components/ui/sonner";
import {
  useFundAccountFromSpotWalletMutation,
  useFundAccountFromCreditWalletMutation,
  useFundAccountFromBonusWalletMutation,
  useSendAccountToSpotWalletMutation,
} from "@/services/trading-accounts/trading-account-queries";
import type { TradingAccount } from "@/services/trading-accounts/trading-account-types";

type Direction = "fund" | "send";
type Source = "spot" | "credit" | "bonus";

interface FundAccountDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  account: TradingAccount;
  spotWalletBalance: number;
  creditWalletAvailable: number;
  creditWalletLimit: number;
  bonusWalletBalance: number;
  formatCurrency: (value: number) => string;
}

// Moves real money between a client-level wallet (Spot, Credit, or Bonus)
// and one live trading account, via TradingAccountController's
// deposit()/transfer()/fund-from-credit/fund-from-bonus endpoints. The
// "send back" direction only ever targets the Spot Wallet -- repaying
// Credit is a manual admin action (see User::creditWalletOwed()), and
// Bonus is a one-directional grant, neither can be sent back by the client.
export function FundAccountDialog({
  open,
  onOpenChange,
  account,
  spotWalletBalance,
  creditWalletAvailable,
  creditWalletLimit,
  bonusWalletBalance,
  formatCurrency,
}: FundAccountDialogProps) {
  const [direction, setDirection] = useState<Direction>("fund");
  const [source, setSource] = useState<Source>("spot");
  const [amount, setAmount] = useState("");

  const fundFromSpotMutation = useFundAccountFromSpotWalletMutation();
  const fundFromCreditMutation = useFundAccountFromCreditWalletMutation();
  const fundFromBonusMutation = useFundAccountFromBonusWalletMutation();
  const sendMutation = useSendAccountToSpotWalletMutation();
  const isPending =
    fundFromSpotMutation.isPending ||
    fundFromCreditMutation.isPending ||
    fundFromBonusMutation.isPending ||
    sendMutation.isPending;

  // Only Profit + Deposit ever leave an account (never Bonus/Credit) --
  // withdrawable_balance already reflects that server-side, same rule the
  // deposit modal's client-initiated withdrawal path enforces.
  const maxSendable = Math.max(0, account.withdrawable_balance ?? account.available_balance ?? 0);

  const sourceMax: Record<Source, number> = {
    spot: spotWalletBalance,
    credit: creditWalletAvailable,
    bonus: bonusWalletBalance,
  };

  const max = direction === "fund" ? sourceMax[source] : maxSendable;

  const handleClose = () => {
    setAmount("");
    setDirection("fund");
    setSource("spot");
    onOpenChange(false);
  };

  const handleSubmit = () => {
    const numericAmount = Number(amount);

    if (!numericAmount || numericAmount <= 0) {
      toast.error("Enter a valid amount.");
      return;
    }

    if (numericAmount > max) {
      toast.error(
        direction === "fund"
          ? `Amount exceeds your available ${sourceLabel(source)}.`
          : "Amount exceeds what this account can send (Profit + Deposit only, minus margin in use).",
      );
      return;
    }

    const accountId = account.account_id;
    if (!accountId) {
      toast.error("This account is missing an account number.");
      return;
    }

    const mutation =
      direction === "send"
        ? sendMutation
        : source === "credit"
          ? fundFromCreditMutation
          : source === "bonus"
            ? fundFromBonusMutation
            : fundFromSpotMutation;

    mutation.mutate(
      { accountId, amount: numericAmount },
      {
        onSuccess: () => {
          toast.success(
            direction === "fund"
              ? `${formatCurrency(numericAmount)} moved into this account from your ${sourceLabel(source)}.`
              : `${formatCurrency(numericAmount)} sent to your Spot Wallet.`,
          );
          handleClose();
        },
        onError: (err: unknown) => {
          const message =
            (err as { response?: { data?: { message?: string } } })?.response?.data
              ?.message ?? "Transfer failed. Please try again.";
          toast.error(message);
        },
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={handleClose}>
      <DialogContent className="sm:max-w-md p-0 overflow-hidden bg-white border border-gray-200 rounded-2xl">
        <DialogHeader className="px-6 pt-6 pb-4 border-b border-gray-100">
          <DialogTitle className="text-xl font-bold text-gray-900">
            Move Funds
          </DialogTitle>
          <DialogDescription className="text-sm text-gray-500 mt-0.5">
            Account #{account.account_id}
          </DialogDescription>
        </DialogHeader>

        <div className="px-6 py-5 space-y-4">
          {/* Direction toggle */}
          <div className="grid grid-cols-2 gap-2 rounded-xl bg-gray-100 p-1">
            <button
              type="button"
              onClick={() => setDirection("fund")}
              className={`py-2 rounded-lg text-xs font-bold uppercase tracking-wide transition-colors ${
                direction === "fund"
                  ? "bg-white text-[#1C2030] shadow-sm"
                  : "text-gray-500"
              }`}
            >
              Fund Account
            </button>
            <button
              type="button"
              onClick={() => setDirection("send")}
              className={`py-2 rounded-lg text-xs font-bold uppercase tracking-wide transition-colors ${
                direction === "send"
                  ? "bg-white text-[#1C2030] shadow-sm"
                  : "text-gray-500"
              }`}
            >
              Send to Wallet
            </button>
          </div>

          {/* Source toggle, only when funding an account */}
          {direction === "fund" && (
            <div className="grid grid-cols-3 gap-2 rounded-xl bg-gray-100 p-1">
              {(["spot", "credit", "bonus"] as Source[]).map((s) => (
                <button
                  key={s}
                  type="button"
                  onClick={() => setSource(s)}
                  className={`py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-wide transition-colors ${
                    source === s ? "bg-white text-[#1C2030] shadow-sm" : "text-gray-500"
                  }`}
                >
                  {sourceLabel(s)}
                </button>
              ))}
            </div>
          )}

          <p className="text-xs text-gray-500 leading-relaxed">
            {direction === "fund" ? (
              source === "credit" ? (
                `Draw against your Credit Wallet ($${creditWalletLimit.toLocaleString()} limit, ${formatCurrency(creditWalletAvailable)} available) into this account.`
              ) : source === "bonus" ? (
                `Move money from your Bonus Wallet (${formatCurrency(bonusWalletBalance)} available) into this account.`
              ) : (
                `Move money from your Spot Wallet (${formatCurrency(spotWalletBalance)} available) into this account.`
              )
            ) : (
              `Move money from this account back to your Spot Wallet. Only Profit and Deposit can be sent (${formatCurrency(maxSendable)} available) -- Bonus and Credit never leave the account.`
            )}
          </p>

          {/* Amount */}
          <div className="space-y-1.5">
            <div className="flex items-center justify-between">
              <label className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                Amount
              </label>
              <button
                type="button"
                onClick={() => setAmount(String(max))}
                className="text-[11px] font-bold text-[#FF7700] hover:underline"
              >
                Max
              </button>
            </div>
            <div className="relative">
              <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">
                $
              </span>
              <input
                type="number"
                min="0"
                step="any"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
                placeholder="0.00"
                className="w-full rounded-lg border border-gray-200 pl-7 pr-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#FF7700]/40 focus:border-[#FF7700]"
              />
            </div>
          </div>

          {/* Actions */}
          <div className="flex gap-3 pt-1">
            <button
              type="button"
              onClick={handleClose}
              className="flex-1 rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              onClick={handleSubmit}
              disabled={isPending}
              className="flex-1 rounded-xl bg-[#FF7700] py-3 text-sm font-semibold text-white hover:bg-[#e56d00] transition-colors disabled:opacity-60"
            >
              {isPending ? "Processing…" : "Confirm"}
            </button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

function sourceLabel(source: Source): string {
  if (source === "credit") return "Credit Wallet";
  if (source === "bonus") return "Bonus Wallet";
  return "Spot Wallet";
}
