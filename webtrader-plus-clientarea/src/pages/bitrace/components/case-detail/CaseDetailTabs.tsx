import { CASE_DETAIL_TABS, type CaseDetailTab } from "../../constants"

type Props = {
  activeTab: CaseDetailTab
  onTabChange: (tab: CaseDetailTab) => void
}

const CaseDetailTabs = ({ activeTab, onTabChange }: Props) => {
  return (
    <div className="border-b border-[#E2E8F0] mb-6">
      <nav className="flex gap-0 overflow-x-auto" aria-label="Case detail tabs">
        {CASE_DETAIL_TABS.map((tab) => (
          <button
            key={tab}
            type="button"
            onClick={() => onTabChange(tab)}
            className={`px-5 py-3 text-[13px] font-medium whitespace-nowrap border-b-2 transition-colors ${
              tab === activeTab
                ? "text-[#2563EB] border-[#2563EB]"
                : "text-[#6B7A8D] border-transparent hover:text-[#0A1628]"
            }`}
            aria-current={tab === activeTab ? "page" : undefined}
          >
            {tab}
          </button>
        ))}
      </nav>
    </div>
  )
}

export default CaseDetailTabs
