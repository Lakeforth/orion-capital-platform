import { ChevronRight, FolderOpen } from "lucide-react"
import { BitraceCaseSummary } from "@/services/bitrace/bitrace-types"
import {
  BITRACE_CASES_PATH,
  BITRACE_SUPPORT_PATH,
  getBitraceCaseDetailPath,
} from "../bitrace-routes"
import {
  formatBitraceDate,
  getBitraceStatusBadgeClass,
  getBitraceStatusLabel,
  getStageStepIndex,
} from "../utils/bitrace-mappers"
import CaseProgressTracker from "./CaseProgressTracker"
import BitraceLink from "./BitraceLink"

type YourCasesSectionProps = {
  cases: BitraceCaseSummary[]
  isLoading?: boolean
  isError?: boolean
  onRetry?: () => void
}

const YourCasesSection = ({
  cases,
  isLoading,
  isError,
  onRetry,
}: YourCasesSectionProps) => {
  if (isLoading) {
    return (
      <section>
        <h2 className="text-[#0A1628] text-[17px] font-bold mb-4">Your Cases</h2>
        <div className="bg-white rounded-xl p-6 border border-[#EEF1F5] animate-pulse h-[220px]" />
      </section>
    )
  }

  if (isError) {
    return (
      <section>
        <h2 className="text-[#0A1628] text-[17px] font-bold mb-4">Your Cases</h2>
        <div className="bg-white rounded-xl p-6 border border-[#EEF1F5] text-center">
          <p className="text-[#6B7A8D] text-[13px] mb-4">Unable to load your cases.</p>
          {onRetry && (
            <button
              type="button"
              onClick={onRetry}
              className="text-[#2563EB] text-[13px] font-semibold hover:underline"
            >
              Try again
            </button>
          )}
        </div>
      </section>
    )
  }

  if (cases.length === 0) {
    return (
      <section>
        <h2 className="text-[#0A1628] text-[17px] font-bold mb-4">Your Cases</h2>
        <div className="bg-white rounded-xl p-6 border border-[#EEF1F5] text-center">
          <p className="text-[#6B7A8D] text-[13px] mb-4 leading-relaxed">
            Your case will be opened after registration. If you have questions, our
            support team is here to help.
          </p>
          <BitraceLink
            to={BITRACE_SUPPORT_PATH}
            className="text-[#2563EB] text-[13px] font-semibold hover:underline"
          >
            Contact support
          </BitraceLink>
        </div>
      </section>
    )
  }

  const displayCases = cases.slice(0, 3)

  return (
    <section>
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-[#0A1628] text-[17px] font-bold">Your Cases</h2>
        {cases.length > 0 && (
          <BitraceLink
            to={BITRACE_CASES_PATH}
            className="text-[#2563EB] text-[13px] font-medium flex items-center gap-0.5 hover:underline"
          >
            View All Cases
            <ChevronRight className="w-4 h-4" />
          </BitraceLink>
        )}
      </div>

      <div className="space-y-4">
        {displayCases.map((caseItem) => {
          const casePath = getBitraceCaseDetailPath(caseItem.id)
          const isWithdrawn = caseItem.status === "withdrawn"

          return (
            <BitraceLink
              key={caseItem.id}
              to={casePath}
              className={`block bg-white rounded-xl p-6 shadow-[0_1px_4px_rgba(0,0,0,0.06)] border transition-colors ${
                isWithdrawn
                  ? "border-[#FECACA] opacity-80 hover:border-[#FCA5A5]"
                  : "border-[#EEF1F5] hover:border-[#BFDBFE]"
              }`}
            >
              <div className="flex items-start gap-4 mb-6">
                <div className="w-11 h-11 rounded-xl bg-[#EBF2FF] flex items-center justify-center shrink-0">
                  <FolderOpen className="w-5 h-5 text-[#2563EB]" />
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <h3 className="text-[#0A1628] text-[14px] font-bold">
                      Case ID: {caseItem.case_number}
                    </h3>
                    <span className="text-[#6B7A8D] text-[13px]">{caseItem.title}</span>
                  </div>
                  <p className="text-[#94A3B8] text-[12px] mt-0.5">
                    Submitted on {formatBitraceDate(caseItem.created_at)}
                  </p>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 pb-6 border-b border-[#EEF1F5]">
                <div>
                  <p className="text-[#94A3B8] text-[10px] font-semibold tracking-[0.08em] uppercase mb-1.5">
                    Status
                  </p>
                  <span
                    className={`inline-flex items-center px-3 py-1 rounded-full text-[12px] font-semibold ${getBitraceStatusBadgeClass(caseItem.status)}`}
                  >
                    {getBitraceStatusLabel(caseItem.status)}
                  </span>
                </div>
                <div>
                  <p className="text-[#94A3B8] text-[10px] font-semibold tracking-[0.08em] uppercase mb-1.5">
                    Last Updated
                  </p>
                  <p className="text-[#0A1628] text-[13px] font-bold">
                    {formatBitraceDate(caseItem.updated_at)}
                  </p>
                </div>
                <div>
                  <p className="text-[#94A3B8] text-[10px] font-semibold tracking-[0.08em] uppercase mb-1.5">
                    Current Stage
                  </p>
                  <p className="text-[#0A1628] text-[13px] font-bold">
                    {caseItem.current_stage ?? "Case Opened"}
                  </p>
                </div>
              </div>

              <CaseProgressTracker activeStep={getStageStepIndex(caseItem.current_stage)} />
            </BitraceLink>
          )
        })}
      </div>
    </section>
  )
}

export default YourCasesSection
