import { Clock } from "lucide-react"
import { BitraceCaseSummary } from "@/services/bitrace/bitrace-types"
import { getStageLegendCounts } from "../utils/bitrace-mappers"

type CaseStatusOverviewProps = {
  cases: BitraceCaseSummary[]
}

const CaseStatusOverview = ({ cases }: CaseStatusOverviewProps) => {
  const totalCases = cases.length
  const legend = getStageLegendCounts(cases)
  const progressPercent = totalCases > 0 ? Math.min((totalCases / 4) * 100, 100) : 0
  const activeCase = cases[0]

  return (
    <div
      className="rounded-[20px] p-6 text-white"
      style={{
        background: "linear-gradient(160deg, #0A1E38 0%, #0C2547 50%, #0E2D55 100%)",
      }}
    >
      <h3 className="text-[15px] font-bold mb-5">Case Status Overview</h3>

      <div className="flex items-center gap-5 mb-5">
        <div className="relative w-[100px] h-[100px] shrink-0">
          <svg viewBox="0 0 100 100" className="w-full h-full -rotate-90">
            <circle
              cx="50"
              cy="50"
              r="38"
              fill="none"
              stroke="#1E3A5F"
              strokeWidth="10"
            />
            <circle
              cx="50"
              cy="50"
              r="38"
              fill="none"
              stroke="#3B82F6"
              strokeWidth="10"
              strokeDasharray={`${progressPercent * 2.39} ${239 - progressPercent * 2.39}`}
              strokeLinecap="round"
            />
          </svg>
          <div className="absolute inset-0 flex flex-col items-center justify-center">
            <span className="text-[28px] font-bold leading-none">{totalCases}</span>
            <span className="text-[8px] font-semibold tracking-[0.1em] text-[#94A8C4] mt-1 uppercase">
              Total Cases
            </span>
          </div>
        </div>

        <div className="flex-1 space-y-2.5">
          {legend.map((item) => (
            <div key={item.label} className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span
                  className="w-2 h-2 rounded-full shrink-0"
                  style={{ backgroundColor: item.color }}
                />
                <span className="text-[12px] text-[#C8D6E8]">{item.label}</span>
              </div>
              <span className="text-[12px] font-semibold text-white">
                ({item.count})
              </span>
            </div>
          ))}
        </div>
      </div>

      <div className="bg-[#0D2240]/80 rounded-xl p-4 flex gap-3 border border-[#1E3A5F]/50">
        <div className="w-8 h-8 rounded-lg bg-[#1A3055] flex items-center justify-center shrink-0">
          <Clock className="w-4 h-4 text-[#60A5FA]" />
        </div>
        <div>
          <p className="text-[13px] font-bold text-white mb-0.5">
            Working on your case
          </p>
          <p className="text-[11px] text-[#94A8C4] leading-[1.5]">
            {activeCase
              ? `Your case ${activeCase.case_number} is currently at the ${activeCase.current_stage} stage.`
              : "Our team is ready to review your submitted information."}
          </p>
        </div>
      </div>
    </div>
  )
}

export default CaseStatusOverview
