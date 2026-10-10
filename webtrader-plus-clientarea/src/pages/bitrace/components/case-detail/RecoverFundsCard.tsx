import { Info } from "lucide-react"
import { BITRACE_SUPPORT_PATH } from "../../bitrace-routes"
import BitraceLink from "../BitraceLink"

const RecoverFundsCard = () => {
  return (
    <div className="bg-white rounded-xl border border-[#EEF1F5] shadow-[0_1px_4px_rgba(0,0,0,0.06)] p-5">
      <h2 className="text-[#0A1628] text-[15px] font-bold mb-4">
        How to Recover Your Funds
      </h2>

      <div className="bg-[#F0F7FF] border border-[#BFDBFE] rounded-lg p-4 flex gap-3 mb-4">
        <Info className="w-5 h-5 text-[#2563EB] shrink-0 mt-0.5" />
        <div className="text-[#6B7A8D] text-[12px] leading-[1.6] space-y-2">
          <p>
            Follow the steps in your case progress panel: submit your Ethereum wallet,
            complete the test transfer when requested, and stay in touch with your case
            manager.
          </p>
          <p>
            Our team will guide you through each phase until your funds are recovered.
          </p>
        </div>
      </div>

      <BitraceLink
        to={BITRACE_SUPPORT_PATH}
        className="block w-full text-center px-4 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-[#2563EB] text-[13px] font-semibold hover:bg-[#F8FAFC] transition-colors"
      >
        Get help from support
      </BitraceLink>
    </div>
  )
}

export default RecoverFundsCard
