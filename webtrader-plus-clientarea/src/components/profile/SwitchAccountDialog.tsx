// Pick which trading account the webtrader is showing.
//
// A webtrader session is bound to one account at login, so switching means a
// fresh session for the other account (see switchTradingAccount). Opened from
// the avatar menu, controlled rather than trigger-wrapped for the same reason
// the profile dialog is -- the menu unmounts its items as it closes.

import * as React from "react";
import { toast } from "sonner";
import { Check, Loader2 } from "lucide-react";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import axiosInstance from "@/lib/axios";
import useUserStore from "@/store/userStore";
import { switchTradingAccount } from "@/lib/trade-room-link";

interface TradingAccountRow {
  id: string;
  account_id: string | number;
  account_type: string | null;
  name: string | null;
  balance: string | null;
  base_currency: string | null;
  is_active: boolean;
}

interface Props {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export default function SwitchAccountDialog({ open, onOpenChange }: Props) {
  const current = useUserStore((state) => state.activeTradingAccount);
  const [accounts, setAccounts] = React.useState<TradingAccountRow[] | null>(null);
  const [switchingTo, setSwitchingTo] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (!open) return;

    let cancelled = false;
    setAccounts(null);

    (async () => {
      try {
        const response = await axiosInstance.get("/trading-accounts");
        if (!cancelled) setAccounts(response.data?.data ?? []);
      } catch {
        if (!cancelled) {
          setAccounts([]);
          toast.error("Could not load your trading accounts.");
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [open]);

  const choose = async (account: TradingAccountRow) => {
    const id = String(account.account_id);

    if (id === String(current?.account_id ?? "")) {
      onOpenChange(false);
      return;
    }

    setSwitchingTo(id);
    // On success the browser navigates away, so nothing resets the spinner.
    const ok = await switchTradingAccount(id);
    if (!ok) setSwitchingTo(null);
  };

  const isDemo = (type: string | null) =>
    ["practice", "demo"].includes((type || "").toLowerCase());

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Switch Account</DialogTitle>
          <DialogDescription>
            Choose which trading account to open in the terminal.
          </DialogDescription>
        </DialogHeader>

        {accounts === null && (
          <div className="flex items-center justify-center py-10 text-muted-foreground">
            <Loader2 className="h-5 w-5 animate-spin" />
          </div>
        )}

        {accounts?.length === 0 && (
          <p className="py-6 text-sm text-muted-foreground">
            You do not have a trading account yet.
          </p>
        )}

        <div className="flex flex-col gap-2">
          {accounts?.map((account) => {
            const id = String(account.account_id);
            const isCurrent = id === String(current?.account_id ?? "");

            return (
              <button
                key={account.id || id}
                type="button"
                onClick={() => choose(account)}
                disabled={switchingTo !== null}
                className="flex items-center justify-between gap-3 rounded-lg border border-border px-3 py-2.5 text-left transition-colors hover:bg-muted/60 disabled:opacity-60"
              >
                <span className="flex min-w-0 flex-col">
                  <span className="flex items-center gap-2">
                    <span
                      className={`rounded px-1 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white ${
                        isDemo(account.account_type) ? "bg-amber-500" : "bg-green-600"
                      }`}
                    >
                      {isDemo(account.account_type) ? "Demo" : "Live"}
                    </span>
                    <span className="truncate text-sm font-semibold">#{id}</span>
                  </span>
                  <span className="truncate text-xs text-muted-foreground">
                    {account.name || "Trading account"}
                    {account.balance ? ` · ${account.balance}` : ""}
                  </span>
                </span>

                {switchingTo === id ? (
                  <Loader2 className="h-4 w-4 shrink-0 animate-spin" />
                ) : isCurrent ? (
                  <span className="flex shrink-0 items-center gap-1 text-xs text-muted-foreground">
                    <Check className="h-4 w-4" /> Current
                  </span>
                ) : null}
              </button>
            );
          })}
        </div>
      </DialogContent>
    </Dialog>
  );
}
