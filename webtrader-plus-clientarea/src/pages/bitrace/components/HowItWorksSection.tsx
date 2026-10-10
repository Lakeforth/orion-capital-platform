import { FileText, Search, ShieldCheck, TrendingUp } from "lucide-react"
import { HOW_IT_WORKS_STEPS } from "../constants"

const iconMap = {
  "file-text": FileText,
  search: Search,
  "trending-up": TrendingUp,
  "shield-check": ShieldCheck,
} as const

const HowItWorksSection = () => {
  return (
    <section>
      <h2 className="text-[#0A1628] text-[17px] font-bold mb-4">
        How It Works
      </h2>
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {HOW_IT_WORKS_STEPS.map((step) => {
          const Icon = iconMap[step.icon]
          return (
            <div
              key={step.step}
              className="bg-white rounded-xl p-5 shadow-[0_1px_4px_rgba(0,0,0,0.06)] border border-[#EEF1F5]"
            >
              <div className="w-10 h-10 rounded-full bg-[#EBF2FF] flex items-center justify-center mb-4">
                <Icon className="w-[18px] h-[18px] text-[#2563EB]" strokeWidth={1.75} />
              </div>
              <h3 className="text-[#0A1628] text-[13px] font-bold mb-2">
                {step.step}
              </h3>
              <p className="text-[#6B7A8D] text-[12px] leading-[1.6]">
                {step.description}
              </p>
            </div>
          )
        })}
      </div>
    </section>
  )
}

export default HowItWorksSection
