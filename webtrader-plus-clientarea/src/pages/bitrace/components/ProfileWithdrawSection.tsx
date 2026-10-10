import { useState } from "react"
import { useNavigate } from "react-router-dom"
import { AxiosError } from "axios"
import { toast } from "@/components/ui/sonner"
import { useBitraceCasesQuery } from "@/services/bitrace/bitrace-queries"
import { useWithdrawCaseMutation } from "@/services/recovery/recovery-queries"
import { getWithdrawableRecoveryCases } from "../utils/recovery-helpers"
import type { BitraceCaseSummary } from "@/services/bitrace/bitrace-types"

const ProfileWithdrawSection = () => {
  const navigate = useNavigate()
  const { data: cases = [] } = useBitraceCasesQuery()
  const withdrawableCases = getWithdrawableRecoveryCases(cases)
  const [selectedCaseId, setSelectedCaseId] = useState<string>("")
  const [confirming, setConfirming] = useState(false)

  const activeCaseId = selectedCaseId || withdrawableCases[0]?.id
  const withdrawMutation = useWithdrawCaseMutation(activeCaseId)

  if (withdrawableCases.length === 0) {
    return null
  }

  const handleWithdraw = async () => {
    if (!activeCaseId) return

    if (!confirming) {
      setConfirming(true)
      return
    }

    try {
      await withdrawMutation.mutateAsync()
      toast.success("Your case has been withdrawn")
      setConfirming(false)
      navigate("/main/bitrace/cases")
    } catch (err) {
      const e = err as AxiosError<{ message?: string }>
      toast.error(e.response?.data?.message || "Failed to withdraw case")
      setConfirming(false)
    }
  }

  return (
    <div className="bg-[#F8FAFC] border border-[#EEF1F5] rounded-xl p-5">
      <h2 className="text-[#6B7A8D] text-[13px] font-semibold mb-1">Case withdrawal</h2>
      <p className="text-[#94A3B8] text-[12px] mb-4 leading-relaxed">
        Permanently withdraw from a recovery case if you no longer wish to proceed.
        This action cannot be undone.
      </p>

      {withdrawableCases.length > 1 && (
        <div className="mb-4">
          <label htmlFor="withdraw-case-select" className="block text-[#94A3B8] text-[10px] font-semibold uppercase tracking-wide mb-1.5">
            Select case
          </label>
          <select
            id="withdraw-case-select"
            value={activeCaseId}
            onChange={(e) => {
              setSelectedCaseId(e.target.value)
              setConfirming(false)
            }}
            className="w-full px-3 py-2 border border-[#E2E8F0] rounded-lg text-[13px] text-[#0A1628] bg-white"
          >
            {withdrawableCases.map((caseItem: BitraceCaseSummary) => (
              <option key={caseItem.id} value={caseItem.id}>
                {caseItem.case_number} — {caseItem.title}
              </option>
            ))}
          </select>
        </div>
      )}

      <button
        type="button"
        onClick={handleWithdraw}
        disabled={withdrawMutation.isPending}
        className="text-[#DC2626] text-[13px] font-medium hover:underline disabled:opacity-50"
      >
        {confirming
          ? "Click again to confirm withdrawal"
          : "Withdraw from this case"}
      </button>

      {confirming && (
        <button
          type="button"
          onClick={() => setConfirming(false)}
          className="block mt-2 text-[#6B7A8D] text-[12px] hover:underline"
        >
          Cancel
        </button>
      )}
    </div>
  )
}

export default ProfileWithdrawSection
