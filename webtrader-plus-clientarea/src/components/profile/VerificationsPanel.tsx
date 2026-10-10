// The Verifications tab of the webtrader profile dialog.
//
// One row per verification attempt, which is what the client asked for: an
// attempt is everything sent in one go, and it carries one status. Nothing on
// file yet means one "KYC" button; a rejected attempt means the
// button comes back so they can send the documents again, with the reviewer's
// reason printed above it. While an attempt is pending there is nothing to
// press -- sending the same passport twice does not make anyone look sooner.
//
// The documents collected are the two the reviewer's KYC tab has panels for:
// an identity document and a proof of address.

import * as React from "react";
import { toast } from "sonner";
import type { AxiosError } from "axios";
import { FileText, Loader2, ShieldCheck, Upload } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import axiosInstance from "@/lib/axios";
import useUserStore from "@/store/userStore";
import { cn } from "@/lib/utils";

type AttemptStatus = "pending" | "approved" | "rejected";

interface KycDocument {
  id: string;
  type: string;
  status: string;
  original_name?: string | null;
  review_note?: string | null;
}

interface KycAttempt {
  attempt: number;
  status: AttemptStatus;
  submitted_at: string | null;
  reviewed_at: string | null;
  note: string | null;
  documents: KycDocument[];
}

const ID_TYPES = [
  { value: "passport", label: "Passport" },
  { value: "national-id", label: "National ID" },
  { value: "drivers-licence", label: "Driver's Licence" },
];

const DOCUMENT_LABELS: Record<string, string> = {
  id: "Identity Document",
  proof_of_address: "Proof of Address",
  selfie: "Selfie Match",
};

const STATUS_STYLES: Record<AttemptStatus, string> = {
  pending: "bg-amber-500/15 text-amber-400 border-amber-500/30",
  approved: "bg-emerald-500/15 text-emerald-400 border-emerald-500/30",
  rejected: "bg-red-500/15 text-red-400 border-red-500/30",
};

const formatDate = (value: string | null) => {
  if (!value) return "—";
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? "—" : parsed.toLocaleDateString();
};

function StatusBadge({ status }: { status: AttemptStatus }) {
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize",
        STATUS_STYLES[status] ?? STATUS_STYLES.pending
      )}
    >
      {status}
    </span>
  );
}

/** One file slot: a button that opens the picker and then shows what was picked. */
function FileSlot({
  label,
  hint,
  file,
  onPick,
  accept = ".jpg,.jpeg,.png,.pdf",
}: {
  label: string;
  hint?: string;
  file: File | null;
  onPick: (file: File | null) => void;
  accept?: string;
}) {
  const inputRef = React.useRef<HTMLInputElement>(null);

  return (
    <div className="space-y-1.5">
      <div className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {label}
      </div>
      <button
        type="button"
        onClick={() => inputRef.current?.click()}
        className="flex w-full items-center gap-3 rounded-md border border-dashed border-border bg-muted/30 px-3 py-3 text-left transition-colors hover:border-[#FF7700]/60"
      >
        {file ? (
          <FileText className="h-4 w-4 shrink-0 text-emerald-400" />
        ) : (
          <Upload className="h-4 w-4 shrink-0 text-muted-foreground" />
        )}
        <span className="min-w-0 flex-1">
          <span className="block truncate text-sm text-foreground">
            {file ? file.name : "Choose a file"}
          </span>
          {hint && !file && (
            <span className="block text-xs text-muted-foreground">{hint}</span>
          )}
        </span>
      </button>
      <input
        ref={inputRef}
        type="file"
        accept={accept}
        className="hidden"
        onChange={(event) => onPick(event.target.files?.[0] ?? null)}
      />
    </div>
  );
}

export default function VerificationsPanel() {
  const [attempts, setAttempts] = React.useState<KycAttempt[] | null>(null);
  const [flowOpen, setFlowOpen] = React.useState(false);
  const [submitting, setSubmitting] = React.useState(false);

  const [idType, setIdType] = React.useState("passport");
  const [idFront, setIdFront] = React.useState<File | null>(null);
  const [idBack, setIdBack] = React.useState<File | null>(null);
  const [addressFile, setAddressFile] = React.useState<File | null>(null);

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
  // Nothing sent, or the last one came back rejected: there is something for
  // them to do. Pending or approved: there is not.
  const canStart = !latest || latest.status === "rejected";
  const isApproved = latest?.status === "approved";

  const resetFlow = () => {
    setIdFront(null);
    setIdBack(null);
    setAddressFile(null);
    setIdType("passport");
  };

  const submit = async () => {
    if (!idFront) {
      toast.error("Please attach the front of your identity document.");
      return;
    }
    if (!addressFile) {
      toast.error("Please attach a proof of address.");
      return;
    }

    setSubmitting(true);
    try {
      // Sent as separate posts because that is what /update/kyc takes. They
      // land in the same attempt: the server keeps an attempt open while any
      // of its documents is still pending.
      const post = (body: FormData) =>
        axiosInstance.post("/update/kyc", body, {
          headers: { "Content-Type": "multipart/form-data" },
        });

      const front = new FormData();
      front.append("type", "id");
      front.append("id_front", idFront);
      front.append("document_label", ID_TYPES.find((t) => t.value === idType)?.label ?? idType);
      await post(front);

      if (idBack) {
        const back = new FormData();
        back.append("type", "id");
        back.append("id_back", idBack);
        await post(back);
      }

      const address = new FormData();
      address.append("type", "proof_of_address");
      address.append("document", addressFile);
      await post(address);

      toast.success("Documents submitted. We will email you once they are reviewed.");
      setFlowOpen(false);
      resetFlow();
      await load();

      // The avatar and the rest of the app read verification_status off the
      // stored user, so it has to move too.
      const token = useUserStore.getState().token;
      const current = useUserStore.getState().user;
      if (current && token) {
        useUserStore.getState().setUser({ ...current, verification_status: "incomplete" }, token);
      }
    } catch (error) {
      const response = (
        error as AxiosError<{ message?: string; errors?: Record<string, string[]> }>
      ).response;
      const firstFieldError = Object.values(response?.data?.errors || {})[0]?.[0];
      toast.error(
        firstFieldError || response?.data?.message || "Could not upload those documents."
      );
    } finally {
      setSubmitting(false);
    }
  };

  if (attempts === null) {
    return (
      <div className="flex items-center justify-center py-16">
        <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return (
    // Fills the panel's fixed height so the table absorbs the spare room
    // rather than leaving a void under it.
    <div className="flex h-full flex-col gap-5">
      <div className="flex items-start justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold tracking-tight text-foreground">KYC</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            {isApproved
              ? "Your identity has been verified."
              : "Send an identity document and a proof of address. Each submission is listed below."}
          </p>
        </div>
        {canStart && (
          <Button
            type="button"
            size="sm"
            onClick={() => setFlowOpen(true)}
            className="shrink-0 gap-1.5 bg-[#FF7700] text-white hover:bg-[#e66b00]"
          >
            <ShieldCheck className="h-3.5 w-3.5" />
            KYC
          </Button>
        )}
      </div>

      {/* The reviewer's reason, where it is of use: next to the button that
          lets them act on it. */}
      {latest?.status === "rejected" && latest.note && (
        <div className="rounded-md border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-300">
          <span className="font-medium">Reason for rejection: </span>
          {latest.note}
        </div>
      )}

      <div className="flex min-h-0 flex-1 flex-col overflow-hidden rounded-md border border-border">
        <div className="grid grid-cols-[48px_1fr_120px_110px] gap-3 border-b border-border bg-muted/40 px-3 py-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
          <span>#</span>
          <span>Documents</span>
          <span>Submitted</span>
          <span className="text-right">Status</span>
        </div>

        {attempts.length === 0 ? (
          <div className="flex flex-1 items-center justify-center px-3 py-8 text-center text-sm text-muted-foreground">
            No verification started yet.
          </div>
        ) : (
          <div className="min-h-0 flex-1 overflow-y-auto">
          {attempts.map((attempt) => (
            <div
              key={attempt.attempt}
              className="grid grid-cols-[48px_1fr_120px_110px] items-center gap-3 border-b border-border px-3 py-3 text-sm last:border-b-0"
            >
              <span className="text-muted-foreground">{attempt.attempt}</span>
              <span className="min-w-0 truncate text-foreground">
                {attempt.documents
                  .map((document) => DOCUMENT_LABELS[document.type] ?? document.type)
                  .join(", ") || "—"}
              </span>
              <span className="text-muted-foreground">{formatDate(attempt.submitted_at)}</span>
              <span className="text-right">
                <StatusBadge status={attempt.status} />
              </span>
            </div>
          ))}
          </div>
        )}
      </div>

      {/* ------------------------------------------------------- upload flow */}
      <Dialog
        open={flowOpen}
        onOpenChange={(open) => {
          setFlowOpen(open);
          if (!open) resetFlow();
        }}
      >
        <DialogContent className="sm:max-w-lg max-h-[85vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Start Verification</DialogTitle>
            <DialogDescription>
              We need an identity document and something showing your address.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            <div className="space-y-1.5">
              <div className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                Document Type
              </div>
              <Select value={idType} onValueChange={setIdType}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {ID_TYPES.map((type) => (
                    <SelectItem key={type.value} value={type.value}>
                      {type.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <FileSlot label="Document — Front" file={idFront} onPick={setIdFront} />
            <FileSlot
              label="Document — Back"
              hint="Only if your document has one"
              file={idBack}
              onPick={setIdBack}
            />
            <FileSlot
              label="Proof of Address"
              hint="A utility bill or bank statement from the last 3 months"
              file={addressFile}
              onPick={setAddressFile}
            />

            <p className="text-xs text-muted-foreground">
              JPG, PNG or PDF, up to 5 MB each.
            </p>
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => setFlowOpen(false)}
              disabled={submitting}
            >
              Cancel
            </Button>
            <Button
              type="button"
              onClick={submit}
              disabled={submitting}
              className="bg-[#FF7700] text-white hover:bg-[#e66b00]"
            >
              {submitting ? "Uploading..." : "Submit for Review"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
