import { forwardRef } from "react"
import {
  FileText,
  FolderOpen,
  LayoutDashboard,
  User,
} from "lucide-react"
import { getBitraceSidebarItems } from "../constants"
import BitraceLink from "./BitraceLink"

type BitraceSidebarProps = {
  activeLabel: string
  isOpen?: boolean
  onLinkClick?: () => void
}

const iconMap = {
  "layout-dashboard": LayoutDashboard,
  folder: FolderOpen,
  user: User,
  file: FileText,
} as const

const BitraceSidebar = forwardRef<HTMLDivElement, BitraceSidebarProps>(({ activeLabel, isOpen = false, onLinkClick }, ref) => {
  const sidebarItems = getBitraceSidebarItems(activeLabel)

  return (
    <aside
      ref={ref}
      className={`fixed top-0 left-0 bottom-0 w-[240px] bg-[#0F172A] flex flex-col z-40 transition-transform transform ${
        isOpen ? "translate-x-0" : "-translate-x-full"
      } md:translate-x-0`}
    >
      <div className="px-5 pt-6 pb-4">
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 rounded-lg bg-[#1E293B] flex items-center justify-center shrink-0">
            <span className="text-white text-xs font-bold">FR</span>
          </div>
          <div>
            <p className="text-white text-[13px] font-semibold leading-tight">
              Recovery Module
            </p>
            <p className="text-[#64748B] text-[10px] leading-tight">
              We Recover What&apos;s Yours
            </p>
          </div>
        </div>
      </div>

      <div className="px-5 mb-3">
        <p className="text-[#64748B] text-[10px] font-semibold tracking-[0.1em] uppercase">
          Client Area
        </p>
      </div>

      <nav className="flex-1 px-3 space-y-1" aria-label="Sidebar navigation">
        {sidebarItems.map((item) => {
          const Icon = iconMap[item.icon]
          const content = (
            <>
              <Icon className="w-[16px] h-[16px] shrink-0" strokeWidth={1.75} />
              <span>{item.label}</span>
            </>
          )

          const className = `flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-medium transition-colors ${
            item.active
              ? "bg-[#EFF6FF] text-[#1E40AF] border-l-[3px] border-[#2563EB] rounded-l-none"
              : "text-[#94A3B8] hover:text-white hover:bg-[#1E293B]/60"
          }`

          if (item.href.startsWith("/")) {
            return (
              <BitraceLink key={item.label} to={item.href} className={className} onClick={onLinkClick}>
                {content}
              </BitraceLink>
            )
          }

          return (
            <a key={item.label} href={item.href} className={className} onClick={onLinkClick}>
              {content}
            </a>
          )
        })}
      </nav>
    </aside>
  )
})

export default BitraceSidebar
