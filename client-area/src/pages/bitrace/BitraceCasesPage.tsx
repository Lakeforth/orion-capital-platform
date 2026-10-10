import { useState } from "react"
import { FolderOpen } from "lucide-react"
import { useBitraceCasesQuery } from "@/services/bitrace/bitrace-queries"
import { useBitraceEnabled } from "./useBitraceEnabled"
import {
  BITRACE_SUPPORT_PATH,
  getBitraceCaseDetailPath,
} from "./bitrace-routes"
import BitraceAppLayout from "./layout/BitraceAppLayout"
import BitraceLink from "./components/BitraceLink"
import CreateCaseModal from "./components/CreateCaseModal"
import {
  formatBitraceDate,
  getBitraceStatusBadgeClass,
  getBitraceStatusLabel,
} from "./utils/bitrace-mappers"
import { hasActiveRecoveryCase } from "./utils/recovery-helpers"

const BitraceCasesPage = () => {
  const bitraceEnabled = useBitraceEnabled()
  const { data: cases = [], isLoading, isError, refetch } = useBitraceCasesQuery(bitraceEnabled)
  const [createOpen, setCreateOpen] = useState(false)
  const canCreateCase = !hasActiveRecoveryCase(cases)

  if (isLoading) {
    return (
      <BitraceAppLayout>
        <div className="space-y-4">
          <div className="h-8 w-48 bg-white rounded animate-pulse" />
          {[1, 2].map((i) => (
            <div
              key={i}
              className="h-[120px] bg-white rounded-xl border border-[#EEF1F5] animate-pulse"
            />
          ))}
        </div>
      </BitraceAppLayout>
    )
  }

  if (isError) {
    return (
      <BitraceAppLayout>
        <div className="bg-white rounded-xl border border-[#EEF1F5] p-8 text-center max-w-lg mx-auto">
          <h1 className="text-[#0A1628] text-[18px] font-bold mb-2">Unable to load cases</h1>
          <p className="text-[#6B7A8D] text-[14px] mb-6">
            We could not load your recovery cases. Please try again.
          </p>
          <button
            type="button"
            onClick={() => refetch()}
            className="px-5 py-2.5 bg-[#2563EB] text-white text-[13px] font-semibold rounded-lg hover:bg-[#1D4ED8]"
          >
            Retry
          </button>
        </div>
      </BitraceAppLayout>
    )
  }

  if (cases.length === 0) {
    return (
      <BitraceAppLayout>
        <div className="bg-white rounded-xl border border-[#EEF1F5] p-8 text-center max-w-lg mx-auto">
          <h1 className="text-[#0A1628] text-[20px] font-bold mb-2">My Cases</h1>
          <p className="text-[#6B7A8D] text-[14px] mb-6">
            Your recovery case will be opened automatically after registration. If
            you just signed up, please wait a moment and refresh.
          </p>
          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            <button
              type="button"
              onClick={() => refetch()}
              className="px-5 py-2.5 border border-[#E2E8F0] text-[#0A1628] text-[13px] font-semibold rounded-lg hover:bg-[#F8FAFC]"
            >
              Refresh
            </button>
            <BitraceLink
              to={BITRACE_SUPPORT_PATH}
              className="inline-flex items-center justify-center px-5 py-2.5 bg-[#2563EB] text-white text-[13px] font-semibold rounded-lg hover:bg-[#1D4ED8]"
            >
              Contact Support
            </BitraceLink>
          </div>
        </div>
      </BitraceAppLayout>
    )
  }

  return (
    <BitraceAppLayout>
      <div className="space-y-6">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-[#0A1628] text-[22px] font-bold mb-1">My Cases</h1>
            <p className="text-[#6B7A8D] text-[14px]">
              View and manage your recovery cases.
            </p>
          </div>
          {canCreateCase && (
            <button
              type="button"
              onClick={() => setCreateOpen(true)}
              className="shrink-0 px-4 py-2.5 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-[13px] font-semibold rounded-lg transition-colors"
            >
              New case
            </button>
          )}
        </div>

        <ul className="space-y-4">
          {cases.map((caseItem) => (
            <li key={caseItem.id}>
              <BitraceLink
                to={getBitraceCaseDetailPath(caseItem.id)}
                className={`block bg-white rounded-xl p-5 border shadow-[0_1px_4px_rgba(0,0,0,0.06)] hover:border-[#BFDBFE] transition-colors ${
                  caseItem.status === "withdrawn"
                    ? "border-[#FECACA] bg-[#FFFBFB]"
                    : "border-[#EEF1F5]"
                }`}
              >
                <div className="flex items-start gap-4">
                  <div className="w-11 h-11 rounded-xl bg-[#EBF2FF] flex items-center justify-center shrink-0">
                    <FolderOpen className="w-5 h-5 text-[#2563EB]" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex flex-wrap items-center gap-2 mb-1">
                      <h2 className="text-[#0A1628] text-[14px] font-bold">
                        {caseItem.case_number}
                      </h2>
                      <span
                        className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold ${getBitraceStatusBadgeClass(caseItem.status)}`}
                      >
                        {getBitraceStatusLabel(caseItem.status)}
                      </span>
                    </div>
                    <p className="text-[#6B7A8D] text-[13px] mb-1">{caseItem.title}</p>
                    <p className="text-[#94A3B8] text-[12px]">
                      Stage: {caseItem.current_stage ?? "Case Opened"} · Submitted{" "}
                      {formatBitraceDate(caseItem.created_at)} · Updated{" "}
                      {formatBitraceDate(caseItem.updated_at)}
                    </p>
                  </div>
                </div>
              </BitraceLink>
            </li>
          ))}
        </ul>
      </div>

      <CreateCaseModal open={createOpen} onClose={() => setCreateOpen(false)} />
    </BitraceAppLayout>
  )
}

export default BitraceCasesPage
