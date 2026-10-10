import { useState } from "react"
import { CheckCircle2, Settings2 } from "lucide-react"
import { toast } from "@/components/ui/sonner"
import { AxiosError } from "axios"
import {
  useSubmitWalletMutation,
  useWalletHistoryQuery,
} from "@/services/recovery/recovery-queries"
import { isValidEthereumAddress } from "../utils/recovery-helpers"

type WalletAddressSectionProps = {
  caseId?: string
  disabled?: boolean
  showTitle?: boolean
}

const WalletAddressSection = ({
  caseId,
  disabled = false,
  showTitle = true,
}: WalletAddressSectionProps) => {
  const { data: wallets = [], isLoading, isError, refetch } = useWalletHistoryQuery()
  const mutation = useSubmitWalletMutation()
  const currentWallet = wallets.find((w) => w.is_current)
  const [isEditing, setIsEditing] = useState(false)
  const [walletAddress, setWalletAddress] = useState("")

  const handleStartEdit = () => {
    setWalletAddress(currentWallet?.wallet_address ?? "")
    setIsEditing(true)
  }

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
      await mutation.mutateAsync({
        wallet_address: trimmed,
        ...(caseId ? { case_id: caseId } : {}),
      })
      setIsEditing(false)
      toast.success("Wallet address updated")
    } catch (err) {
      const e = err as AxiosError<{ message?: string }>
      toast.error(e.response?.data?.message || "Failed to update wallet address")
    }
  }

  if (isLoading) {
    return (
      <div className="bg-white rounded-xl border border-[#EEF1F5] p-6 animate-pulse h-[180px]" />
    )
  }

  if (isError) {
    return (
      <div className="bg-white rounded-xl border border-[#EEF1F5] p-6 text-center">
        <p className="text-[#6B7A8D] text-[13px] mb-3">Unable to load wallet history.</p>
        <button
          type="button"
          onClick={() => refetch()}
          className="text-[#2563EB] text-[13px] font-semibold hover:underline"
        >
          Try again
        </button>
      </div>
    )
  }

  return (
    <div className="bg-white rounded-xl border border-[#EEF1F5] shadow-[0_1px_4px_rgba(0,0,0,0.06)] p-6">
      {showTitle && (
        <div className="flex items-center justify-between mb-4">
          <div>
            <h2 className="text-[#0A1628] text-[15px] font-bold">
              Ethereum Receiving Wallet
            </h2>
            <p className="text-[#94A3B8] text-[12px] mt-0.5">
              Used to receive your recovered funds
            </p>
          </div>
          {!disabled && (
            <button
              type="button"
              onClick={handleStartEdit}
              className="inline-flex items-center gap-1.5 text-[#2563EB] text-[12px] font-semibold hover:underline"
              aria-label="Modify wallet address"
            >
              <Settings2 className="w-4 h-4" />
              Modify
            </button>
          )}
        </div>
      )}

      {currentWallet && !isEditing ? (
        <div className="bg-[#F0FDF4] border border-[#BBF7D0] rounded-xl p-4 flex gap-3 mb-4">
          <CheckCircle2 className="w-5 h-5 text-[#22C55E] shrink-0 mt-0.5" />
          <div className="min-w-0">
            <p className="text-[#0A1628] text-[13px] font-semibold mb-1">Current address</p>
            <p className="text-[#6B7A8D] text-[12px] break-all">{currentWallet.wallet_address}</p>
          </div>
        </div>
      ) : !isEditing ? (
        <p className="text-[#6B7A8D] text-[13px] mb-4">
          No wallet address on file yet. Add one when prompted in your active case phase.
        </p>
      ) : null}

      {isEditing && !disabled && (
        <div className="space-y-3 mb-4">
          <input
            type="text"
            value={walletAddress}
            onChange={(e) => setWalletAddress(e.target.value)}
            placeholder="0xYourEthereumWalletAddress"
            className="w-full px-4 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-[13px] text-[#0A1628] placeholder:text-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20 focus:border-[#2563EB]"
          />
          <div className="flex gap-2">
            <button
              type="button"
              onClick={handleSubmit}
              disabled={mutation.isPending}
              className="px-4 py-2 bg-[#2563EB] hover:bg-[#1D4ED8] disabled:opacity-60 text-white text-[13px] font-semibold rounded-lg"
            >
              {mutation.isPending ? "Saving..." : "Save address"}
            </button>
            <button
              type="button"
              onClick={() => setIsEditing(false)}
              className="px-4 py-2 border border-[#E2E8F0] text-[#6B7A8D] text-[13px] font-medium rounded-lg hover:bg-[#F8FAFC]"
            >
              Cancel
            </button>
          </div>
        </div>
      )}

      {wallets.length > 0 && (
        <div>
          <p className="text-[#94A3B8] text-[10px] font-semibold tracking-[0.08em] uppercase mb-2">
            Address history
          </p>
          <ul className="space-y-2">
            {wallets.slice(0, 4).map((entry) => (
              <li
                key={entry.id}
                className="flex items-start justify-between gap-3 text-[12px] border border-[#EEF1F5] rounded-lg px-3 py-2"
              >
                <span className="text-[#0A1628] break-all font-medium">
                  {entry.wallet_address}
                </span>
                {entry.is_current && (
                  <span className="text-[#22C55E] text-[10px] font-semibold shrink-0">
                    Current
                  </span>
                )}
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  )
}

export default WalletAddressSection
