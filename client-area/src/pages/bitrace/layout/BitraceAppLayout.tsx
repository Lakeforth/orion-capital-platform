import { ReactNode, useRef, useState } from "react"
import BitraceSidebar from "../components/BitraceSidebar"
import BitraceHeader from "../components/BitraceHeader"
import BitraceSupportWidget from "../components/BitraceSupportWidget"
import { useBitraceNavState } from "../hooks/useBitraceNavState"

type BitraceAppLayoutProps = {
  children: ReactNode
  activeSidebarLabel?: string
}

const BitraceAppLayout = ({ children, activeSidebarLabel: overrideLabel }: BitraceAppLayoutProps) => {
  const { activeSidebarLabel } = useBitraceNavState()
  const sidebarLabel = overrideLabel ?? activeSidebarLabel
  const [isSidebarOpen, setSidebarOpen] = useState(false)
  const sidebarRef = useRef<HTMLDivElement>(null)

  return (
    <div className="min-h-screen bg-[#F5F7FA] font-['Inter',_'Plus_Jakarta_Sans',_sans-serif]">
      {isSidebarOpen && (
        <div
          className="fixed inset-0 bg-black/40 z-30 md:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}
      <BitraceSidebar
        ref={sidebarRef}
        activeLabel={sidebarLabel}
        isOpen={isSidebarOpen}
        onLinkClick={() => setSidebarOpen(false)}
      />
      <div className="md:ml-[240px] flex flex-col min-h-screen">
        <BitraceHeader onMenuClick={() => setSidebarOpen((v) => !v)} />
        <main className="flex-1 px-4 md:px-6 lg:px-8 py-6">{children}</main>
      </div>
      <BitraceSupportWidget />
    </div>
  )
}

export default BitraceAppLayout
