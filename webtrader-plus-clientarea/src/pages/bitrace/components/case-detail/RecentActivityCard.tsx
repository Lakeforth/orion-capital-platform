import {
  CheckCircle2,
  FileText,
  RefreshCw,
  Search,
} from "lucide-react"
import { BitraceActivity } from "@/services/bitrace/bitrace-types"
import { getActivityPresentation } from "../../utils/bitrace-mappers"

type RecentActivityCardProps = {
  activities: BitraceActivity[]
}

const iconMap = {
  "check-circle": CheckCircle2,
  search: Search,
  refresh: RefreshCw,
  file: FileText,
} as const

const RecentActivityCard = ({ activities }: RecentActivityCardProps) => {
  const items = activities.map(getActivityPresentation)

  return (
    <div className="bg-white rounded-xl border border-[#EEF1F5] shadow-[0_1px_4px_rgba(0,0,0,0.06)] p-6">
      <h2 className="text-[#0A1628] text-[15px] font-bold mb-5">
        Recent Activity
      </h2>

      {items.length === 0 ? (
        <p className="text-[#6B7A8D] text-[13px]">No activity recorded yet.</p>
      ) : (
        <div className="space-y-5">
          {items.map((activity) => {
            const Icon = iconMap[activity.icon]

            return (
              <div key={activity.id} className="flex items-start gap-3">
                <div
                  className="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
                  style={{ backgroundColor: activity.iconBg }}
                >
                  <Icon
                    className="w-4 h-4"
                    style={{ color: activity.iconColor }}
                    strokeWidth={1.75}
                  />
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <p className="text-[#0A1628] text-[13px] font-semibold">
                        {activity.title}
                      </p>
                      <p className="text-[#6B7A8D] text-[12px] leading-[1.5] mt-0.5">
                        {activity.description}
                      </p>
                    </div>
                    <span className="text-[#94A3B8] text-[11px] whitespace-nowrap shrink-0">
                      {activity.time}
                    </span>
                  </div>
                </div>
              </div>
            )
          })}
        </div>
      )}

      <div className="mt-6 pt-5 border-t border-[#EEF1F5] text-center">
        <button
          type="button"
          className="px-5 py-2 bg-white border border-[#E2E8F0] rounded-lg text-[#0A1628] text-[13px] font-medium hover:bg-[#F8FAFC] transition-colors"
        >
          View Full Timeline
        </button>
      </div>
    </div>
  )
}

export default RecentActivityCard
