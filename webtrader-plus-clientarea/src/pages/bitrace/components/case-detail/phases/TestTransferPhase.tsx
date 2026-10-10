import { useMemo, useState } from "react"
import { CheckCircle2, Clock3, Info } from "lucide-react"
import { toast } from "@/components/ui/sonner"
import { AxiosError } from "axios"
import { useSubmitTransferRequestMutation } from "@/services/recovery/recovery-queries"
import type { RecoveryAction, RecoveryTransferRequest } from "@/services/recovery/recovery-types"

interface Props {
  action: RecoveryAction
  caseId: string
  transferRequests?: RecoveryTransferRequest[]
  disabled?: boolean
}

const TestTransferPhase = ({
  action,
  caseId,
  transferRequests = [],
  disabled = false,
}: Props) => {
  const [confirmed, setConfirmed] = useState(false)
  const mutation = useSubmitTransferRequestMutation(caseId)

  const activeTransfer = useMemo(
    () =>
      transferRequests.find((request) =>
        ["pending", "initiated"].includes(request.status),
      ),
    [transferRequests],
  )

  const rejectedTransfer = useMemo(
    () => transferRequests.find((request) => request.status === "rejected"),
    [transferRequests],
  )

  const isCompleted = action.status === "completed"
  const isPendingReview = Boolean(activeTransfer) && action.status === "pending"
  const canResubmit = action.status === "pending" && !activeTransfer

  const handleSubmit = async () => {
    try {
      await mutation.mutateAsync(action.id)
      setConfirmed(false)
      toast.success("Test transfer request submitted for review")
    } catch (err) {
      const e = err as AxiosError<{ message?: string }>
      toast.error(e.response?.data?.message || "Failed to submit transfer request")
    }
  }

  if (disabled) {
    return (
      <div className="bg-[#F8FAFC] border border-[#EEF1F5] rounded-xl p-5 text-[#6B7A8D] text-[13px]">
        Test transfer requests are unavailable for withdrawn cases.
      </div>
    )
  }

  if (isCompleted) {
    return (
      <div className="bg-[#F0FDF4] border border-[#BBF7D0] rounded-xl p-5 flex gap-3">
        <CheckCircle2 className="w-5 h-5 text-[#22C55E] shrink-0 mt-0.5" />
        <div>
          <p className="text-[#0A1628] text-[14px] font-bold mb-0.5">
            Test transfer approved
          </p>
          <p className="text-[#6B7A8D] text-[12px] leading-[1.6]">
            Your test transfer has been reviewed and approved by your case manager.
          </p>
        </div>
      </div>
    )
  }

  if (isPendingReview) {
    const statusLabel =
      activeTransfer?.status === "initiated" ? "initiated" : "pending review"

    return (
      <div className="bg-[#FFFBEB] border border-[#FDE68A] rounded-xl p-5 flex gap-3">
        <Clock3 className="w-5 h-5 text-[#D97706] shrink-0 mt-0.5" />
        <div>
          <p className="text-[#0A1628] text-[14px] font-bold mb-0.5">
            Test transfer {statusLabel}
          </p>
          <p className="text-[#6B7A8D] text-[12px] leading-[1.6]">
            Your test transfer request has been submitted. Your case manager will process it and
            update you when there is progress.
          </p>
        </div>
      </div>
    )
  }

  return (
    <div className="bg-[#F0F7FF] border border-[#BFDBFE] rounded-xl p-5 space-y-4">
      <div>
        <h3 className="text-[#0A1628] text-[14px] font-bold mb-1">
          Request Test Transfer
        </h3>
        <p className="text-[#6B7A8D] text-[12px] leading-[1.6]">
          Request a small test transfer to confirm your payment details work before the remaining
          funds are sent.
        </p>
      </div>

      {rejectedTransfer && canResubmit && (
        <div className="bg-[#FEF2F2] border border-[#FECACA] rounded-lg p-4 text-[#991B1B] text-[12px]">
          Your previous test transfer request was rejected. You may submit a new request below.
        </div>
      )}

      <div className="bg-white/70 border border-[#DBEAFE] rounded-lg p-4 flex gap-3">
        <Info className="w-5 h-5 text-[#2563EB] shrink-0 mt-0.5" />
        <p className="text-[#6B7A8D] text-[12px] leading-relaxed">
          This step verifies that funds can reach your account before the remaining recovery
          amount is transferred.
        </p>
      </div>

      {!confirmed ? (
        <button
          type="button"
          onClick={() => setConfirmed(true)}
          className="px-5 py-2.5 bg-white border border-[#BFDBFE] text-[#2563EB] text-[13px] font-semibold rounded-lg hover:bg-[#EFF6FF] transition-colors"
        >
          I understand — continue
        </button>
      ) : (
        <button
          type="button"
          onClick={handleSubmit}
          disabled={mutation.isPending || !canResubmit}
          className="px-5 py-2.5 bg-[#2563EB] hover:bg-[#1D4ED8] disabled:opacity-60 text-white text-[13px] font-semibold rounded-lg transition-colors"
        >
          {mutation.isPending ? "Submitting..." : "Request Test Transfer"}
        </button>
      )}
    </div>
  )
}

export default TestTransferPhase
