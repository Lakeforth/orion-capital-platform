// KYC — the client's own view of their documents.
//
// Built against the submission model the back office uses: /user/kyc returns
// one entry per attempt, each carrying its documents, a rolled-up status and
// the reviewer's note when something came back rejected.
//
// Deliberately read-only about decisions. The design this follows is the
// reviewer's screen and carries Approve, Reject and an Update KYC Status card;
// those are not here, because this page is the client's and a client who could
// approve their own passport is not a KYC check. Deciding stays in the CRM.

import * as React from "react";
import { useSearchParams } from "react-router-dom";
import { Loader2, ShieldCheck } from "lucide-react";

import { Button } from "@/components/ui/button";
import axiosInstance from "@/lib/axios";
import useUserStore from "@/store/userStore";
import { cn } from "@/lib/utils";
import KycWizard from "./KycWizard";
import KycRequestDialog, { type KycRequestDocument } from "./KycRequestDialog";

type KycDocument = KycRequestDocument;

interface KycAttempt {
  attempt: number;
  status: "pending" | "approved" | "rejected";
  state?: string;
  reference?: string | null;
  submitted_at: string | null;
  reviewed_at: string | null;
  note: string | null;
  documents: KycDocument[];
  missing_types?: string[];
}

const TONE: Record<string, string> = {
  pending: "bg-amber-500/15 text-amber-400 border-amber-500/30",
  approved: "bg-emerald-500/15 text-emerald-400 border-emerald-500/30",
  rejected: "bg-red-500/15 text-red-400 border-red-500/30",
  expired: "bg-orange-500/15 text-orange-400 border-orange-500/30",
};

// The back office's names for a request's state, so client and agent read the
// same thing; status is the fallback for anything older than state.
const STATE_LABELS: Record<string, string> = {
  draft: "In progress",
  pending_approval: "Pending Review",
  under_review: "Under Review",
  approved: "Approved",
  rejected: "Rejected",
  superseded: "Superseded",
};

const STATE_TONE: Record<string, string> = {
  draft: "pending",
  pending_approval: "pending",
  under_review: "pending",
  approved: "approved",
  rejected: "rejected",
  superseded: "expired",
};

const requestLabel = (attempt: KycAttempt) =>
  (attempt.state && STATE_LABELS[attempt.state]) || attempt.status;

const requestTone = (attempt: KycAttempt) =>
  (attempt.state && STATE_TONE[attempt.state]) || attempt.status;

const Badge = ({ status, label }: { status: string; label?: string }) => (
  <span
    className={cn(
      "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize",
      TONE[status] ?? TONE.pending
    )}
  >
    {label ?? status}
  </span>
);

const onDate = (value?: string | null) => {
  if (!value) return "—";
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? "—" : parsed.toLocaleDateString();
};

function SummaryCard({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="rounded-lg border border-border bg-card px-4 py-3">
      <div className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{title}</div>
      <div className="mt-1 text-sm font-semibold text-foreground">{children}</div>
    </div>
  );
}

function Panel({
  title,
  action,
  children,
}: {
  title: React.ReactNode;
  action?: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <section className="overflow-hidden rounded-lg border border-border bg-card">
      <header className="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
        <h2 className="text-base font-semibold text-foreground">{title}</h2>
        {action}
      </header>
      {children}
    </section>
  );
}

export default function KycTab() {
  const user = useUserStore((state) => state.user);
  const [attempts, setAttempts] = React.useState<KycAttempt[] | null>(null);
  // New KYC opens as a page in this tab; the URL holds it so a refresh or the
  // back button behaves.
  const [params, setParams] = useSearchParams();
  const creating = params.get("kyc") === "new";
  const showWizard = (show: boolean) => {
    const next = new URLSearchParams(params);
    if (show) next.set("kyc", "new");
    else next.delete("kyc");
    setParams(next);
  };

  const load = React.useCallback(async () => {
    try {
      const response = await axiosInstance.get("/user/kyc");
      setAttempts(response.data?.data ?? []);
    } catch {
      setAttempts([]);
    }
  }, []);

  React.useEffect(() => {
    load();
  }, [load]);

  const latest = attempts?.[0] ?? null;
  const inProgress = latest?.state === "draft";
  // As in the back office: a draft is not a request until it is submitted, and
  // New KYC is offered unless a request is already waiting for review.
  const requests = (attempts ?? []).filter((attempt) => attempt.state !== "draft");
  const awaitingReview = latest
    ? latest.state
      ? latest.state === "pending_approval" || latest.state === "under_review"
      : latest.status === "pending"
    : false;
  const canStart = !awaitingReview;
  const [openRequest, setOpenRequest] = React.useState<string | null>(null);
  const requestKey = (attempt: KycAttempt) => attempt.reference ?? String(attempt.attempt);
  const opened = requests.find((attempt) => requestKey(attempt) === openRequest) ?? null;
  const idLabel = (attempt: KycAttempt) =>
    attempt.documents.find((document) => document.type === "id")?.document_label ?? "—";
  const addressLabel = (attempt: KycAttempt) =>
    attempt.documents.find((document) => document.type === "proof_of_address")?.document_label ?? "—";
  const lastReview = (attempts ?? [])
    .map((attempt) => attempt.reviewed_at)
    .filter(Boolean)
    .sort()
    .pop();

  if (attempts === null) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (creating && canStart) {
    return (
      <KycWizard
        onClose={() => {
          showWizard(false);
          load();
        }}
        onSubmitted={load}
      />
    );
  }

  return (
    <div className="space-y-5">
      {latest?.status === "rejected" && latest.note && (
        <div className="rounded-md border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
          <span className="font-medium">Reason for rejection: </span>
          {latest.note}
        </div>
      )}

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <SummaryCard title="KYC Status">
          <span className="capitalize">{user?.verification_status || "unverified"}</span>
        </SummaryCard>
        <SummaryCard title="Current Request">
          {latest ? (
            <span className="flex flex-wrap items-center gap-2">
              {latest.reference ?? `#${latest.attempt}`}
              <Badge status={requestTone(latest)} label={requestLabel(latest)} />
            </span>
          ) : (
            "None yet"
          )}
        </SummaryCard>
        <SummaryCard title="Documents Submitted">{latest?.documents.length ?? 0}</SummaryCard>
        <SummaryCard title="Last Review Date">{onDate(lastReview)}</SummaryCard>
      </div>

      {inProgress && (
        <div className="rounded-md border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground">
          A KYC draft{latest?.reference ? ` (${latest.reference})` : ""} is being put together. It becomes a
          request once it is submitted; New KYC continues it.
        </div>
      )}

      <Panel
        title="KYC Documents"
        action={
          canStart ? (
            <Button
              size="sm"
              className="gap-1.5 bg-[#FF7700] text-white hover:bg-[#e66b00]"
              onClick={() => showWizard(true)}
            >
              <ShieldCheck className="h-3.5 w-3.5" />
              New KYC
            </Button>
          ) : (
            <span className="text-xs text-muted-foreground">A KYC submission is already awaiting review</span>
          )
        }
      >
        <div data-table="head" className="grid grid-cols-[minmax(150px,1fr)_130px_150px_150px_130px] gap-3 border-b border-border bg-muted/40 px-5 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
          <span>KYC Request ID</span>
          <span>Submission Date</span>
          <span>Document Type</span>
          <span>Proof of Address</span>
          <span>Status</span>
        </div>

        {requests.length ? (
          requests.map((attempt) => (
            <div
              key={requestKey(attempt)}
              data-table="row"
              className="grid grid-cols-[minmax(150px,1fr)_130px_150px_150px_130px] items-center gap-3 border-b border-border px-5 py-3 text-sm last:border-b-0"
            >
              <span>
                <button
                  type="button"
                  className="whitespace-nowrap font-medium text-[#FF7700] hover:underline"
                  onClick={() => {
                    // The file links expire, so opening a request fetches fresh ones.
                    load();
                    setOpenRequest(requestKey(attempt));
                  }}
                >
                  {attempt.reference ?? `#${attempt.attempt}`}
                </button>
              </span>
              <span className="text-muted-foreground">{onDate(attempt.submitted_at)}</span>
              <span className="truncate text-muted-foreground">{idLabel(attempt)}</span>
              <span className="truncate text-muted-foreground">{addressLabel(attempt)}</span>
              <span>
                <Badge status={requestTone(attempt)} label={requestLabel(attempt)} />
              </span>
            </div>
          ))
        ) : (
          <div className="px-5 py-10 text-center text-sm text-muted-foreground">No KYC requests yet.</div>
        )}

        <footer className="border-t border-border px-5 py-3 text-sm text-muted-foreground">
          Showing {requests.length} {requests.length === 1 ? "request" : "requests"}
        </footer>
      </Panel>

      <KycRequestDialog
        request={opened}
        verification={user?.verification_status || "unverified"}
        badge={opened && <Badge status={requestTone(opened)} label={requestLabel(opened)} />}
        onOpenChange={(next) => !next && setOpenRequest(null)}
      />

      <p className="text-xs text-muted-foreground">
        Documents are reviewed by our compliance team. Your account currently reads as{" "}
        <span className="capitalize text-foreground">{user?.verification_status || "unverified"}</span>.
      </p>
    </div>
  );
}
