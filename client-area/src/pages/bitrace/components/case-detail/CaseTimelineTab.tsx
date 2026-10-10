import { useBitraceCaseActivitiesQuery } from "@/services/bitrace/bitrace-queries"
import { getActivityPresentation } from "../../utils/bitrace-mappers"
import { CheckCircle2, FileText, RefreshCw, Search } from "lucide-react"

const iconComponents = {
  "check-circle": CheckCircle2,
  search: Search,
  refresh: RefreshCw,
  file: FileText,
} as const

type Props = {
  caseId: string
}

const CaseTimelineTab = ({ caseId }: Props) => {
  const { data: activities = [], isLoading, isError, refetch } =
    useBitraceCaseActivitiesQuery(caseId)

  if (isLoading) {
    return (
      <div className="bg-white rounded-xl border border-[#EEF1F5] p-8 animate-pulse h-[240px]" />
    )
  }

  if (isError) {
    return (
      <div className="bg-white rounded-xl border border-[#EEF1F5] p-8 text-center">
        <p className="text-[#6B7A8D] text-[14px] mb-4">Unable to load case timeline.</p>
        <button
          type="button"
          onClick={() => refetch()}
          className="text-[#2563EB] text-[13px] font-semibold hover:underline"
        >
          Try again
        </button>
      </div>
    )
  }

  if (activities.length === 0) {
    return (
      <div className="bg-white rounded-xl border border-[#EEF1F5] p-8 text-center">
        <p className="text-[#6B7A8D] text-[14px]">
          No timeline activity yet. Updates will appear here as your case progresses.
        </p>
      </div>
    )
  }

  return (
    <div className="bg-white rounded-xl border border-[#EEF1F5] p-6">
      <h2 className="text-[#0A1628] text-[15px] font-bold mb-6">Case Timeline</h2>
      <ul className="space-y-4">
        {activities.map((activity) => {
          const presentation = getActivityPresentation(activity)
          const Icon = iconComponents[presentation.icon]

          return (
            <li key={activity.id} className="flex gap-4">
              <div
                className="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                style={{ backgroundColor: presentation.iconBg }}
              >
                <Icon className="w-4 h-4" style={{ color: presentation.iconColor }} />
              </div>
              <div className="flex-1 min-w-0 pb-4 border-b border-[#EEF1F5] last:border-0">
                <p className="text-[#0A1628] text-[13px] font-semibold mb-0.5">
                  {presentation.title}
                </p>
                <p className="text-[#6B7A8D] text-[12px] mb-1">{presentation.description}</p>
                <p className="text-[#94A3B8] text-[11px]">{presentation.time}</p>
              </div>
            </li>
          )
        })}
      </ul>
    </div>
  )
}

export default CaseTimelineTab
