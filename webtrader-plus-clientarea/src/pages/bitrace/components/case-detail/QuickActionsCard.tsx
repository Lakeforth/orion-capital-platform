import { useState } from "react"
import { Trash2 } from "lucide-react"
import { QUICK_ACTIONS } from "../../constants"

const iconMap = {
  trash: Trash2,
} as const

interface Props {
  onWithdrawCase?: () => void
  isWithdrawing?: boolean
  disabled?: boolean
}

const QuickActionsCard = ({ onWithdrawCase, isWithdrawing = false, disabled = false }: Props) => {
  const [confirming, setConfirming] = useState(false)

  const handleAction = (action: (typeof QUICK_ACTIONS)[number]) => {
    if (disabled) return

    if (action.danger) {
      if (!confirming) {
        setConfirming(true)
        return
      }
      onWithdrawCase?.()
      setConfirming(false)
      return
    }
  }

  return (
    <div className="bg-white rounded-xl border border-[#EEF1F5] shadow-[0_1px_4px_rgba(0,0,0,0.06)] p-5">
      <h2 className="text-[#0A1628] text-[15px] font-bold mb-1">Quick Actions</h2>
      <p className="text-[#94A3B8] text-[11px] mb-4">
        Withdrawal is permanent and should only be used if you no longer wish to proceed.
      </p>

      <ul className="space-y-1">
        {QUICK_ACTIONS.map((action) => {
          const Icon = iconMap[action.icon]
          const isConfirming = action.danger && confirming

          return (
            <li key={action.label}>
              <button
                type="button"
                onClick={() => handleAction(action)}
                disabled={disabled || isWithdrawing}
                className={`w-full flex items-center gap-3 px-2 py-2.5 rounded-lg text-[13px] font-medium transition-colors text-left disabled:opacity-50 ${
                  action.danger
                    ? "text-[#EF4444] hover:bg-red-50"
                    : "text-[#6B7A8D] hover:bg-[#F8FAFC]"
                }`}
              >
                <Icon className="w-4 h-4 shrink-0" />
                <span>
                  {isConfirming ? "Click again to confirm withdrawal" : action.label}
                </span>
              </button>
            </li>
          )
        })}
      </ul>

      {confirming && (
        <button
          type="button"
          onClick={() => setConfirming(false)}
          className="mt-2 text-[#6B7A8D] text-[12px] hover:underline"
        >
          Cancel
        </button>
      )}

      {isWithdrawing && (
        <p className="text-[#6B7A8D] text-[12px] mt-2">Processing withdrawal...</p>
      )}
    </div>
  )
}

export default QuickActionsCard
