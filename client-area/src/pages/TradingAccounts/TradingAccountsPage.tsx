import { useState } from "react";
import { Link } from "react-router-dom";
import { Plus, Eye, EyeOff, Copy, Loader2, UsersRound, Trash2, ArrowLeftRight, KeyRound } from "lucide-react";
import { toast } from "@/components/ui/sonner";
import {
  useTradingAccountsQuery,
  useCreateTradingAccountMutation,
} from "@/services/trading-accounts/trading-account-queries";
import type { TradingAccount } from "@/services/trading-accounts/trading-account-types";
import { usePartnersQuery, useDeletePartnerMutation } from "@/services/partners/partner-queries";
import { AddPartnerDialog } from "@/components/AddPartnerDialog";
import { FundAccountDialog } from "@/components/trading-accounts/FundAccountDialog";
import { ChangePasswordDialog } from "@/components/trading-accounts/ChangePasswordDialog";
import { openTradeRoomInNewTab } from "@/lib/trade-room-link";
import { usePortfolioSummaryQuery } from "@/services/portfolio/portfolio-queries";
import { useCurrency } from "@/hooks/useCurrency";
import { parseApiError } from "@/lib/api-error";

type TabType = "live" | "demo" | "joint";

type AccountType = "live" | "practice";

const ACCOUNT_LABEL: Record<AccountType, string> = {
  live: "Live",
  practice: "Demo",
};

// Plan keys the create endpoint can report, phrased for the customer.
const LIMIT_NOUN: Record<string, string> = {
  trading_accounts_max: "live trading accounts",
  practice_accounts_max: "demo accounts",
};

function AccountCard({
  account,
  spotWalletBalance,
  creditWalletAvailable,
  creditWalletLimit,
  bonusWalletBalance,
  formatCurrency,
}: {
  account: TradingAccount;
  spotWalletBalance: number;
  creditWalletAvailable: number;
  creditWalletLimit: number;
  bonusWalletBalance: number;
  formatCurrency: (value: number) => string;
}) {
  const [showPassword, setShowPassword] = useState(false);
  const [isLaunching, setIsLaunching] = useState(false);
  const [fundDialogOpen, setFundDialogOpen] = useState(false);
  const [changePasswordOpen, setChangePasswordOpen] = useState(false);

  const handleLaunch = async () => {
    if (!account.account_id) return;
    setIsLaunching(true);
    try {
      await openTradeRoomInNewTab(account.account_id);
    } finally {
      setIsLaunching(false);
    }
  };

  const copyPassword = () => {
    if (!account.password) return;
    navigator.clipboard.writeText(account.password);
    toast.success("Password copied");
  };

  return (
    <div className="bg-white rounded-xl p-5 border border-[#00234B]/10 flex flex-col gap-4">
      <div className="flex items-start justify-between">
        <div className="flex items-center gap-2">
          <div className={`h-2 w-2 rounded-full ${account.is_active ? "bg-emerald-500" : "bg-gray-300"}`} />
          <span className="text-[#00234B] text-sm font-bold uppercase tracking-tight">
            {account.name || `${account.account_type} account`}
          </span>
        </div>
        <span className="text-[10px] font-bold uppercase tracking-wide rounded bg-[#00234B]/5 text-[#00234B]/70 px-2 py-1">
          {account.account_type}
        </span>
      </div>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <p className="text-[10px] font-medium uppercase tracking-wide text-[#00234B]/50 mb-1">Balance</p>
          <p className="text-[#00234B] text-base font-bold">${account.balance.toLocaleString()}</p>
        </div>
        <div>
          <p className="text-[10px] font-medium uppercase tracking-wide text-[#00234B]/50 mb-1">Account No</p>
          <p className="text-[#00234B] text-sm font-semibold">{account.account_id ?? "—"}</p>
        </div>
        <div>
          <p className="text-[10px] font-medium uppercase tracking-wide text-[#00234B]/50 mb-1">Password</p>
          <div className="flex items-center gap-1.5">
            <p className="text-[#00234B] text-sm font-semibold">
              {showPassword ? account.password ?? "—" : "••••••••"}
            </p>
            {account.password && (
              <>
                <button type="button" onClick={() => setShowPassword((v) => !v)} className="text-[#00234B]/50 hover:text-[#00234B]">
                  {showPassword ? <EyeOff size={13} /> : <Eye size={13} />}
                </button>
                <button type="button" onClick={copyPassword} className="text-[#00234B]/50 hover:text-[#00234B]">
                  <Copy size={13} />
                </button>
              </>
            )}
            {account.account_id && (
              <button
                type="button"
                onClick={() => setChangePasswordOpen(true)}
                title="Set your own password"
                className="text-[#00234B]/50 hover:text-[#00234B]"
              >
                <KeyRound size={13} />
              </button>
            )}
          </div>
        </div>
        <div>
          <p className="text-[10px] font-medium uppercase tracking-wide text-[#00234B]/50 mb-1">Currency</p>
          <p className="text-[#00234B] text-sm font-semibold uppercase">{account.base_currency}</p>
        </div>
      </div>

      <div className="flex gap-2">
        {account.account_id && (
          <Link
            to={`/main/trading-accounts/${account.account_id}`}
            className="flex-1 whitespace-nowrap px-3 py-2.5 rounded-lg border border-[#00234B]/15 text-[#00234B] text-xs font-bold uppercase tracking-wide hover:bg-[#00234B]/5 transition-colors text-center"
          >
            View Details
          </Link>
        )}
        {account.account_type === "live" && (
          <button
            type="button"
            onClick={() => setFundDialogOpen(true)}
            title="Move funds between this account and your wallets"
            className="px-3 py-2.5 rounded-lg border border-[#00234B]/15 text-[#00234B] hover:bg-[#00234B]/5 transition-colors"
          >
            <ArrowLeftRight size={14} />
          </button>
        )}
        <button
          type="button"
          onClick={handleLaunch}
          disabled={isLaunching}
          className="flex-1 whitespace-nowrap px-3 py-2.5 rounded-lg bg-[#FF7700] text-white text-xs font-bold uppercase tracking-wide hover:bg-[#e56d00] transition-colors disabled:opacity-60"
        >
          {isLaunching ? "Opening…" : "Launch WebTrader"}
        </button>
      </div>

      {account.account_type === "live" && (
        <FundAccountDialog
          open={fundDialogOpen}
          onOpenChange={setFundDialogOpen}
          account={account}
          spotWalletBalance={spotWalletBalance}
          creditWalletAvailable={creditWalletAvailable}
          creditWalletLimit={creditWalletLimit}
          bonusWalletBalance={bonusWalletBalance}
          formatCurrency={formatCurrency}
        />
      )}

      <ChangePasswordDialog open={changePasswordOpen} onOpenChange={setChangePasswordOpen} account={account} />
    </div>
  );
}

function JointTab() {
  const { data: partners = [], isLoading } = usePartnersQuery();
  const { mutate: deletePartner } = useDeletePartnerMutation();
  const [addOpen, setAddOpen] = useState(false);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-16">
        <Loader2 className="h-6 w-6 animate-spin text-[#FF7700]" />
      </div>
    );
  }

  return (
    <div className="max-w-3xl">
      <div className="bg-white rounded-xl border border-[#00234B]/10 p-6">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h3 className="text-[#00234B] text-base font-bold">Account Partners</h3>
            <p className="text-[#00234B]/70 text-sm mt-0.5">People with joint access to your account details.</p>
          </div>
          <button
            type="button"
            onClick={() => setAddOpen(true)}
            className="flex items-center gap-2 rounded-lg bg-[#FF7700] px-4 py-2 text-sm font-bold text-white hover:bg-[#e56d00]"
          >
            <Plus size={16} /> Add a Partner
          </button>
        </div>

        {partners.length === 0 ? (
          <div className="text-center py-10 border-t border-[#00234B]/10">
            <UsersRound className="mx-auto text-[#00234B]/30 mb-2" size={28} />
            <p className="text-[#00234B]/70 text-sm">No partners added yet.</p>
          </div>
        ) : (
          <div className="flex flex-col gap-3 border-t border-[#00234B]/10 pt-4">
            {partners.map((partner) => (
              <div key={partner.id} className="flex items-center justify-between p-4 border border-[#00234B]/10 rounded-lg">
                <div>
                  <p className="text-[#00234B] text-sm font-bold">{partner.name}</p>
                  <p className="text-[#00234B]/60 text-xs mt-0.5">{partner.email}</p>
                </div>
                <button
                  type="button"
                  onClick={() => {
                    if (confirm(`Remove ${partner.name} as a partner?`)) {
                      deletePartner(partner.id, {
                        onSuccess: () => toast.success("Partner removed"),
                        onError: () => toast.error("Failed to remove partner."),
                      });
                    }
                  }}
                  className="text-red-500 text-xs font-bold flex items-center gap-1 hover:underline"
                >
                  <Trash2 size={13} /> Remove
                </button>
              </div>
            ))}
          </div>
        )}

        <Link to="/main/partners" className="inline-block mt-4 text-[#FF7700] text-xs font-bold hover:underline">
          Manage all partners →
        </Link>
      </div>

      <AddPartnerDialog open={addOpen} onOpenChange={setAddOpen} />
    </div>
  );
}

export default function TradingAccountsPage() {
  const [tab, setTab] = useState<TabType>("live");
  // Tracked per type so opening a demo account doesn't disable the live button.
  const [pendingType, setPendingType] = useState<AccountType | null>(null);
  const [limitNotice, setLimitNotice] = useState<{ tab: TabType; text: string } | null>(null);
  const { data: accounts = [], isLoading } = useTradingAccountsQuery();
  const { mutate: createAccount } = useCreateTradingAccountMutation();
  const { data: portfolioSummary } = usePortfolioSummaryQuery();
  const { formatCurrency } = useCurrency();
  const spotWalletBalance = portfolioSummary?.spot_wallet_balance ?? 0;
  const creditWalletAvailable = portfolioSummary?.credit_wallet_available ?? 0;
  const creditWalletLimit = portfolioSummary?.credit_wallet_limit ?? 0;
  const bonusWalletBalance = portfolioSummary?.bonus_wallet_balance ?? 0;

  const liveAccounts = accounts.filter((a) => a.account_type === "live");
  const demoAccounts = accounts.filter((a) => a.account_type === "practice");

  const handleCreate = (accountType: AccountType) => {
    const label = ACCOUNT_LABEL[accountType];

    setPendingType(accountType);
    setLimitNotice(null);

    createAccount(
      { account_type: accountType },
      {
        onSuccess: (account) => {
          toast.success(
            account?.account_id
              ? `${label} account ${account.account_id} created`
              : `${label} account created`,
          );
        },
        onError: (err: unknown) => {
          const info = parseApiError(
            err,
            `We couldn't open your ${label.toLowerCase()} account. Please try again.`,
          );

          // A plan cap is the one failure the customer can act on, so spell out
          // which allowance ran out rather than showing the bare backend string.
          if (info.type === "upgrade_required") {
            const noun = (info.limitKey && LIMIT_NOUN[info.limitKey]) ?? "trading accounts";
            const headline =
              typeof info.limit === "number" && info.limit >= 0
                ? `Your plan includes ${info.limit} ${noun} and you already have ${info.used ?? info.limit}.`
                : `You've reached the ${noun} limit on your plan.`;

            toast.error(headline);
            setLimitNotice({
              tab: accountType === "practice" ? "demo" : "live",
              text: `${headline} ${info.message}`.trim(),
            });
            return;
          }

          toast.error(info.message);
        },
        onSettled: () => setPendingType(null),
      },
    );
  };

  const tabs: { key: TabType; label: string; count: number }[] = [
    { key: "live", label: "Live Trading Accounts", count: liveAccounts.length },
    { key: "demo", label: "Demo Practice Accounts", count: demoAccounts.length },
    { key: "joint", label: "Joint Account Users", count: 0 },
  ];

  return (
    <div className="bg-[#121A33] p-5 pt-10 min-h-screen font-sans">
      <div
        className="max-w-[1600px] mx-auto bg-[#EDF0F4] rounded-xl shadow-2xl p-6 md:p-8 flex flex-col gap-6"
        style={{ minHeight: "calc(100vh - 5rem)" }}
      >
        <div className="flex items-center justify-between flex-wrap gap-3">
          <div>
            <h1 className="text-2xl font-bold text-[#00234B]">Trading Accounts</h1>
            <p className="text-sm text-[#00234B]/85 font-medium mt-0.5">Manage your live and demo trading accounts.</p>
          </div>
          <button
            type="button"
            onClick={() => handleCreate("live")}
            disabled={pendingType !== null}
            className="flex items-center gap-2 rounded-xl bg-[#FF7700] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#e56d00] transition-colors disabled:opacity-60"
          >
            <Plus className="h-4 w-4" /> {pendingType === "live" ? "Opening…" : "Open Live Account"}
          </button>
        </div>

        <div className="flex items-center border-b border-[#00234B]/10 overflow-x-auto">
          {tabs.map((t) => (
            <button
              key={t.key}
              type="button"
              onClick={() => setTab(t.key)}
              className={`px-5 py-3 text-sm font-medium border-b-2 whitespace-nowrap flex items-center gap-2 ${
                tab === t.key ? "text-[#00234B] border-[#FF7700] font-semibold" : "text-[#00234B]/60 border-transparent"
              }`}
            >
              {t.label}
              <span className={`px-2 py-0.5 rounded-full text-xs font-bold ${tab === t.key ? "bg-[#FF7700] text-white" : "bg-[#00234B]/10 text-[#00234B]/60"}`}>
                {t.count}
              </span>
            </button>
          ))}
        </div>

        {limitNotice?.tab === tab && (
          <div className="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {limitNotice.text}
          </div>
        )}

        {isLoading ? (
          <div className="flex items-center justify-center py-20">
            <Loader2 className="h-6 w-6 animate-spin text-[#FF7700]" />
          </div>
        ) : tab === "live" ? (
          <div className="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4">
            {liveAccounts.length > 0 ? (
              liveAccounts.map((a) => (
                <AccountCard
                  key={a.id}
                  account={a}
                  spotWalletBalance={spotWalletBalance}
                  creditWalletAvailable={creditWalletAvailable}
                  creditWalletLimit={creditWalletLimit}
                  bonusWalletBalance={bonusWalletBalance}
                  formatCurrency={formatCurrency}
                />
              ))
            ) : (
              <div className="col-span-full bg-white rounded-xl p-6 border border-[#00234B]/10 text-center">
                <p className="text-[#00234B]/70 text-sm">No live trading accounts yet. Click "Open Live Account" to create your first one.</p>
              </div>
            )}
          </div>
        ) : tab === "demo" ? (
          <div className="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4">
            {demoAccounts.length === 0 && (
              <div className="col-span-full bg-white rounded-xl p-6 border border-[#00234B]/10 text-center">
                <p className="text-[#00234B]/70 text-sm">
                  No demo accounts yet. Open one below to practice trading with virtual funds, risk-free.
                </p>
              </div>
            )}
            {demoAccounts.map((a) => (
              <AccountCard
                key={a.id}
                account={a}
                spotWalletBalance={spotWalletBalance}
                creditWalletAvailable={creditWalletAvailable}
                creditWalletLimit={creditWalletLimit}
                bonusWalletBalance={bonusWalletBalance}
                formatCurrency={formatCurrency}
              />
            ))}
            <button
              type="button"
              onClick={() => handleCreate("practice")}
              disabled={pendingType !== null}
              className="bg-white rounded-xl p-5 border-2 border-dashed border-[#00234B]/15 hover:border-[#FF7700] transition-colors min-h-[220px] flex flex-col items-center justify-center gap-3 disabled:opacity-60"
            >
              <div className="w-12 h-12 rounded-full border-2 border-dashed border-[#00234B]/15 flex items-center justify-center">
                <Plus size={22} className="text-[#00234B]/50" />
              </div>
              <div className="text-center">
                <p className="text-[#00234B] text-sm font-bold">{pendingType === "practice" ? "Opening…" : "Open New Demo Account"}</p>
                <p className="text-[#00234B]/60 text-xs mt-0.5">Practice trading risk-free</p>
              </div>
            </button>
          </div>
        ) : (
          <JointTab />
        )}
      </div>
    </div>
  );
}
