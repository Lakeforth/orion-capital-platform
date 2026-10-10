import { ChevronRight, Download } from "lucide-react"
import { BitraceCaseDetail } from "@/services/bitrace/bitrace-types"
import { BITRACE_CASES_PATH } from "../../bitrace-routes"
import {
  formatBitraceDate,
  getBitraceStatusBadgeClass,
  getBitraceStatusLabel,
} from "../../utils/bitrace-mappers"
import BitraceLink from "../BitraceLink"

type CaseDetailPageHeaderProps = {
  caseData: BitraceCaseDetail
}

const CaseDetailPageHeader = ({ caseData }: CaseDetailPageHeaderProps) => {
  return (
    <div className="mb-6">
      <nav className="flex items-center gap-1.5 text-[12px] text-[#94A3B8] mb-4" aria-label="Breadcrumb">
        <BitraceLink to={BITRACE_CASES_PATH} className="hover:text-[#2563EB]">
          My Cases
        </BitraceLink>
        <ChevronRight className="w-3.5 h-3.5" />
        <span className="text-[#6B7A8D]">Case Details</span>
      </nav>

      <div className="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
          <div className="flex flex-wrap items-center gap-3 mb-1.5">
            <h1 className="text-[#0A1628] text-[22px] font-bold">
              Case ID: {caseData.case_number}
            </h1>
            <span
              className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-[0.06em] uppercase ${getBitraceStatusBadgeClass(caseData.status)}`}
            >
              {getBitraceStatusLabel(caseData.status)}
            </span>
          </div>
          <p className="text-[#6B7A8D] text-[13px]">
            {caseData.title} • Submitted on {formatBitraceDate(caseData.created_at)}
          </p>
        </div>

        <button
          type="button"
          className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-[#E2E8F0] rounded-lg text-[#0A1628] text-[13px] font-medium hover:bg-[#F8FAFC] transition-colors shrink-0"
        >
          <Download className="w-4 h-4" />
          Download Case Summary
        </button>
      </div>
    </div>
  )
}

export default CaseDetailPageHeader
