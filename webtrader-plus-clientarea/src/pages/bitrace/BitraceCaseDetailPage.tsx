import { useState } from "react"
import { Navigate, useNavigate, useParams } from "react-router-dom"
import { AxiosError } from "axios"
import { toast } from "@/components/ui/sonner"
import { useBitraceCaseQuery } from "@/services/bitrace/bitrace-queries"
import {
  useRecoveryCaseQuery,
  useWalletHistoryQuery,
  useWithdrawCaseMutation,
} from "@/services/recovery/recovery-queries"
import { useBitraceEnabled } from "./useBitraceEnabled"
import { BITRACE_CASES_PATH, BITRACE_DASHBOARD_PATH } from "./bitrace-routes"
import { openBitraceSupportChat } from "@/store/bitraceSupportWidgetStore"
import { getActivePhase, isRecoveryCaseWithdrawn } from "./utils/recovery-helpers"
import BitraceAppLayout from "./layout/BitraceAppLayout"
import CaseDetailPageHeader from "./components/case-detail/CaseDetailPageHeader"
import CaseDetailTabs from "./components/case-detail/CaseDetailTabs"
import CaseProgressCard from "./components/case-detail/CaseProgressCard"
import CaseDescriptionCard from "./components/case-detail/CaseDescriptionCard"
import RecentActivityCard from "./components/case-detail/RecentActivityCard"
import CaseInformationCard from "./components/case-detail/CaseInformationCard"
import ContactCommunicationCard from "./components/case-detail/ContactCommunicationCard"
import QuickActionsCard from "./components/case-detail/QuickActionsCard"
import RecoverFundsCard from "./components/case-detail/RecoverFundsCard"
import CaseTimelineTab from "./components/case-detail/CaseTimelineTab"
import CaseCommunicationsTab from "./components/case-detail/CaseCommunicationsTab"
import CaseDocumentsTab from "./components/case-detail/CaseDocumentsTab"
import type { CaseDetailTab } from "./constants"

const BitraceCaseDetailPage = () => {
  const { caseId } = useParams<{ caseId: string }>()
  const navigate = useNavigate()
  const bitraceEnabled = useBitraceEnabled()
  const [activeTab, setActiveTab] = useState<CaseDetailTab>("Overview")

  const { data: caseData, isLoading, isError } = useBitraceCaseQuery(caseId)
  const {
    data: recoveryCase,
    isLoading: recoveryLoading,
    isError: recoveryError,
  } = useRecoveryCaseQuery(caseId, bitraceEnabled && Boolean(caseId))
  const { data: walletHistory = [] } = useWalletHistoryQuery(bitraceEnabled)
  const withdrawMutation = useWithdrawCaseMutation(caseId)

  const activePhase = getActivePhase(recoveryCase)
  const isWithdrawn =
    isRecoveryCaseWithdrawn(caseData?.status) ||
    isRecoveryCaseWithdrawn(recoveryCase?.status)
  const currentWallet = walletHistory.find((w) => w.is_current)?.wallet_address

  const handleWithdrawCase = async () => {
    if (!caseId) return
    try {
      await withdrawMutation.mutateAsync()
      toast.success("Your case has been withdrawn")
      navigate(BITRACE_CASES_PATH)
    } catch (err) {
      const e = err as AxiosError<{ message?: string }>
      toast.error(e.response?.data?.message || "Failed to withdraw case")
    }
  }

  const handleOpenChat = () => openBitraceSupportChat()

  if (isLoading) {
    return (
      <BitraceAppLayout>
        <div className="space-y-6 animate-pulse">
          <div className="h-24 bg-white rounded-xl border border-[#EEF1F5]" />
          <div className="h-10 bg-white rounded-xl border border-[#EEF1F5]" />
          <div className="h-[480px] bg-white rounded-xl border border-[#EEF1F5]" />
        </div>
      </BitraceAppLayout>
    )
  }

  if (isError || !caseData) {
    return <Navigate to={BITRACE_DASHBOARD_PATH} replace />
  }

  return (
    <BitraceAppLayout>
      {isWithdrawn && (
        <div className="mb-6 bg-[#FEF2F2] border border-[#FECACA] rounded-xl px-5 py-4 text-[#991B1B] text-[13px]">
          This case has been withdrawn. You can still view its history, but recovery
          actions are no longer available.
        </div>
      )}

      {!recoveryLoading && recoveryError && (
        <div className="mb-6 bg-[#FFFBEB] border border-[#FDE68A] rounded-xl px-5 py-4 text-[#92400E] text-[13px]">
          Recovery phase data is temporarily unavailable. Case details below may still
          be visible.
        </div>
      )}

      <CaseDetailPageHeader caseData={caseData} />
      <CaseDetailTabs activeTab={activeTab} onTabChange={setActiveTab} />

      {activeTab === "Overview" && (
        <div className="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6">
          <div className="space-y-6 min-w-0">
            <CaseProgressCard
              caseData={caseData}
              caseId={caseId!}
              phases={recoveryCase?.phases ?? []}
              activePhase={activePhase}
              transferRequests={recoveryCase?.transfer_requests ?? []}
              recoveryLoading={recoveryLoading}
              isWithdrawn={isWithdrawn}
              onLiveChat={handleOpenChat}
            />
            <CaseDescriptionCard caseData={caseData} />
            <RecentActivityCard activities={caseData.activities} />
          </div>

          <div className="space-y-5">
            <CaseInformationCard caseData={caseData} currentWallet={currentWallet} />
            <ContactCommunicationCard />
            <QuickActionsCard
              onWithdrawCase={handleWithdrawCase}
              isWithdrawing={withdrawMutation.isPending}
              disabled={isWithdrawn}
            />
            <RecoverFundsCard />
          </div>
        </div>
      )}

      {activeTab === "Case Timeline" && caseId && (
        <CaseTimelineTab caseId={caseId} />
      )}

      {activeTab === "Documents" && caseId && (
        <CaseDocumentsTab caseId={caseId} isWithdrawn={isWithdrawn} />
      )}

      {activeTab === "Communications" && (
        <CaseCommunicationsTab onOpenChat={handleOpenChat} />
      )}
    </BitraceAppLayout>
  )
}

export default BitraceCaseDetailPage
