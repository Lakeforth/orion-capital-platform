import { BitraceCaseDetail } from "@/services/bitrace/bitrace-types"
import {
  formatBitraceDate,
  getBitraceStatusLabel,
} from "../../utils/bitrace-mappers"

type CaseDescriptionCardProps = {
  caseData: BitraceCaseDetail
}

const CaseDescriptionCard = ({ caseData }: CaseDescriptionCardProps) => {
  const details = [
    { label: "Case Number", value: caseData.case_number },
    { label: "Current Stage", value: caseData.current_stage ?? "Case Opened" },
    { label: "Status", value: getBitraceStatusLabel(caseData.status) },
    { label: "Last Updated", value: formatBitraceDate(caseData.updated_at) },
  ]

  return (
    <div className="bg-white rounded-xl border border-[#EEF1F5] shadow-[0_1px_4px_rgba(0,0,0,0.06)] p-6">
      <h2 className="text-[#0A1628] text-[15px] font-bold mb-4">
        Case Description
      </h2>
      <p className="text-[#6B7A8D] text-[13px] leading-[1.65] mb-6">
        {caseData.notes || caseData.title}
      </p>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-6 pt-5 border-t border-[#EEF1F5]">
        {details.map((item) => (
          <div key={item.label}>
            <p className="text-[#94A3B8] text-[10px] font-semibold tracking-[0.08em] uppercase mb-1.5">
              {item.label}
            </p>
            <p className="text-[#0A1628] text-[13px] font-bold">{item.value}</p>
          </div>
        ))}
      </div>
    </div>
  )
}

export default CaseDescriptionCard
