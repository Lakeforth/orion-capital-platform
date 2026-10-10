// Withdraw, from inside the webtrader.
//
// The terminal has had a Deposit button for a long time but no way out again:
// the withdrawal flow only existed as a client-area route, and the webtrader
// is a separate origin that mounts none of those routes. Rather than a second
// implementation that would drift from the real one, this wraps the client
// area's own WithdrawalForm -- same validation, same wire-transfer
// confirmation step, same history -- in a dialog.

import { lazy, Suspense } from "react";
import { Loader2 } from "lucide-react";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

// Lazily loaded: it pulls in the whole form, its schemas and the history
// table, none of which a trading session needs until the button is pressed.
const WithdrawalForm = lazy(() => import("@/pages/Preferences/withdrawal-form"));

interface Props {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export default function WithdrawFunds({ open, onOpenChange }: Props) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-3xl max-h-[88vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Withdrawal</DialogTitle>
          <DialogDescription>
            Request a withdrawal from your Spot Wallet or a live trading account.
          </DialogDescription>
        </DialogHeader>

        <Suspense
          fallback={
            <div className="flex items-center justify-center py-16 text-muted-foreground">
              <Loader2 className="h-5 w-5 animate-spin" />
            </div>
          }
        >
          {/* Mounted only while open, so each visit starts on a clean form and
              re-reads the balances rather than showing a stale snapshot. */}
          {open && <WithdrawalForm embedded />}
        </Suspense>
      </DialogContent>
    </Dialog>
  );
}
