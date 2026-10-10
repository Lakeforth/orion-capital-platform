import { ChevronRight, Phone } from "lucide-react"
import { openBitraceSupportChat } from "@/store/bitraceSupportWidgetStore"
import { BITRACE_SUPPORT_PATH } from "../bitrace-routes"
import BitraceLink from "./BitraceLink"

const NeedHelpCard = () => {
  return (
    <div className="bg-[#F0F4F8] rounded-xl p-6 text-center">
      <div className="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center mx-auto mb-4">
        <Phone className="w-5 h-5 text-[#2563EB]" />
      </div>
      <h3 className="text-[#0A1628] text-[15px] font-bold mb-1.5">
        Need Help?
      </h3>
      <p className="text-[#6B7A8D] text-[12px] leading-[1.5] mb-4">
        Our support team is here to assist you.
      </p>
      <div className="flex flex-col items-center gap-2">
        <button
          type="button"
          onClick={openBitraceSupportChat}
          className="inline-flex items-center gap-0.5 text-[#2563EB] text-[13px] font-semibold hover:underline"
        >
          Open Live Chat
          <ChevronRight className="w-4 h-4" />
        </button>
        <BitraceLink
          to={BITRACE_SUPPORT_PATH}
          className="text-[#6B7A8D] text-[12px] hover:underline"
        >
          More support options
        </BitraceLink>
      </div>
    </div>
  )
}

export default NeedHelpCard
