// The account block in the top right: who this terminal is logged in as, and
// on the webtrader, the way to change it.
//
// There used to be a "Select Account" dropdown here. It was removed because
// the switch it offered was dead (its onClick was commented out) and it listed
// the legacy Balance/Bonus/Credit rows, which are client-level wallets rather
// than selectable accounts. This is the real thing: it lists the user's actual
// trading accounts and switching mints a session for the one picked.
//
// Only on the webtrader. A webtrader session is bound to one account at login,
// so switching means a fresh session on that origin (see switchTradingAccount);
// doing that from the client area's embedded trade room would throw the client
// out of the client area, which is not what clicking a balance should do.

import * as React from "react";
import { ChevronDown, Check, Loader2 } from "lucide-react";

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import AccountIdentity from "./account-identity";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { useCurrency } from "@/hooks/useCurrency";
import { isWebtraderHost } from "@/lib/webtrader-host";
import { switchTradingAccount } from "@/lib/trade-room-link";
import axiosInstance from "@/lib/axios";
import { toast } from "sonner";
import { amountTone } from "@/lib/amount-tone";
import { cn } from "@/lib/utils";

interface TradingAccountRow {
  id: string;
  account_id: string | number;
  account_type: string | null;
  name: string | null;
  balance: string | null;
}

const isDemo = (type: string | null) =>
  ["practice", "demo"].includes((type || "").toLowerCase());

export default function AccountSwitcher() {
  const user = useUserStore((state) => state.user);
  const current = useUserStore((state) => state.activeTradingAccount);
  const enablePlan = useSiteSettingsStore(
    (state) => state.settings?.enable_plan === true,
  );
  const { formatCurrency } = useCurrency();

  const [open, setOpen] = React.useState(false);
  const [accounts, setAccounts] = React.useState<TradingAccountRow[] | null>(null);
  const [switchingTo, setSwitchingTo] = React.useState<string | null>(null);

  const onWebtrader = isWebtraderHost();

  // Fetched when the menu opens rather than on mount: most sessions never
  // open it, and a terminal should not spend a request on load for a list
  // nobody asked for.
  React.useEffect(() => {
    if (!open) return;

    let cancelled = false;

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

  // What the switcher is for: which account this is. Live or demo and the
  // account number on top, whose it is underneath -- the client reads the
  // name to know they are in the right place, and the badge and number to
  // know which of their accounts they are in.
  const summary = (
    <span className="flex min-w-0 flex-col items-start gap-0.5">
      <span className="flex items-center gap-2">
        <AccountIdentity variant="status" />
        {current?.account_id && (
          <span className="rounded border border-border px-1 py-0.5 text-[10px] font-semibold text-muted-foreground">
            #{current.account_id}
          </span>
        )}
        {enablePlan && (
          <span className="text-[10px] font-bold uppercase tracking-wide text-primary">
            {user?.account_type?.title || "STANDARD"}
          </span>
        )}
      </span>
      <AccountIdentity variant="name" />
    </span>
  );

  // The balance sits outside the switcher entirely, with a rule and real space
  // on either side. It is the number clients scan for and it was being read as
  // part of whatever it was pressed against.
  const balance = (
    <>
      {/* Grey at zero rather than the brand green: an empty account is not
          good news, and green on $0.00 is the terminal congratulating a client
          who has not funded yet. */}
      <span
        className={cn(
          "whitespace-nowrap text-lg font-bold leading-none",
          amountTone(Number(user?.balance) || 0, { positive: "text-primary" })
        )}
      >
        {formatCurrency(user?.balance || 0)}
      </span>
      {/* bg-border is all but invisible on this toolbar -- it resolves to the
          same value as the background. muted-foreground adapts to both themes. */}
      <span className="h-8 w-px shrink-0 bg-muted-foreground/30" aria-hidden="true" />
    </>
  );

  if (!onWebtrader) {
    return (
      <div className="flex items-center gap-4">
        {balance}
        {summary}
      </div>
    );
  }

  const choose = async (account: TradingAccountRow) => {
    const id = String(account.account_id);

    if (id === String(current?.account_id ?? "")) {
      setOpen(false);
      return;
    }

    setSwitchingTo(id);
    // On success the browser navigates away, so nothing clears the spinner.
    const ok = await switchTradingAccount(id);
    if (!ok) setSwitchingTo(null);
  };

  return (
    <div className="flex items-center gap-4">
      {balance}

      <DropdownMenu open={open} onOpenChange={setOpen}>
        <DropdownMenuTrigger asChild>
          <button
            type="button"
            aria-label="Switch trading account"
            className="flex items-center gap-2 rounded-md px-2 py-1 transition-colors hover:bg-muted/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-trading-accent"
          >
            {summary}
            <ChevronDown
              className={`h-4 w-4 shrink-0 text-muted-foreground transition-transform ${
                open ? "rotate-180" : ""
              }`}
            />
          </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" className="w-72">
          <DropdownMenuLabel>Switch account</DropdownMenuLabel>
          <DropdownMenuSeparator />

          {accounts === null && (
            <div className="flex items-center justify-center py-6 text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
            </div>
          )}

          {accounts?.length === 0 && (
            <p className="px-2 py-4 text-sm text-muted-foreground">
              You do not have a trading account yet.
            </p>
          )}

          {accounts?.map((account) => {
            const id = String(account.account_id);
            const isCurrent = id === String(current?.account_id ?? "");

            return (
              <DropdownMenuItem
                key={account.id || id}
                disabled={switchingTo !== null}
                // Keeps the menu up while the switch is in flight, so it does
                // not blink shut and look like nothing happened.
                onSelect={(event) => {
                  event.preventDefault();
                  choose(account);
                }}
                className="flex items-center justify-between gap-3 py-2"
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
                  <Check className="h-4 w-4 shrink-0 text-muted-foreground" />
                ) : null}
              </DropdownMenuItem>
            );
          })}
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
