import { useEffect, useState } from "react"
import { CheckCircle2, Clock3, Settings2 } from "lucide-react"
import { toast } from "@/components/ui/sonner"
import { AxiosError } from "axios"
import {
  useSubmitWalletMutation,
  useWalletHistoryQuery,
} from "@/services/recovery/recovery-queries"
import type { RecoveryAction } from "@/services/recovery/recovery-types"
import { isValidEthereumAddress } from "../../../utils/recovery-helpers"
import { BITRACE_SUPPORT_PATH } from "../../../bitrace-routes"
import BitraceLink from "../../BitraceLink"

interface Props {
  action: RecoveryAction
  caseId: string
  caseWalletAddress?: string | null
  disabled?: boolean
}

const SubmitWalletPhase = ({
  action,
  caseId,
  caseWalletAddress,
  disabled = false,
}: Props) => {
  const { data: wallets = [] } = useWalletHistoryQuery(true, caseId)
  const currentWallet = wallets.find((w) => w.is_current)
  const submittedAddress = currentWallet?.wallet_address ?? caseWalletAddress ?? ""
  const [walletAddress, setWalletAddress] = useState(submittedAddress)
  const [isEditing, setIsEditing] = useState(!submittedAddress)
  const [showWhy, setShowWhy] = useState(false)
  const mutation = useSubmitWalletMutation()

  const isCompleted = action.status === "completed"
  const isPendingReview =
    action.status === "pending" && Boolean(submittedAddress.trim())

  useEffect(() => {
    if (submittedAddress) {
      setWalletAddress(submittedAddress)
    }
  }, [submittedAddress])

  const handleSubmit = async () => {
    const trimmed = walletAddress.trim()
    if (!trimmed) {
      toast.error("Please enter your Ethereum wallet address")
      return
    }
    if (!isValidEthereumAddress(trimmed)) {
      toast.error("Please enter a valid Ethereum wallet address (0x + 40 hex characters)")
      return
    }
    try {
      await mutation.mutateAsync({ wallet_address: trimmed, case_id: caseId })
      setIsEditing(false)
      toast.success("Wallet address submitted for review")
    } catch (err) {
      const e = err as AxiosError<{ message?: string }>
      toast.error(e.response?.data?.message || "Failed to submit wallet address")
    }
  }

  if (disabled) {
    return (
      <div className="bg-[#F8FAFC] border border-[#EEF1F5] rounded-xl p-5 text-[#6B7A8D] text-[13px]">
        Wallet submission is unavailable for withdrawn cases.
      </div>
    )
  }

  if (isCompleted && !isEditing) {
    return (
      <div className="space-y-4">
        <div className="bg-[#F0FDF4] border border-[#BBF7D0] rounded-xl p-5 flex gap-3">
          <CheckCircle2 className="w-5 h-5 text-[#22C55E] shrink-0 mt-0.5" />
          <div className="flex-1 min-w-0">
            <div className="flex items-start justify-between gap-2">
              <div>
                <p className="text-[#0A1628] text-[14px] font-bold mb-0.5">
                  Wallet address approved
                </p>
                <p className="text-[#6B7A8D] text-[12px] break-all">
                  {submittedAddress || walletAddress}
                </p>
              </div>
              <button
                type="button"
                onClick={() => setIsEditing(true)}
                className="inline-flex items-center gap-1 text-[#2563EB] text-[12px] font-semibold hover:underline shrink-0"
                aria-label="Modify wallet address"
              >
                <Settings2 className="w-4 h-4" />
                Modify
              </button>
            </div>
          </div>
        </div>

        {wallets.length > 1 && (
          <div>
            <p className="text-[#94A3B8] text-[10px] font-semibold tracking-[0.08em] uppercase mb-2">
              Previous addresses
            </p>
            <ul className="space-y-2">
              {wallets
                .filter((w) => !w.is_current)
                .slice(0, 3)
                .map((entry) => (
                  <li
                    key={entry.id}
                    className="text-[#6B7A8D] text-[12px] break-all border border-[#EEF1F5] rounded-lg px-3 py-2"
                  >
                    {entry.wallet_address}
                  </li>
                ))}
            </ul>
          </div>
        )}
      </div>
    )
  }

  if (isPendingReview && !isEditing) {
    return (
      <div className="space-y-4">
        <div className="bg-[#FFFBEB] border border-[#FDE68A] rounded-xl p-5 flex gap-3">
          <Clock3 className="w-5 h-5 text-[#D97706] shrink-0 mt-0.5" />
          <div className="flex-1 min-w-0">
            <div className="flex items-start justify-between gap-2">
              <div>
                <p className="text-[#0A1628] text-[14px] font-bold mb-0.5">
                  Wallet address pending review
                </p>
                <p className="text-[#6B7A8D] text-[12px] leading-[1.6] mb-2">
                  Your wallet address has been submitted. Your case manager will review it shortly.
                </p>
                <p className="text-[#6B7A8D] text-[12px] break-all">
                  {submittedAddress || walletAddress}
                </p>
              </div>
              <button
                type="button"
                onClick={() => setIsEditing(true)}
                className="inline-flex items-center gap-1 text-[#2563EB] text-[12px] font-semibold hover:underline shrink-0"
                aria-label="Update wallet address"
              >
                <Settings2 className="w-4 h-4" />
                Update
              </button>
            </div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-4">
      <div className="bg-[#F0F7FF] border border-[#BFDBFE] rounded-xl p-5">
        <h3 className="text-[#0A1628] text-[14px] font-bold mb-1">
          Enter Your Ethereum Address
        </h3>
        <p className="text-[#6B7A8D] text-[12px] leading-[1.6] mb-4">
          Provide your Ethereum wallet address to receive recovered funds.{" "}
          <button
            type="button"
            onClick={() => setShowWhy((v) => !v)}
            className="text-[#2563EB] font-medium hover:underline"
          >
            Why do we need this?
          </button>
        </p>

        {showWhy && (
          <p className="text-[#6B7A8D] text-[12px] leading-relaxed mb-4 bg-white/70 rounded-lg p-3 border border-[#DBEAFE]">
            We need a verified Ethereum address to route recovered funds securely to you.
            Only ERC-20 compatible wallets are supported.
          </p>
        )}

        <input
          type="text"
          value={walletAddress}
          onChange={(e) => setWalletAddress(e.target.value)}
          placeholder="0xYourEthereumAddressHere"
          className="w-full px-4 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-[13px] text-[#0A1628] placeholder:text-[#94A3B8] mb-3 focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20 focus:border-[#2563EB]"
        />
        <div className="flex gap-2">
          <button
            type="button"
            onClick={handleSubmit}
            disabled={mutation.isPending}
            className="px-5 py-2.5 bg-[#2563EB] hover:bg-[#1D4ED8] disabled:opacity-60 text-white text-[13px] font-semibold rounded-lg transition-colors"
          >
            {mutation.isPending ? "Saving..." : "Submit Ethereum Address"}
          </button>
          {isPendingReview && (
            <button
              type="button"
              onClick={() => setIsEditing(false)}
              className="px-4 py-2.5 border border-[#E2E8F0] text-[#6B7A8D] text-[13px] font-medium rounded-lg"
            >
              Cancel
            </button>
          )}
        </div>
        <p className="text-[#94A3B8] text-[11px] mt-3">
          We only support Ethereum (ERC-20) compatible wallets.{" "}
          <BitraceLink to={BITRACE_SUPPORT_PATH} className="text-[#2563EB] hover:underline">
            Need help?
          </BitraceLink>
        </p>
      </div>
    </div>
  )
}

export default SubmitWalletPhase
