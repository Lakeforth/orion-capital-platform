// One KYC request and its whole bundle, opened from its KYC Request ID -- the
// client's read-only copy of the back office's request modal. Files show
// inline through the request's short-lived signed links; there are no review
// controls here, deciding stays with the agent.

import * as React from "react";
import { ExternalLink } from "lucide-react";

import { Dialog, DialogContent, DialogDescription, DialogTitle } from "@/components/ui/dialog";
import { cn } from "@/lib/utils";

export interface KycRequestDocument {
  id: string;
  type: string;
  status: string;
  original_name?: string | null;
  document_label?: string | null;
  review_note?: string | null;
  created_at?: string | null;
  mime?: string | null;
  document_number_last4?: string | null;
  document_expiry?: string | null;
  issuing_country?: string | null;
  file_url?: string | null;
  back_url?: string | null;
}

export interface KycRequest {
  attempt: number;
  status: string;
  state?: string;
  reference?: string | null;
  submitted_at: string | null;
  reviewed_at: string | null;
  note: string | null;
  documents: KycRequestDocument[];
}

const CATEGORY: Record<string, string> = {
  id: "Identity",
  proof_of_address: "Address",
};

const TYPE_LABELS: Record<string, string> = {
  id: "Identity Document",
  proof_of_address: "Proof of Address",
  selfie: "Selfie Match",
};

const REQUIRED = ["id", "proof_of_address"];

const sidesOf = (document: KycRequestDocument) =>
  [
    { side: "front", url: document.file_url },
    { side: "back", url: document.back_url },
  ].filter((entry): entry is { side: string; url: string } => Boolean(entry.url));

const fileCount = (document: KycRequestDocument) => Math.max(sidesOf(document).length, 1);

const labelOf = (document: KycRequestDocument) =>
  document.document_label || TYPE_LABELS[document.type] || document.type;

const onDateTime = (value?: string | null) => {
  if (!value) return "Not recorded";
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? "Not recorded" : parsed.toLocaleString();
};

const onDate = (value?: string | null) => {
  if (!value) return "—";
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? "—" : parsed.toLocaleDateString();
};

function SummaryItem({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <dt className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{label}</dt>
      <dd className="mt-0.5 text-foreground">{children}</dd>
    </div>
  );
}

function SidePreview({ url, pdf, label }: { url: string; pdf: boolean; label: string }) {
  return pdf ? (
    <object data={`${url}#view=FitH`} type="application/pdf" className="h-[55vh] w-full rounded-md bg-muted">
      <a href={url} target="_blank" rel="noopener noreferrer" className="text-[#FF7700] hover:underline">
        Open the PDF
      </a>
    </object>
  ) : (
    <img src={url} alt={label} className="max-h-[55vh] w-full rounded-md bg-muted object-contain" />
  );
}

export default function KycRequestDialog({
  request,
  verification,
  badge,
  onOpenChange,
}: {
  request: KycRequest | null;
  verification: string;
  badge: React.ReactNode;
  onOpenChange: (open: boolean) => void;
}) {
  const [selected, setSelected] = React.useState<string | null>(null);
  const [side, setSide] = React.useState("front");

  React.useEffect(() => {
    setSelected(null);
    setSide("front");
  }, [request?.reference, request?.attempt]);

  const documents = request?.documents ?? [];
  const current = documents.find((document) => document.id === selected) ?? documents[0] ?? null;
  const sides = current ? sidesOf(current) : [];
  const shown = sides.find((entry) => entry.side === side) ?? sides[0] ?? null;

  const files = documents.reduce((total, document) => total + fileCount(document), 0);
  const present = REQUIRED.filter((type) => documents.some((document) => document.type === type)).length;

  const groups = documents.reduce<Record<string, KycRequestDocument[]>>((all, document) => {
    const category = CATEGORY[document.type] ?? "Supporting";
    (all[category] ??= []).push(document);
    return all;
  }, {});

  return (
    <Dialog open={Boolean(request)} onOpenChange={onOpenChange}>
      <DialogContent className="flex max-h-[90vh] flex-col gap-0 overflow-hidden p-0 sm:max-w-5xl">
        <div className="border-b border-border px-6 pb-4 pt-6">
          <DialogTitle className="whitespace-nowrap text-lg font-bold text-foreground">
            KYC Request {request?.reference ?? (request ? `#${request.attempt}` : "")}
          </DialogTitle>
          <DialogDescription className="mt-1 text-sm text-muted-foreground">
            Read-only. Our compliance team reviews the whole request together.
          </DialogDescription>

          <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-5">
            <SummaryItem label="Your verification">
              <span className="capitalize">{verification}</span>
            </SummaryItem>
            <SummaryItem label="Status">{badge}</SummaryItem>
            <SummaryItem label="Submitted">{onDateTime(request?.submitted_at)}</SummaryItem>
            <SummaryItem label="Reviewed">{onDateTime(request?.reviewed_at)}</SummaryItem>
            <SummaryItem label="Bundle">
              {documents.length} {documents.length === 1 ? "document" : "documents"} · {files}{" "}
              {files === 1 ? "file" : "files"}
              <small className="block text-xs text-muted-foreground">
                {present}/{REQUIRED.length} required documents present
              </small>
            </SummaryItem>
          </dl>

          {request?.status === "rejected" && request.note && (
            <div className="mt-4 rounded-md border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
              <span className="font-medium">Reason for rejection: </span>
              {request.note}
            </div>
          )}
        </div>

        <div className="flex-1 overflow-y-auto px-6 py-5">
          {documents.length === 0 ? (
            <p className="py-10 text-center text-sm text-muted-foreground">
              No documents are attached to this request.
            </p>
          ) : (
            <div className="grid gap-5 md:grid-cols-[260px_1fr]">
              <div className="space-y-3" aria-label="Documents in this request">
                {Object.entries(groups).map(([category, items]) => (
                  <div key={category} className="space-y-2">
                    <p className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                      {category}
                    </p>
                    {items.map((document) => {
                      const active = current?.id === document.id;
                      const count = fileCount(document);
                      return (
                        <button
                          key={document.id}
                          type="button"
                          aria-pressed={active}
                          onClick={() => {
                            setSelected(document.id);
                            setSide("front");
                          }}
                          className={cn(
                            "w-full rounded-md border px-3 py-2.5 text-left transition-colors",
                            active
                              ? "border-[#FF7700] bg-[#FF7700]/10"
                              : "border-border hover:border-[#FF7700]/50"
                          )}
                        >
                          <strong className="block truncate text-sm text-foreground">{labelOf(document)}</strong>
                          <small className="block text-xs text-muted-foreground">
                            {count} {count === 1 ? "file" : "files"} · {onDate(document.created_at)}
                          </small>
                          <span className="mt-1.5 inline-flex rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground">
                            {REQUIRED.includes(document.type) ? "Required" : "Supporting"}
                          </span>
                        </button>
                      );
                    })}
                  </div>
                ))}
              </div>

              {current && (
                <div className="min-w-0 space-y-3">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                      <h3 className="text-base font-semibold text-foreground">{labelOf(current)}</h3>
                      <span className="text-xs text-muted-foreground">
                        {TYPE_LABELS[current.type] ?? current.type}
                        {current.document_number_last4 ? ` · ends in ${current.document_number_last4}` : ""}
                        {current.document_expiry ? ` · expires ${onDate(current.document_expiry)}` : ""}
                        {current.issuing_country ? ` · ${current.issuing_country}` : ""}
                      </span>
                    </div>
                    {sides.length > 1 && (
                      <div className="flex gap-1" role="group" aria-label="Side">
                        {sides.map((entry) => (
                          <button
                            key={entry.side}
                            type="button"
                            aria-pressed={shown?.side === entry.side}
                            onClick={() => setSide(entry.side)}
                            className={cn(
                              "rounded-md border px-3 py-1 text-xs font-medium capitalize",
                              shown?.side === entry.side
                                ? "border-[#FF7700] bg-[#FF7700] text-white"
                                : "border-border text-muted-foreground hover:border-[#FF7700]/50"
                            )}
                          >
                            {entry.side}
                          </button>
                        ))}
                      </div>
                    )}
                  </div>

                  {current.status?.toLowerCase() === "rejected" && current.review_note && (
                    <p className="text-sm text-red-400">{current.review_note}</p>
                  )}

                  {shown ? (
                    <>
                      <SidePreview
                        url={shown.url}
                        pdf={(current.mime ?? "").includes("pdf")}
                        label={`${shown.side} of ${labelOf(current)}`}
                      />
                      <a
                        href={shown.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 text-sm text-[#FF7700] hover:underline"
                      >
                        Open the full file <ExternalLink className="h-3.5 w-3.5" />
                      </a>
                    </>
                  ) : (
                    <p className="rounded-md bg-muted/40 px-4 py-10 text-center text-sm text-muted-foreground">
                      This file cannot be shown here.
                    </p>
                  )}
                </div>
              )}
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
