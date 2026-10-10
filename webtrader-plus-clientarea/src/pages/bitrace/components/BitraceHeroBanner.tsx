import { useState } from "react"
import { ChevronRight, Clock, Lock, Shield } from "lucide-react"
import useUserStore from "@/store/userStore"
import { useBitraceCasesQuery } from "@/services/bitrace/bitrace-queries"
import { BITRACE_CASES_PATH } from "../bitrace-routes"
import BitraceLink from "./BitraceLink"
import CreateCaseModal from "./CreateCaseModal"
import { useBitraceEnabled } from "../useBitraceEnabled"
import { hasActiveRecoveryCase } from "../utils/recovery-helpers"

const BitraceHeroBanner = () => {
  const user = useUserStore((state) => state.user)
  const firstName = (user?.first_name || "John").toUpperCase()
  const [createOpen, setCreateOpen] = useState(false)
  const bitraceEnabled = useBitraceEnabled()
  const { data: cases = [] } = useBitraceCasesQuery(bitraceEnabled)
  const canCreateCase = !hasActiveRecoveryCase(cases)

  return (
    <>
      <section
        className="rounded-[20px] overflow-hidden relative min-h-[220px] flex items-center"
        style={{
          background: "linear-gradient(135deg, #061428 0%, #0C1F3D 45%, #122A4F 100%)",
        }}
      >
        <div className="flex flex-col lg:flex-row items-center justify-between w-full px-8 lg:px-10 py-8 lg:py-10 gap-8">
          <div className="flex-1 max-w-[580px] z-10">
            <p className="text-[#5B9BD5] text-[11px] font-semibold tracking-[0.12em] uppercase mb-3">
              Welcome back, {firstName}
            </p>
            <h1 className="text-white text-[26px] lg:text-[30px] font-bold leading-[1.25] mb-4">
              We&apos;re here to help you{" "}
              <span className="text-[#4DA3FF]">
                recover what&apos;s rightfully yours.
              </span>
            </h1>
            <p className="text-[#94A8C4] text-[13px] leading-[1.65] mb-6 max-w-[480px]">
              Our dedicated recovery team is committed to tracing your funds and
              guiding you through every step of the process with transparency and
              care.
            </p>
            <div className="flex flex-wrap gap-3">
              <BitraceLink
                to={BITRACE_CASES_PATH}
                className="inline-flex items-center gap-2 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-[13px] font-semibold px-5 py-2.5 rounded-lg transition-colors"
              >
                View My Cases
                <ChevronRight className="w-4 h-4" />
              </BitraceLink>
              {canCreateCase && (
                <button
                  type="button"
                  onClick={() => setCreateOpen(true)}
                  className="inline-flex items-center gap-2 bg-white/10 hover:bg-white/15 border border-white/20 text-white text-[13px] font-semibold px-5 py-2.5 rounded-lg transition-colors"
                >
                  Open a new case
                </button>
              )}
            </div>
          </div>

          <div className="relative w-[200px] h-[200px] shrink-0 hidden md:flex items-center justify-center">
            <div className="absolute w-[180px] h-[180px] rounded-full border border-[#1E3A5F]/60" />
            <div className="absolute w-[140px] h-[140px] rounded-full border border-[#2563EB]/20" />
            <div className="absolute w-[100px] h-[100px] rounded-full border border-[#2563EB]/30" />
            <div
              className="absolute w-[90px] h-[90px] rounded-2xl"
              style={{
                background: "radial-gradient(circle, rgba(37,99,235,0.35) 0%, transparent 70%)",
              }}
            />
            <div className="relative w-[72px] h-[72px] rounded-2xl bg-[#1A3A6B]/80 border border-[#2563EB]/40 flex items-center justify-center backdrop-blur-sm">
              <Shield className="w-8 h-8 text-[#4DA3FF]" strokeWidth={1.5} />
            </div>
            <div className="absolute top-6 right-2 w-8 h-8 rounded-lg bg-[#1A3055]/90 border border-[#2563EB]/25 flex items-center justify-center">
              <Lock className="w-3.5 h-3.5 text-[#60A5FA]" />
            </div>
            <div className="absolute bottom-8 left-0 w-8 h-8 rounded-lg bg-[#1A3055]/90 border border-[#2563EB]/25 flex items-center justify-center">
              <Clock className="w-3.5 h-3.5 text-[#60A5FA]" />
            </div>
          </div>
        </div>
      </section>

      <CreateCaseModal open={createOpen} onClose={() => setCreateOpen(false)} />
    </>
  )
}

export default BitraceHeroBanner
