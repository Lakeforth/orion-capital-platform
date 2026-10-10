import { useEffect, useState } from "react";
import { Check, ChevronLeft, Copy, Landmark } from "lucide-react";
import { toast } from "sonner";
import { useDepositMutation } from "@/services/deposit/deposit-queries";
import type { DepositInteracConfig } from "@/store/dataStore";
import type { TradingAccount } from "@/services/trading-accounts/trading-account-types";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

interface InteracFundingProps {
  config: DepositInteracConfig;
  onChangeMethod: () => void;
  onClose?: () => void;
  onStepChange?: (step: number) => void;
  onSubmittedChange?: (submitted: boolean) => void;
  destination: string;
  onDestinationChange?: (destination: string) => void;
  tradingAccounts?: TradingAccount[];
}

export default function InteracFunding({
  config,
  onChangeMethod,
  onClose,
  onStepChange,
  onSubmittedChange,
  destination,
  onDestinationChange,
  tradingAccounts,
}: InteracFundingProps) {
  const [amount, setAmount] = useState("");
  const [proof, setProof] = useState<File | null>(null);
  const [orderId, setOrderId] = useState<number | null>(null);
  const { mutate, isPending } = useDepositMutation();

  useEffect(() => onStepChange?.(1), [onStepChange]);
  useEffect(() => onSubmittedChange?.(orderId !== null), [onSubmittedChange, orderId]);

  const copy = async (label: string, value: string | null) => {
    if (!value) return;
    await navigator.clipboard.writeText(value);
    toast.success(`${label} copied`);
  };

  const submit = () => {
    const numericAmount = Number(amount);
    if (!Number.isFinite(numericAmount) || numericAmount < 5) {
      toast.error("Enter an amount of at least 5.");
      return;
    }

    mutate(
      { amount: numericAmount, method: "interac", payment_proof: proof, destination },
      {
        onSuccess: (response) => {
          setOrderId(response.data.order_id);
          toast.success("Interac deposit submitted", {
            description: "Your transfer is awaiting admin confirmation.",
          });
        },
        onError: (error) => toast.error(
          "Submission failed",
          { description: error instanceof Error ? error.message : "Please try again." },
        ),
      },
    );
  };

  if (orderId !== null) {
    return (
      <div className="flex min-h-[360px] flex-col items-center justify-center text-center">
        <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
          <Check className="h-8 w-8 text-green-600" />
        </div>
        <h3 className="text-xl font-black">Deposit request received</h3>
        <p className="mt-2 max-w-sm text-sm text-gray-500">
          Order #{orderId} is pending. Your balance will be credited after the e-transfer is confirmed.
        </p>
        <button onClick={onClose} className="mt-6 rounded-xl bg-[#1C2030] px-6 py-3 text-xs font-bold text-white">
          Done
        </button>
      </div>
    );
  }

  const rows = [
    ["Recipient", config.recipient_name],
    ["E-transfer email", config.email],
    ["Security question", config.security_question],
    ["Security answer", config.security_answer],
  ] as const;

  return (
    <div className="space-y-4 pb-16 text-[#1C2030]">
      <div className="flex items-start gap-3 rounded-xl border border-[#FFE28A] bg-[#FFF9E6] p-4">
        <Landmark className="mt-0.5 h-5 w-5 shrink-0" />
        <div>
          <h3 className="text-sm font-black">Interac e-Transfer 🇨🇦</h3>
          <p className="mt-1 text-xs text-gray-600">
            Send the exact amount using the details below, then submit your request for confirmation.
          </p>
        </div>
      </div>

      <div className="space-y-2 rounded-xl border border-gray-200 bg-white p-4">
        {rows.map(([label, value]) => (
          <div key={label} className="flex items-center justify-between gap-4 border-b border-gray-100 py-2 last:border-0">
            <div>
              <p className="text-[9px] font-black uppercase tracking-wider text-gray-400">{label}</p>
              <p className="break-all text-sm font-bold">{value}</p>
            </div>
            <button type="button" onClick={() => copy(label, value)} className="rounded-lg p-2 hover:bg-gray-100" aria-label={`Copy ${label}`}>
              <Copy className="h-4 w-4" />
            </button>
          </div>
        ))}
      </div>

      <div>
        <label className="text-[10px] font-black uppercase tracking-widest text-gray-600">Amount</label>
        <input value={amount} onChange={(event) => setAmount(event.target.value)} inputMode="decimal" placeholder="0.00" className="mt-1 w-full rounded-xl border border-gray-200 px-4 py-3 text-xl font-black" />
      </div>

      {tradingAccounts && tradingAccounts.length > 0 && (
        <div>
          <label className="text-[10px] font-black uppercase tracking-widest text-gray-600">Deposit To</label>
          <Select value={destination} onValueChange={onDestinationChange}>
            <SelectTrigger className="mt-1 h-11 w-full rounded-xl bg-[#2D334A] text-white"><SelectValue /></SelectTrigger>
            <SelectContent className="bg-[#2D334A] text-white">
              <SelectItem value="spot_wallet">Spot Wallet</SelectItem>
              {tradingAccounts.map((account) => <SelectItem key={account.id} value={`trading_account:${account.id}`}>Account #{account.account_id}</SelectItem>)}
            </SelectContent>
          </Select>
        </div>
      )}

      <div>
        <label className="text-[10px] font-black uppercase tracking-widest text-gray-600">Proof of transfer (optional)</label>
        <input type="file" accept="image/jpeg,image/png,application/pdf" onChange={(event) => setProof(event.target.files?.[0] ?? null)} className="mt-1 block w-full text-xs" />
      </div>

      <div className="absolute bottom-0 left-0 right-0 flex items-center justify-between border-t bg-[#F9FAFB] px-6 py-3">
        <button type="button" onClick={onChangeMethod} className="flex items-center gap-1 text-xs font-bold"><ChevronLeft className="h-4 w-4" /> Change method</button>
        <button type="button" onClick={submit} disabled={isPending} className="rounded-xl bg-[#1C2030] px-6 py-2.5 text-xs font-bold text-white disabled:opacity-50">
          {isPending ? "Submitting…" : "I have sent the transfer"}
        </button>
      </div>
    </div>
  );
}
