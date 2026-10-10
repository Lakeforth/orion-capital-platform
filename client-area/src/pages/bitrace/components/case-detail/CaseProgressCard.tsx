import { CASE_PROGRESS_STEPS } from "../../constants"
import {
  formatBitraceDate,
  getStageStepIndex,
} from "../../utils/bitrace-mappers"
import type { BitraceCaseDetail } from "@/services/bitrace/bitrace-types"
import type {
  RecoveryPhase as RecoveryPhaseData,
  RecoveryTransferRequest,
} from "@/services/recovery/recovery-types"
import { BITRACE_SUPPORT_PATH } from "../../bitrace-routes"
import BitraceLink from "../BitraceLink"
import SubmitWalletPhase from "./phases/SubmitWalletPhase"
import TestTransferPhase from "./phases/TestTransferPhase"
import UnderInvestigationPhase from "./phases/UnderInvestigationPhase"
import RecoveryPhasePanel from "./phases/RecoveryPhase"

interface Props {
  caseData: BitraceCaseDetail
  caseId: string
  phases?: RecoveryPhaseData[]
  activePhase?: RecoveryPhaseData | null
  transferRequests?: RecoveryTransferRequest[]
  recoveryLoading?: boolean
  isWithdrawn?: boolean
  onLiveChat?: () => void
}

type ProgressStep = {
  label: string
  filled: boolean
  date: string | null
}

const buildStepsFromPhases = (phases: RecoveryPhaseData[]): ProgressStep[] => {
  const ordered = [...phases].sort((a, b) => a.order - b.order)

  const activeIndex = ordered.findIndex((phase) => phase.is_active)
  const lastCompletedIndex = ordered.reduce(
    (last, phase, index) => (phase.status === "completed" ? index : last),
    -1,
  )
  const currentIndex = activeIndex >= 0 ? activeIndex : lastCompletedIndex

  return ordered.map((phase, index) => ({
    label: phase.name,
    filled: index <= currentIndex,
    date: phase.activated_at ?? phase.completed_at ?? null,
  }))
}

const buildStepsFromStage = (currentStage?: string | null): ProgressStep[] => {
  const activeStep = getStageStepIndex(currentStage)

  return CASE_PROGRESS_STEPS.map((label, index) => ({
    label,
    filled: index <= activeStep,
    date: null,
  }))
}

const CaseProgressCard = ({
  caseData,
  caseId,
  phases = [],
  activePhase,
  transferRequests = [],
  recoveryLoading = false,
  isWithdrawn = false,
  onLiveChat,
}: Props) => {
  const steps =
    phases.length > 0
      ? buildStepsFromPhases(phases)
      : buildStepsFromStage(caseData.current_stage)

  const activeStepIndex = steps.reduce(
    (last, step, index) => (step.filled ? index : last),
    0,
  )
  const activePhaseName = (activePhase?.name ?? "").toLowerCase()

  const renderPhaseContent = () => {
    if (isWithdrawn) {
      return (
        <div className="bg-[#FEF2F2] border border-[#FECACA] rounded-xl p-5 text-[#991B1B] text-[13px]">
          This case has been withdrawn. Recovery actions are no longer available.
        </div>
      )
    }

    if (recoveryLoading) {
      return (
        <div className="bg-[#F8FAFC] border border-[#EEF1F5] rounded-xl p-8 animate-pulse h-[160px]" />
      )
    }

    if (!activePhase) {
      return (
        <div className="bg-[#F8FAFC] border border-[#EEF1F5] rounded-xl p-6 text-center">
          <p className="text-[#0A1628] text-[14px] font-semibold mb-2">
            Your case is being prepared
          </p>
          <p className="text-[#6B7A8D] text-[13px] mb-4 leading-relaxed">
            No active phase is assigned yet. Your case manager will activate the
            next step shortly.
          </p>
          <BitraceLink
            to={BITRACE_SUPPORT_PATH}
            className="text-[#2563EB] text-[13px] font-semibold hover:underline"
          >
            Contact support
          </BitraceLink>
        </div>
      )
    }

    const walletAction = activePhase.actions.find(
      (a) => a.action_type.slug === "submit_wallet_details",
    )
    const transferAction = activePhase.actions.find(
      (a) => a.action_type.slug === "test_transfer_request",
    )

    if (walletAction || transferAction) {
      return (
        <div className="space-y-4">
          {walletAction && (
            <SubmitWalletPhase
              action={walletAction}
              caseId={caseId}
              disabled={isWithdrawn}
              caseWalletAddress={caseData.eth_wallet_address}
            />
          )}
          {transferAction && (
            <TestTransferPhase
              action={transferAction}
              caseId={caseId}
              disabled={isWithdrawn}
              transferRequests={transferRequests.filter(
                (request) => request.phase_action_id === transferAction.id,
              )}
            />
          )}
        </div>
      )
    }

    if (activePhaseName.includes("investigation")) {
      return <UnderInvestigationPhase onLiveChat={onLiveChat} />
    }

    if (activePhaseName.includes("recovery")) {
      return <RecoveryPhasePanel />
    }

    if (activePhaseName.includes("closed")) {
      return (
        <div className="bg-[#F8FAFC] border border-[#EEF1F5] rounded-xl p-5 text-[#6B7A8D] text-[13px]">
          This case has been closed. Contact support if you need further assistance.
        </div>
      )
    }

    return (
      <div className="bg-[#F8FAFC] border border-[#EEF1F5] rounded-xl p-5 text-[#6B7A8D] text-[13px]">
        Phase &quot;{activePhase.name}&quot; is active. Your case manager will guide
        you through the next steps.
      </div>
    )
  }

  const phaseContent = renderPhaseContent()

  return (
    <div className="bg-white rounded-xl border border-[#EEF1F5] shadow-[0_1px_4px_rgba(0,0,0,0.06)] p-6">
      <h2 className="text-[#0A1628] text-[15px] font-bold mb-6">Case Progress</h2>

      <div className="relative mb-8">
        <div className="absolute top-[14px] left-[8%] right-[8%] h-[2px] bg-[#E2E8F0]" />
        <div
          className="absolute top-[14px] left-[8%] h-[2px] bg-[#2563EB] transition-all"
          style={{
            width:
              steps.length > 1
                ? `${(activeStepIndex / (steps.length - 1)) * 84}%`
                : "0%",
          }}
        />

        <div className="relative flex justify-between">
          {steps.map((step, index) => {
            const widthClass = `${Math.floor(100 / steps.length)}%`

            return (
              <div
                key={`${step.label}-${index}`}
                className="flex flex-col items-center"
                style={{ width: widthClass }}
              >
                <div
                  className={`w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold mb-2 ${
                    step.filled
                      ? "bg-[#2563EB] text-white"
                      : "bg-[#E2E8F0] text-[#94A3B8]"
                  }`}
                >
                  {index + 1}
                </div>
                <p
                  className={`text-[10px] font-semibold text-center leading-tight ${
                    step.filled ? "text-[#2563EB]" : "text-[#94A3B8]"
                  }`}
                >
                  {step.label}
                </p>
                {step.date && (
                  <p className="text-[9px] text-[#94A3B8] mt-1 text-center">
                    {formatBitraceDate(step.date)}
                  </p>
                )}
              </div>
            )
          })}
        </div>
      </div>

      {phaseContent}
    </div>
  )
}

export default CaseProgressCard
