import { CASE_PROGRESS_STEPS } from "../constants"

type CaseProgressTrackerProps = {
  activeStep?: number
}

const CaseProgressTracker = ({ activeStep = 1 }: CaseProgressTrackerProps) => {
  return (
    <div className="pt-2">
      <div className="relative flex items-start justify-between">
        {/* Progress line background */}
        <div className="absolute top-[5px] left-[10%] right-[10%] h-[2px] bg-[#E2E8F0]" />
        {/* Progress line filled */}
        <div
          className="absolute top-[5px] left-[10%] h-[2px] bg-[#2563EB] transition-all"
          style={{ width: `${(activeStep / (CASE_PROGRESS_STEPS.length - 1)) * 80}%` }}
        />

        {CASE_PROGRESS_STEPS.map((step, index) => {
          const isCompleted = index <= activeStep
          const isCurrent = index === activeStep

          return (
            <div
              key={step}
              className="flex flex-col items-center relative z-10"
              style={{ width: `${100 / CASE_PROGRESS_STEPS.length}%` }}
            >
              <div
                className={`w-[10px] h-[10px] rounded-full border-2 ${
                  isCompleted
                    ? "bg-[#2563EB] border-[#2563EB]"
                    : "bg-white border-[#CBD5E1]"
                } ${isCurrent ? "ring-4 ring-[#2563EB]/20" : ""}`}
              />
              <span
                className={`mt-2.5 text-[10px] text-center leading-tight max-w-[80px] ${
                  isCompleted ? "text-[#2563EB] font-medium" : "text-[#94A3B8]"
                }`}
              >
                {step}
              </span>
            </div>
          )
        })}
      </div>
    </div>
  )
}

export default CaseProgressTracker
