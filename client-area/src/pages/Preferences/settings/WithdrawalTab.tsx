// Withdrawal & Wallets — where the money is, what has been asked for, and
// what has moved recently.
//
// A summary, not a second withdrawal form: New Withdrawal sends the client to
// the existing flow at /main/withdrawal rather than growing a rival copy of it
// that would drift.

import * as React from "react";
import { useNavigate } from "react-router-dom";
import { Loader2, Plus, FileText } from "lucide-react";

import { Button } from "@/components/ui/button";
import axiosInstance from "@/lib/axios";
import useUserStore from "@/store/userStore";
import { useCurrency } from "@/hooks/useCurrency";
import { cn } from "@/lib/utils";
import { amountTone } from "@/lib/amount-tone";

interface Withdrawal {
  id?: string;
  amount?: number | string;
  status?: string | null;
  method?: string | null;
  type?: string | null;
  created_at?: string | null;
  description?: string | null;
  reference?: string | null;
  wallet?: string | null;
  bank_name?: string | null;
}

const TONE: Record<string, string> = {
  approved: "bg-emerald-500/15 text-emerald-400 border-emerald-500/30",
  completed: "bg-emerald-500/15 text-emerald-400 border-emerald-500/30",
  pending: "bg-amber-500/15 text-amber-400 border-amber-500/30",
  declined: "bg-red-500/15 text-red-400 border-red-500/30",
  rejected: "bg-red-500/15 text-red-400 border-red-500/30",
};

const Badge = ({ status }: { status: string }) => (
  <span
    className={cn(
      "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize",
      TONE[status.toLowerCase()] ?? TONE.pending
    )}
  >
    {status}
  </span>
);

const when = (value?: string | null) => {
  if (!value) return "—";
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime())
    ? "—"
    : parsed.toLocaleString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
};

function Row({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex items-center justify-between gap-4 border-b border-border py-2.5 last:border-b-0">
      <span className="text-sm text-muted-foreground">{label}</span>
      <span className="text-sm font-medium text-foreground">{value}</span>
    </div>
  );
}

function Card({
  title,
  badge,
  children,
}: {
  title: string;
  badge?: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <section className="rounded-lg border border-border bg-card p-5">
      <header className="mb-3 flex items-center justify-between gap-3">
        <h2 className="text-base font-semibold text-foreground">{title}</h2>
        {badge}
      </header>
      {children}
    </section>
  );
}

export default function WithdrawalTab() {
  const navigate = useNavigate();
  const user = useUserStore((state) => state.user);
  const account = useUserStore((state) => state.activeTradingAccount);
  const { formatCurrency } = useCurrency();

  const [withdrawals, setWithdrawals] = React.useState<Withdrawal[] | null>(null);

  React.useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const response = await axiosInstance.get("/user/withdrawals");
        const rows = response.data?.data;
        if (!cancelled) setWithdrawals(Array.isArray(rows) ? rows : (rows?.data ?? []));
      } catch {
        if (!cancelled) setWithdrawals([]);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const latest = withdrawals?.[0] ?? null;
  const verified = ["approved", "completed", "verified"].includes(
    (user?.verification_status || "").toLowerCase()
  );

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <Card
          title="Account Information"
          badge={
            <span
              className={cn(
                "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium",
                verified ? TONE.approved : TONE.pending
              )}
            >
              {verified ? "KYC Verified" : "KYC Not Verified"}
            </span>
          }
        >
          <Row label="Account ID" value={account?.account_id ? `#${account.account_id}` : "—"} />
          <Row label="Trading Server" value={account?.trading_server || "—"} />
          <Row
            label="Account Leverage"
            value={user?.account_type?.leverage ? `1:${user.account_type.leverage}` : "—"}
          />
          <Row
            label="Total Net Balance"
            value={
              <span className={amountTone(Number(user?.balance ?? 0), { positive: "text-emerald-400" })}>
                {formatCurrency(Number(user?.balance ?? 0))}
              </span>
            }
          />
        </Card>

        <Card
          title="Latest Request"
          badge={latest?.status ? <Badge status={latest.status} /> : undefined}
        >
          {withdrawals === null ? (
            <div className="flex justify-center py-6">
              <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
            </div>
          ) : latest ? (
            <>
              <div className="mb-3 rounded-md bg-muted/40 px-4 py-3">
                <div className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                  Requested Amount
                </div>
                <div className="mt-0.5 text-2xl font-bold text-foreground">
                  {formatCurrency(Number(latest.amount ?? 0))}
                </div>
              </div>
              <Row label="Method / Type" value={latest.method || latest.type || "—"} />
              <Row label="Receiving Bank" value={latest.bank_name || "—"} />
              <Row label="Recipient Account / Wallet" value={latest.wallet || latest.reference || "—"} />
              <Row label="Date Requested" value={when(latest.created_at)} />
            </>
          ) : (
            <div className="py-8 text-center text-sm text-muted-foreground">
              No withdrawal requests yet.
            </div>
          )}
        </Card>
      </div>

      <section className="overflow-hidden rounded-lg border border-border bg-card">
        <header className="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
          <h2 className="text-base font-semibold text-foreground">Recent Withdrawals</h2>
          <Button
            size="sm"
            className="gap-1.5 bg-[#FF7700] text-white hover:bg-[#e66b00]"
            onClick={() => navigate("/main/withdrawal")}
          >
            <Plus className="h-3.5 w-3.5" />
            New Withdrawal
          </Button>
        </header>

        <div data-table="head" className="grid grid-cols-[1fr_130px_120px_160px] gap-3 border-b border-border bg-muted/40 px-5 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
          <span>Description</span>
          <span className="text-right">Amount</span>
          <span>Status</span>
          <span className="text-right">Date</span>
        </div>

        {withdrawals === null ? (
          <div className="flex justify-center py-10">
            <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
          </div>
        ) : withdrawals.length === 0 ? (
          <div className="flex flex-col items-center gap-2 px-5 py-12 text-center">
            <FileText className="h-6 w-6 text-muted-foreground" />
            <p className="text-sm text-muted-foreground">Nothing withdrawn yet.</p>
          </div>
        ) : (
          withdrawals.slice(0, 8).map((row, index) => (
            <div
              key={row.id ?? index}
              data-table="row"
              className="grid grid-cols-[1fr_130px_120px_160px] items-center gap-3 border-b border-border px-5 py-3 text-sm last:border-b-0"
            >
              <span className="min-w-0 truncate text-foreground">
                {row.description || row.method || "Withdrawal"}
              </span>
              <span className="text-right font-medium text-foreground">
                −{formatCurrency(Number(row.amount ?? 0))}
              </span>
              <span>
                <Badge status={row.status || "pending"} />
              </span>
              <span className="text-right text-muted-foreground">{when(row.created_at)}</span>
            </div>
          ))
        )}
      </section>
    </div>
  );
}
