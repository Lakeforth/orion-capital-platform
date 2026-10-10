import { useState } from "react";
import { AxiosError } from "axios";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { toast } from "@/components/ui/sonner";
import { useChangeTradingAccountPasswordMutation } from "@/services/trading-accounts/trading-account-queries";
import type { TradingAccount } from "@/services/trading-accounts/trading-account-types";

interface ChangePasswordDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  account: TradingAccount;
}

// Lets a client set their own memorable password for one trading account,
// replacing the random system-generated one shown on the card. Calls
// TradingAccountController::changePassword() -- stores plain, same as the
// generated password already was, so webtrader/main-app login both keep
// working against whatever the client picks here.
export function ChangePasswordDialog({ open, onOpenChange, account }: ChangePasswordDialogProps) {
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");

  const mutation = useChangeTradingAccountPasswordMutation();

  const reset = () => {
    setPassword("");
    setConfirmPassword("");
  };

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();

    if (password.length < 6) {
      toast.error("Password must be at least 6 characters");
      return;
    }

    if (password !== confirmPassword) {
      toast.error("Passwords do not match");
      return;
    }

    if (!account.account_id) return;

    mutation.mutate(
      { accountId: account.account_id, password },
      {
        onSuccess: () => {
          toast.success("Password updated");
          reset();
          onOpenChange(false);
        },
        onError: (error) => {
          const axiosError = error as AxiosError<{ message?: string }>;
          toast.error(axiosError.response?.data?.message || "Failed to update password");
        },
      },
    );
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(next) => {
        if (!next) reset();
        onOpenChange(next);
      }}
    >
      <DialogContent className="sm:max-w-md p-0 overflow-hidden bg-white border border-gray-200 rounded-2xl">
        <DialogHeader className="px-6 pt-6 pb-4 border-b border-gray-100">
          <DialogTitle className="text-xl font-bold text-gray-900">Change account password</DialogTitle>
          <DialogDescription className="text-sm text-gray-500 mt-0.5">
            Set a password you'll actually remember for account {account.account_id}. This replaces the current
            one immediately.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="px-6 py-5 space-y-4">
          <div>
            <label className="text-xs font-semibold uppercase tracking-wide text-gray-500 block mb-1.5">
              New password
            </label>
            <input
              type="password"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              placeholder="At least 6 characters"
              className="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg outline-none focus:border-[#FF7700] text-gray-900"
              autoFocus
            />
          </div>

          <div>
            <label className="text-xs font-semibold uppercase tracking-wide text-gray-500 block mb-1.5">
              Confirm new password
            </label>
            <input
              type="password"
              value={confirmPassword}
              onChange={(event) => setConfirmPassword(event.target.value)}
              placeholder="Re-enter your new password"
              className="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg outline-none focus:border-[#FF7700] text-gray-900"
            />
          </div>

          <button
            type="submit"
            disabled={mutation.isPending}
            className="w-full py-2.5 rounded-lg bg-[#FF7700] text-white text-xs font-bold uppercase tracking-wide hover:bg-[#e56d00] transition-colors disabled:opacity-60"
          >
            {mutation.isPending ? "Updating…" : "Update password"}
          </button>
        </form>
      </DialogContent>
    </Dialog>
  );
}
