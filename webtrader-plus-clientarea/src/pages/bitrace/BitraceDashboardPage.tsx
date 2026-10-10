import { useBitraceCasesQuery, useBitraceCaseQuery } from "@/services/bitrace/bitrace-queries"
import { useRecoveryCaseQuery } from "@/services/recovery/recovery-queries"
import { useBitraceEnabled } from "./useBitraceEnabled"
import { getActivePhase } from "./utils/recovery-helpers"
import { BITRACE_SUPPORT_PATH } from "./bitrace-routes"
import BitraceAppLayout from "./layout/BitraceAppLayout"
import BitraceHeroBanner from "./components/BitraceHeroBanner"
import HowItWorksSection from "./components/HowItWorksSection"
import YourCasesSection from "./components/YourCasesSection"
import ImportantDocumentsSection from "./components/ImportantDocumentsSection"
import CaseStatusOverview from "./components/CaseStatusOverview"
import QuickLinks from "./components/QuickLinks"
import NeedHelpCard from "./components/NeedHelpCard"
import BitraceLink from "./components/BitraceLink"
import RecoveryWelcomeModal from "./components/RecoveryWelcomeModal"

const BitraceDashboardPage = () => {
  const bitraceEnabled = useBitraceEnabled()
  const {
    data: cases = [],
    isLoading,
    isError,
    refetch,
  } = useBitraceCasesQuery(bitraceEnabled)
  const primaryCaseId = cases[0]?.id
  const { data: primaryCaseDetail } = useBitraceCaseQuery(
    primaryCaseId,
    bitraceEnabled && Boolean(primaryCaseId),
  )
  const {
    data: recoveryCase,
    isLoading: recoveryLoading,
  } = useRecoveryCaseQuery(primaryCaseId, bitraceEnabled && Boolean(primaryCaseId))
  const activePhase = getActivePhase(recoveryCase)
  const showNoPhaseBanner =
    cases.length > 0 && !recoveryLoading && Boolean(primaryCaseId) && !activePhase

  return (
    <BitraceAppLayout>
      <div className="space-y-8">
        <BitraceHeroBanner />

        {showNoPhaseBanner && (
          <div className="bg-[#F0F7FF] border border-[#BFDBFE] rounded-xl px-5 py-4 text-[#1E40AF] text-[13px]">
            Your case is being prepared. No active phase is assigned yet — your case
            manager will activate the next step shortly.{" "}
            <BitraceLink to={BITRACE_SUPPORT_PATH} className="font-semibold underline">
              Contact support
            </BitraceLink>
          </div>
        )}

        <div className="grid grid-cols-1 xl:grid-cols-[1fr_340px] gap-6 xl:gap-8">
          <div className="space-y-8 min-w-0">
            <HowItWorksSection />
            <YourCasesSection
              cases={cases}
              isLoading={isLoading}
              isError={isError}
              onRetry={() => refetch()}
            />
            <ImportantDocumentsSection
              documents={primaryCaseDetail?.documents}
              caseId={primaryCaseId}
            />
          </div>

          <div className="space-y-5">
            <CaseStatusOverview cases={cases} />
            <QuickLinks />
            <NeedHelpCard />
          </div>
        </div>
      </div>

      <RecoveryWelcomeModal />
    </BitraceAppLayout>
  )
}

export default BitraceDashboardPage
