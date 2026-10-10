import { useEffect, useState } from "react"
import { useNavigate } from "react-router-dom"
import { X } from "lucide-react"
import useSiteSettingsStore from "@/store/siteSettingStore"
import useUserStore from "@/store/userStore"
import { useBitraceCasesQuery } from "@/services/bitrace/bitrace-queries"
import {
  BITRACE_CASES_PATH,
} from "../bitrace-routes"

const getStorageKey = (userId: string) => `recovery_welcome_seen_${userId}`

const RecoveryWelcomeModal = () => {
  const navigate = useNavigate()
  const user = useUserStore((state) => state.user)
  const settings = useSiteSettingsStore((state) => state.settings)
  const { isLoading } = useBitraceCasesQuery()
  const [visible, setVisible] = useState(false)

  const title = settings?.recovery_welcome_title ?? "Welcome to Funds Recovery"
  const message =
    settings?.recovery_welcome_message ??
    "Thank you for joining our recovery program. Your case has been opened and our team will guide you through each step."

  useEffect(() => {
    if (!user?.id || isLoading) return
    const seen = localStorage.getItem(getStorageKey(user.id))
    if (!seen) {
      setVisible(true)
    }
  }, [user?.id, isLoading])

  const handleDismiss = () => {
    if (user?.id) {
      localStorage.setItem(getStorageKey(user.id), "1")
    }
    setVisible(false)
  }

  const handleViewCases = () => {
    handleDismiss()
    navigate(BITRACE_CASES_PATH)
  }

  if (!visible) return null

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
      role="dialog"
      aria-modal="true"
      aria-labelledby="recovery-welcome-title"
    >
      <div className="bg-white rounded-xl border border-[#EEF1F5] shadow-xl w-full max-w-lg overflow-hidden relative">
        <div
          className="px-6 py-5"
          style={{
            background: "linear-gradient(135deg, #061428 0%, #0C1F3D 45%, #122A4F 100%)",
          }}
        >
          <button
            type="button"
            onClick={handleDismiss}
            className="absolute top-4 right-4 text-white/70 hover:text-white"
            aria-label="Close welcome message"
          >
            <X className="w-5 h-5" />
          </button>
          <p className="text-[#5B9BD5] text-[10px] font-semibold tracking-[0.12em] uppercase mb-2">
            Recovery Module
          </p>
          <h2 id="recovery-welcome-title" className="text-white text-[20px] font-bold pr-8">
            {title}
          </h2>
        </div>

        <div className="p-6">
          <p className="text-[#6B7A8D] text-[14px] leading-relaxed mb-6 whitespace-pre-line">
            {message}
          </p>
          <div className="flex flex-col sm:flex-row gap-3">
            <button
              type="button"
              onClick={handleViewCases}
              className="flex-1 px-4 py-2.5 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-[13px] font-semibold rounded-lg transition-colors"
            >
              View my cases
            </button>
            <button
              type="button"
              onClick={handleDismiss}
              className="px-4 py-2.5 text-[#6B7A8D] text-[13px] font-medium hover:underline"
            >
              Got it
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}

export default RecoveryWelcomeModal
