import { ChevronRight, FolderOpen, Headphones, User } from "lucide-react"
import { QUICK_LINKS } from "../constants"
import BitraceLink from "./BitraceLink"

const iconMap = {
  folder: FolderOpen,
  user: User,
  headphones: Headphones,
} as const

const QuickLinks = () => {
  return (
    <div className="bg-white rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.06)] border border-[#EEF1F5] overflow-hidden">
      <div className="px-5 py-4 border-b border-[#EEF1F5]">
        <h3 className="text-[#0A1628] text-[15px] font-bold">Quick Links</h3>
      </div>
      <ul>
        {QUICK_LINKS.map((link, index) => {
          const Icon = iconMap[link.icon]

          return (
            <li key={link.label}>
              <BitraceLink
                to={link.href}
                className="flex items-center justify-between px-5 py-3.5 hover:bg-[#F8FAFC] transition-colors group"
              >
                <div className="flex items-center gap-3">
                  <Icon className="w-[16px] h-[16px] text-[#94A3B8] group-hover:text-[#6B7A8D]" />
                  <span className="text-[#0A1628] text-[13px] font-medium">
                    {link.label}
                  </span>
                </div>
                <ChevronRight className="w-4 h-4 text-[#CBD5E1]" />
              </BitraceLink>
              {index < QUICK_LINKS.length - 1 && (
                <div className="mx-5 border-b border-[#EEF1F5]" />
              )}
            </li>
          )
        })}
      </ul>
    </div>
  )
}

export default QuickLinks
