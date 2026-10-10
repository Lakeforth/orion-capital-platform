import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { ArrowLeft, Copy, Loader2, Plus } from "lucide-react";
import { toast } from "@/components/ui/sonner";
import { useCurrency } from "@/hooks/useCurrency";
import { useTradingAccountsQuery } from "@/services/trading-accounts/trading-account-queries";
import useTradeStore from "@/store/tradeStore";
import type { Trade } from "@/store/tradeStore";
import useActiveTradingAccountOverrideStore from "@/store/activeTradingAccountOverrideStore";
import { openTradeRoomInNewTab } from "@/lib/trade-room-link";
import { formatTradePrice } from "@/lib/price-format";
import useUserStore from "@/store/userStore";
import { amountTone, isZeroAmount } from "@/lib/amount-tone";

type TabType = "positions" | "orders" | "history";

function StatTile({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <p className="text-[10px] font-bold uppercase tracking-wide text-[#00234B]/50 mb-1">{label}</p>
      <p className="text-[#00234B] text-lg font-bold">{value}</p>
    </div>
  );
}

function PositionRow({
  trade,
  formatCurrency,
}: {
  trade: Trade;
  formatCurrency: (value: number) => string;
}) {
  const isBuy = trade.trade_type === "buy";
  return (
    <div className="grid grid-cols-[1.3fr_0.8fr_0.9fr_0.9fr_0.9fr_1fr] items-center gap-2 px-4 py-3.5 border-b border-[#00234B]/10 last:border-b-0 min-w-[640px]">
      <div>
        <p className="text-[#00234B] text-sm font-bold">{trade.asset_symbol}</p>
        <p className="text-[#00234B]/50 text-[11px]">Forex Pair</p>
      </div>
      <span
        className={`w-fit px-2 py-0.5 rounded text-[11px] font-bold uppercase ${
          isBuy ? "bg-emerald-50 text-emerald-600" : "bg-red-50 text-red-600"
        }`}
      >
        {trade.trade_type}
      </span>
      <div>
        <p className="text-[#00234B]/50 text-[10px] uppercase font-medium">Size</p>
        <p className="text-[#00234B] text-sm font-semibold">{(trade.volume_lots ?? trade.volume).toFixed(2)} Lots</p>
      </div>
      <div>
        <p className="text-[#00234B]/50 text-[10px] uppercase font-medium">Open</p>
        <p className="text-[#00234B] text-sm font-semibold font-mono tabular-nums w-20">{formatTradePrice(trade.opening_price, trade.asset_category, trade.asset_symbol)}</p>
      </div>
      <div>
        <p className="text-[#00234B]/50 text-[10px] uppercase font-medium">Current</p>
        <p className="text-[#00234B] text-sm font-semibold font-mono tabular-nums w-20">
          {formatTradePrice(trade.closing_price || trade.opening_price, trade.asset_category, trade.asset_symbol)}
        </p>
      </div>
      <div>
        <p className="text-[#00234B]/50 text-[10px] uppercase font-medium">Profit/Loss</p>
        <p className={`text-sm font-bold ${amountTone(trade.pnl, { positive: "text-emerald-600", negative: "text-red-600" })}`}>
          {isZeroAmount(trade.pnl) || trade.pnl < 0 ? "" : "+"}
          {formatCurrency(trade.pnl)}
        </p>
      </div>
    </div>
  );
}

function HistoryRow({ trade, formatCurrency }: { trade: Trade; formatCurrency: (value: number) => string }) {
  const isBuy = trade.trade_type === "buy";
  return (
    <div className="grid grid-cols-[1.3fr_0.8fr_0.9fr_0.9fr_0.9fr_1fr] items-center gap-2 px-4 py-3.5 border-b border-[#00234B]/10 last:border-b-0 min-w-[640px]">
      <div>
        <p className="text-[#00234B] text-sm font-bold">{trade.asset_symbol}</p>
        <p className="text-[#00234B]/50 text-[11px]">{trade.close_time || trade.open_time}</p>
      </div>
      <span
        className={`w-fit px-2 py-0.5 rounded text-[11px] font-bold uppercase ${
          isBuy ? "bg-emerald-50 text-emerald-600" : "bg-red-50 text-red-600"
        }`}
      >
        {trade.trade_type}
      </span>
      <div>
        <p className="text-[#00234B]/50 text-[10px] uppercase font-medium">Size</p>
        <p className="text-[#00234B] text-sm font-semibold">{(trade.volume_lots ?? trade.volume).toFixed(2)} Lots</p>
      </div>
      <div>
        <p className="text-[#00234B]/50 text-[10px] uppercase font-medium">Open</p>
        <p className="text-[#00234B] text-sm font-semibold font-mono tabular-nums w-20">{formatTradePrice(trade.opening_price, trade.asset_category, trade.asset_symbol)}</p>
      </div>
      <div>
        <p className="text-[#00234B]/50 text-[10px] uppercase font-medium">Close</p>
        <p className="text-[#00234B] text-sm font-semibold font-mono tabular-nums w-20">{formatTradePrice(trade.closing_price, trade.asset_category, trade.asset_symbol)}</p>
      </div>
      <div>
        <p className="text-[#00234B]/50 text-[10px] uppercase font-medium">Profit/Loss</p>
        <p className={`text-sm font-bold ${amountTone(trade.pnl, { positive: "text-emerald-600", negative: "text-red-600" })}`}>
          {isZeroAmount(trade.pnl) || trade.pnl < 0 ? "" : "+"}
          {formatCurrency(trade.pnl)}
        </p>
      </div>
    </div>
  );
}

export default function TradingAccountDetailPage() {
  const { accountId } = useParams<{ accountId: string }>();
  const navigate = useNavigate();
  const { formatCurrency } = useCurrency();
  const [tab, setTab] = useState<TabType>("positions");
  const [isLaunching, setIsLaunching] = useState(false);

  const { data: accounts = [], isLoading: isLoadingAccounts } = useTradingAccountsQuery();
  const account = accounts.find((a) => String(a.account_id) === accountId);

  const {
    openTrades,
    closedTrades,
    accountSummary,
    isLoadingOpen,
    isLoadingClosed,
    fetchOpenTrades,
    fetchClosedTrades,
    resetTrades,
  } = useTradeStore();

  // Scope every trade/account request on this page to the account in the URL,
  // same mechanism the /trading?account= flow uses -- restore normal
  // default-account resolution for the rest of the app on unmount.
  //
  // accountSummary.balance (rendered below) comes from useUserStore's
  // cached user.balance, which the backend scopes to whichever trading
  // account the X-TRADING-ACCOUNT-ID header names. getCurrentUser() must
  // resolve (and update that cache) BEFORE fetchOpenTrades() runs --
  // fetchOpenTrades() calls calculateAccountSummary() itself right after
  // it completes, reading whatever user.balance is in the store at that
  // exact moment. Firing both requests in parallel let fetchOpenTrades()
  // win the race most of the time (getCurrentUser() also loads
  // notifications/trade counts, so it's typically slower), which is why
  // the account summary flashed the previous account's balance before
  // correcting itself once some unrelated event (a price tick) re-ran
  // calculateAccountSummary() later. Awaiting getCurrentUser() first
  // removes the race instead of just narrowing it.
  useEffect(() => {
    if (!accountId) return;
    let cancelled = false;
    useActiveTradingAccountOverrideStore.getState().setOverrideAccountId(accountId);
    (async () => {
      await useUserStore.getState().getCurrentUser();
      if (cancelled) return;
      fetchOpenTrades();
      fetchClosedTrades();
    })();
    return () => {
      cancelled = true;
      useActiveTradingAccountOverrideStore.getState().clearOverrideAccountId();
      useUserStore.getState().getCurrentUser();
      resetTrades();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [accountId]);

  const handleNewTrade = async () => {
    if (!accountId) return;
    setIsLaunching(true);
    try {
      await openTradeRoomInNewTab(accountId);
    } finally {
      setIsLaunching(false);
    }
  };

  const copyAccountNo = () => {
    if (!accountId) return;
    navigator.clipboard.writeText(accountId);
    toast.success("Account number copied");
  };

  if (isLoadingAccounts) {
    return (
      <div className="bg-[#121A33] p-5 pt-10 min-h-screen flex items-center justify-center">
        <Loader2 className="h-6 w-6 animate-spin text-[#FF7700]" />
      </div>
    );
  }

  if (!account) {
    return (
      <div className="bg-[#121A33] p-5 pt-10 min-h-screen font-sans">
        <div className="max-w-[1600px] mx-auto bg-[#EDF0F4] rounded-xl shadow-2xl p-8 text-center">
          <p className="text-[#00234B]/70 text-sm mb-4">Trading account not found.</p>
          <Link to="/main/trading-accounts" className="text-[#FF7700] text-sm font-bold hover:underline">
            ← Back to Trading Accounts
          </Link>
        </div>
      </div>
    );
  }

  const tabs: { key: TabType; label: string; count: number }[] = [
    { key: "positions", label: "Positions", count: openTrades.length },
    { key: "orders", label: "Orders", count: 0 },
    { key: "history", label: "Trade History", count: closedTrades.length },
  ];

  return (
    <div className="bg-[#121A33] p-5 pt-10 min-h-screen font-sans">
      <div className="max-w-[1600px] mx-auto bg-[#EDF0F4] rounded-xl shadow-2xl p-6 md:p-8 flex flex-col gap-6 relative">
        {/* Header */}
        <div className="flex items-start justify-between flex-wrap gap-4">
          <div className="flex items-start gap-3">
            <button
              type="button"
              onClick={() => navigate("/main/trading-accounts")}
              className="w-9 h-9 rounded-full bg-white border border-[#00234B]/10 flex items-center justify-center text-[#00234B] hover:bg-[#00234B]/5 shrink-0"
            >
              <ArrowLeft size={16} />
            </button>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-xl font-bold text-[#00234B]">
                  {account.name || `${account.account_type} Account`} {accountId}
                </h1>
                <span
                  className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide ${
                    account.account_type === "live"
                      ? "bg-emerald-50 text-emerald-600"
                      : "bg-blue-50 text-blue-600"
                  }`}
                >
                  {account.account_type}
                </span>
              </div>
              <div className="flex items-center gap-1.5 mt-1 text-[#00234B]/60 text-sm">
                <span>Account No {accountId}</span>
                <button type="button" onClick={copyAccountNo} className="hover:text-[#00234B]">
                  <Copy size={13} />
                </button>
              </div>
            </div>
          </div>

          <Link
            to="/main/withdrawal"
            className="rounded-xl bg-[#FF7700] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#e56d00] transition-colors"
          >
            Withdraw Funds
          </Link>
        </div>

        {/* Tabs */}
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

        {/* Summary bar */}
        <div className="bg-white rounded-xl border border-[#00234B]/10 p-5 grid grid-cols-2 md:grid-cols-4 gap-6">
          <StatTile label="Equity" value={formatCurrency(accountSummary.equity)} />
          <StatTile label="Balance" value={formatCurrency(accountSummary.balance)} />
          <StatTile label="Used Margin" value={formatCurrency(accountSummary.margin)} />
          <StatTile label="Free Margin" value={formatCurrency(accountSummary.freeMargin)} />
        </div>

        {/* Tab content */}
        {tab === "positions" && (
          <div className="bg-white rounded-xl border border-[#00234B]/10 overflow-hidden">
            <div className="flex items-center justify-between px-4 py-3 border-b border-[#00234B]/10">
              <h3 className="text-[#00234B] text-sm font-bold">Open Positions ({openTrades.length})</h3>
            </div>
            {isLoadingOpen ? (
              <div className="flex items-center justify-center py-16">
                <Loader2 className="h-5 w-5 animate-spin text-[#FF7700]" />
              </div>
            ) : openTrades.length === 0 ? (
              <p className="text-[#00234B]/60 text-sm text-center py-12">No open positions on this account.</p>
            ) : (
              <div className="overflow-x-auto">
                {openTrades.map((trade) => (
                  <PositionRow key={trade.id} trade={trade} formatCurrency={formatCurrency} />
                ))}
              </div>
            )}
          </div>
        )}

        {tab === "orders" && (
          <div className="bg-white rounded-xl border border-[#00234B]/10 p-12 text-center">
            <p className="text-[#00234B]/60 text-sm">No pending orders on this account.</p>
          </div>
        )}

        {tab === "history" && (
          <div className="bg-white rounded-xl border border-[#00234B]/10 overflow-hidden">
            <div className="flex items-center justify-between px-4 py-3 border-b border-[#00234B]/10">
              <h3 className="text-[#00234B] text-sm font-bold">Trade History ({closedTrades.length})</h3>
            </div>
            {isLoadingClosed ? (
              <div className="flex items-center justify-center py-16">
                <Loader2 className="h-5 w-5 animate-spin text-[#FF7700]" />
              </div>
            ) : closedTrades.length === 0 ? (
              <p className="text-[#00234B]/60 text-sm text-center py-12">No closed trades yet on this account.</p>
            ) : (
              <div className="overflow-x-auto">
                {closedTrades.map((trade) => (
                  <HistoryRow key={trade.id} trade={trade} formatCurrency={formatCurrency} />
                ))}
              </div>
            )}
          </div>
        )}

        <button
          type="button"
          onClick={handleNewTrade}
          disabled={isLaunching}
          className="fixed bottom-8 right-8 rounded-full bg-[#FF7700] hover:bg-[#e56d00] text-white px-6 py-3.5 font-bold text-sm flex items-center gap-2 shadow-lg transition-colors disabled:opacity-60"
        >
          <Plus size={18} />
          {isLaunching ? "Opening…" : "New Trade"}
        </button>
      </div>
    </div>
  );
}
